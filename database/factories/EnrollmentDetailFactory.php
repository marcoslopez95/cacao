<?php

namespace Database\Factories;

use App\Enums\EnrollmentDetailStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnrollmentDetail>
 */
class EnrollmentDetailFactory extends Factory
{
    public function definition(): array
    {
        $subject = Subject::factory()->create();
        $section = Section::factory()->create(['subject_id' => $subject->id]);

        return [
            'enrollment_id' => Enrollment::factory(),
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'status' => EnrollmentDetailStatus::Draft,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => EnrollmentDetailStatus::Confirmed]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => EnrollmentDetailStatus::Rejected]);
    }
}
