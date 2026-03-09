<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()
            ->withProfile()
            ->create(['email' => 'admin@example.com']);

        $admin->assignRole('admin');

        $specialStaff = User::factory()
            ->withProfile()
            ->create(['email' => 'special@example.com']);

        $specialStaff->assignRole('staff');
        $specialStaff->givePermissionTo('delete');

        User::factory(18)
            ->withProfile()
            ->create()
            ->each(function ($user) {
                $user->assignRole('staff');
            });
    }
}
