<?php

namespace App\Services\Concerns;

/**
 * CSV side of the export services.
 *
 * Kept in one place so every module writes the same file: UTF-8 with a byte
 * order mark (Excel needs it to read accented names and the peso sign), one
 * row per record, and plain values instead of spreadsheet formulas.
 */
trait WritesCsvExport
{
    /**
     * @param  array<string, mixed>  $columns       header => anything (usually the width)
     * @param  callable|null         $formatValue   fn ($value, int $index): string
     */
    protected function writeCsvFile(
        iterable $rows,
        array $columns,
        ?callable $formatValue = null
    ): string {
        $path = tempnam(sys_get_temp_dir(), 'csv_export_') . '.csv';

        $handle = fopen($path, 'w');

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, array_keys($columns));

        foreach ($rows as $row) {
            $line = [];

            foreach (array_keys($columns) as $index => $header) {
                $value = $row[$index] ?? null;

                if ($value === null || $value === '') {
                    $line[] = '';
                    continue;
                }

                $line[] = $formatValue
                    ? $formatValue($value, $index)
                    : (string) $value;
            }

            fputcsv($handle, $line);
        }

        fclose($handle);

        return $path;
    }
}

