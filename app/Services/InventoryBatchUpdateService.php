<?php

namespace App\Services;

use App\Exceptions\InventoryImportException;
use App\Models\InventoryItem;
use App\Models\ItemClassification;
use App\Models\AcknowledgementItem;
use App\Models\AcknowledgementReceipt;
use App\Models\ItemHistoryLocation;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Str;
use Throwable;

class InventoryBatchUpdateService
{
    /** Same data columns as batch add, with a classification ID override. */
    private const HEADERS = [
        'PAR/ICS Number', 'PAR/ICS Date', 'New Property No.', 'Classification ID',
        'Old Property No.', 'Qty', 'Unit', 'Unit Cost', 'Total Amount', 'Description',
        'Serial No.', 'Date Acquired', 'Accountable Person', 'Fund Source', 'PR#', 'PO#',
        'Location', 'Supplier', 'Invoice', 'Remarks', 'Issued By',
    ];

    private const FIELD_COLUMNS = [
        'old_property_number' => ['oldpropertyno', 'oldpropertynumber'],
        'quantity' => ['qty', 'quantity'],
        'unit' => ['unit', 'unitofmeasure'],
        'unit_cost' => ['unitcost'],
        'total_amount' => ['totalamount', 'totalcost', 'total'],
        'description' => ['description', 'itemdescription'],
        'item_name' => ['itemname'],
        'brand' => ['brand'],
        'model' => ['model'],
        'serial_number' => ['serialno', 'serialnumber'],
        'date_acquired' => ['dateacquired'],
        'fund_source' => ['fundsource'],
        'pr_number' => ['pr', 'prno', 'prnumber'],
        'po_number' => ['po', 'pono', 'ponumber'],
        'supplier_id' => ['supplier', 'supplierid'],
        'invoice' => ['invoice', 'invoiceno', 'invoicenumber'],
        'remarks' => ['remarks', 'itemremarks'],
    ];

    public function sampleToFile(): string
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Batch Update');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count(self::HEADERS)) . '1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . Coordinate::stringFromColumnIndex(count(self::HEADERS)) . '1');
        $path = tempnam(sys_get_temp_dir(), 'inventory_batch_update_') . '.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    public function update(UploadedFile $file, bool $dryRun = false, ?string $sheetName = null): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $reader = in_array($extension, ['csv', 'txt'], true)
            ? new CsvReader()
            : IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $sheetName ? $spreadsheet->getSheetByName($sheetName) : $spreadsheet->getActiveSheet();
        if (! $sheet instanceof Worksheet) {
            throw new InventoryImportException('The selected sheet was not found in the file.');
        }

        $lastColumn = $sheet->getHighestDataColumn();
        $headerValues = $sheet->rangeToArray('A1:' . $lastColumn . '1')[0];
        $columns = [];
        foreach ($headerValues as $index => $header) {
            $key = $this->normalize((string) $header);
            if ($key !== '') $columns[$key] = $index;
        }

        $propertyColumn = $columns['newpropertyno'] ?? $columns['propertyno'] ?? $columns['propertynumber'] ?? null;
        if ($propertyColumn === null) {
            throw new InventoryImportException('The file must include the New Property No. column to identify each existing item.');
        }

        $classifications = ItemClassification::query()->get(['id', 'classification_code'])
            ->mapWithKeys(fn (ItemClassification $classification) => [trim((string) $classification->classification_code) => (int) $classification->id])
            ->all();
        $classificationIds = ItemClassification::pluck('id')->mapWithKeys(fn ($id) => [(string) $id => true])->all();
        $seen = [];
        $plans = [];
        $failed = [];
        $warnings = [];
        $roomIds = null;

        for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
            $values = $sheet->rangeToArray('A' . $row . ':' . $lastColumn . $row, null, true, false)[0];
            if (! array_filter($values, fn ($value) => $value !== null && trim((string) $value) !== '')) continue;

            $propertyNumber = trim((string) ($values[$propertyColumn] ?? ''));
            $errors = [];
            $rowWarnings = [];
            if ($propertyNumber === '') $errors[] = 'New Property No. is required to identify an existing item.';
            if (isset($seen[$propertyNumber])) $errors[] = 'Property number is duplicated in this file.';
            $seen[$propertyNumber] = true;
            $item = $propertyNumber !== '' ? InventoryItem::where('property_number', $propertyNumber)->first() : null;
            if ($propertyNumber !== '' && ! $item) $errors[] = 'No active inventory item has this property number.';

            $updates = [];
            foreach (self::FIELD_COLUMNS as $field => $aliases) {
                $value = $this->value($values, $columns, $aliases);
                if ($value === null || trim((string) $value) === '') continue;
                $updates[$field] = trim((string) $value);
            }

            $parNumber = $this->value($values, $columns, ['paricsnumber', 'parnumber', 'parno', 'paricsreceipt']);
            $parDate = $this->value($values, $columns, ['paricsdate', 'pardate']);
            $accountableRaw = $this->value($values, $columns, ['accountableperson', 'accountablepersonid']);
            $issuedByRaw = $this->value($values, $columns, ['issuedby', 'issuedbyid']);
            $locationRaw = $this->value($values, $columns, ['location', 'roomlocation', 'room']);
            $assignment = [];
            if ($parNumber !== null && trim((string) $parNumber) !== '') $assignment['par_number'] = trim((string) $parNumber);
            if (! empty($assignment['par_number']) && mb_strlen($assignment['par_number']) > 100) $errors[] = 'PAR/ICS Number cannot exceed 100 characters.';
            if ($parDate !== null && trim((string) $parDate) !== '') {
                try {
                    $assignment['par_date'] = $this->parseDate($parDate)->toDateString();
                } catch (\Throwable) {
                    $errors[] = 'PAR/ICS Date must be a valid date.';
                }
            }
            if ($accountableRaw !== null && trim((string) $accountableRaw) !== '') {
                $accountableRaw = trim((string) $accountableRaw);
                if (! ctype_digit($accountableRaw) || ! UserProfile::whereKey($accountableRaw)->exists()) $errors[] = 'Accountable Person must be a valid user profile ID.';
                else $assignment['accountable_person_id'] = (int) $accountableRaw;
            }
            if ($issuedByRaw !== null && trim((string) $issuedByRaw) !== '') {
                $issuedByRaw = trim((string) $issuedByRaw);
                if (! ctype_digit($issuedByRaw) || ! User::whereKey($issuedByRaw)->exists() || ! UserProfile::whereKey($issuedByRaw)->exists()) $errors[] = 'Issued By must be a valid user/profile ID.';
                else $assignment['issued_by_id'] = (int) $issuedByRaw;
            }
            if ($locationRaw !== null && trim((string) $locationRaw) !== '') {
                $locationRaw = trim((string) $locationRaw);
                if (! ctype_digit($locationRaw)) {
                    $errors[] = 'Location must be a numeric room ID.';
                } else {
                    if ($roomIds === null) {
                        $roomData = app(RoomApiService::class)->fetchRooms()['data'] ?? [];
                        $roomIds = collect($roomData)->pluck('id')->map(fn ($id) => (int) $id)->all();
                    }
                    if (! in_array((int) $locationRaw, $roomIds, true)) $errors[] = 'Location ID must match a room from the rooms API.';
                    else $assignment['room_id'] = (int) $locationRaw;
                }
            }
            if (! empty($assignment['accountable_person_id']) && empty($assignment['par_number'])) {
                $rowWarnings[] = 'Accountable Person was not assigned because PAR/ICS Number is empty.';
                unset($assignment['accountable_person_id']);
            } elseif (! empty($assignment['par_number']) && empty($assignment['accountable_person_id'])) {
                $rowWarnings[] = 'PAR/ICS Number was not linked because no accountable person ID was provided.';
                unset($assignment['par_number'], $assignment['par_date'], $assignment['issued_by_id']);
            }

            $classificationId = null;
            $classificationRaw = $this->value($values, $columns, ['classificationid', 'itemclassificationid']);
            if ($classificationRaw !== null && trim((string) $classificationRaw) !== '') {
                $classificationRaw = trim((string) $classificationRaw);
                if (! ctype_digit($classificationRaw) || ! isset($classificationIds[$classificationRaw])) {
                    $errors[] = 'Classification ID must match an existing classification.';
                } else {
                    $classificationId = (int) $classificationRaw;
                }
            } else {
                $parts = explode('-', $propertyNumber);
                if (isset($parts[1], $parts[2]) && ctype_digit($parts[1]) && ctype_digit($parts[2])) {
                    $classificationCode = $parts[1] . '-' . $parts[2];
                    $classificationId = $classifications[$classificationCode] ?? null;
                    if ($classificationId === null) {
                        $rowWarnings[] = 'Classification code ' . $classificationCode . ' from the property number does not exist; the item classification was left unchanged.';
                    }
                } elseif ($propertyNumber !== '') {
                    $rowWarnings[] = 'Could not read a classification code from the second and third property-number segments; the item classification was left unchanged.';
                }
            }
            if ($classificationId !== null) $updates['item_classification_id'] = $classificationId;

            foreach (['old_property_number' => 255, 'fund_source' => 255, 'invoice' => 50, 'unit' => 50, 'pr_number' => 255, 'po_number' => 255, 'serial_number' => 255] as $field => $max) {
                if (isset($updates[$field]) && mb_strlen($updates[$field]) > $max) $errors[] = ucfirst(str_replace('_', ' ', $field)) . " cannot exceed {$max} characters.";
            }
            if (isset($updates['quantity']) && (! ctype_digit($updates['quantity']) || (int) $updates['quantity'] < 1)) $errors[] = 'Qty must be a positive whole number.';
            foreach (['unit_cost', 'total_amount'] as $field) {
                if (isset($updates[$field]) && ! is_numeric(str_replace(',', '', $updates[$field]))) $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' must be numeric.';
                elseif (isset($updates[$field])) $updates[$field] = (float) str_replace(',', '', $updates[$field]);
            }
            if (isset($updates['supplier_id']) && (! ctype_digit($updates['supplier_id']) || ! DB::table('suppliers')->where('id', $updates['supplier_id'])->exists())) $errors[] = 'Supplier must be a valid supplier ID.';
            foreach (['date_acquired' => ['dateacquired']] as $field => $aliases) {
                $value = $this->value($values, $columns, $aliases);
                if ($value === null || trim((string) $value) === '') continue;
                try {
                    $updates[$field] = $this->parseDate($value)->toDateString();
                } catch (\Throwable) {
                    $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid date.';
                }
            }

            if ($errors) {
                $failed[] = ['row' => $row, 'property_number' => $propertyNumber, 'messages' => $errors];
                continue;
            }
            $plans[] = ['item' => $item, 'updates' => $updates, 'assignment' => $assignment, 'row' => $row, 'property_number' => $propertyNumber];
            if ($rowWarnings) $warnings[] = ['row' => $row, 'property_number' => $propertyNumber, 'messages' => $rowWarnings];
        }

        $successfulCount = count($plans);
        if (! $dryRun) {
            $successfulCount = 0;
            foreach ($plans as $plan) {
                try {
                    DB::transaction(fn () => $this->savePlan($plan));
                    $successfulCount++;
                } catch (Throwable $exception) {
                    $failed[] = [
                        'row' => $plan['row'],
                        'property_number' => $plan['property_number'],
                        'messages' => ['Could not be saved: ' . Str::limit($exception->getMessage(), 300)],
                    ];
                }
            }
        }

        return [
            'file' => $file->getClientOriginalName(), 'sheet' => $sheet->getTitle(),
            'start_row' => 2, 'end_row' => $sheet->getHighestDataRow(),
            'imported_rows' => $successfulCount, 'imported_items' => $successfulCount,
            'receipts_created' => 0, 'disposal_items' => 0,
            'failed' => $failed, 'warnings' => $warnings, 'skipped_rows' => [],
            'dry_run' => $dryRun,
        ];
    }

    public function sheetNames(UploadedFile $file): array
    {
        return app(InventoryImportService::class)->sheetNames($file);
    }

    private function savePlan(array $plan): void
    {
        $plan['item']->update($plan['updates']);
        $assignment = $plan['assignment'];
        if (! empty($assignment['room_id'])) {
            ItemHistoryLocation::create(['inventory_item_id' => $plan['item']->id, 'room_id' => $assignment['room_id']]);
        }
        if (empty($assignment['par_number']) || empty($assignment['accountable_person_id'])) return;

        $receipt = AcknowledgementReceipt::firstOrCreate(
            ['category' => $assignment['par_number']],
            ['par_date' => $assignment['par_date'] ?? null, 'issued_by_id' => $assignment['issued_by_id'] ?? InventoryImportService::ISSUED_BY_ID, 'created_by' => auth()->id()],
        );
        if (array_key_exists('par_date', $assignment)) $receipt->update(['par_date' => $assignment['par_date']]);
        $acknowledgementItem = $plan['item']->acknowledgementItems()->latest()->first();
        $acknowledgementItem ??= new AcknowledgementItem(['inventory_item_id' => $plan['item']->id]);
        $acknowledgementItem->fill([
            'acknowledgement_id' => $receipt->id,
            'accountable_person_id' => $assignment['accountable_person_id'],
            'issued_by_id' => $assignment['issued_by_id'] ?? $acknowledgementItem->issued_by_id ?? InventoryImportService::ISSUED_BY_ID,
            'status' => 1,
        ])->save();
    }

    private function value(array $values, array $columns, array $aliases): mixed
    {
        foreach ($aliases as $alias) {
            $key = $this->normalize($alias);
            if (array_key_exists($key, $columns)) return $values[$columns[$key]] ?? null;
        }
        return null;
    }

    private function normalize(string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', trim($value)));
    }

    private function parseDate(mixed $value): Carbon
    {
        if (is_numeric($value)) return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
        $value = trim((string) $value);
        foreach (['m-d-Y', 'm/d/Y', 'Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $value);
                if ($date && $date->format($format) === $value) return $date;
            } catch (\Throwable) {
            }
        }
        return Carbon::parse($value);
    }
}
