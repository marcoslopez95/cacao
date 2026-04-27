<?php

namespace Database\Factories;

use App\Enums\ClassroomType;
use App\Models\Building;
use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'identifier'  => fake()->unique()->bothify('???##'),
            'type'        => fake()->randomElement(ClassroomType::cases())->value,
            'capacity'    => fake()->numberBetween(20, 60),
        ];
    }

    public function theory(): static
    {
        return $this->state(['type' => ClassroomType::Theory->value]);
    }

    public function laboratory(): static
    {
        return $this->state(['type' => ClassroomType::Laboratory->value]);
    }
}
