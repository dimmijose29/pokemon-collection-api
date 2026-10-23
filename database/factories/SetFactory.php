<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tcgdex_id'           => $this->faker->unique()->bothify('??##'),
            'name'                => $this->faker->unique()->words(3, true),
            'series_id'           => null,
            'card_count_official' => $this->faker->numberBetween(100, 200),
            'card_count_total'    => $this->faker->numberBetween(200, 250),
            'release_date'        => $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'symbol_url'          => null,
            'logo_url'            => null,
        ];
    }
}
