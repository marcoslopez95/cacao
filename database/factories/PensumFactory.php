<?php

namespace Database\Factories;

use App\Models\Career;
use App\Models\Pensum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pensum>
 */
class PensumFactory extends Factory
{
    public function definition(): array
    {
        return [
            'career_id' => Career::factory(),
            'name' => 'Plan de Estudios '.fake()->unique()->numberBetween(2000, 2030),
            'period_type' => fake()->randomElement(['semester', 'year']),
            'total_periods' => fake()->numberBetween(6, 12),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
