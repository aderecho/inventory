<?php

namespace Tests\Feature;

use App\Models\AssetCondition;
use App\Models\InventoryItem;
use App\Models\ItemClassification;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use App\Support\PropertyNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryPropertyNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_item_keeps_the_full_property_number_without_a_generated_suffix(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $supplier = Supplier::factory()->create();
        $classification = ItemClassification::create([
            'classification_code' => '50-30',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);
        $condition = AssetCondition::create([
            'condition_name' => 'Good',
            'disposition' => AssetCondition::DISPOSITION_SERVICEABLE,
        ]);

        app(InventoryService::class)->createInventoryItems([
            'item_classification_id' => $classification->id,
            'supplier_id' => $supplier->id,
            'room_id' => null,
            'unit' => 'unit',
            'date_acquired' => '2016-01-01',
            'lifespan' => 5,
            'quantity' => 1,
            'unit_cost' => 100,
            'item_name' => 'Test item',
            'brand' => null,
            'model' => null,
            'pr_number' => 'PR-1',
            'po_number' => 'PO-1',
            'po_number_date' => '2016-01-01',
            'remarks' => null,
            'invoice' => 'INV-1',
            'sales_invoice_date' => '2016-01-01',
            'fund_source' => 'FS-1',
            'description' => 'Description',
            'serial_numbers' => ['SN-1'],
            'is_private' => 0,
            'asset_condition_id' => $condition->id,
            'property_number' => '2016-50-30-0031-161',
        ]);

        $this->assertDatabaseHas('inventory_items', [
            'property_number' => '2016-50-30-0031-161',
            'item_classification_id' => $classification->id,
        ]);
    }

    public function test_creating_a_batch_increments_the_sequence_without_a_quantity_suffix(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $supplier = Supplier::factory()->create();
        $classification = ItemClassification::create([
            'classification_code' => '50-30',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);
        $condition = AssetCondition::create([
            'condition_name' => 'Good',
            'disposition' => AssetCondition::DISPOSITION_SERVICEABLE,
        ]);

        app(InventoryService::class)->createInventoryItems([
            'item_classification_id' => $classification->id,
            'supplier_id' => $supplier->id,
            'room_id' => null,
            'unit' => 'unit',
            'date_acquired' => '2016-01-01',
            'lifespan' => 5,
            'quantity' => 3,
            'unit_cost' => 100,
            'item_name' => 'Test item',
            'brand' => null,
            'model' => null,
            'pr_number' => 'PR-1',
            'po_number' => 'PO-1',
            'po_number_date' => '2016-01-01',
            'remarks' => null,
            'invoice' => 'INV-1',
            'sales_invoice_date' => '2016-01-01',
            'fund_source' => 'FS-1',
            'description' => 'Description',
            'serial_numbers' => ['SN-1', 'SN-2', 'SN-3'],
            'is_private' => 0,
            'asset_condition_id' => $condition->id,
            'property_number' => '2026-50-30-2000-234',
        ]);

        $this->assertSame(
            ['2026-50-30-2000-234', '2026-50-30-2001-234', '2026-50-30-2002-234'],
            InventoryItem::orderBy('id')->pluck('property_number')->all(),
        );
    }

    public function test_an_item_can_be_created_without_a_condition(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $supplier = Supplier::factory()->create();
        $classification = ItemClassification::create([
            'classification_code' => '50-30',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);

        app(InventoryService::class)->createInventoryItems([
            'item_classification_id' => $classification->id,
            'supplier_id' => $supplier->id,
            'room_id' => null,
            'unit' => 'unit',
            'date_acquired' => '2026-01-01',
            'lifespan' => 5,
            'quantity' => 1,
            'unit_cost' => 100,
            'item_name' => 'No condition item',
            'brand' => null,
            'model' => null,
            'pr_number' => 'PR-1',
            'po_number' => 'PO-1',
            'po_number_date' => '2026-01-01',
            'remarks' => null,
            'invoice' => 'INV-1',
            'sales_invoice_date' => '2026-01-01',
            'fund_source' => 'FS-1',
            'description' => 'Description',
            'serial_numbers' => ['SN-1'],
            'is_private' => 0,
            'asset_condition_id' => null,
            'property_number' => '2026-50-30-2000-999',
        ]);

        $item = InventoryItem::where('property_number', '2026-50-30-2000-999')->firstOrFail();

        $this->assertSame(InventoryItem::STATUS_AVAILABLE, $item->status);
        $this->assertDatabaseMissing('asset_inspections', [
            'inventory_item_id' => $item->id,
        ]);
    }

    public function test_the_next_property_number_increments_the_highest_sequence(): void
    {
        $classification = ItemClassification::create([
            'classification_code' => '50-30',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);

        InventoryItem::factory()->create(['property_number' => '2026-50-30-2001-356']);
        InventoryItem::factory()->create(['property_number' => '2026-50-30-2003-999']);
        InventoryItem::factory()->create(['property_number' => '2026-50-30-1502-111']);

        $suggestions = app(InventoryService::class)
            ->nextPropertyNumbersByYear([$classification]);

        $this->assertSame(2004, $suggestions['2026']['number']);
        $this->assertArrayNotHasKey('suffix', $suggestions['2026']);
    }

    public function test_the_next_property_number_defaults_to_2000_below_the_starting_sequence(): void
    {
        $classification = ItemClassification::create([
            'classification_code' => '50-30',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);

        InventoryItem::factory()->create(['property_number' => '2026-50-30-1502-356']);

        $suggestions = app(InventoryService::class)
            ->nextPropertyNumbersByYear([$classification]);

        $this->assertSame(2000, $suggestions['2026']['number']);
        $this->assertArrayNotHasKey('suffix', $suggestions['2026']);
    }

    public function test_quantity_increments_the_sequence_segment(): void
    {
        $this->assertSame(
            ['2026-50-30-2000-234', '2026-50-30-2001-234'],
            PropertyNumber::forQuantity('2026-50-30-2000-234', 2),
        );
        $this->assertSame(
            ['2026-50-30-456-234', '2026-50-30-457-234'],
            PropertyNumber::forQuantity('2026-50-30-456-234', 2),
        );
        $this->assertSame(
            ['2026-50-30-2000', '2026-50-30-2001'],
            PropertyNumber::forQuantity('2026-50-30-2000-', 2),
        );
        $this->assertSame(
            ['ABC-2000', 'ABC-2001'],
            PropertyNumber::forQuantity('ABC', 2),
        );
    }

    public function test_a_legacy_quantity_suffix_is_removed_before_incrementing(): void
    {
        $classification = ItemClassification::create([
            'classification_code' => '50-30',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);

        InventoryItem::factory()->create(['property_number' => '2026-50-30-2000-234-001']);
        InventoryItem::factory()->create(['property_number' => '2026-50-30-2000-234-002']);
        InventoryItem::factory()->create(['property_number' => '2026-50-30-2000-234-003']);

        $suggestions = app(InventoryService::class)
            ->nextPropertyNumbersByYear([$classification]);

        $this->assertSame(2001, $suggestions['2026']['number']);
        $this->assertArrayNotHasKey('suffix', $suggestions['2026']);
        $this->assertSame(
            ['2026-50-30-2001-234', '2026-50-30-2002-234'],
            PropertyNumber::forQuantity('2026-50-30-2001-234-001', 2),
        );
    }

    public function test_the_sequence_continues_across_a_new_classification(): void
    {
        $existing = ItemClassification::create([
            'classification_code' => '50-30',
            'classification_name' => 'Existing Classification',
            'status' => 1,
        ]);
        $new = ItemClassification::create([
            'classification_code' => '30-20',
            'classification_name' => 'New Classification',
            'status' => 1,
        ]);

        InventoryItem::factory()->create(['property_number' => '2026-50-30-2008-234']);

        $suggestions = app(InventoryService::class)
            ->nextPropertyNumbersByYear([$existing, $new]);

        $this->assertSame(2009, $suggestions['2026']['number']);
        $this->assertArrayNotHasKey('suffix', $suggestions['2026']);
    }

    public function test_a_soft_deleted_property_number_does_not_advance_the_sequence(): void
    {
        $classification = ItemClassification::create([
            'classification_code' => '50-30',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);

        InventoryItem::factory()->create(['property_number' => '2026-50-30-2000-100']);
        InventoryItem::factory()
            ->create(['property_number' => '2026-50-30-2008-200'])
            ->delete();

        $suggestions = app(InventoryService::class)
            ->nextPropertyNumbersByYear([$classification]);

        $this->assertSame(2001, $suggestions['2026']['number']);
        $this->assertArrayNotHasKey('suffix', $suggestions['2026']);
    }
}
