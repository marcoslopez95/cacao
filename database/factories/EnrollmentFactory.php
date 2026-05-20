<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'period_id' => Period::factory()->semester(),
            'pensum_id' => Pensum::factory(),
            'uc_disponibles' => fake()->numberBetween(12, 24),
            'uc_inscritas' => 0,
            'status' => EnrollmentStatus::Draft,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => EnrollmentStatus::Confirmed]);
    }

    public function approved(): static
    {
        return $this->state(['status' => EnrollmentStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => EnrollmentStatus::Rejected]);
    }
}
