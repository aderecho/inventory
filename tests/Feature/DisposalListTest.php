<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Services\DisposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisposalListTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_disposal_list_only_carries_disposed_items(): void
    {
        InventoryItem::factory()->create();
        InventoryItem::factory()->create(['status' => InventoryItem::STATUS_BORROWED]);
        $disposed = InventoryItem::factory()->create(['status' => InventoryItem::STATUS_DISPOSED]);

        $listed = app(DisposalService::class)->filterAndPaginateDisposal(perPage: 50);

        $this->assertSame([$disposed->id], $listed->pluck('id')->all());
    }

    public function test_the_disposal_list_keeps_working_with_its_filters(): void
    {
        InventoryItem::factory()->create(['status' => InventoryItem::STATUS_DISPOSED, 'item_name' => 'Old Printer']);
        InventoryItem::factory()->create(['status' => InventoryItem::STATUS_DISPOSED, 'item_name' => 'Old Laptop']);
        InventoryItem::factory()->create(['item_name' => 'Old Server']);

        $listed = app(DisposalService::class)->filterAndPaginateDisposal(search: 'Old Laptop', perPage: 50);

        $this->assertSame(1, $listed->total());
        $this->assertSame('Old Laptop', $listed->first()->item_name);
    }
}
