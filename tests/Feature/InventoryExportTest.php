<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Services\InventoryExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class InventoryExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sample_workbook_has_the_requested_headers(): void
    {
        $path = app(InventoryExportService::class)->sampleToFile();
        $sheet = IOFactory::load($path)->getActiveSheet();
        $headers = $sheet->rangeToArray('A1:T1')[0];

        $this->assertSame(array_keys(InventoryExportService::COLUMNS), $headers);
        $this->assertSame('mm-dd-yyyy', $sheet->getStyle('B2')->getNumberFormat()->getFormatCode());
        $this->assertSame('mm-dd-yyyy', $sheet->getStyle('K2')->getNumberFormat()->getFormatCode());

        unlink($path);
    }

    public function test_the_export_row_includes_date_acquired(): void
    {
        $item = InventoryItem::factory()->create([
            'property_number' => 'NEW-001',
            'old_property_number' => 'OLD-001',
            'serial_number' => 'SERIAL-001',
            'date_acquired' => '2016-05-04',
        ]);

        $row = app(InventoryExportService::class)->rowFor($item->fresh());
        $data = array_combine(array_keys(InventoryExportService::COLUMNS), $row);

        $this->assertSame('NEW-001', $data['New Property No.']);
        $this->assertSame('OLD-001', $data['Old Property No.']);
        $this->assertSame('SERIAL-001', $data['Serial No.']);
        $this->assertSame('2016-05-04', $data['Date Acquired']);
    }
}
