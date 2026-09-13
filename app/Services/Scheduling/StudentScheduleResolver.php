<?php

namespace App\Services\Scheduling;

use App\Enums\PeriodStatus;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Support\Collection;

class StudentScheduleResolver
{
    /**
     * Resuelve los horarios vigentes de las secciones confirmadas del estudiante
     * en el período activo. Sin período activo, sin Enrollment del estudiante para
     * ese período, o sin ningún EnrollmentDetail confirmado, retorna una colección vacía.
     *
     * @return Collection<int, Schedule>
     */
    public function resolve(Student $student): Collection
    {
        $period = Period::where('status', PeriodStatus::Active)->first();

        if ($period === null) {
            return collect();
        }

        $enrollment = $student->enrollments()
            ->where('period_id', $period->id)
            ->with(['confirmedDetails.section.schedules' => function ($query) {
                $query->whereDate('valid_from', '<=', now())
                    ->where(function ($validity) {
                        $validity->whereNull('valid_until')
                            ->orWhereDate('valid_until', '>=', now());
                    })
                    ->with(['professor.user', 'classroom', 'subject.pensum.career']);
            }])
            ->first();

        if ($enrollment === null) {
            return collect();
        }

        return $enrollment->confirmedDetails
            ->flatMap(fn ($detail) => $detail->section->schedules)
            ->values();
    }
}
