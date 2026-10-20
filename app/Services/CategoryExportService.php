<?php

namespace App\Services;

use App\Models\ItemClassification;
use App\Services\Concerns\WritesCsvExport;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Builds the category workbook (.xlsx): the fixed sample used as the Batch Add
 * template, and the full export using those same columns.
 */
class CategoryExportService
{
    use WritesCsvExport;

    /** Column header => width. */
    public const COLUMNS = [
        'Classification Code'        => 20,
        'Classification Name'        => 34,
        'Status (Active / Inactive)' => 16,
    ];

    /** Fixed, made-up rows: the sample must never look like real category data. */
    public const SAMPLE_ROWS = [
        ['901', 'Sample Classification', 'Active'],
        ['902', 'Demo Classification', 'Active'],
        ['903', 'Example Classification', 'Inactive'],
    ];

    public function sampleToFile(): string
    {
        return $this->writeToFile(self::SAMPLE_ROWS, 'Categories');
    }

    /**
     * Every category in the database, written in the sample's column order so the
     * file can be edited and fed straight back into Batch Add.
     */
    public function exportToFile(string $format = 'xlsx'): string
    {
        $rows = ItemClassification::orderBy('classification_code')
            ->get()
            ->map(fn (ItemClassification $category) => $this->rowFor($category));

        if ($format === 'csv') {
            return $this->writeCsvFile($rows, self::COLUMNS);
        }

        return $this->writeToFile($rows, 'Categories');
    }

    /**
     * One row per category. The order must match self::COLUMNS.
     */
    public function rowFor(ItemClassification $category): array
    {
        return [
            $category->classification_code,
            $category->classification_name,
            $category->status ? 'Active' : 'Inactive',
        ];
    }

    /**
     * Builds the formatted workbook and saves it to a temporary file.
     */
    public function writeToFile(iterable $rows, string $sheetTitle): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetTitle);

        $headers = array_keys(self::COLUMNS);
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->fromArray($headers, null, 'A1');

        $rowNumber = 1;
        foreach ($rows as $row) {
            $rowNumber++;
            foreach (array_values(self::COLUMNS) as $index => $width) {
                $value = $row[$index] ?? null;

                if ($value === null || $value === '') {
                    continue;
                }

                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex($index + 1) . $rowNumber,
                    (string) $value,
                    DataType::TYPE_STRING
                );
            }
        }

        $header = $sheet->getStyle("A1:{$lastColumn}1");
        $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('005740');
        $header->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $sheet->getStyle("A1:{$lastColumn}{$rowNumber}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
        $sheet->getStyle("A2:{$lastColumn}{$rowNumber}")
            ->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

        foreach (array_values(self::COLUMNS) as $index => $width) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($width);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$rowNumber}");

        $path = tempnam(sys_get_temp_dir(), 'category_sample_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
