<?php

namespace Tests\Feature;

use App\Models\AcknowledgementItem;
use App\Models\AcknowledgementReceipt;
use App\Models\AssetCondition;
use App\Models\AssetInspection;
use App\Models\InventoryItem;
use App\Models\ItemHistoryLocation;
use App\Models\Supplier;
use App\Models\TransferType;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClearInventoryTestDataMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_empties_the_inventory_tables_and_keeps_everything_else(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);
        $item = InventoryItem::factory()->create();
        $receipt = AcknowledgementReceipt::factory()->create();
        $line = AcknowledgementItem::factory()->create([
            'acknowledgement_id' => $receipt->id,
            'inventory_item_id' => $item->id,
            'accountable_person_id' => $profile->id,
            'issued_by_id' => $profile->id,
        ]);
        $line->transferTypes()->attach(TransferType::firstOrCreate(['name' => 'Reassignment'])->id);
        $condition = AssetCondition::create(['condition_name' => 'Good', 'disposition' => AssetCondition::DISPOSITION_SERVICEABLE]);
        AssetInspection::create(['inventory_item_id' => $item->id, 'asset_condition_id' => $condition->id, 'inspection_date' => '2026-10-10']);
        ItemHistoryLocation::create(['inventory_item_id' => $item->id, 'room_id' => 1]);

        $suppliers = Supplier::count();

        (require database_path('migrations/2026_10_29_000200_clear_inventory_test_data.php'))->up();

        foreach ([
            'acknowledgement_items',
            'acknowledgement_item_transfer_type',
            'acknowledgement_receipts',
            'asset_inspections',
            'inventory_items',
            'item_history_location',
        ] as $table) {
            $this->assertSame(0, DB::table($table)->count(), $table . ' should be empty');
        }

        $this->assertNotNull(User::find($user->id));
        $this->assertNotNull(UserProfile::find($profile->id));
        $this->assertNotNull(AssetCondition::find($condition->id));
        $this->assertSame($suppliers, Supplier::count());
        $this->assertGreaterThan(0, TransferType::count());
    }
}
