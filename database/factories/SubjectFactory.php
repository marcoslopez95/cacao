<?php

namespace Database\Factories;

use App\Models\Pensum;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pensum_id' => Pensum::factory(),
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->unique()->lexify('???')).'-'.fake()->numberBetween(100, 999),
            'credits_uc' => fake()->numberBetween(2, 6),
            'period_number' => 1,
            'description' => null,
        ];
    }
}
