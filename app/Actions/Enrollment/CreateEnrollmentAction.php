<?php

namespace App\Actions\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Enums\PeriodStatus;
use App\Models\Enrollment;
use App\Models\Period;
use App\Models\Student;

class CreateEnrollmentAction
{
    public function handle(Student $student): Enrollment
    {
        $period = Period::where('status', PeriodStatus::Active)->firstOrFail();

        $ucDisponibles = $student->pensum
            ? (int) $student->pensum->subjects()->sum('credits_uc')
            : 0;

        return Enrollment::create([
            'student_id' => $student->id,
            'period_id' => $period->id,
            'pensum_id' => $student->current_pensum_id,
            'uc_disponibles' => $ucDisponibles,
            'uc_inscritas' => 0,
            'status' => EnrollmentStatus::Draft,
        ]);
    }
}
