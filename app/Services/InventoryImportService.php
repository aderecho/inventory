<?php

namespace App\Services;

use App\Exceptions\InventoryImportException;
use App\Models\AcknowledgementItem;
use App\Models\AcknowledgementReceipt;
use App\Models\AssetCondition;
use App\Models\AssetInspection;
use App\Models\InventoryItem;
use App\Models\ItemClassification;
use App\Models\ItemHistoryLocation;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Imports inventory items from the Batch Add workbook, including their location
 * and receipt assignment. Supplier, location, accountable person, and issuer are
 * numeric IDs, so the file never has to match names against the database.
 *
 * Each spreadsheet row is validated as a whole. A row with any problem is not imported
 * and every problem on it is reported against its Excel row number.
 */
class InventoryImportService
{
    /**
     * The header sits on row 1, so data starts on row 2. This used to be a
     * testing offset of 703 that silently skipped every row above it.
     */
    public const DEFAULT_START_ROW = 2;

    /** Fallback issuer when the optional Issued By cell is empty. */
    public const ISSUED_BY_ID = 2169;

    /** Header text (lowercased, letters and digits only) => field. */
    protected const HEADER_ALIASES = [
        'parnumber'             => 'par_number',
        'parno'                 => 'par_number',
        'paricsnumber'          => 'par_number',
        'paricsno'              => 'par_number',
        'paricsreceipt'         => 'par_number',
        'receiptno'             => 'par_number',
        'receiptnumber'         => 'par_number',
        'pardate'               => 'par_date',
        'paricsdate'            => 'par_date',
        'receiptdate'           => 'par_date',
        'newpropertyno'         => 'new_property_no',
        'newpropertynumber'     => 'new_property_no',
        'propertyno'            => 'property_no',
        'propertynumber'        => 'property_no',
        'oldpropertyno'         => 'old_property_number',
        'oldpropertynumber'     => 'old_property_number',
        'qty'                   => 'qty',
        'quantity'              => 'qty',
        'unit'                  => 'unit',
        'unitofmeasure'         => 'unit',
        'unitcost'              => 'unit_cost',
        'totalamount'           => 'total_amount',
        'totalcost'             => 'total_amount',
        'total'                 => 'total_amount',
        'itemname'              => 'item_name',
        'brand'                 => 'brand',
        'model'                 => 'model',
        'description'           => 'description',
        'itemdescription'       => 'description',
        'serialnumber'          => 'serial_number',
        'serialno'              => 'serial_number',
        'accountableperson'     => 'accountable_person',
        'accountablepersonid'   => 'accountable_person',
        'fundsource'            => 'fund_source',
        'pr'                    => 'pr_number',
        'prno'                  => 'pr_number',
        'prnumber'              => 'pr_number',
        'purchaserequestno'     => 'pr_number',
        'purchaserequestnumber' => 'pr_number',
        'po'                    => 'po_number',
        'pono'                  => 'po_number',
        'ponumber'              => 'po_number',
        'purchaseorderno'       => 'po_number',
        'purchaseordernumber'   => 'po_number',
        'location'              => 'location',
        'roomlocation'          => 'location',
        'room'                  => 'location',
        'supplier'              => 'supplier',
        'suppliername'          => 'supplier',
        'supplierid'            => 'supplier',
        'invoice'               => 'invoice',
        'invoiceno'             => 'invoice',
        'invoicenumber'         => 'invoice',
        'salesinvoiceno'        => 'invoice',
        'salesinvoicenumber'    => 'invoice',
        'remarks'               => 'remarks',
        'itemremarks'           => 'remarks',
        'issuedby'              => 'issued_by',
        'issuedbyid'            => 'issued_by',
    ];

    protected const FIELD_LABELS = [
        'par_number'         => 'PAR/ICS number',
        'par_date'           => 'PAR/ICS date',
        'new_property_no'    => 'New Property No.',
        'property_no'        => 'Property number',
        'old_property_number'=> 'old property number',
        'qty'                => 'qty',
        'unit'               => 'unit',
        'unit_cost'          => 'unit cost',
        'total_amount'       => 'total amount',
        'item_name'          => 'item name',
        'brand'              => 'brand',
        'model'              => 'model',
        'description'        => 'description',
        'serial_number'      => 'serial number',
        'accountable_person' => 'accountable person',
        'fund_source'        => 'fund source',
        'pr_number'          => 'pr number',
        'po_number'          => 'po number',
        'location'           => 'location',
        'supplier'           => 'supplier',
        'invoice'            => 'invoice',
        'remarks'            => 'remarks',
        'issued_by'          => 'issued by',
    ];

    /** Database column limits for the free text fields. */
    protected const MAX_LENGTHS = [
        'par_number'      => 100,
        'property_number' => 19,
        'fund_source'     => 50,
        'invoice'         => 50,
        'unit'            => 40,
        'pr_number'       => 60,
        'po_number'       => 60,
        'item_name'       => 255,
        'serial_number'   => 255,
        'brand'           => 255,
        'model'           => 255,
        'old_property_number' => 255,
    ];

    protected const MAX_QTY = 1000;

    /**
     * "SN: X", "SN X", "S/N X", "Serial #: X", "Serial Numbers: X", "Serial No.: X".
     * SERIAL needs punctuation after it so prose like "serial port" is not a marker.
     */
    protected const SERIAL_MARKER = '/(?:^|[^A-Z0-9])(?:(?:S\/N|SN)(?![A-Z])\s*[:#.]?|SERIAL\s*(?:NUMBERS?|NOS?|#)?\s*[#:.]+)\s*(.*)$/i';

    protected Worksheet $sheet;

    /** @var array<string, int> field => column index */
    protected array $columns = [];

    /** @var array<string, string> field => header text as written in the file */
    protected array $headerLabels = [];

    /** @var array<int, true> */
    protected array $supplierIds = [];
    /** @var array<string, int> classification code => id */
    protected array $classificationIds = [];
    /** @var array<int, true> */
    protected array $roomIds = [];
    /** @var array<int, true> */
    protected array $profileIds = [];
    /** @var array<int, true> */
    protected array $userIds = [];
    protected array $existingPropertyNumbers = [];
    protected array $filePropertyNumbers = [];
    protected array $receipts = [];
    protected ?int $serviceableConditionId = null;

    public function __construct(protected RoomApiService $roomApi)
    {
    }

    /**
     * $dryRun reviews the file: the import runs in full and the writes are
     * rolled back afterwards, so the report shows exactly what the real
     * import would do without changing the database.
     */
    public function import(
        UploadedFile $file,
        int $startRow = self::DEFAULT_START_ROW,
        bool $dryRun = false
    ): array {
        if (! $dryRun) {
            return $this->runImport($file, $startRow);
        }

        DB::beginTransaction();

        try {
            $result = $this->runImport($file, $startRow);
        } finally {
            DB::rollBack();
        }

        $result['dry_run'] = true;

        return $result;
    }

    protected function runImport(UploadedFile $file, int $startRow): array
    {
        @set_time_limit(600);

        $extension = strtolower($file->getClientOriginalExtension());
        $this->sheet = $this->loadFirstSheet($file->getRealPath(), $extension);

        $this->mapHeader();
        $this->loadLookups();

        $startRow = max(2, $startRow);
        $lastRow = $this->sheet->getHighestDataRow();

        $result = [
            'file'             => $file->getClientOriginalName(),
            'sheet'            => $this->sheet->getTitle(),
            'start_row'        => $startRow,
            'end_row'          => $lastRow,
            'imported_rows'    => 0,
            'imported_items'   => 0,
            'receipts_created' => 0,
            'disposal_items'   => 0,
            'failed'           => [],
            'warnings'         => [],
            'skipped_rows'     => [],
            'notices'          => [],
        ];

        for ($row = $startRow; $row <= $lastRow; $row++) {
            $values = $this->readRow($row);

            if ($this->isBlank($values)) {
                continue;
            }

            if (! $this->hasItemData($values)) {
                $result['skipped_rows'][] = $row;
                continue;
            }

            $errors = [];
            $warnings = [];
            $plan = $this->buildRowPlan($row, $values, $errors, $warnings);
            $label = $this->rowLabel($values);

            if ($errors || $plan === null) {
                $result['failed'][] = ['row' => $row, 'property_number' => $label, 'messages' => $errors];
                continue;
            }

            try {
                $saved = DB::transaction(fn () => $this->saveRow($plan));
                $receiptCreated = $saved['receipt_created'];
                $warnings = array_merge($warnings, $saved['warnings']);
            } catch (Throwable $e) {
                Log::error('Inventory import row failed', ['row' => $row, 'error' => $e->getMessage()]);

                $result['failed'][] = [
                    'row'             => $row,
                    'property_number' => $label,
                    'messages'        => ['Could not be saved: ' . Str::limit($e->getMessage(), 300)],
                ];
                continue;
            }

            foreach ($plan['items'] as $item) {
                $this->existingPropertyNumbers[$this->key($item['property_number'])] = true;
            }

            $result['imported_rows']++;
            $result['imported_items'] += count($plan['items']);
            $result['receipts_created'] += $receiptCreated ? 1 : 0;

            if ($warnings) {
                $result['warnings'][] = ['row' => $row, 'property_number' => $label, 'messages' => $warnings];
            }
        }

        return $result;
    }

    /* ------------------------------------------------------------------
     | Loading
     * ------------------------------------------------------------------ */

    protected function loadFirstSheet(string $path, string $extension): Worksheet
    {
        $type = match ($extension) {
            'xlsx'       => 'Xlsx',
            'xls'        => 'Xls',
            'csv', 'txt' => 'Csv',
            default      => throw new InventoryImportException('Only .xlsx, .xls, or .csv files can be imported.'),
        };

        try {
            $reader = IOFactory::createReader($type);

            if ($reader instanceof CsvReader) {
                $reader->setInputEncoding(CsvReader::GUESS_ENCODING);
            } else {
                // Only the first sheet is imported; skip loading the other office tabs.
                $names = $reader->listWorksheetNames($path);
                if ($names) {
                    $reader->setLoadSheetsOnly([$names[0]]);
                }
            }

            $spreadsheet = $reader->load($path);
        } catch (Throwable $e) {
            throw new InventoryImportException('The file could not be read: ' . $e->getMessage());
        }

        return $spreadsheet->getSheet(0);
    }

    protected function mapHeader(): void
    {
        $lastColumn = Coordinate::columnIndexFromString($this->sheet->getHighestDataColumn());

        for ($col = 1; $col <= $lastColumn; $col++) {
            $raw = $this->text($this->rawValue($col, 1));
            if ($raw === null) {
                continue;
            }

            $clean = str_replace("\u{FEFF}", '', $raw);
            $normalized = preg_replace('/[^a-z0-9]/', '', strtolower($clean));
            $field = self::HEADER_ALIASES[$normalized] ?? null;

            if ($field === null) {
                // Ignore hints in brackets, such as "PAR/ICS number (nullable)".
                $normalized = preg_replace('/[^a-z0-9]/', '', strtolower(preg_replace('/\([^)]*\)/', '', $clean)));
                $field = self::HEADER_ALIASES[$normalized] ?? null;
            }

            if ($field === null) {
                continue; // Columns outside the Batch Add template are ignored.
            }

            if (! isset($this->columns[$field])) {
                $this->columns[$field] = $col;
                $this->headerLabels[$field] = trim(preg_replace('/\s+/', ' ', preg_replace('/\([^)]*\)|\?/', '', $raw))) ?: trim($raw);
            }
        }

        $missing = [];
        if (! isset($this->columns['qty'])) {
            $missing[] = self::FIELD_LABELS['qty'];
        }
        if (! isset($this->columns['new_property_no']) && ! isset($this->columns['property_no'])) {
            $missing[] = self::FIELD_LABELS['property_no'];
        }

        if ($missing) {
            throw new InventoryImportException(
                'The first row of the sheet is missing these column(s): ' . implode(', ', $missing)
                . '. Use the column headers from Download Sample.'
            );
        }
    }

    protected function loadLookups(): void
    {
        $this->serviceableConditionId = AssetCondition::query()
            ->where('disposition', AssetCondition::DISPOSITION_SERVICEABLE)
            ->value('id');

        $rooms = $this->roomApi->fetchRooms()['data'] ?? [];
        foreach ($rooms as $room) {
            if (! empty($room['id'])) {
                $this->roomIds[(int) $room['id']] = true;
            }
        }

        foreach (Supplier::pluck('id') as $supplierId) {
            $this->supplierIds[(int) $supplierId] = true;
        }

        foreach (ItemClassification::all(['id', 'classification_code']) as $classification) {
            $this->classificationIds[trim((string) $classification->classification_code)] ??= $classification->id;
        }

        foreach (UserProfile::pluck('id') as $profileId) {
            $this->profileIds[(int) $profileId] = true;
        }

        foreach (User::pluck('id') as $userId) {
            $this->userIds[(int) $userId] = true;
        }

        // Archived items do not block an import: the new item is added and the
        // archived one can no longer be restored while this number is in use.
        foreach (InventoryItem::pluck('property_number') as $number) {
            $this->existingPropertyNumbers[$this->key($number)] = true;
        }
    }

    /* ------------------------------------------------------------------
     | Row validation
     * ------------------------------------------------------------------ */

    /**
     * Validates one spreadsheet row and returns everything needed to save it,
     * or null when it has errors.
     */
    protected function buildRowPlan(int $row, array $v, array &$errors, array &$warnings): ?array
    {
        $qty = null;
        $qtyRaw = $v['qty'];
        if ($this->isEmpty($qtyRaw)) {
            $errors[] = 'qty is empty.';
        } else {
            $qtyNumber = $this->number($qtyRaw);
            if ($qtyNumber === null || floor($qtyNumber) != $qtyNumber || $qtyNumber < 1) {
                $errors[] = 'qty "' . $this->text($qtyRaw) . '" must be a whole number of 1 or more.';
            } elseif ($qtyNumber > self::MAX_QTY) {
                $errors[] = 'qty ' . (int) $qtyNumber . ' is more than ' . self::MAX_QTY . '.';
            } else {
                $qty = (int) $qtyNumber;
            }
        }

        $unitCost = null;
        if (! $this->isEmpty($v['unit_cost'] ?? null)) {
            $unitCost = $this->number($v['unit_cost']);
            if ($unitCost === null || $unitCost < 0) {
                $errors[] = $this->label('unit_cost') . ' "' . $this->text($v['unit_cost']) . '" is not a valid amount.';
                $unitCost = null;
            }
        }

        $totalAmount = null;
        if (! $this->isEmpty($v['total_amount'] ?? null)) {
            $totalAmount = $this->number($v['total_amount']);
            if ($totalAmount === null || $totalAmount < 0) {
                $errors[] = $this->label('total_amount') . ' "' . $this->text($v['total_amount']) . '" is not a valid amount.';
                $totalAmount = null;
            }
        }

        if ($unitCost !== null && $qty !== null && $totalAmount !== null) {
            $expected = round($unitCost * $qty, 2);
            if (abs($totalAmount - $expected) > 0.01) {
                $warnings[] = $this->label('total_amount') . ' ' . $this->money($totalAmount)
                    . ' does not equal unit cost × qty (' . $this->money($expected) . ').';
            }
        }

        $description = $this->text($v['description'] ?? null);
        $itemName = $this->singleLine($v['item_name'] ?? null);
        if ($itemName !== null && mb_strlen($itemName) > self::MAX_LENGTHS['item_name']) {
            $errors[] = $this->label('item_name') . ' is longer than ' . self::MAX_LENGTHS['item_name'] . ' characters.';
        }

        $propertyNumbers = $this->resolvePropertyNumbers($v, $qty, $errors, $warnings);
        foreach ($propertyNumbers['numbers'] as $number) {
            if (mb_strlen($number) > self::MAX_LENGTHS['property_number']) {
                $errors[] = 'Property number "' . $number . '" is longer than ' . self::MAX_LENGTHS['property_number'] . ' characters.';
                continue;
            }

            $key = $this->key($number);
            if (isset($this->filePropertyNumbers[$key]) && $this->filePropertyNumbers[$key] !== $row) {
                $errors[] = 'Property number ' . $number . ' is repeated (also on row ' . $this->filePropertyNumbers[$key] . ').';
            } elseif (isset($this->existingPropertyNumbers[$key])) {
                $errors[] = 'Property number ' . $number . ' already exists in the inventory.';
            } else {
                $this->filePropertyNumbers[$key] = $row;
            }
        }

        $classificationId = $this->resolveClassification($v, $propertyNumbers['numbers'][0] ?? null);

        $oldPropertyNumber = $this->singleLine($v['old_property_number'] ?? null);
        if ($oldPropertyNumber !== null && mb_strlen($oldPropertyNumber) > self::MAX_LENGTHS['old_property_number']) {
            $errors[] = $this->label('old_property_number') . ' is longer than ' . self::MAX_LENGTHS['old_property_number'] . ' characters.';
        }

        $supplierId = null;
        if (! $this->isEmpty($v['supplier'] ?? null)) {
            $supplierId = $this->integerId($v['supplier']);
            if ($supplierId === null) {
                $errors[] = $this->label('supplier') . ' must be a numeric supplier id.';
            } elseif (! isset($this->supplierIds[$supplierId])) {
                $errors[] = $this->label('supplier') . ' id ' . $supplierId . ' does not exist in Suppliers.';
            }
        }

        $roomId = null;
        if (! $this->isEmpty($v['location'] ?? null)) {
            $roomId = $this->integerId($v['location']);
            if ($roomId === null) {
                $errors[] = $this->label('location') . ' must be a numeric room id.';
            } elseif (! isset($this->roomIds[$roomId])) {
                $errors[] = $this->label('location') . ' id ' . $roomId . ' does not exist in the rooms API.';
            }
        }

        $assignment = $this->resolveAssignment($row, $v, $errors, $warnings);

        $text = [];
        foreach (['unit', 'fund_source', 'pr_number', 'po_number', 'invoice', 'brand', 'model'] as $field) {
            $value = $this->singleLine($v[$field] ?? null);
            if ($value !== null && mb_strlen($value) > self::MAX_LENGTHS[$field]) {
                $errors[] = $this->label($field) . ' "' . Str::limit($value, 40) . '" is longer than ' . self::MAX_LENGTHS[$field] . ' characters.';
            }
            $text[$field] = $value;
        }

        $serials = $this->serialNumbers($v['serial_number'] ?? null, $qty, $errors);
        if ($serials === [] && $qty !== null) {
            $serials = $this->splitSerials($this->extractSerials($description), (int) $qty, $warnings);
        }

        if ($errors) {
            return null;
        }

        $remarks = $this->text($v['remarks'] ?? null);

        $items = [];
        foreach ($propertyNumbers['numbers'] as $index => $number) {
            $itemRemarks = $remarks;
            $oldNumber = $propertyNumbers['old'][$index] ?? null;
            if ($oldNumber !== null && $oldNumber !== $number) {
                $itemRemarks = trim(($itemRemarks ? $itemRemarks . "\n" : '') . 'Old Property No.: ' . $oldNumber);
            }

            $items[] = [
                'property_number' => $number,
                'serial_number'   => $serials[$index] ?? null,
                'remarks'         => $itemRemarks,
            ];
        }

        return [
            'row'               => $row,
            'items'             => $items,
            'classification_id' => $classificationId,
            'supplier_id'       => $supplierId,
            'room_id'           => $roomId,
            'item_name'         => $itemName,
            'description'       => $description,
            'unit_cost'         => $unitCost,
            'total_amount'      => $totalAmount,
            'old_property_number' => $oldPropertyNumber,
            'condition_id'      => $this->serviceableConditionId,
            'assignment'        => $assignment,
        ] + $text;
    }

    /**
     * Splits the optional Serial Number cell into one value per item.
     *
     * @return string[]
     */
    protected function serialNumbers(mixed $raw, ?int $qty, array &$errors): array
    {
        $serial = $this->text($raw);
        if ($serial === null || $qty === null) {
            return [];
        }

        $parts = $qty === 1
            ? [trim(preg_replace('/\s+/', ' ', $serial))]
            : array_values(array_filter(array_map('trim', preg_split('/[\n,;]+/', $serial))));

        if (count($parts) !== $qty) {
            $errors[] = $this->label('serial_number') . ' lists ' . count($parts) . ' serial number(s) but qty is ' . $qty . '. Separate them with commas, one per unit.';
        } elseif (collect($parts)->contains(fn ($part) => mb_strlen($part) > self::MAX_LENGTHS['serial_number'])) {
            $errors[] = $this->label('serial_number') . ' is longer than ' . self::MAX_LENGTHS['serial_number'] . ' characters.';
        } else {
            return $parts;
        }

        return [];
    }

    /**
     * Picks New Property No. when present (otherwise Property No.) and expands ranges
     * like 240-269(001-002) into one number per unit.
     *
     * @return array{numbers: string[], old: array<int, string|null>}
     */
    protected function resolvePropertyNumbers(array $v, ?int $qty, array &$errors, array &$warnings): array
    {
        $new = $this->text($v['new_property_no'] ?? null);
        $old = $this->text($v['property_no'] ?? null);

        if ($new === null && $old === null) {
            $errors[] = 'New Property No. and Property No. are both empty.';
            return ['numbers' => [], 'old' => []];
        }

        $label = $new !== null ? 'New Property No.' : 'Property No.';
        $parsed = $this->expandPropertyNumber($new ?? $old);

        if ($parsed === null) {
            $errors[] = $label . ' "' . $this->singleLine($new ?? $old) . '" could not be read. Write ranges like 240-269-(001-002).';
            return ['numbers' => [], 'old' => []];
        }

        $numbers = $parsed['numbers'];

        if ($qty !== null && count($numbers) !== $qty) {
            if ($qty === 1) {
                // e.g. 236-455-001-(A-B) with Qty 1: one item made of parts, keep it as written.
                $numbers = [$parsed['compact']];
                $warnings[] = $label . ' "' . $parsed['compact'] . '" lists several parts but Qty is 1, so it was saved as one item with that number.';
            } elseif (count($numbers) === 1) {
                $errors[] = 'Qty is ' . $qty . ' but ' . $label . ' "' . $parsed['compact'] . '" is a single number. Write the range like ' . $this->exampleRange($parsed['compact'], $qty) . '.';
                return ['numbers' => [], 'old' => []];
            } else {
                $errors[] = 'Qty is ' . $qty . ' but ' . $label . ' "' . $parsed['compact'] . '" gives ' . count($numbers) . ' numbers.';
                return ['numbers' => [], 'old' => []];
            }
        }

        // Keep the old number for each item so it can be noted in its remarks.
        $oldPerItem = [];
        if ($new !== null && $old !== null) {
            $oldParsed = $this->expandPropertyNumber($old);
            foreach ($numbers as $index => $unused) {
                $oldPerItem[$index] = $oldParsed && count($oldParsed['numbers']) === count($numbers)
                    ? $oldParsed['numbers'][$index]
                    : ($oldParsed['compact'] ?? $this->singleLine($old));
            }
        }

        return ['numbers' => $numbers, 'old' => $oldPerItem];
    }

    /**
     * 240-269(001-002)            => 240-269-001, 240-269-002
     * 2022-70-10-(1030-1031)-123   => 2022-70-10-1030-123, 2022-70-10-1031-123
     * 236-455-001-(A-C)            => 236-455-001-A, -B, -C
     * 223-465-(001,003)            => 223-465-001, 223-465-003
     *
     * @return array{numbers: string[], compact: string}|null
     */
    protected function expandPropertyNumber(string $raw): ?array
    {
        // Notes such as "7321-7322 - Old Property No." follow a blank line; ignore them.
        $firstBlock = preg_split('/\n\s*\n/', trim($raw))[0];
        $compact = preg_replace('/\s+/', '', $firstBlock);

        if ($compact === '') {
            return null;
        }

        if (! preg_match('/^(.*?)\(([^()]*)\)(.*)$/', $compact, $m)) {
            return str_contains($compact, '(') || str_contains($compact, ')')
                ? null
                : ['numbers' => [$compact], 'compact' => $compact];
        }

        [, $prefix, $inner, $suffix] = $m;
        $values = $this->expandRangeValues($inner);

        if ($values === null || str_contains($prefix . $suffix, '(')) {
            return null;
        }

        $numbers = array_map(function ($value) use ($prefix, $suffix) {
            $parts = array_filter([rtrim($prefix, '-'), $value, ltrim($suffix, '-')], fn ($part) => $part !== '');
            return implode('-', $parts);
        }, $values);

        return ['numbers' => $numbers, 'compact' => $compact];
    }

    protected function expandRangeValues(string $inner): ?array
    {
        if (preg_match('/^(\d+)(?:-|to)(\d+)$/i', $inner, $m)) {
            [$start, $end] = [(int) $m[1], (int) $m[2]];
            if ($end < $start || $end - $start >= self::MAX_QTY) {
                return null;
            }
            $width = strlen($m[1]);
            return array_map(fn ($n) => str_pad((string) $n, $width, '0', STR_PAD_LEFT), range($start, $end));
        }

        if (preg_match('/^([A-Z])(?:-|to)([A-Z])$/i', $inner, $m)) {
            $start = strtoupper($m[1]);
            $end = strtoupper($m[2]);
            return $end < $start ? null : range($start, $end);
        }

        if (str_contains($inner, ',')) {
            $values = array_map('trim', explode(',', $inner));
            return in_array('', $values, true) ? null : $values;
        }

        return preg_match('/^[A-Z0-9]+$/i', $inner) ? [$inner] : null;
    }

    protected function exampleRange(string $number, int $qty): string
    {
        return rtrim($number, '-') . '-(001-' . str_pad((string) $qty, 3, '0', STR_PAD_LEFT) . ')';
    }

    /** Uses a matching PAR/ICS or property-number prefix when one exists. */
    protected function resolveClassification(array $v, ?string $propertyNumber): ?int
    {
        $candidates = [];
        foreach ([$this->singleLine($v['par_number'] ?? null), $propertyNumber] as $value) {
            if ($value !== null && preg_match('/^(\d+)-/', $value, $m)) {
                $candidates[$m[1]] = true;
            }
        }

        foreach (array_keys($candidates) as $code) {
            if (isset($this->classificationIds[$code])) {
                return $this->classificationIds[$code];
            }
        }

        return null;
    }

    /**
     * The accountable person, PAR/ICS number, PAR/ICS date, and issuer are all
     * optional. An assignment needs both an accountable person id and a PAR/ICS
     * number; a blank issuer falls back to the configured import account.
     */
    protected function resolveAssignment(int $row, array $v, array &$errors, array &$warnings): ?array
    {
        $parNumber = $this->singleLine($v['par_number'] ?? null);
        $accountableRaw = $this->text($v['accountable_person'] ?? null);

        if ($accountableRaw === null) {
            if ($parNumber !== null) {
                $warnings[] = $this->label('par_number') . ' was not saved because no accountable person id was given.';
            }
            return null;
        }

        $profileId = $this->integerId($accountableRaw);
        if ($profileId === null || ! isset($this->profileIds[$profileId])) {
            $errors[] = $this->label('accountable_person') . ' "' . $accountableRaw . '" must be a numeric user profile id that exists.';
            return null;
        }

        if ($parNumber === null) {
            $warnings[] = $this->label('accountable_person') . ' id ' . $profileId . ' was not assigned because ' . $this->label('par_number') . ' is empty.';
            return null;
        }

        if (mb_strlen($parNumber) > self::MAX_LENGTHS['par_number']) {
            $errors[] = $this->label('par_number') . ' is longer than ' . self::MAX_LENGTHS['par_number'] . ' characters.';
        }

        $parDate = $this->date($row, 'par_date', $errors);
        if ($parDate === null && ! $this->isEmpty($v['par_date'] ?? null)) {
            return null; // the date error was already recorded
        }

        $issuedById = self::ISSUED_BY_ID;
        if (! $this->isEmpty($v['issued_by'] ?? null)) {
            $issuedById = $this->integerId($v['issued_by']);
            if ($issuedById === null) {
                $errors[] = $this->label('issued_by') . ' must be a numeric user id.';
                return null;
            }
        }

        if (! isset($this->userIds[$issuedById]) || ! isset($this->profileIds[$issuedById])) {
            $errors[] = $this->label('issued_by') . ' id ' . $issuedById . ' must exist in Users and User Profiles.';
            return null;
        }

        return [
            'profile_id'    => $profileId,
            'par_number'    => $parNumber,
            'par_date'      => $parDate?->toDateString(),
            'issued_by_id'  => $issuedById,
        ];
    }

    /* ------------------------------------------------------------------
     | Saving
     * ------------------------------------------------------------------ */

    /**
     * @return array{receipt_created: bool, warnings: string[]}
     */
    protected function saveRow(array $plan): array
    {
        $assignment = $plan['assignment'];
        $receiptCreated = false;
        $warnings = [];
        $receipt = null;
        $totalPerItem = $plan['unit_cost'];

        if ($assignment) {
            [$receipt, $receiptCreated] = $this->findOrCreateReceipt($assignment);
        }

        if ($totalPerItem === null && $plan['total_amount'] !== null) {
            $totalPerItem = round($plan['total_amount'] / max(count($plan['items']), 1), 2);
        }

        foreach ($plan['items'] as $item) {
            $inventoryItem = InventoryItem::create([
                'item_classification_id' => $plan['classification_id'],
                'supplier_id'            => $plan['supplier_id'],
                'invoice'                => $plan['invoice'],
                'fund_source'            => $plan['fund_source'],
                'item_name'              => $plan['item_name'],
                'description'            => $plan['description'],
                'quantity'               => 1,
                'unit'                   => $plan['unit'],
                'unit_cost'              => $plan['unit_cost'],
                'total_amount'           => $totalPerItem,
                'property_number'        => $item['property_number'],
                'old_property_number'    => $plan['old_property_number'],
                'serial_number'          => $item['serial_number'],
                'pr_number'              => $plan['pr_number'] ?? null,
                'po_number'              => $plan['po_number'] ?? null,
                'remarks'                => $item['remarks'],
                'brand'                  => $plan['brand'] ?? null,
                'model'                  => $plan['model'] ?? null,
            ]);

            if ($plan['condition_id'] !== null) {
                AssetInspection::create([
                    'inventory_item_id'  => $inventoryItem->id,
                    'asset_condition_id' => $plan['condition_id'],
                    'inspected_by'       => auth()->id(),
                    'inspection_date'    => now(),
                    'remarks'            => 'Initial condition recorded on import (row ' . $plan['row'] . ').',
                ]);
            }

            if ($plan['room_id'] !== null) {
                ItemHistoryLocation::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'room_id'           => $plan['room_id'],
                ]);
            }

            if ($receipt) {
                AcknowledgementItem::create([
                    'acknowledgement_id'    => $receipt->id,
                    'inventory_item_id'     => $inventoryItem->id,
                    'accountable_person_id' => $assignment['profile_id'],
                    'issued_by_id'          => $assignment['issued_by_id'],
                    'status'                => 1,
                ]);
            }
        }

        return [
            'receipt_created' => $receiptCreated,
            'warnings'        => $warnings,
        ];
    }

    /** One receipt per PAR/ICS number, reusing an existing receipt. */
    protected function findOrCreateReceipt(array $assignment): array
    {
        $key = $this->key($assignment['par_number']);

        if (isset($this->receipts[$key])) {
            return [$this->receipts[$key], false];
        }

        $receipt = AcknowledgementReceipt::where('category', $assignment['par_number'])->first();
        $created = false;

        if (! $receipt) {
            $receipt = AcknowledgementReceipt::create([
                'category'     => $assignment['par_number'],
                'par_date'     => $assignment['par_date'],
                'issued_by_id' => $assignment['issued_by_id'],
                'created_by'   => auth()->id(),
            ]);
            $created = true;
        }

        // Only remember it once the surrounding transaction commits.
        DB::afterCommit(fn () => $this->receipts[$key] = $receipt);

        return [$receipt, $created];
    }

    /* ------------------------------------------------------------------
     | Serial numbers
     * ------------------------------------------------------------------ */

    /** Pulls serial numbers out of "SN: ...", "SN 123", "Serial #: Indoor: ... Outdoor: ..." text. */
    protected function extractSerials(?string $description): array
    {
        if ($description === null) {
            return [];
        }

        $serials = [];
        $capturing = false;
        $lastTokenOnly = false;

        foreach (preg_split('/\n/', $description) as $line) {
            $line = trim(str_replace("\t", ' ', $line));

            if ($line === '') {
                $capturing = false;
                continue;
            }

            $afterMarker = false;
            if (preg_match(self::SERIAL_MARKER, $line, $m)) {
                $capturing = true;
                $afterMarker = true;
                // "Indoor Model # & Serial #:" lists "FTKQ50TVM, EO13268" per line.
                $lastTokenOnly = (bool) preg_match('/MODEL\s*(?:#|NO\.?)?\s*(?:&|AND|\/)\s*SERIAL/i', $line);
                $line = $m[1];
            } elseif (! $capturing || preg_match('/^(MODEL|PART|BRAND|OFFER|SPEC)/i', $line)) {
                $capturing = false;
                continue;
            }

            $tokens = $this->serialTokens($line, $afterMarker);
            if ($lastTokenOnly && $tokens) {
                $tokens = [end($tokens)];
            }
            array_push($serials, ...$tokens);
        }

        return $serials;
    }

    protected function serialTokens(string $text, bool $afterMarker = false): array
    {
        $text = preg_replace('/\([^)]*\)/', ' ', $text);                // "(Property No ...)"
        $text = preg_replace('/^\s*\d+\s*[.)]+\s*/', '', $text);        // "1.)"
        $text = preg_replace('/\b[A-Za-z][A-Za-z ]{1,19}:\s*/', ' ', $text); // "Indoor:" labels

        $serials = [];

        foreach (preg_split('/[\s,;\/]+/', $text, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            $token = trim($token, '-.');

            // "SN:162119 Standford Research System" - stop at the first ordinary word.
            if ($serials && preg_match('/[a-z]/', $token)) {
                break;
            }

            $looksLikeSerial = preg_match('/^[A-Z0-9][A-Z0-9\-]{3,}$/i', $token) && preg_match('/\d/', $token);
            // Right after "SN:", an all-capitals code without digits ("HRWWQXJQHC") still counts.
            $capsAfterMarker = $afterMarker && ! $serials && preg_match('/^[A-Z]{6,}$/', $token);

            if ($looksLikeSerial || $capsAfterMarker) {
                $serials[] = $token;
            }
        }

        return $serials;
    }

    /**
     * Gives each unit its own serial when the count lines up with Qty.
     * With Indoor/Outdoor pairs (2 serials per unit) unit 1 gets the first of each group.
     */
    protected function splitSerials(array $serials, int $qty, array &$warnings): array
    {
        $count = count($serials);

        if ($count === 0) {
            return [];
        }

        $perItem = [];

        if ($qty === 1) {
            $perItem[] = implode(' / ', array_unique($serials));
        } elseif ($count % $qty === 0) {
            $groups = intdiv($count, $qty);
            for ($i = 0; $i < $qty; $i++) {
                $perItem[] = implode(' / ', array_map(fn ($g) => $serials[$i + $g * $qty], range(0, $groups - 1)));
            }
        } else {
            $warnings[] = 'Found ' . $count . ' serial number(s) for Qty ' . $qty . ', so they could not be split per item. They remain in the description.';
            return [];
        }

        foreach ($perItem as $index => $serial) {
            if (mb_strlen($serial) > 255) {
                $warnings[] = 'The serial numbers for item ' . ($index + 1) . ' are longer than 255 characters, so they were left in the description only.';
                $perItem[$index] = null;
            }
        }

        return $perItem;
    }

    /* ------------------------------------------------------------------
     | Cell helpers
     * ------------------------------------------------------------------ */

    protected function readRow(int $row): array
    {
        $values = [];
        foreach (array_keys(self::FIELD_LABELS) as $field) {
            $values[$field] = isset($this->columns[$field]) ? $this->rawValue($this->columns[$field], $row) : null;
        }

        return $values;
    }

    protected function rawValue(int $col, int $row): mixed
    {
        $cell = $this->sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row);

        try {
            $value = $cell->getCalculatedValue();
        } catch (Throwable) {
            $value = $cell->getOldCalculatedValue();
        }

        return $value instanceof RichText ? $value->getPlainText() : $value;
    }

    protected function date(int $row, string $field, array &$errors): ?Carbon
    {
        if (! isset($this->columns[$field])) {
            return null;
        }

        $label = $this->label($field);
        $col = $this->columns[$field];
        $value = $this->rawValue($col, $row);

        if ($this->isEmpty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (float) $value;
            // Excel serial numbers between 1950-01-01 and 2100-12-31.
            if ($serial < 18264 || $serial > 73415) {
                $errors[] = $label . ' "' . $this->text($value) . '" is not a valid date.';
                return null;
            }
            return Carbon::instance(ExcelDate::excelToDateTimeObject($serial))->startOfDay();
        }

        $text = $this->firstLine($value);
        foreach (['Y-m-d', 'm/d/Y', 'n/j/Y', 'm-d-Y', 'n-j-Y', 'm/d/y', 'n/j/y', 'Y/m/d', 'F j, Y', 'M j, Y', 'F d, Y', 'M d, Y', 'j-M-Y', 'd-M-Y', 'j M Y', 'Y-m-d H:i:s'] as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $text);
            } catch (Throwable) {
                continue;
            }
            if ($date && $date->format($format) === $text && $date->year >= 1950 && $date->year <= 2100) {
                return $date->startOfDay();
            }
        }

        $errors[] = $label . ' "' . $text . '" is not a valid date. Use a date like 2022-01-31 or 01/31/2022.';

        return null;
    }

    protected function isBlank(array $values): bool
    {
        foreach ($values as $value) {
            if (! $this->isEmpty($value)) {
                return false;
            }
        }

        return true;
    }

    /** Section labels ("January to December 2023") and formula-only total rows have no item data. */
    protected function hasItemData(array $values): bool
    {
        foreach (['new_property_no', 'property_no', 'item_name', 'description', 'qty', 'unit_cost'] as $field) {
            if (! $this->isEmpty($values[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    protected function rowLabel(array $values): ?string
    {
        $number = $this->text($values['new_property_no'] ?? null) ?? $this->text($values['property_no'] ?? null);

        return $number === null ? null : Str::limit(preg_replace('/\s+/', ' ', $number), 60);
    }

    /** The column's header as written in the file. */
    protected function label(string $field): string
    {
        return $this->headerLabels[$field] ?? self::FIELD_LABELS[$field] ?? $field;
    }

    protected function isEmpty(mixed $value): bool
    {
        return $this->text($value) === null;
    }

    protected function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_float($value) && floor($value) == $value && abs($value) < 1e15) {
            $value = (string) (int) $value;
        }

        $value = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], (string) $value);
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    protected function singleLine(mixed $value): ?string
    {
        $text = $this->text($value);

        return $text === null ? null : trim(preg_replace('/\s+/', ' ', $text));
    }

    protected function firstLine(mixed $value): string
    {
        foreach (preg_split('/\n/', (string) $this->text($value)) as $line) {
            if (trim($line) !== '') {
                return trim(preg_replace('/\s+/', ' ', $line));
            }
        }

        return '';
    }

    protected function number(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = str_replace(['₱', 'PHP', 'Php', ',', ' '], '', (string) $this->text($value));

        return is_numeric($text) ? (float) $text : null;
    }

    protected function money(float $amount): string
    {
        return '₱' . number_format($amount, 2);
    }

    protected function key(?string $value): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }

    protected function integerId(mixed $value): ?int
    {
        $text = $this->singleLine($value);

        return $text !== null && ctype_digit($text) ? (int) $text : null;
    }
}
