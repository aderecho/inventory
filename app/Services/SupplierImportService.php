<?php

namespace App\Services;

use App\Exceptions\InventoryImportException;
use App\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Imports suppliers from the sample workbook layout (Supplier Name, Contact No.,
 * Email, Address, Status). Every row is checked on its own; a row with a problem
 * is skipped and reported against its Excel row number.
 */
class SupplierImportService
{
    /** Header text (lowercased, letters and digits only) => field. */
    protected const HEADER_ALIASES = [
        'suppliername'  => 'supplier_name',
        'supplier'      => 'supplier_name',
        'name'          => 'supplier_name',
        'contactno'     => 'contact_no',
        'contactnumber' => 'contact_no',
        'contact'       => 'contact_no',
        'phonenumber'   => 'contact_no',
        'email'         => 'email',
        'emailaddress'  => 'email',
        'address'       => 'address',
        'status'        => 'status',
        'active'        => 'status',
    ];

    protected const FIELD_LABELS = [
        'supplier_name' => 'Supplier Name',
        'contact_no'    => 'Contact No.',
        'email'         => 'Email',
        'address'       => 'Address',
        'status'        => 'Status',
    ];

    /** Database column limits (supplier_name has a unique index, so it counts trashed rows too). */
    protected const MAX_LENGTHS = [
        'supplier_name' => 180,
        'contact_no'    => 60,
        'email'         => 60,
        'address'       => 255,
    ];

    protected Worksheet $sheet;

    /** @var array<string, int> field => column index */
    protected array $columns = [];

    /** @var array<string, string> field => header text as written in the file */
    protected array $headerLabels = [];

    /** @var array<string, int> lower-case supplier name => supplier id */
    protected array $existingNames = [];

    /** @var array<string, int> lower-case email => supplier id */
    protected array $existingEmails = [];

    /** @var array<string, \App\Models\Supplier> lower-case name => archived supplier */
    protected array $archivedNames = [];

    /** @var array<string, \App\Models\Supplier> lower-case email => archived supplier */
    protected array $archivedEmails = [];

    /** @var array<string, int> lower-case name => Excel row already seen in this file */
    protected array $fileNames = [];

    /**
     * $dryRun reviews the file: the import runs in full and the writes are
     * rolled back afterwards, so the report shows exactly what the real
     * import would do without changing the database.
     */
    public function import(UploadedFile $file, bool $dryRun = false): array
    {
        if (! $dryRun) {
            return $this->runImport($file);
        }

        DB::beginTransaction();

        try {
            $result = $this->runImport($file);
        } finally {
            DB::rollBack();
        }

        $result['dry_run'] = true;

        return $result;
    }

    protected function runImport(UploadedFile $file): array
    {
        @set_time_limit(600);

        $this->sheet = $this->loadFirstSheet($file->getRealPath(), strtolower($file->getClientOriginalExtension()));
        $this->mapHeader();
        $this->loadLookups();

        $lastRow = $this->sheet->getHighestDataRow();

        $result = [
            'file'             => $file->getClientOriginalName(),
            'sheet'            => $this->sheet->getTitle(),
            'start_row'        => 2,
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

        for ($row = 2; $row <= $lastRow; $row++) {
            $values = $this->readRow($row);

            if ($this->isBlank($values)) {
                continue;
            }

            $errors = [];
            $warnings = [];
            $plan = $this->buildRowPlan($row, $values, $errors, $warnings);
            $label = $this->singleLine($values['supplier_name'] ?? null);

            if ($errors || $plan === null) {
                $result['failed'][] = ['row' => $row, 'reference' => $label, 'messages' => $errors];
                continue;
            }

            try {
                $restoreId = $plan['restore_id'] ?? null;
                unset($plan['restore_id']);

                if ($restoreId) {
                    // Bring the archived supplier back with the values from this row.
                    $archivedSupplier = Supplier::onlyTrashed()->findOrFail($restoreId);
                    $archivedSupplier->restore();
                    $archivedSupplier->update($plan);
                } else {
                    Supplier::create($plan);
                }
            } catch (Throwable $e) {
                Log::error('Supplier import row failed', ['row' => $row, 'error' => $e->getMessage()]);

                $result['failed'][] = [
                    'row'        => $row,
                    'reference'  => $label,
                    'messages'   => ['Could not be saved: ' . Str::limit($e->getMessage(), 300)],
                ];
                continue;
            }

            $result['imported_rows']++;
            $result['imported_items']++;

            if ($warnings) {
                $result['warnings'][] = ['row' => $row, 'reference' => $label, 'messages' => $warnings];
            }
        }

        // Anything before the first header row is not a supplier.
        if ($result['imported_rows'] === 0 && empty($result['failed'])) {
            $result['notices'][] = 'No supplier rows were found below the header row.';
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
                $normalized = preg_replace('/[^a-z0-9]/', '', strtolower(preg_replace('/\([^)]*\)/', '', $clean)));
                $field = self::HEADER_ALIASES[$normalized] ?? null;
            }

            if ($field === null || isset($this->columns[$field])) {
                continue;
            }

            $this->columns[$field] = $col;
            $this->headerLabels[$field] = trim(preg_replace('/\s+/', ' ', preg_replace('/\([^)]*\)|\?/', '', $raw))) ?: trim($raw);
        }

        if (! isset($this->columns['supplier_name'])) {
            throw new InventoryImportException(
                'The first row must have a "Supplier Name" column. Use the columns from Download Sample.'
            );
        }
    }

    protected function loadLookups(): void
    {
        foreach (Supplier::get(['id', 'supplier_name', 'email']) as $supplier) {
            $this->existingNames[$this->key($supplier->supplier_name)] = $supplier->id;

            if ($supplier->email) {
                $this->existingEmails[$this->key($supplier->email)] = $supplier->id;
            }
        }

        // A name that only exists in the archive is accepted: supplier_name is unique
        // across archived rows too, so that supplier is restored and updated instead
        // of being reported as a duplicate.
        foreach (Supplier::onlyTrashed()->get(['id', 'supplier_name', 'email']) as $supplier) {
            $this->archivedNames[$this->key($supplier->supplier_name)] = $supplier;

            if ($supplier->email) {
                $this->archivedEmails[$this->key($supplier->email)] = $supplier;
            }
        }
    }

    /* ------------------------------------------------------------------
     | Row validation
     | ------------------------------------------------------------------ */

    protected function buildRowPlan(int $row, array $v, array &$errors, array &$warnings): ?array
    {
        $plan = [];

        // Supplier Name
        $name = $this->singleLine($v['supplier_name'] ?? null);
        if ($name === null) {
            $errors[] = 'Supplier Name is empty.';
        } else {
            if (mb_strlen($name) > self::MAX_LENGTHS['supplier_name']) {
                $errors[] = 'Supplier Name is longer than ' . self::MAX_LENGTHS['supplier_name'] . ' characters.';
            }

            $key = $this->key($name);
            $archived = $this->archivedNames[$key] ?? null;

            if (isset($this->fileNames[$key]) && $this->fileNames[$key] !== $row) {
                $errors[] = 'Supplier "' . $name . '" is repeated (also on row ' . $this->fileNames[$key] . ').';
            } elseif (isset($this->existingNames[$key])) {
                $errors[] = 'Supplier "' . $name . '" already exists in Suppliers.';
            } else {
                $this->fileNames[$key] = $row;

                if ($archived) {
                    $plan['restore_id'] = $archived->id;
                    $warnings[] = 'The archived supplier "' . $name . '" was restored and updated with this row.';
                }
            }

            $plan['supplier_name'] = $name;
        }

        // Contact No.
        $contact = $this->singleLine($v['contact_no'] ?? null);
        if ($contact !== null && mb_strlen($contact) > self::MAX_LENGTHS['contact_no']) {
            $errors[] = 'Contact No. "' . Str::limit($contact, 30) . '" is longer than ' . self::MAX_LENGTHS['contact_no'] . ' characters.';
        } elseif ($contact === null) {
            $warnings[] = 'Contact No. is empty.';
        }
        $plan['contact_no'] = $contact;

        // Email
        $email = $this->singleLine($v['email'] ?? null);
        if ($email !== null) {
            if (mb_strlen($email) > self::MAX_LENGTHS['email']) {
                $errors[] = 'Email is longer than ' . self::MAX_LENGTHS['email'] . ' characters.';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email "' . $email . '" is not a valid email address.';
            } elseif (isset($this->existingEmails[$this->key($email)])) {
                $errors[] = 'Email "' . $email . '" already belongs to another supplier.';
            } elseif (
                isset($this->archivedEmails[$this->key($email)])
                && ($plan['restore_id'] ?? null) !== $this->archivedEmails[$this->key($email)]->id
            ) {
                $warnings[] = 'Email "' . $email . '" is also used by an archived supplier.';
            }
        } else {
            $warnings[] = 'Email is empty.';
        }
        $plan['email'] = $email;

        // Address
        $address = $this->singleLine($v['address'] ?? null);
        if ($address !== null && mb_strlen($address) > self::MAX_LENGTHS['address']) {
            $errors[] = 'Address is longer than ' . self::MAX_LENGTHS['address'] . ' characters.';
        } elseif ($address === null) {
            $warnings[] = 'Address is empty.';
        }
        $plan['address'] = $address;

        // Status (defaults to Active)
        $status = $this->singleLine($v['status'] ?? null);
        if ($status === null) {
            $plan['status'] = 1;
        } else {
            $plan['status'] = match (strtolower($status)) {
                'active', 'yes', 'y', 'true', '1' => 1,
                'inactive', 'no', 'n', 'false', '0' => 0,
                default => null,
            };

            if ($plan['status'] === null) {
                $errors[] = 'Status "' . $status . '" must be Active or Inactive.';
            }
        }

        if ($errors) {
            return null;
        }

        return $plan;
    }

    /* ------------------------------------------------------------------
     | Cell helpers
     | ------------------------------------------------------------------ */

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

    protected function isBlank(array $values): bool
    {
        foreach ($values as $value) {
            if ($this->text($value) !== null) {
                return false;
            }
        }

        return true;
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

    protected function key(?string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }
}
