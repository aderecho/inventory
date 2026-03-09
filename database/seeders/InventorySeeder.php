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
        $itemClassifications = ItemClassification::factory(13)->create();

        $inventoryItems = InventoryItem::factory(100)->create([
            'item_classification_id' => fn() => $itemClassifications->random()->id,
            'supplier_id' => fn() => $suppliers->random()->id,
        ]);

        InventoryTransaction::factory(15)->create([
            'inventory_item_id' => fn() => $inventoryItems->random()->id,
        ]);
    }
}
