<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'status' => 1,
        ];
    }
    public function withProfile(): static
    {
        return $this->has(
            \App\Models\UserProfile::factory(),
            'userProfiles'
        );
    }
}
