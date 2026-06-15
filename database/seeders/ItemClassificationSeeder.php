<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ItemClassification;

class ItemClassificationSeeder extends Seeder
{
    public function run(): void
    {
        ItemClassification::factory(13)->create();
    }
}
