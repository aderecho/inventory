<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Writes the import report as a CSV or a one-column XLSX worksheet. */
class ImportReportExportService
{
    public function export(string $report, string $format): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $report);

        return $format === 'csv'
            ? $this->toCsv($lines)
            : $this->toXlsx($lines);
    }

    /** @param array<int, string> $lines */
    private function toCsv(array $lines): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import_report_') . '.csv';
        $handle = fopen($path, 'w');

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Report']);

        foreach ($lines as $line) {
            fputcsv($handle, [$line]);
        }

        fclose($handle);

        return $path;
    }

    /** @param array<int, string> $lines */
    private function toXlsx(array $lines): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Report');
        $sheet->setCellValue('A1', 'Report');

        $header = $sheet->getStyle('A1');
        $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('005740');
        $header->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach ($lines as $index => $line) {
            $sheet->setCellValueExplicit(
                'A' . ($index + 2),
                $line,
                DataType::TYPE_STRING,
            );
        }

        $lastRow = max(count($lines) + 1, 2);
        $sheet->getStyle("A2:A{$lastRow}")
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getColumnDimension('A')->setWidth(120);
        $sheet->freezePane('A2');

        $path = tempnam(sys_get_temp_dir(), 'import_report_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
