<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\InventoryItem;
use Illuminate\Database\Seeder;
use App\Models\ItemClassification;
use App\Models\InventoryTransaction;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = Supplier::factory(5)->create();

        $classifications = ItemClassification::all();

        if ($classifications->isEmpty()) {
            throw new \Exception('No ItemClassifications found. Run ItemClassificationSeeder first.');
        }

        $inventoryItems = collect();

        for ($i = 0; $i < 100; $i++) {
            $inventoryItems->push(
                InventoryItem::factory()->create([
                    'item_classification_id' => $classifications->random()->id,
                    'supplier_id' => $suppliers->random()->id,
                ])
            );
        }

        InventoryTransaction::factory(15)->create([
            'inventory_item_id' => fn() => $inventoryItems->random()->id,
        ]);
    }
}
