<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RarityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Common',
                'Uncommon',
                'Rare',
                'Rare Holo',
                'Ultra Rare',
                'Secret Rare',
                'Rainbow Rare',
                'Gold Rare',
                'EX',
                'GX',
                'V',
                'VMAX',
                'TAG TEAM',
            ]),
        ];
    }
}