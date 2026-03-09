<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\RolePermissionSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            AccountablePersonSeeder::class,
            InventorySeeder::class,
            AcknowledgementSeeder::class,
        ]);
    }
}
