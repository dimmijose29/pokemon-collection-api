<?php

namespace Database\Factories;

use App\Models\Set;
use Illuminate\Database\Eloquent\Factories\Factory;

class CardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tcgdex_id'    => $this->faker->unique()->bothify('??##-###'),
            'set_id'       => Set::factory(),
            'artist_id'    => null,
            'rarity_id'    => null,
            'card_type_id' => null,
            'local_id'     => (string) $this->faker->numberBetween(1, 200),
            'image_url'    => null,
            'stage'        => $this->faker->randomElement(['Basic', 'Stage 1', 'Stage 2', null]),
            'energy_types' => $this->faker->randomElement([['Fire'], ['Water'], ['Grass'], ['Fire', 'Water'], null]),
        ];
    }
}
