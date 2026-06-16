<?php

namespace Database\Factories;

use App\Models\AcknowledgementReceipt;
use App\Models\InventoryItem;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcknowledgementItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'acknowledgement_id' => AcknowledgementReceipt::factory(),
            'inventory_item_id' => InventoryItem::inRandomOrder()->value('id')
                ?? InventoryItem::factory()->state(function () {
                    return [
                        'item_classification_id' => \App\Models\ItemClassification::inRandomOrder()->value('id'),
                        'supplier_id' => \App\Models\Supplier::inRandomOrder()->value('id'),
                    ];
                }),
            'accountable_person_id' => UserProfile::inRandomOrder()->value('id'),
            'issued_by_id' => UserProfile::inRandomOrder()->value('id'),
            'status' => $this->faker->boolean ? 1 : 0,
        ];
    }
}