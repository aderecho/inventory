<?php

namespace Tests\Feature;

use App\Models\AssetCondition;
use App\Models\InventoryItem;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisposeItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_ticked_items_are_disposed_in_one_step(): void
    {
        $items = InventoryItem::factory()->count(2)->create();
        $this->disposedCondition();

        $this->actingAs(User::factory()->create())
            ->patch(route('items.dispose'), ['ids' => $items->pluck('id')->all()])
            ->assertRedirect();

        foreach ($items as $item) {
            $this->assertSame(InventoryItem::STATUS_DISPOSED, $item->fresh()->status);
        }

        // Disposed property leaves the inventory list it was ticked from.
        $this->assertSame(0, app(InventoryService::class)->filterAndPaginateInventory(perPage: 50)->total());
    }

    public function test_disposing_without_a_disposed_condition_is_refused(): void
    {
        $item = InventoryItem::factory()->create();

        AssetCondition::create([
            'condition_name' => 'Good',
            'disposition' => AssetCondition::DISPOSITION_SERVICEABLE,
        ]);

        $this->actingAs(User::factory()->create())
            ->patch(route('items.dispose'), ['ids' => [$item->id]])
            ->assertSessionHasErrors('ids');

        $this->assertSame(InventoryItem::STATUS_AVAILABLE, $item->fresh()->status);
    }

    public function test_an_item_on_loan_cannot_be_disposed(): void
    {
        $item = InventoryItem::factory()->create(['status' => InventoryItem::STATUS_BORROWED]);
        $this->disposedCondition();

        $this->actingAs(User::factory()->create())
            ->patch(route('items.dispose'), ['ids' => [$item->id]])
            ->assertSessionHasErrors('ids.0');

        $this->assertSame(InventoryItem::STATUS_BORROWED, $item->fresh()->status);
    }

    public function test_disposing_nothing_is_refused(): void
    {
        $this->disposedCondition();

        $this->actingAs(User::factory()->create())
            ->patch(route('items.dispose'), ['ids' => []])
            ->assertSessionHasErrors('ids');
    }

    private function disposedCondition(): AssetCondition
    {
        return AssetCondition::create([
            'condition_name' => 'Disposed',
            'disposition' => AssetCondition::DISPOSITION_DISPOSED,
        ]);
    }
}
