<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemHistoryLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::query()
                ->inRandomOrder()
                ->value('id'),
            'room_id' => $this->faker->numberBetween(1, 30),
        ];
    }
}
