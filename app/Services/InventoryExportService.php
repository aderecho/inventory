<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Services\Concerns\WritesCsvExport;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Builds the inventory workbook (.xlsx): the fixed sample used as the Batch Add
 * template, and the full export using those same columns.
 */
class InventoryExportService
{
    use WritesCsvExport;

    /**
     * Column header => [width, type]. Types: text, money, int, date, wrap.
     */
    public const COLUMNS = [
        'PAR/ICS Number'    => [22, 'text'],
        'PAR/ICS Date'      => [16, 'date'],
        'New Property No.'  => [20, 'text'],
        'Property No.'      => [20, 'text'],
        'Qty'               => [10, 'int'],
        'Unit'              => [14, 'text'],
        'Unit Cost'         => [15, 'money'],
        'Total Amount'      => [15, 'money'],
        'Description'       => [46, 'wrap'],
        'Date Acquired'     => [16, 'date'],
        'Accountable Person' => [18, 'int'],
        'Fund Source'       => [14, 'text'],
        'PR#'               => [17, 'text'],
        'PO#'               => [17, 'text'],
        'Location'          => [12, 'int'],
        'Supplier'          => [12, 'int'],
        'Invoice'           => [15, 'text'],
        'Remarks'           => [30, 'wrap'],
        'Issued By'         => [12, 'int'],
    ];

    /**
     * Fixed, made-up rows for the sample workbook (docs/Inventory_Export_Sample.xlsx).
     * The ID columns are examples and need to be replaced with ids from the database.
     */
    public const SAMPLE_ROWS = [
        [
            '221-2024-03-512', '2024-03-11', '221-905-001', null, 1, 'unit', 68500, 68500,
            "2.0 HP wall-mounted split-type inverter aircon with remote\nIndoor: CW-SPL20X-IN / Outdoor: CW-SPL20X-OUT",
            '2024-03-08', 1, '164', 'PR-2024-02-118', 'PO-2024-02-071', 1, 1, 'SI-20417',
            'Installed beside the conference room door', 1,
        ],
        [
            '223-2025-06-533', '2025-06-17', '223-918-001', null, 1, 'unit', 54990, 54990,
            'Laptop, Intel Core i5, 16GB RAM, 512GB SSD, 14-inch FHD display, Windows 11 Pro',
            '2025-06-16', 2, '101', 'PR-2025-05-044', 'PO-2025-05-019', 2, 2, 'SI-88203',
            'For field data collection', 2,
        ],
        [
            '222-2019-09-488', '2019-09-30', '222-931-001', null, 1, 'unit', 52300, 52300,
            'Heavy-duty steel filing cabinet, 4 lockable drawers, legal size, gray finish',
            '2019-09-27', 3, '164', 'PR-2019-09-210', 'PO-2019-09-088', 3, 3, 'SI-50981',
            'Rusted drawer rails; recommended for disposal', 3,
        ],
    ];

    public function sampleToFile(): string
    {
        return $this->writeToFile(self::SAMPLE_ROWS, 'Inventory Items');
    }

    /**
     * Every item in the database, written in the sample's column order so the
     * file can be edited and fed straight back into Batch Add.
     */
    public function exportToFile(string $format = 'xlsx'): string
    {
        $rows = InventoryItem::with([
            'latestHistoryLocation',
            'latestAcknowledgementItem.acknowledgementReceipts',
        ])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (InventoryItem $item) => $this->rowFor($item));

        if ($format === 'csv') {
            $types = array_map(
                fn ($column) => $column[1],
                array_values(self::COLUMNS),
            );

            return $this->writeCsvFile(
                $rows,
                self::COLUMNS,
                fn ($value, $index) => ($types[$index] ?? null) === 'date'
                    ? Carbon::parse($value)->format('m-d-Y')
                    : (string) $value,
            );
        }

        return $this->writeToFile($rows, 'Inventory Items');
    }

    /**
     * One row per item. The order must match self::COLUMNS.
     */
    public function rowFor(InventoryItem $item): array
    {
        $acknowledgement = $item->latestAcknowledgementItem;
        $receipt = $acknowledgement?->acknowledgementReceipts;

        return [
            $receipt?->category,
            $receipt?->par_date,
            $item->property_number,
            $item->old_property_number,
            $item->quantity,
            $item->unit,
            $item->unit_cost,
            $item->total_amount,
            $item->description,
            $item->date_acquired,
            $acknowledgement?->accountable_person_id,
            $item->fund_source,
            $item->pr_number,
            $item->po_number,
            $item->latestHistoryLocation?->room_id,
            $item->supplier_id,
            $item->invoice,
            $item->remarks,
            $acknowledgement?->issued_by_id ?? $receipt?->issued_by_id,
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
            foreach (array_values(self::COLUMNS) as $index => [$width, $type]) {
                $value = $row[$index] ?? null;
                $cell = Coordinate::stringFromColumnIndex($index + 1) . $rowNumber;

                if ($value === null || $value === '') {
                    continue;
                }

                if ($type === 'date') {
                    $sheet->setCellValue($cell, ExcelDate::PHPToExcel(Carbon::parse($value)->startOfDay()));
                } elseif ($type === 'money' || $type === 'int') {
                    $sheet->setCellValue($cell, is_numeric($value) ? $value + 0 : $value);
                } else {
                    $sheet->setCellValueExplicit($cell, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
        }

        $this->formatSheet($sheet, $lastColumn, max($rowNumber, 2));

        $path = tempnam(sys_get_temp_dir(), 'inventory_export_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    protected function formatSheet($sheet, string $lastColumn, int $lastRow): void
    {
        $header = $sheet->getStyle("A1:{$lastColumn}1");
        $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('005740');
        $header->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(48);

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
        $sheet->getStyle("A2:{$lastColumn}{$lastRow}")
            ->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        foreach (array_values(self::COLUMNS) as $index => [$width, $type]) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->getColumnDimension($column)->setWidth($width);
            $range = "{$column}2:{$column}{$lastRow}";

            match ($type) {
                'money' => $sheet->getStyle($range)->getNumberFormat()->setFormatCode('"₱"#,##0.00'),
                'int'   => $sheet->getStyle($range)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER),
                'date'  => $sheet->getStyle($range)->getNumberFormat()->setFormatCode('mm-dd-yyyy'),
                'wrap'  => $sheet->getStyle($range)->getAlignment()->setWrapText(true),
                default => null,
            };
        }

        $sheet->freezePane('B2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
    }
}
