<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => fake()->name(),
            'email'     => fake()->unique()->safeEmail(),
            'password'  => Hash::make('password123'),
            'role'      => fake()->randomElement(['admin', 'petugas']),
            'phone'     => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
