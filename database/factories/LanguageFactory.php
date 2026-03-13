<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Language>
 */
class LanguageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code'       => strtoupper($this->faker->unique()->lexify('??')), // es. 'XX', 'YY' (unici)
            'name'       => $this->faker->unique()->word() . ' Language',     // es. 'Test Language'
            'country_code' => $this->faker->countryCode(),                    // es. 'IT', 'FR', 'JP'
        ];
    }

    /**
     * Stato per lingua "reale" (es. Italiano)
     */
    public function italiano()
    {
        return $this->state([
            'code'       => 'IT',
            'name'       => 'Italiano',
            'country_code' => 'IT',
        ]);
    }

    /**
     * Stato per inglese (utile per test unique)
     */
    public function english()
    {
        return $this->state([
            'code'       => 'EN',
            'name'       => 'English',
            'country_code' => 'GB',
        ]);
    }
}