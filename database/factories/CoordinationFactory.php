<?php

namespace Database\Factories;

use App\Models\Coordination;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coordination>
 */
class CoordinationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Coordinación de '.fake()->words(2, true),
            'type' => 'career',
            'education_level' => 'university',
            'secondary_type' => null,
            'career_id' => null,
            'grade_year' => null,
            'active' => true,
        ];
    }

    public function career(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'career',
            'education_level' => 'university',
            'secondary_type' => null,
            'grade_year' => null,
        ]);
    }

    public function grade(string $secondaryType = 'media_general', int $year = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'grade',
            'education_level' => 'secondary',
            'secondary_type' => $secondaryType,
            'grade_year' => $year,
            'career_id' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
