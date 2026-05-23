<?php

namespace Database\Factories;

use App\Models\GradeConfig;
use App\Models\GradeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeSlot>
 */
class GradeSlotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'grade_config_id' => GradeConfig::factory(),
            'name' => fake()->randomElement(['Primer Parcial', 'Segundo Parcial', 'Examen Final']),
            'weight' => '33.33',
            'sort_order' => 1,
            'is_remedial' => false,
        ];
    }

    public function remedial(): static
    {
        return $this->state([
            'name' => 'Reparación',
            'weight' => '0.00',
            'is_remedial' => true,
        ]);
    }
}
