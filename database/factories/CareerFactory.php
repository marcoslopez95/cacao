<?php

namespace Database\Factories;

use App\Models\Career;
use App\Models\CareerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Career>
 */
class CareerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'career_category_id' => CareerCategory::factory(),
            'name' => fake()->unique()->sentence(3, false),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
