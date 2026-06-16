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

        User::factory(18)
            ->withProfile()
            ->create()
            ->each(function ($user) {
                $user->assignRole('staff');
            });
    }
}
