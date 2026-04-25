<?php

namespace Database\Factories;

use App\Models\CareerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerCategory>
 */
class CareerCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
