<?php

namespace App\Services;

use App\Models\User;
use App\Services\Concerns\WritesCsvExport;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Builds the User Management export workbook and CSV. */
class UserExportService
{
    use WritesCsvExport;

    /** Column header => width. */
    public const COLUMNS = [
        'ID'          => 8,
        'Full Name'   => 30,
        'First Name'  => 22,
        'Middle Name' => 22,
        'Last Name'   => 22,
    ];

    public function exportToFile(string $format = 'xlsx'): string
    {
        $rows = User::with('userProfiles')
            ->orderBy('email')
            ->get()
            ->map(fn (User $user) => $this->rowFor($user));

        if ($format === 'csv') {
            return $this->writeCsvFile($rows, self::COLUMNS);
        }

        return $this->writeToFile($rows, 'Users');
    }

    /** One row per user. The order must match self::COLUMNS. */
    public function rowFor(User $user): array
    {
        $profile = $user->userProfiles;

        return [
            $user->id,
            $profile?->full_name,
            $profile?->first_name,
            $profile?->middle_name,
            $profile?->last_name,
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

        $path = tempnam(sys_get_temp_dir(), 'users_export_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
