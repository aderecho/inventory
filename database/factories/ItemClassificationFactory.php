<?php

namespace Database\Factories;

use App\Models\ItemClassification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

class ItemClassificationFactory extends Factory
{
    protected $model = ItemClassification::class;

    public function definition()
    {
        return [
            'classification_code' => '000',
            'classification_name' => 'Placeholder',
            'status' => $this->faker->boolean(80),
        ];
    }

    public function configure()
    {
        $pairs = [
            ['221', 'Office Equipment'],
            ['222', 'Furnitures'],
            ['223', 'IT/Software'],
            ['229', 'Comm.Egpt'],
            ['236', 'Tech./Scientific Egpt.'],
            ['231', 'Firefighting Egpt.'],
            ['232', 'Hospital Egpt'],
            ['233', 'Medical, Dental and Lab Egpt'],
            ['235', 'Sport Egpt'],
            ['240', 'Other Machineries & Egpt.'],
            ['224', 'Library Books'],
            ['241', 'Motor Vehicles'],
            ['250', 'Other PPE'],
        ];

        return $this->state(new Sequence(
            ...array_map(fn($pair) => [
                'classification_code' => $pair[0],
                'classification_name' => $pair[1],
            ], $pairs)
        ));
    }
}