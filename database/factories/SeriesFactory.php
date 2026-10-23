<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SeriesFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tcgdex_id' => $this->faker->unique()->bothify('ser##??'),
            'name'      => $this->faker->unique()->words(2, true),
            'logo_url'  => null,
        ];
    }
}
