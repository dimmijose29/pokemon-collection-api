<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GradingCompany>
 */
class GradingCompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'PSA',
                'BGS',
                'CGC',
                'SGC',
                'TAG',
                'HGA',
                'GRAAD',
                'AiGrading',
                'Eur0grading',
                'ACE Grading',
                'PCA',
                'AP Grading',
                'ARS'
            ]),
        ];
    }
}
