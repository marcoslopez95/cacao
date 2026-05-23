<?php

namespace Database\Factories;

use App\Models\EnrollmentDetail;
use App\Models\GradeEntry;
use App\Models\GradeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeEntry>
 */
class GradeEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_detail_id' => EnrollmentDetail::factory(),
            'grade_slot_id' => GradeSlot::factory(),
            'lapse_id' => null,
            'parent_id' => null,
            'name' => null,
            'weight' => null,
            'value' => fake()->randomFloat(2, 0, 20),
            'is_published' => true,
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }

    public function subEntry(GradeEntry $parent): static
    {
        return $this->state([
            'enrollment_detail_id' => $parent->enrollment_detail_id,
            'grade_slot_id' => $parent->grade_slot_id,
            'lapse_id' => $parent->lapse_id,
            'parent_id' => $parent->id,
            'name' => fake()->randomElement(['Quiz', 'Tarea', 'Práctica']),
            'weight' => '50.00',
            'value' => fake()->randomFloat(2, 0, 20),
        ]);
    }
}
