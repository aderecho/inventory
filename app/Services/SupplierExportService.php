<?php

namespace App\Services;

use App\Models\Supplier;
use App\Services\Concerns\WritesCsvExport;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Builds the supplier workbook (.xlsx): the fixed sample used as the Batch Add
 * template, and the full export using those same columns.
 */
class SupplierExportService
{
    use WritesCsvExport;

    /** Column header => width. */
    public const COLUMNS = [
        'Supplier Name'              => 34,
        'Contact No.'                => 18,
        'Email'                      => 30,
        'Address'                    => 40,
        'Status (Active / Inactive)' => 16,
    ];

    /** Fixed, made-up rows: the sample must never look like real supplier data. */
    public const SAMPLE_ROWS = [
        ['Sample Trading Corp.', '0917 000 0001', 'sample.trading@example.com', '123 Sample Street, Cebu City', 'Active'],
        ['Demo Enterprises Inc.', '0917 000 0002', 'demo.enterprises@example.com', '456 Demo Avenue, Mandaue City', 'Active'],
        ['Example Supplies Co.', '(032) 000 0003', null, '789 Example Road, Lapu-Lapu City', 'Inactive'],
    ];

    public function sampleToFile(): string
    {
        return $this->writeToFile(self::SAMPLE_ROWS, 'Suppliers');
    }

    /**
     * Every supplier in the database, written in the sample's column order so the
     * file can be edited and fed straight back into Batch Add.
     */
    public function exportToFile(string $format = 'xlsx'): string
    {
        $rows = Supplier::orderBy('supplier_name')
            ->get()
            ->map(fn (Supplier $supplier) => $this->rowFor($supplier));

        if ($format === 'csv') {
            return $this->writeCsvFile($rows, self::COLUMNS);
        }

        return $this->writeToFile($rows, 'Suppliers');
    }

    /**
     * One row per supplier. The order must match self::COLUMNS.
     */
    public function rowFor(Supplier $supplier): array
    {
        return [
            $supplier->supplier_name,
            $supplier->contact_no,
            $supplier->email,
            $supplier->address,
            $supplier->status ? 'Active' : 'Inactive',
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

        $path = tempnam(sys_get_temp_dir(), 'supplier_sample_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
