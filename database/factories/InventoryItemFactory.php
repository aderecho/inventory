<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\InventoryItem;
use App\Models\ItemClassification;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        $unitCost = $this->faker->randomFloat(2, 30000, 99000);
        $qty = $this->faker->numberBetween(1, 20);

        $data = require database_path('factories/factory_data/inventory_faker.php');
        $item = fake()->randomElement($data);

        $code = $this->faker->randomElement(['250', '451', '320', '150']);
        $group = 1;

        $count = InventoryItem::where('property_number', 'like', "{$code}-{$group}-%")->count();
        $propertyNumber = "{$code}-{$group}-" . ($count + 1);

        return [
            'fund_source' => strtoupper($this->faker->unique()->bothify('FS-###')),
            'invoice' => strtoupper($this->faker->unique()->bothify('INV-###')),
            'item_name' => $item['item_name'],
            'description' => $item['description'],
            'quantity' => 1,
            'unit' => $item['unit'],
            'unit_cost' => $item['unit_cost'],
            'total_amount' => $qty * $item['unit_cost'],
            'pr_number' => strtoupper($this->faker->unique()->bothify('PR-###')),
            'po_number' => strtoupper($this->faker->unique()->bothify('PO-###')),
            'property_number' => $propertyNumber,
            'serial_number' => strtoupper($this->faker->unique()->bothify('SER-###')),
            'remarks' => strtoupper($this->faker->unique()->bothify('RM-###')),
            'date_acquired' => $this->faker->date('Y-m-d', 'now'),
            'status' => $this->faker->numberBetween(0, 1),
        ];
    }
}