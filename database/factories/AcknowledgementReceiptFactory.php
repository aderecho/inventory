<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\FundSource;
use App\Models\InventoryItem;
use App\Models\AccountablePerson;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcknowledgementReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'par_date' => $this->faker->date(),
            'issued_by_id' => User::factory(),
            'category' => function () {
                $code = $this->faker->randomElement(['250', '451', '320', '150']);
                $year = date('Y');
                $month = str_pad(date('n'), 2, '0', STR_PAD_LEFT);
                $number = $this->faker->numberBetween(1, 20);
                return "{$code}-{$year}-{$month}-{$number}";
            },
            'remarks' => $this->faker->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
