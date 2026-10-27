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
        'Property Number'                                 => [20, 'text'],
        'Item Name'                                       => [30, 'wrap'],
        'Brand'                                           => [14, 'text'],
        'Model'                                           => [14, 'text'],
        'Item Description'                                => [46, 'wrap'],
        'Serial Number'                                   => [24, 'wrap'],
        'Supplier Name'                                   => [28, 'text'],
        'Quantity'                                        => [10, 'int'],
        'Unit of Measure (e.g. unit, pcs, lot)'           => [16, 'text'],
        'Unit Cost (₱)'                                   => [15, 'money'],
        'Total Cost (₱)'                                  => [15, 'money'],
        'Fund Source'                                     => [14, 'text'],
        'Sales Invoice No.'                               => [15, 'text'],
        'Sales Invoice Date (YYYY-MM-DD)'                 => [16, 'date'],
        'Purchase Request (PR) No.'                       => [17, 'text'],
        'Purchase Order (PO) No.'                         => [17, 'text'],
        'Purchase Order (PO) Date (YYYY-MM-DD)'           => [17, 'date'],
        'Date Acquired (YYYY-MM-DD)'                      => [16, 'date'],
        'Useful Life (Years)'                             => [12, 'int'],
        'Condition (Good / Disposal)'                     => [14, 'text'],
        'Room / Location'                                 => [18, 'text'],
        'Accountable Person (Full Name)'                  => [24, 'text'],
        'Receipt No. (PAR / ICS)'                         => [20, 'text'],
        'Receipt Date (YYYY-MM-DD)'                       => [16, 'date'],
        'Approval Status (Pending / Approved / Rejected)' => [20, 'text'],
        'Private Item? (Yes / No)'                        => [12, 'text'],
        'Item Remarks'                                    => [30, 'wrap'],
        'Receipt Remarks'                                 => [30, 'wrap'],
    ];

    /**
     * Fixed, made-up rows for the sample workbook (docs/Inventory_Export_Sample.xlsx).
     * Names, suppliers, and numbers are fictional; each row uses a different
     * classification (from the Property Number), approval status, and private flag.
     */
    public const SAMPLE_ROWS = [
        [
            '221-905-001', 'Split-type Air Conditioner 2.0 HP', 'Coolwave', 'CW-SPL20X', "2.0 HP wall-mounted split-type inverter aircon with remote\nIndoor: CW-SPL20X-IN / Outdoor: CW-SPL20X-OUT",
            'CWX4471A20 / CWX4471B20', 'Brightline Trading Corp.', 1, 'unit', 68500, 68500,
            '164', 'SI-20417', '2024-03-04', 'PR-2024-02-118', 'PO-2024-02-071', '2024-02-19', '2024-03-08', 10,
            'Good', 'ADM 201', 'Marisol T. Dacanay', '221-2024-03-512', '2024-03-11', 'Approved', 'No',
            'Installed beside the conference room door', 'Issued for the Admin conference room',
        ],
        [
            '223-918-001', 'Laptop Computer, 14-inch', 'Nexora', 'NX-14 Pro', 'Laptop, Intel Core i5, 16GB RAM, 512GB SSD, 14-inch FHD display, Windows 11 Pro',
            'NXP14K9T2271', 'Pinnacle Systems Supply', 1, 'unit', 54990, 54990,
            '101', 'SI-88203', '2025-06-12', 'PR-2025-05-044', 'PO-2025-05-019', '2025-05-26', '2025-06-16', 5,
            'Good', 'AS 134', 'Rodel V. Sanpedro', '223-2025-06-533', '2025-06-17', 'Pending', 'Yes',
            'For field data collection', 'Laptop for the research team; return when the project ends',
        ],
        [
            '222-931-001', 'Steel Filing Cabinet, 4 Drawers', 'Ironvale', 'IV-FC4', 'Heavy-duty steel filing cabinet, 4 lockable drawers, legal size, gray finish',
            null, 'Hilltop Office Furnishing', 1, 'unit', 52300, 52300,
            '164', 'SI-50981', '2019-09-23', 'PR-2019-09-210', 'PO-2019-09-088', '2019-09-09', '2019-09-27', 15,
            'Disposal', 'ADW 1', 'Jocelyn P. Abrenica', '222-2019-09-488', '2019-09-30', 'Rejected', 'No',
            'Rusted drawer rails; recommended for disposal', 'Receipt cancelled after inspection',
        ],
    ];

    /**
     * Writes the fixed sample rows to a temporary .xlsx file and returns its path.
     */
    public function __construct(protected RoomApiService $roomApi)
    {
    }

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
        $roomNames = collect($this->roomApi->fetchRooms()['data'] ?? [])
            ->mapWithKeys(fn ($room) => [(int) $room['id'] => $room['room_name'] ?? null]);

        $rows = InventoryItem::with([
            'supplier',
            'latestHistoryLocation',
            'latestInspection.assetCondition',
            'latestAcknowledgementItem.accountablePerson',
            'latestAcknowledgementItem.acknowledgementReceipts',
        ])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (InventoryItem $item) => $this->rowFor($item, $roomNames->all()));

        if ($format === 'csv') {
            $types = array_map(
                fn ($column) => $column[1],
                array_values(self::COLUMNS),
            );

            return $this->writeCsvFile(
                $rows,
                self::COLUMNS,
                fn ($value, $index) => ($types[$index] ?? null) === 'date'
                    ? Carbon::parse($value)->format('Y-m-d')
                    : (string) $value,
            );
        }

        return $this->writeToFile($rows, 'Inventory Items');
    }

    /**
     * One row per item. The order must match self::COLUMNS.
     */
    public function rowFor(InventoryItem $item, array $roomNames = []): array
    {
        $acknowledgement = $item->latestAcknowledgementItem;
        $receipt = $acknowledgement?->acknowledgementReceipts;
        $roomId = $item->latestHistoryLocation?->room_id;

        return [
            $item->property_number,
            $item->item_name,
            $item->brand,
            $item->model,
            $item->description,
            $item->serial_number,
            $item->supplier?->supplier_name,
            $item->quantity,
            $item->unit,
            $item->unit_cost,
            $item->total_amount,
            $item->fund_source,
            $item->invoice,
            $item->sales_invoice_date,
            $item->pr_number,
            $item->po_number,
            $item->po_number_date,
            $item->date_acquired,
            $item->lifespan,
            $item->latestInspection?->assetCondition?->condition_name,
            $roomId ? ($roomNames[(int) $roomId] ?? 'Room #' . $roomId) : null,
            $acknowledgement?->accountablePerson?->full_name,
            $receipt?->category,
            $receipt?->par_date,
            $item->approval_status ? ucfirst($item->approval_status) : null,
            $item->is_private ? 'Yes' : 'No',
            $item->remarks,
            $receipt?->remarks,
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
                'date'  => $sheet->getStyle($range)->getNumberFormat()->setFormatCode('yyyy-mm-dd'),
                'wrap'  => $sheet->getStyle($range)->getAlignment()->setWrapText(true),
                default => null,
            };
        }

        $sheet->freezePane('B2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
    }
}
