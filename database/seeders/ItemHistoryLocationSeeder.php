<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ItemHistoryLocation;

class ItemHistoryLocationSeeder extends Seeder
{
    public function run(): void
    {
        ItemHistoryLocation::factory()->count(10)->create();
    }
}
