<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\ItemClassification;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_list_can_be_filtered_by_classification(): void
    {
        $selectedClassification = ItemClassification::create([
            'classification_code' => '221',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);
        $otherClassification = ItemClassification::create([
            'classification_code' => '222',
            'classification_name' => 'Furniture',
            'status' => 1,
        ]);

        $selectedItem = InventoryItem::factory()->create([
            'item_classification_id' => $selectedClassification->id,
        ]);
        InventoryItem::factory()->create([
            'item_classification_id' => $otherClassification->id,
        ]);

        $items = app(InventoryService::class)->filterAndPaginateInventory(
            perPage: 50,
            classificationId: $selectedClassification->id,
        );

        $this->assertSame([$selectedItem->id], $items->pluck('id')->all());
    }
}
