<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ImportReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ImportReportDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_report_can_be_downloaded_as_csv_or_xlsx(): void
    {
        $user = User::factory()->create();
        $report = "Import review: items.xlsx\nImported: 2 rows";

        $this->actingAs($user)
            ->post(route('import-report.download'), [
                'report' => $report,
                'format' => 'csv',
                'filename' => 'import review',
            ])
            ->assertOk()
            ->assertDownload('import-review.csv');

        $this->actingAs($user)
            ->post(route('import-report.download'), [
                'report' => $report,
                'format' => 'xlsx',
                'filename' => 'import review',
            ])
            ->assertOk()
            ->assertDownload('import-review.xlsx');
    }

    public function test_the_csv_and_xlsx_files_contain_the_report_lines(): void
    {
        $service = app(ImportReportExportService::class);
        $report = "Import review: items.xlsx\nImported: 2 rows";

        $csv = $service->export($report, 'csv');
        $this->assertStringContainsString('Imported: 2 rows', file_get_contents($csv));
        unlink($csv);

        $xlsx = $service->export($report, 'xlsx');
        $sheet = IOFactory::load($xlsx)->getActiveSheet();
        $this->assertSame('Import review: items.xlsx', $sheet->getCell('A2')->getValue());
        $this->assertSame('Imported: 2 rows', $sheet->getCell('A3')->getValue());
        unlink($xlsx);
    }
}
