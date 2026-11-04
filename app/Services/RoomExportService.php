<?php

namespace App\Services;

use App\Services\Concerns\WritesCsvExport;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Builds the rooms export workbook and CSV from the rooms API. */
class RoomExportService
{
    use WritesCsvExport;

    /** Column header => width. */
    public const COLUMNS = [
        'ID'            => 8,
        'Room Name'     => 30,
        'Description'   => 40,
        'Building Name' => 30,
    ];

    public function __construct(protected RoomApiService $roomApi)
    {
    }

    public function exportToFile(string $format = 'xlsx'): string
    {
        $rows = collect($this->roomApi->fetchRooms()['data'] ?? [])
            ->map(fn (array $room) => $this->rowFor($room));

        if ($format === 'csv') {
            return $this->writeCsvFile($rows, self::COLUMNS);
        }

        return $this->writeToFile($rows, 'Rooms');
    }

    /** One row per room. The order must match self::COLUMNS. */
    public function rowFor(array $room): array
    {
        return [
            $room['id'] ?? null,
            $room['room_name'] ?? null,
            $room['description'] ?? null,
            $room['building_name'] ?? null,
        ];
    }

    /** Builds the formatted workbook and saves it to a temporary file. */
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

        $path = tempnam(sys_get_temp_dir(), 'rooms_export_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
