<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'username' => fake()->unique()->userName(),
            'password' => Hash::make('password123'), // plain per test, poi hasherà
            'is_active' => true,
            'is_admin' => false,
            'country' => fake()->country(),
            'preferred_currency' => fake()->randomElement(['EUR', 'USD']),
            'birth_year' => fake()->numberBetween(1970, 2005),
            'avatar_url' => fake()->imageUrl(),
            'bio' => fake()->paragraph(),
        ];
    }

    // Stato per admin (utile nei test)
    public function admin()
    {
        return $this->state([
            'is_admin' => true,
        ]);
    }
}