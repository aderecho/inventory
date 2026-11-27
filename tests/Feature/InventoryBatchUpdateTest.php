<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\ItemClassification;
use App\Services\InventoryBatchUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class InventoryBatchUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_update_changes_only_the_classification_of_a_matching_active_item(): void
    {
        $oldClassification = ItemClassification::create([
            'classification_code' => '40-10', 'classification_name' => 'Old', 'status' => 1,
        ]);
        $newClassification = ItemClassification::create([
            'classification_code' => '50-20', 'classification_name' => 'New', 'status' => 1,
        ]);
        $item = InventoryItem::factory()->create([
            'item_classification_id' => $oldClassification->id,
            'property_number' => '2017-50-20-0054-101',
            'item_name' => 'Existing item',
            'description' => 'Old description',
        ]);
        $unknownClassificationItem = InventoryItem::factory()->create([
            'item_classification_id' => $oldClassification->id,
            'property_number' => '2017-51-30-0055-101',
            'description' => 'Old description',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'batch_update_test_') . '.xlsx';
        $sheet = (new Spreadsheet())->getActiveSheet();
        $sheet->fromArray(['New Property No.', 'Description', 'Classification ID'], null, 'A1');
        $sheet->fromArray([$item->property_number, 'Updated description', null], null, 'A2');
        $sheet->fromArray([$unknownClassificationItem->property_number, 'Updated despite warning', null], null, 'A3');
        (new Xlsx($sheet->getParent()))->save($path);
        $file = new UploadedFile($path, 'batch-update.xlsx', null, null, true);

        try {
            $service = app(InventoryBatchUpdateService::class);
            $preview = $service->update($file, dryRun: true);
            $this->assertSame(2, $preview['imported_rows']);
            $this->assertCount(1, $preview['warnings']);
            $this->assertSame($oldClassification->id, $item->fresh()->item_classification_id);

            $result = $service->update($file);
            $this->assertSame(2, $result['imported_rows']);
            $this->assertSame($newClassification->id, $item->fresh()->item_classification_id);
            $this->assertSame('Existing item', $item->fresh()->item_name);
            $this->assertSame('Updated description', $item->fresh()->description);
            $this->assertSame($oldClassification->id, $unknownClassificationItem->fresh()->item_classification_id);
            $this->assertSame('Updated despite warning', $unknownClassificationItem->fresh()->description);
        } finally {
            @unlink($path);
        }
    }
}
