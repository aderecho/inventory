<?php

namespace App\Services;

use App\Exceptions\InventoryImportException;
use App\Models\ItemClassification;
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
 * Imports categories (item classifications) from the sample workbook layout
 * (Classification Code, Classification Name, Status). A row with a problem is
 * skipped and reported against its Excel row number.
 */
class CategoryImportService
{
    /** Header text (lowercased, letters and digits only) => field. */
    protected const HEADER_ALIASES = [
        'classificationcode' => 'classification_code',
        'code'               => 'classification_code',
        'classificationname' => 'classification_name',
        'name'               => 'classification_name',
        'status'             => 'status',
        'active'             => 'status',
    ];

    protected const FIELD_LABELS = [
        'classification_code' => 'Classification Code',
        'classification_name' => 'Classification Name',
        'status'              => 'Status',
    ];

    /** Database column limits. */
    protected const MAX_LENGTHS = [
        'classification_code' => 50,
        'classification_name' => 50,
    ];

    protected Worksheet $sheet;

    /** @var array<string, int> field => column index */
    protected array $columns = [];

    /** @var array<string, int> lower-case code => category id */
    protected array $existingCodes = [];

    /** @var array<string, ItemClassification> lower-case code => archived category */
    protected array $archivedCodes = [];

    /** @var array<string, int> lower-case code => Excel row already seen in this file */
    protected array $fileCodes = [];

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
            $label = $this->singleLine($values['classification_code'] ?? null);

            if ($errors || $plan === null) {
                $result['failed'][] = ['row' => $row, 'reference' => $label, 'messages' => $errors];
                continue;
            }

            try {
                $restoreId = $plan['restore_id'] ?? null;
                unset($plan['restore_id']);

                if ($restoreId) {
                    // Bring the archived category back with the values from this row.
                    $archived = ItemClassification::onlyTrashed()->findOrFail($restoreId);
                    $archived->restore();
                    $archived->update($plan);
                } else {
                    ItemClassification::create($plan);
                }
            } catch (Throwable $e) {
                Log::error('Category import row failed', ['row' => $row, 'error' => $e->getMessage()]);

                $result['failed'][] = [
                    'row'       => $row,
                    'reference' => $label,
                    'messages'  => ['Could not be saved: ' . Str::limit($e->getMessage(), 300)],
                ];
                continue;
            }

            $result['imported_rows']++;
            $result['imported_items']++;

            if ($warnings) {
                $result['warnings'][] = ['row' => $row, 'reference' => $label, 'messages' => $warnings];
            }
        }

        if ($result['imported_rows'] === 0 && empty($result['failed'])) {
            $result['notices'][] = 'No category rows were found below the header row.';
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
        }

        $missing = [];
        foreach (['classification_code', 'classification_name'] as $field) {
            if (! isset($this->columns[$field])) {
                $missing[] = self::FIELD_LABELS[$field];
            }
        }

        if ($missing) {
            throw new InventoryImportException(
                'The first row of the sheet is missing these column(s): ' . implode(', ', $missing)
                . '. Use the columns from Download Sample.'
            );
        }
    }

    protected function loadLookups(): void
    {
        foreach (ItemClassification::get(['id', 'classification_code']) as $category) {
            $this->existingCodes[$this->key($category->classification_code)] = $category->id;
        }

        // A code that only exists in the archive is accepted: that category is
        // restored and updated instead of being reported as a duplicate.
        foreach (ItemClassification::onlyTrashed()->get(['id', 'classification_code']) as $category) {
            $this->archivedCodes[$this->key($category->classification_code)] = $category;
        }
    }

    /* ------------------------------------------------------------------
     | Row validation
     | ------------------------------------------------------------------ */

    protected function buildRowPlan(int $row, array $v, array &$errors, array &$warnings): ?array
    {
        $plan = [];

        // Classification Code
        $code = $this->singleLine($v['classification_code'] ?? null);
        if ($code === null) {
            $errors[] = 'Classification Code is empty.';
        } else {
            if (mb_strlen($code) > self::MAX_LENGTHS['classification_code']) {
                $errors[] = 'Classification Code "' . $code . '" is longer than ' . self::MAX_LENGTHS['classification_code'] . ' characters.';
            }

            $key = $this->key($code);
            $archived = $this->archivedCodes[$key] ?? null;

            if (isset($this->fileCodes[$key]) && $this->fileCodes[$key] !== $row) {
                $errors[] = 'Classification Code "' . $code . '" is repeated (also on row ' . $this->fileCodes[$key] . ').';
            } elseif (isset($this->existingCodes[$key])) {
                $errors[] = 'Classification Code "' . $code . '" already exists in Categories.';
            } else {
                $this->fileCodes[$key] = $row;

                if ($archived) {
                    $plan['restore_id'] = $archived->id;
                    $warnings[] = 'The archived category "' . $code . '" was restored and updated with this row.';
                }
            }

            $plan['classification_code'] = $code;
        }

        // Classification Name
        $name = $this->singleLine($v['classification_name'] ?? null);
        if ($name === null) {
            $errors[] = 'Classification Name is empty.';
        } elseif (mb_strlen($name) > self::MAX_LENGTHS['classification_name']) {
            $errors[] = 'Classification Name "' . Str::limit($name, 40) . '" is longer than ' . self::MAX_LENGTHS['classification_name'] . ' characters.';
        }
        $plan['classification_name'] = $name;

        // Status (defaults to Active)
        $status = $this->singleLine($v['status'] ?? null);
        if ($status === null) {
            $plan['status'] = 1;
            $warnings[] = 'Status is empty; the category was added as Active.';
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
