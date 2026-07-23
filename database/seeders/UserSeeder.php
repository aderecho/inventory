<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()
            ->withProfile()
            ->create([
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
            ]);

        $admin->assignRole('admin');

        User::factory(18)
            ->withProfile()
            ->create()
            ->each(function ($user) {
                $user->assignRole('staff');
            });
    }
}
