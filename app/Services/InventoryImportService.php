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
use App\Models\TransferType;
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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Imports the PAR/ICS spreadsheet layout (PAR Number, PAR Date, New Property No., ...)
 * into inventory items, their condition, location, and receipt assignment.
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

    /** Receipts and assignments are issued by this user / user profile (both share the id). */
    public const ISSUED_BY_ID = 2169;

    /** Header text (lowercased, letters and digits only) => field. */
    protected const HEADER_ALIASES = [
        'parnumber'            => 'par_number',
        'parno'                => 'par_number',
        'pardate'              => 'par_date',
        'newpropertyno'        => 'new_property_no',
        'newpropertynumber'    => 'new_property_no',
        'propertyno'           => 'property_no',
        'propertynumber'       => 'property_no',
        'qty'                  => 'qty',
        'quantity'             => 'qty',
        'unit'                 => 'unit',
        'unitcost'             => 'unit_cost',
        'totalamount'          => 'total_amount',
        'total'                => 'total_amount',
        'description'          => 'description',
        'dateacquired'         => 'date_acquired',
        'accountableperson'    => 'accountable_person',
        'newaccountableperson' => 'new_accountable_person',
        'newaccountable'       => 'new_accountable',
        'fundsource'           => 'fund_source',
        'pr'                   => 'pr_number',
        'prno'                 => 'pr_number',
        'prnumber'             => 'pr_number',
        'po'                   => 'po_number',
        'pono'                 => 'po_number',
        'ponumber'             => 'po_number',
        'location'             => 'location',
        'supplier'             => 'supplier',
        'invoice'              => 'invoice',
        'invoiceno'            => 'invoice',
        'invoicenumber'        => 'invoice',
        'remarks'              => 'remarks',

        // Columns from the sample workbook (Download Sample).
        'receiptno'            => 'par_number',
        'receiptnumber'        => 'par_number',
        'receiptdate'          => 'par_date',
        'itemname'             => 'item_name',
        'serialnumber'         => 'serial_number',
        'serialno'             => 'serial_number',
        'brand'                => 'brand',
        'model'                => 'model',
        'condition'            => 'condition',
        'lifespan'             => 'lifespan',
        'lifespanyears'        => 'lifespan',
        'salesinvoicedate'     => 'sales_invoice_date',
        'podate'               => 'po_date',
        'ponumberdate'         => 'po_date',
        'approvalstatus'       => 'approval_status',
        'private'              => 'private',
        'isprivate'            => 'private',

        // Clearer headers used by the current sample (hints in brackets are ignored).
        'itemdescription'       => 'description',
        'suppliername'          => 'supplier',
        'unitofmeasure'         => 'unit',
        'totalcost'             => 'total_amount',
        'salesinvoiceno'        => 'invoice',
        'salesinvoicenumber'    => 'invoice',
        'purchaserequestno'     => 'pr_number',
        'purchaserequestnumber' => 'pr_number',
        'purchaseorderno'       => 'po_number',
        'purchaseordernumber'   => 'po_number',
        'purchaseorderdate'     => 'po_date',
        'usefullife'            => 'lifespan',
        'roomlocation'          => 'location',
        'room'                  => 'location',
        'privateitem'           => 'private',
        'itemremarks'           => 'remarks',
        'receiptremarks'        => 'receipt_remarks',
    ];

    protected const FIELD_LABELS = [
        'par_number'             => 'PAR Number',
        'par_date'               => 'PAR Date',
        'new_property_no'        => 'New Property No.',
        'property_no'            => 'Property No.',
        'qty'                    => 'Qty',
        'unit'                   => 'Unit',
        'unit_cost'              => 'Unit Cost',
        'total_amount'           => 'Total Amount',
        'description'            => 'Description',
        'date_acquired'          => 'Date Acquired',
        'accountable_person'     => 'Accountable Person',
        'new_accountable_person' => 'New Accountable Person',
        'new_accountable'        => 'New Accountable',
        'fund_source'            => 'Fund Source',
        'pr_number'              => 'PR#',
        'po_number'              => 'PO#',
        'location'               => 'Location',
        'supplier'               => 'Supplier',
        'invoice'                => 'Invoice',
        'remarks'                => 'Remarks',
        'item_name'              => 'Item Name',
        'serial_number'          => 'Serial Number',
        'brand'                  => 'Brand',
        'model'                  => 'Model',
        'condition'              => 'Condition',
        'lifespan'               => 'Lifespan',
        'sales_invoice_date'     => 'Sales Invoice Date',
        'po_date'                => 'PO Date',
        'approval_status'        => 'Approval Status',
        'private'                => 'Private',
        'receipt_remarks'        => 'Receipt Remarks',
    ];

    /** Database column limits for the free text fields. */
    protected const MAX_LENGTHS = [
        'par_number'      => 100,
        'property_number' => 80,
        'fund_source'     => 50,
        'invoice'         => 50,
        'unit'            => 40,
        'pr_number'       => 60,
        'po_number'       => 60,
        'item_name'       => 255,
        'serial_number'   => 255,
        'brand'           => 255,
        'model'           => 255,
        'receipt_remarks' => 255,
    ];

    protected const APPROVAL_STATUSES = ['pending', 'approved', 'rejected'];

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

    /** True for the PAR workbook (has a "PAR Number" column), false for the sample layout. */
    protected bool $isParLayout = false;

    /** @var array<string, int> upper-case condition name => asset_conditions id */
    protected array $conditionIds = [];
    protected array $disposalPathConditionIds = [];

    protected bool $readsColours = true;

    protected array $supplierIds = [];
    protected array $classificationIds = [];
    protected array $roomIds = [];
    protected array $profiles = [];
    protected array $profileMatches = [];
    protected array $existingPropertyNumbers = [];
    protected array $filePropertyNumbers = [];
    protected array $receipts = [];
    protected int $serviceableConditionId;
    protected int $disposedConditionId;
    protected ?int $reassignmentTypeId = null;

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
        $this->readsColours = $extension !== 'csv' && $extension !== 'txt';

        $this->mapHeader();
        $this->loadLookups();

        // The temporary start row is for the PAR workbook; sample-layout files start at row 2.
        $startRow = $this->isParLayout ? max(2, $startRow) : 2;
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

        if (! $this->readsColours && ! isset($this->columns['condition'])) {
            $result['notices'][] = 'CSV files carry no cell colours, so yellow (Disposal) rows cannot be detected. Every item was recorded as Good.';
        }

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
            $result['disposal_items'] += $plan['is_disposal'] ? count($plan['items']) : 0;

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
                // "Date Acquired (YYYY-MM-DD)", "Private Item? (Yes / No)", ...
                $normalized = preg_replace('/[^a-z0-9]/', '', strtolower(preg_replace('/\([^)]*\)/', '', $clean)));
                $field = self::HEADER_ALIASES[$normalized] ?? null;
            }

            if ($field === null) {
                continue; // Office, Warranty, Issued By, Sticker, Picture, ... are ignored.
            }

            // The sheet repeats "New Accountable Person"; the second one is the newest holder.
            if ($field === 'new_accountable_person' && isset($this->columns['new_accountable_person'])) {
                $field = 'new_accountable';
            }

            if (in_array($normalized, ['parnumber', 'parno'], true)) {
                $this->isParLayout = true;
            }

            if (! isset($this->columns[$field])) {
                $this->columns[$field] = $col;
                $this->headerLabels[$field] = trim(preg_replace('/\s+/', ' ', preg_replace('/\([^)]*\)|\?/', '', $raw))) ?: trim($raw);
            }
        }

        $missing = [];
        foreach (['par_number', 'par_date', 'qty', 'unit_cost', 'description', 'location', 'supplier'] as $field) {
            if (! isset($this->columns[$field])) {
                $missing[] = match ($field) {
                    'par_number'  => 'Receipt No. (PAR / ICS)',
                    'par_date'    => 'Receipt Date',
                    'qty'         => 'Quantity',
                    'unit_cost'   => 'Unit Cost',
                    'description' => 'Item Description',
                    'location'    => 'Room / Location',
                    'supplier'    => 'Supplier Name',
                    default       => self::FIELD_LABELS[$field],
                };
            }
        }
        if (! isset($this->columns['new_property_no']) && ! isset($this->columns['property_no'])) {
            $missing[] = 'Property Number';
        }
        if (! array_intersect_key($this->columns, array_flip(['accountable_person', 'new_accountable_person', 'new_accountable']))) {
            $missing[] = 'Accountable Person (Full Name)';
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
        if (! User::whereKey(self::ISSUED_BY_ID)->exists() || ! UserProfile::whereKey(self::ISSUED_BY_ID)->exists()) {
            throw new InventoryImportException('The issuing user (id ' . self::ISSUED_BY_ID . ') does not exist, so receipts cannot be created.');
        }

        $conditions = AssetCondition::all();

        $serviceable = $conditions
            ->firstWhere('disposition', AssetCondition::DISPOSITION_SERVICEABLE);
        $disposed = $conditions
            ->firstWhere('disposition', AssetCondition::DISPOSITION_DISPOSED);

        if (! $serviceable || ! $disposed) {
            throw new InventoryImportException('The asset conditions need one serviceable condition and one disposed condition before importing.');
        }

        $this->serviceableConditionId = $serviceable->id;
        $this->disposedConditionId = $disposed->id;
        $this->conditionIds = $conditions->keyBy(fn ($c) => strtoupper(trim($c->condition_name)))->map(fn ($c) => $c->id)->all();
        $this->disposalPathConditionIds = $conditions
            ->filter(fn ($c) => $c->isOnDisposalPath())
            ->pluck('id')
            ->all();

        $rooms = $this->roomApi->fetchRooms()['data'] ?? [];
        if (empty($rooms)) {
            throw new InventoryImportException('The rooms API returned no rooms, so locations cannot be checked. Please try again later.');
        }
        foreach ($rooms as $room) {
            foreach (['room_name', 'room_code'] as $key) {
                if (! empty($room[$key])) {
                    $this->roomIds[$this->key($room[$key])] ??= (int) $room['id'];
                }
            }
        }

        foreach (Supplier::all(['id', 'supplier_name']) as $supplier) {
            $this->supplierIds[$this->supplierKey($supplier->supplier_name)] ??= $supplier->id;
        }

        foreach (ItemClassification::all(['id', 'classification_code']) as $classification) {
            $this->classificationIds[trim((string) $classification->classification_code)] ??= $classification->id;
        }

        foreach (UserProfile::all(['id', 'first_name', 'middle_name', 'last_name']) as $profile) {
            $this->profiles[] = [
                'id'     => $profile->id,
                'first'  => $this->nameKey($profile->first_name),
                'middle' => $this->nameKey($profile->middle_name),
                'last'   => $this->nameKey($profile->last_name),
                'name'   => trim($profile->first_name . ' ' . $profile->last_name),
            ];
        }

        // Archived items do not block an import: the new item is added and the
        // archived one can no longer be restored while this number is in use.
        foreach (InventoryItem::pluck('property_number') as $number) {
            $this->existingPropertyNumbers[$this->key($number)] = true;
        }

        $this->reassignmentTypeId = TransferType::whereRaw('LOWER(name) = ?', ['reassignment'])->value('id');
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
        // Qty
        $qty = null;
        $qtyRaw = $v['qty'];
        if ($this->isEmpty($qtyRaw)) {
            $errors[] = 'Qty is empty.';
        } else {
            $qtyNumber = $this->number($qtyRaw);
            if ($qtyNumber === null || floor($qtyNumber) != $qtyNumber || $qtyNumber < 1) {
                $errors[] = 'Qty "' . $this->text($qtyRaw) . '" must be a whole number of 1 or more.';
            } elseif ($qtyNumber > self::MAX_QTY) {
                $errors[] = 'Qty ' . (int) $qtyNumber . ' is more than ' . self::MAX_QTY . '.';
            } else {
                $qty = (int) $qtyNumber;
            }
        }

        // Unit cost / total amount
        $unitCost = null;
        if ($this->isEmpty($v['unit_cost'])) {
            $errors[] = 'Unit Cost is empty.';
        } else {
            $unitCost = $this->number($v['unit_cost']);
            if ($unitCost === null || $unitCost <= 0) {
                $errors[] = 'Unit Cost "' . $this->text($v['unit_cost']) . '" is not a valid amount.';
                $unitCost = null;
            }
        }

        if ($unitCost !== null && $qty !== null && ! $this->isEmpty($v['total_amount'] ?? null)) {
            $total = $this->number($v['total_amount']);
            $expected = round($unitCost * $qty, 2);
            if ($total === null) {
                $warnings[] = 'Total Amount "' . $this->text($v['total_amount']) . '" is not a number; used Unit Cost × Qty (' . $this->money($expected) . ').';
            } elseif (abs($total - $expected) > 0.01) {
                $warnings[] = 'Total Amount ' . $this->money($total) . ' does not equal Unit Cost × Qty (' . $this->money($expected) . '); each item was saved with its Unit Cost.';
            }
        }

        // Description
        $description = $this->text($v['description']);
        if ($description === null) {
            $errors[] = 'Description is empty.';
        }

        // Property numbers
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

        // Classification
        $classificationId = $this->resolveClassification($v, $propertyNumbers['numbers'][0] ?? null, $errors);

        // Supplier
        $supplierId = null;
        $supplier = $this->singleLine($v['supplier']);
        if ($supplier === null) {
            $errors[] = 'Supplier is empty.';
        } else {
            $supplierId = $this->supplierIds[$this->supplierKey($supplier)] ?? null;
            if ($supplierId === null) {
                $errors[] = 'Supplier "' . $supplier . '" does not exist in Suppliers.';
            }
        }

        // Location
        $roomId = null;
        $location = $this->singleLine($v['location']);
        if ($location === null) {
            $errors[] = 'Location is empty.';
        } else {
            $roomId = $this->roomIds[$this->key($location)] ?? null;
            if ($roomId === null) {
                $errors[] = 'Location "' . $location . '" does not exist in the rooms API.';
            }
        }

        // Dates
        $dateAcquired = $this->date($row, 'date_acquired', $errors);
        if ($dateAcquired && $dateAcquired->isFuture()) {
            $warnings[] = 'Date Acquired ' . $dateAcquired->toDateString() . ' is in the future.';
        }

        // Accountable person and receipt
        $assignment = $this->resolveAssignment($row, $v, $unitCost, $errors, $warnings);

        $receiptRemarks = $this->text($v['receipt_remarks'] ?? null);
        if ($receiptRemarks !== null && mb_strlen($receiptRemarks) > self::MAX_LENGTHS['receipt_remarks']) {
            $errors[] = $this->label('receipt_remarks') . ' is longer than ' . self::MAX_LENGTHS['receipt_remarks'] . ' characters.';
        } elseif ($assignment) {
            $assignment['receipt_remarks'] = $receiptRemarks;
        } elseif ($receiptRemarks !== null) {
            $warnings[] = $this->label('receipt_remarks') . ' was not saved because the item has no receipt.';
        }

        // Short text fields
        $text = [];
        foreach (['unit', 'fund_source', 'pr_number', 'po_number', 'invoice', 'brand', 'model'] as $field) {
            $value = $this->singleLine($v[$field] ?? null);
            if ($value !== null && mb_strlen($value) > self::MAX_LENGTHS[$field]) {
                $errors[] = $this->label($field) . ' "' . Str::limit($value, 40) . '" is longer than ' . self::MAX_LENGTHS[$field] . ' characters.';
            }
            $text[$field] = $value;
        }
        if ($text['unit'] === null) {
            $warnings[] = 'Unit is empty.';
        }

        $extra = $this->resolveSampleColumns($row, $v, $qty, $errors);

        if ($errors) {
            return null;
        }

        $conditionId = $extra['condition_id'] ?? null;
        $isDisposal = in_array($conditionId, $this->disposalPathConditionIds, true)
            || ($conditionId === null && $this->isYellowRow($row));
        $serials = $extra['serials'] ?? $this->splitSerials($this->extractSerials($description), $qty, $warnings);
        $remarks = $this->text($v['remarks'] ?? null);
        $itemName = $extra['item_name'] ?? $this->itemName($description);

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
            'date_acquired'     => $dateAcquired?->toDateString(),
            'is_disposal'       => $isDisposal,
            'condition_id'      => $conditionId ?? ($isDisposal ? $this->disposedConditionId : $this->serviceableConditionId),
            'assignment'        => $assignment,
            'lifespan'          => $extra['lifespan'] ?? null,
            'sales_invoice_date'=> $extra['sales_invoice_date'] ?? null,
            'po_date'           => $extra['po_date'] ?? null,
            'approval_status'   => $extra['approval_status'] ?? null,
            'is_private'        => $extra['is_private'] ?? false,
        ] + $text;
    }

    /**
     * Optional columns from the sample layout: Item Name, Serial Number, Condition,
     * Lifespan, Sales Invoice Date, PO Date, Approval Status, Private.
     * Missing or empty cells fall back to the usual import rules.
     */
    protected function resolveSampleColumns(int $row, array $v, ?int $qty, array &$errors): array
    {
        $extra = [];

        $itemName = $this->singleLine($v['item_name'] ?? null);
        if ($itemName !== null) {
            if (mb_strlen($itemName) > self::MAX_LENGTHS['item_name']) {
                $errors[] = $this->label('item_name') . ' is longer than ' . self::MAX_LENGTHS['item_name'] . ' characters.';
            }
            $extra['item_name'] = $itemName;
        }

        $serial = $this->text($v['serial_number'] ?? null);
        if ($serial !== null && $qty !== null) {
            // One unit keeps the whole cell; several units need one serial each.
            $parts = $qty === 1
                ? [trim(preg_replace('/\s+/', ' ', $serial))]
                : array_values(array_filter(array_map('trim', preg_split('/[\n,;]+/', $serial))));

            if (count($parts) !== $qty) {
                $errors[] = $this->label('serial_number') . ' lists ' . count($parts) . ' serial number(s) but Quantity is ' . $qty . '. Separate them with commas, one per unit.';
            } elseif (collect($parts)->contains(fn ($part) => mb_strlen($part) > self::MAX_LENGTHS['serial_number'])) {
                $errors[] = $this->label('serial_number') . ' is longer than ' . self::MAX_LENGTHS['serial_number'] . ' characters.';
            } else {
                $extra['serials'] = $parts;
            }
        }

        $condition = $this->singleLine($v['condition'] ?? null);
        if ($condition !== null) {
            $extra['condition_id'] = $this->conditionIds[strtoupper($condition)] ?? null;
            if ($extra['condition_id'] === null) {
                $errors[] = $this->label('condition') . ' "' . $condition . '" does not exist. Use one of: ' . implode(', ', array_map('ucfirst', array_map('strtolower', array_keys($this->conditionIds)))) . '.';
            }
        }

        $lifespan = $this->text($v['lifespan'] ?? null);
        if ($lifespan !== null) {
            $years = $this->number($lifespan);
            if ($years === null || floor($years) != $years || $years < 0 || $years > 100) {
                $errors[] = $this->label('lifespan') . ' "' . $lifespan . '" must be a whole number of years.';
            } else {
                $extra['lifespan'] = (int) $years;
            }
        }

        $extra['sales_invoice_date'] = $this->date($row, 'sales_invoice_date', $errors)?->toDateString();
        $extra['po_date'] = $this->date($row, 'po_date', $errors)?->toDateString();

        $status = $this->singleLine($v['approval_status'] ?? null);
        if ($status !== null) {
            if (! in_array(strtolower($status), self::APPROVAL_STATUSES, true)) {
                $errors[] = $this->label('approval_status') . ' "' . $status . '" must be Pending, Approved, or Rejected.';
            } else {
                $extra['approval_status'] = strtolower($status);
            }
        }

        $private = $this->singleLine($v['private'] ?? null);
        if ($private !== null) {
            $flag = match (strtolower($private)) {
                'yes', 'y', 'true', '1' => true,
                'no', 'n', 'false', '0' => false,
                default => null,
            };
            if ($flag === null) {
                $errors[] = $this->label('private') . ' "' . $private . '" must be Yes or No.';
            } else {
                $extra['is_private'] = $flag;
            }
        }

        return $extra;
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

    /**
     * The classification code is the first number of the Property Number:
     * 221-905-001 => 221, which must exist in item_classifications.
     */
    protected function resolveClassification(array $v, ?string $propertyNumber, array &$errors): ?int
    {
        if (! $this->isParLayout) {
            $code = $propertyNumber !== null && preg_match('/^(\d+)-/', $propertyNumber, $m) ? $m[1] : null;

            if ($code === null) {
                $errors[] = $this->label('property_no') . ' "' . $propertyNumber . '" must start with a classification code, for example 221-905-001.';
                return null;
            }

            if (! isset($this->classificationIds[$code])) {
                $errors[] = 'Classification code ' . $code . ' (from ' . $this->label('property_no') . ' ' . $propertyNumber . ') does not exist in Classifications.';
                return null;
            }

            return $this->classificationIds[$code];
        }

        // PAR workbook: New Property No. starts with the year (2022-70-10-...), so also
        // look at the PAR Number and the old Property No.
        $candidates = [];
        foreach ([$this->singleLine($v['par_number']), $this->singleLine($v['property_no'] ?? null), $propertyNumber, $this->singleLine($v['new_property_no'] ?? null)] as $value) {
            if ($value !== null && preg_match('/^(\d+)/', $value, $m)) {
                $candidates[] = $m[1];
            }
        }

        foreach ($candidates as $code) {
            if (isset($this->classificationIds[$code])) {
                return $this->classificationIds[$code];
            }
        }

        $tried = $candidates ? ' (tried ' . implode(', ', array_unique($candidates)) . ')' : '';
        $errors[] = 'No item classification matches the first number of the PAR Number or Property No.' . $tried . '.';

        return null;
    }

    /**
     * Uses New Accountable, then New Accountable Person, then Accountable Person.
     * The PAR Number becomes the receipt; the PAR Date its date.
     */
    protected function resolveAssignment(int $row, array $v, ?float $unitCost, array &$errors, array &$warnings): ?array
    {
        $chosenField = null;
        foreach (['new_accountable', 'new_accountable_person', 'accountable_person'] as $field) {
            if ($this->text($v[$field] ?? null) !== null) {
                $chosenField = $field;
                break;
            }
        }

        $parNumber = $this->singleLine($v['par_number']);

        if ($chosenField === null) {
            $warnings[] = 'No accountable person, so the item was imported without an assignment.';
            return null;
        }

        // Names often carry the hand-over date: "SHERYL G. ALFORQUE 04-20-2026".
        $name = trim(preg_replace('/\b\d{1,4}[-\/.]\d{1,2}[-\/.]\d{1,4}\b/', '', $this->firstLine($v[$chosenField])));
        $matches = $this->matchProfiles($name);
        $label = $this->label($chosenField);
        $profileId = null;

        if (count($matches) === 0) {
            $errors[] = $label . ' "' . $name . '" does not match any user profile.';
        } elseif (count($matches) > 1) {
            $errors[] = $label . ' "' . $name . '" matches more than one user profile (' . implode(', ', array_column($matches, 'name')) . '). Write the full name.';
        } else {
            $profileId = $matches[0]['id'];
        }

        if ($parNumber === null) {
            $warnings[] = $this->label('par_number') . ' is empty, so the item was imported without an assignment to ' . $name . '.';
            return null;
        }

        if (mb_strlen($parNumber) > self::MAX_LENGTHS['par_number']) {
            $errors[] = $this->label('par_number') . ' is longer than ' . self::MAX_LENGTHS['par_number'] . ' characters.';
        }

        $parDate = $this->date($row, 'par_date', $errors);
        if ($parDate === null && ! $this->isEmpty($v['par_date'] ?? null)) {
            return null; // the date error was already recorded
        }
        if ($parDate === null) {
            $errors[] = $this->label('par_date') . ' is empty for ' . $this->label('par_number') . ' ' . $parNumber . '.';
            return null;
        }

        // Only the PAR workbook is meant to hold ₱50,000-and-up items.
        if ($this->isParLayout && $unitCost !== null && $unitCost < 50000) {
            $warnings[] = 'Unit Cost ' . $this->money($unitCost) . ' is below ₱50,000; PAR is for items ₱50,000 and above (this may belong on an ICS).';
        }

        $previousHolder = $chosenField !== 'accountable_person'
            && ($this->text($v['accountable_person'] ?? null) !== null || $chosenField === 'new_accountable' && $this->text($v['new_accountable_person'] ?? null) !== null);

        return [
            'profile_id'   => $profileId,
            'par_number'   => $parNumber,
            'par_date'     => $parDate->toDateString(),
            'reassignment' => $previousHolder,
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

        if ($assignment) {
            [$receipt, $receiptCreated] = $this->findOrCreateReceipt($assignment, $warnings);
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
                'total_amount'           => $plan['unit_cost'],
                'property_number'        => $item['property_number'],
                'serial_number'          => $item['serial_number'],
                'pr_number'              => $plan['pr_number'] ?? '',
                'po_number'              => $plan['po_number'] ?? '',
                'remarks'                => $item['remarks'],
                'date_acquired'          => $plan['date_acquired'],
                'brand'                  => $plan['brand'] ?? null,
                'model'                  => $plan['model'] ?? null,
                'lifespan'               => $plan['lifespan'],
                'sales_invoice_date'     => $plan['sales_invoice_date'],
                'po_number_date'         => $plan['po_date'],
                'approval_status'        => $plan['approval_status'] ?? ($receipt ? 'approved' : 'pending'),
                'is_private'             => $plan['is_private'] ? 1 : 0,
            ]);

            AssetInspection::create([
                'inventory_item_id'  => $inventoryItem->id,
                'asset_condition_id' => $plan['condition_id'],
                'inspected_by'       => auth()->id(),
                'inspection_date'    => now(),
                'remarks'            => 'Initial condition recorded on import (row ' . $plan['row'] . ').',
            ]);

            ItemHistoryLocation::create([
                'inventory_item_id' => $inventoryItem->id,
                'room_id'           => $plan['room_id'],
            ]);

            if ($receipt) {
                $acknowledgementItem = AcknowledgementItem::create([
                    'acknowledgement_id'    => $receipt->id,
                    'inventory_item_id'     => $inventoryItem->id,
                    'accountable_person_id' => $assignment['profile_id'],
                    'issued_by_id'          => self::ISSUED_BY_ID,
                    'status'                => 1,
                ]);

                if ($assignment['reassignment'] && $this->reassignmentTypeId) {
                    $acknowledgementItem->transferTypes()->sync([$this->reassignmentTypeId]);
                }
            }
        }

        return [
            'receipt_created' => $receiptCreated,
            'warnings'        => $warnings,
        ];
    }

    /**
     * One receipt per PAR Number, reusing a receipt that already exists. The
     * file is the source of truth for that receipt's remarks: a differing value
     * updates the receipt and says so, instead of being dropped silently.
     */
    protected function findOrCreateReceipt(array $assignment, array &$warnings = []): array
    {
        $key = $this->key($assignment['par_number']);
        $receiptRemarks = $assignment['receipt_remarks'] ?? null;

        if (isset($this->receipts[$key])) {
            $receipt = $this->receipts[$key];
            $this->applyReceiptRemarks($receipt, $receiptRemarks, $warnings);

            return [$receipt, false];
        }

        $receipt = AcknowledgementReceipt::where('category', $assignment['par_number'])->first();
        $created = false;

        if (! $receipt) {
            $receipt = AcknowledgementReceipt::create([
                'category'     => $assignment['par_number'],
                'par_date'     => $assignment['par_date'],
                'issued_by_id' => self::ISSUED_BY_ID,
                'created_by'   => auth()->id(),
                'remarks'      => $receiptRemarks,
            ]);
            $created = true;
        } else {
            $this->applyReceiptRemarks($receipt, $receiptRemarks, $warnings);
        }

        // Only remember it once the surrounding transaction commits.
        DB::afterCommit(fn () => $this->receipts[$key] = $receipt);

        return [$receipt, $created];
    }

    /**
     * Keeps an existing receipt in step with the file. Only a value actually
     * written in the sheet can change it; an empty cell leaves it alone.
     */
    protected function applyReceiptRemarks(
        AcknowledgementReceipt $receipt,
        ?string $remarks,
        array &$warnings,
    ): void {
        if (blank($remarks) || trim((string) $receipt->remarks) === trim($remarks)) {
            return;
        }

        $receipt->update(['remarks' => $remarks]);

        $warnings[] = 'Receipt Remarks for ' . $receipt->category
            . ' were updated from this file because they differed from the receipt on record.';
    }

    /* ------------------------------------------------------------------
     | Accountable person matching
     * ------------------------------------------------------------------ */

    /**
     * Matches names written as "M. PEDRANO", "JK. CUIZON", "SHERYL G. ALFORQUE",
     * or "JONATHAN VICTOLERO" against user profiles.
     */
    protected function matchProfiles(string $name): array
    {
        $key = $this->nameKey(str_replace('.', ' ', $name));

        if (array_key_exists($key, $this->profileMatches)) {
            return $this->profileMatches[$key];
        }

        $tokens = $key === '' ? [] : explode(' ', $key);
        $matches = [];

        foreach ($this->profiles as $profile) {
            if ($profile['last'] === '' || $profile['first'] === '') {
                continue;
            }

            $lastTokens = explode(' ', $profile['last']);
            if (count($tokens) <= count($lastTokens) || array_slice($tokens, -count($lastTokens)) !== $lastTokens) {
                continue;
            }

            $given = array_slice($tokens, 0, -count($lastTokens));

            // Drop a trailing middle initial ("SHERYL G") or middle name.
            while (count($given) > 1 && strlen(end($given)) === 1) {
                if ($profile['middle'] !== '' && end($given) !== $profile['middle'][0]) {
                    break;
                }
                array_pop($given);
            }
            if ($profile['middle'] !== '' && count($given) > 1 && str_ends_with(implode(' ', $given), ' ' . $profile['middle'])) {
                $given = explode(' ', trim(substr(implode(' ', $given), 0, -strlen($profile['middle']))));
            }

            if ($this->givenNameMatches(implode(' ', $given), $profile['first'])) {
                $matches[] = $profile;
            }
        }

        return $this->profileMatches[$key] = $matches;
    }

    protected function givenNameMatches(string $given, string $first): bool
    {
        if ($given === '') {
            return false;
        }

        $firstWords = explode(' ', $first);
        $initials = implode('', array_map(fn ($word) => $word[0], $firstWords));

        return $given === $first
            || $given === $firstWords[0]
            || (strlen($given) === 1 && $given === $first[0])
            || (strlen($given) <= 3 && ! str_contains($given, ' ') && $given === $initials);
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

    protected function itemName(string $description): string
    {
        $line = $this->firstLine($description);
        if (preg_match(self::SERIAL_MARKER, $line, $m, PREG_OFFSET_CAPTURE)) {
            $line = trim(substr($line, 0, $m[0][1])) ?: $line;
        }

        return Str::limit($line, 250);
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

    protected function isYellowRow(int $row): bool
    {
        if (! $this->readsColours) {
            return false;
        }

        $lastColumn = max($this->columns);
        $yellow = 0;

        for ($col = 1; $col <= $lastColumn; $col++) {
            $fill = $this->sheet->getStyle(Coordinate::stringFromColumnIndex($col) . $row)->getFill();
            if ($fill->getFillType() === Fill::FILL_SOLID && strtoupper($fill->getStartColor()->getRGB()) === 'FFFF00') {
                $yellow++;
            }
        }

        // Single highlighted cells are common; only a mostly yellow row means Disposal.
        return $yellow * 2 > $lastColumn;
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
        foreach (['new_property_no', 'property_no', 'description', 'qty', 'unit_cost'] as $field) {
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

    /** The column's header as written in the file, e.g. "Receipt No." or "PAR Number". */
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

    protected function supplierKey(?string $value): string
    {
        return $this->key(str_replace(['.', ','], '', Str::ascii((string) $value)));
    }

    protected function nameKey(?string $value): string
    {
        $value = strtoupper(Str::ascii((string) $value));

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Z ]/', ' ', $value)));
    }
}
