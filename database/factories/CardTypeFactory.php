<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CardTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Pokémon', 
                'Trainer',
                'Energy',
                'Object',
            ]),
        ];
    }
}