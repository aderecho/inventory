<?php

namespace Tests\Feature;

use App\Exceptions\InventoryImportException;
use App\Models\AssetCondition;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\DashboardService;
use App\Services\InspectionService;
use App\Services\InventoryImportService;
use App\Services\InventoryService;
use App\Services\RoomApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class ConditionDispositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_disposed_condition_marks_the_item_disposed(): void
    {
        $item = InventoryItem::factory()->create();
        $condition = $this->condition('Unserviceable', AssetCondition::DISPOSITION_DISPOSED);

        $this->inspect($condition, [$item->id]);

        $this->assertSame(InventoryItem::STATUS_DISPOSED, $item->fresh()->status);
    }

    public function test_a_later_serviceable_inspection_puts_the_item_back_in_stock(): void
    {
        $item = InventoryItem::factory()->create();
        $disposed = $this->condition('Disposed', AssetCondition::DISPOSITION_DISPOSED);
        $serviceable = $this->condition('Good', AssetCondition::DISPOSITION_SERVICEABLE);

        $this->inspect($disposed, [$item->id], '2026-10-01');
        $this->inspect($serviceable, [$item->id], '2026-10-05');

        $this->assertSame(InventoryItem::STATUS_AVAILABLE, $item->fresh()->status);
    }

    public function test_disposed_items_leave_the_inventory_list_unless_asked_for(): void
    {
        $kept = InventoryItem::factory()->create();
        $disposed = InventoryItem::factory()->create();

        $this->inspect(
            $this->condition('Disposed', AssetCondition::DISPOSITION_DISPOSED),
            [$disposed->id],
        );

        $service = app(InventoryService::class);

        $this->assertSame(
            [$kept->id],
            $service->filterAndPaginateInventory()->pluck('id')->all(),
        );

        $this->assertSame(
            [$disposed->id],
            $service->filterAndPaginateInventory(status: InventoryItem::STATUS_DISPOSED)->pluck('id')->all(),
        );
    }

    public function test_an_item_on_loan_cannot_be_inspected_as_disposed(): void
    {
        $item = InventoryItem::factory()->create(['status' => InventoryItem::STATUS_BORROWED]);
        $condition = $this->condition('Disposed', AssetCondition::DISPOSITION_DISPOSED);

        $this->actingAs(User::factory()->create())
            ->post(route('inspection.store'), [
                'asset_condition_id' => $condition->id,
                'inspection_date' => '2026-10-10',
                'selectedItemIds' => [$item->id],
            ])
            ->assertSessionHasErrors('selectedItemIds.0');

        $this->assertSame(InventoryItem::STATUS_BORROWED, $item->fresh()->status);
    }

    public function test_the_dashboard_counts_disposed_items_from_their_status(): void
    {
        InventoryItem::factory()->create();
        $disposed = InventoryItem::factory()->create();
        $markedForDisposal = InventoryItem::factory()->create();

        $this->inspect(
            $this->condition('Disposed', AssetCondition::DISPOSITION_DISPOSED),
            [$disposed->id],
        );
        $this->inspect(
            $this->condition('Unserviceable', AssetCondition::DISPOSITION_FOR_DISPOSAL),
            [$markedForDisposal->id],
        );

        $highlights = app(DashboardService::class)->getHighlights();

        $this->assertSame(1, $highlights['disposal_items']);
        // Marked for disposal is still in stock; disposed is not counted twice.
        $this->assertSame(1, $highlights['for_disposal_items']);
        $this->assertSame(2, app(DashboardService::class)->getStats()['items']);
    }

    public function test_the_import_looks_conditions_up_by_disposition_not_by_name(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn([
                'data' => [['id' => 7, 'room_name' => 'Supply Office']],
            ]);
        });

        $this->createIssuingRecords();

        // Deliberately named nothing like "disposal": the disposition decides.
        $serviceable = $this->condition('Working', AssetCondition::DISPOSITION_SERVICEABLE);
        $disposed = $this->condition('Unserviceable', AssetCondition::DISPOSITION_DISPOSED);

        $service = app(InventoryImportService::class);
        $load = new ReflectionMethod($service, 'loadLookups');
        $load->invoke($service);

        $this->assertSame($serviceable->id, $this->property($service, 'serviceableConditionId'));
        $this->assertSame($disposed->id, $this->property($service, 'disposedConditionId'));
    }

    public function test_the_import_refuses_to_run_without_a_disposed_condition(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => [['id' => 7, 'room_name' => 'Supply Office']]]);
        });

        $this->createIssuingRecords();

        $this->condition('Good', AssetCondition::DISPOSITION_SERVICEABLE);

        $this->expectException(InventoryImportException::class);

        (new ReflectionMethod(InventoryImportService::class, 'loadLookups'))
            ->invoke(app(InventoryImportService::class));
    }

    public function test_the_disposition_helpers_describe_each_condition(): void
    {
        $serviceable = $this->condition('Good', AssetCondition::DISPOSITION_SERVICEABLE);
        $candidate = $this->condition('For disposal', AssetCondition::DISPOSITION_FOR_DISPOSAL);
        $disposed = $this->condition('Disposed', AssetCondition::DISPOSITION_DISPOSED);

        $this->assertFalse($serviceable->isDisposed());
        $this->assertFalse($serviceable->isForDisposal());
        $this->assertFalse($serviceable->isOnDisposalPath());

        $this->assertTrue($candidate->isForDisposal());
        $this->assertTrue($candidate->isOnDisposalPath());

        $this->assertTrue($disposed->isDisposed());
        $this->assertTrue($disposed->isOnDisposalPath());

        $this->assertCount(1, AssetCondition::ofDisposition(AssetCondition::DISPOSITION_FOR_DISPOSAL)->get());
        $this->assertCount(1, AssetCondition::ofDisposition(AssetCondition::DISPOSITION_DISPOSED)->get());
    }

    public function test_a_new_condition_has_to_declare_what_it_means(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('asset-conditions.store'), ['condition_name' => 'Mint'])
            ->assertSessionHasErrors('disposition');

        $this->actingAs($user)
            ->post(route('asset-conditions.store'), [
                'condition_name' => 'Mint',
                'disposition' => 'somewhere_in_between',
            ])
            ->assertSessionHasErrors('disposition');

        $this->actingAs($user)
            ->post(route('asset-conditions.store'), [
                'condition_name' => 'Mint',
                'description' => 'Like new.',
                'disposition' => AssetCondition::DISPOSITION_SERVICEABLE,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('asset_conditions', [
            'condition_name' => 'Mint',
            'disposition' => AssetCondition::DISPOSITION_SERVICEABLE,
        ]);
    }

    private function condition(string $name, string $disposition): AssetCondition
    {
        return AssetCondition::create([
            'condition_name' => $name,
            'disposition' => $disposition,
        ]);
    }

    /** The import service expects an issuing user and profile with this key. */
    private function createIssuingRecords(): void
    {
        User::factory()->create()->forceFill(['id' => InventoryImportService::ISSUED_BY_ID])->save();

        UserProfile::factory()
            ->create(['user_id' => InventoryImportService::ISSUED_BY_ID])
            ->forceFill(['id' => InventoryImportService::ISSUED_BY_ID])
            ->save();
    }

    private function property(object $target, string $name): mixed
    {
        $property = new ReflectionProperty($target, $name);
        $property->setAccessible(true);

        return $property->getValue($target);
    }

    /** @param array<int, int> $itemIds */
    private function inspect(AssetCondition $condition, array $itemIds, string $date = '2026-10-10'): void
    {
        app(InspectionService::class)->createInspections(
            assetConditionId: $condition->id,
            inspectionDate: $date,
            remarks: null,
            itemIds: $itemIds,
            inspectedBy: null,
        );
    }
}
