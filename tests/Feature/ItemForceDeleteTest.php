<?php

namespace Tests\Feature;

use App\Models\AcknowledgementItem;
use App\Models\AcknowledgementReceipt;
use App\Models\AssetCondition;
use App\Models\AssetInspection;
use App\Models\InventoryItem;
use App\Models\ItemHistoryLocation;
use App\Models\TransferType;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\ItemArchivingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ItemForceDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_deleting_an_item_removes_everything_recorded_against_it(): void
    {
        $item = InventoryItem::factory()->create();
        $receipt = AcknowledgementReceipt::factory()->create();
        $line = $this->acknowledge($item, $receipt);

        $line->transferTypes()->attach(TransferType::firstOrCreate(['name' => 'Reassignment'])->id);
        $this->inspect($item);
        ItemHistoryLocation::create(['inventory_item_id' => $item->id, 'room_id' => 1]);

        $item->delete();
        app(ItemArchivingService::class)->forceDelete($item->id);

        $this->assertNull(InventoryItem::withTrashed()->find($item->id));
        $this->assertSame(0, AcknowledgementItem::where('inventory_item_id', $item->id)->count());
        $this->assertSame(0, DB::table('acknowledgement_item_transfer_type')->where('acknowledgement_item_id', $line->id)->count());
        $this->assertNull(AcknowledgementReceipt::find($receipt->id));
        $this->assertSame(0, AssetInspection::where('inventory_item_id', $item->id)->count());
        $this->assertSame(0, ItemHistoryLocation::where('inventory_item_id', $item->id)->count());
    }

    public function test_a_receipt_that_still_lists_other_items_is_kept(): void
    {
        $receipt = AcknowledgementReceipt::factory()->create();
        $deleted = InventoryItem::factory()->create();
        $kept = InventoryItem::factory()->create();

        $this->acknowledge($deleted, $receipt);
        $keptLine = $this->acknowledge($kept, $receipt);
        $this->inspect($kept);

        $deleted->delete();
        app(ItemArchivingService::class)->forceDelete($deleted->id);

        $this->assertNotNull(AcknowledgementReceipt::find($receipt->id));
        $this->assertNotNull(AcknowledgementItem::find($keptLine->id));
        $this->assertSame(1, AssetInspection::where('inventory_item_id', $kept->id)->count());
    }

    public function test_only_archived_items_can_be_force_deleted(): void
    {
        $item = InventoryItem::factory()->create();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        app(ItemArchivingService::class)->forceDelete($item->id);
    }

    private function acknowledge(InventoryItem $item, AcknowledgementReceipt $receipt): AcknowledgementItem
    {
        $profile = UserProfile::factory()->create(['user_id' => User::factory()]);

        return AcknowledgementItem::factory()->create([
            'acknowledgement_id' => $receipt->id,
            'inventory_item_id' => $item->id,
            'accountable_person_id' => $profile->id,
            'issued_by_id' => $profile->id,
        ]);
    }

    private function inspect(InventoryItem $item): void
    {
        $condition = AssetCondition::firstOrCreate(
            ['condition_name' => 'Good'],
            ['disposition' => AssetCondition::DISPOSITION_SERVICEABLE],
        );

        AssetInspection::create([
            'inventory_item_id' => $item->id,
            'asset_condition_id' => $condition->id,
            'inspection_date' => '2026-10-10',
        ]);
    }
}
