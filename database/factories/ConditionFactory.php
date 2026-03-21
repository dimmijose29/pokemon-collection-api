<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Condition>
 */
class ConditionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code'       => strtoupper($this->faker->unique()->lexify('??')), // es. 'XX', 'YY' (unici)
            'name'       => $this->faker->unique()->word() . ' Condition',     // es. 'Test Condition'
        ];
    }
}
