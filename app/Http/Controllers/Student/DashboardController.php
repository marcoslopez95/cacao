<?php

namespace App\Http\Controllers\Student;

use App\Enums\DayOfWeek;
use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\Period;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $student = $user->student;
        abort_unless($student !== null, 404);

        $period = Period::where('status', PeriodStatus::Active)->first();

        $now = now();
        $todayValue = strtolower($now->format('l'));
        $todayLabel = DayOfWeek::from($todayValue)->label();
        $currentTime = $now->format('H:i:s');

        $enrollment = $period
            ? $student->enrollments()
                ->where('period_id', $period->id)
                ->with([
                    'details.section.subject',
                    'details.section.schedules' => fn ($q) => $q->where('day_of_week', $todayValue)
                        ->with('classroom')
                        ->orderBy('start_time'),
                ])
                ->first()
            : null;

        $ucPensum = $student->pensum?->subjects()->sum('credits_uc') ?? 0;

        $todaySchedules = $enrollment
            ? $enrollment->details->flatMap(fn ($detail) => $detail->section->schedules->map(fn ($schedule) => [
                'subject_name' => $detail->section->subject->name,
                'section_code' => $detail->section->code,
                'classroom_name' => $schedule->classroom?->name ?? '—',
                'start_time' => substr($schedule->start_time, 0, 5),
                'end_time' => substr($schedule->end_time, 0, 5),
                'is_current' => $currentTime >= $schedule->start_time
                                    && $currentTime <= $schedule->end_time,
            ])
            )->sortBy('start_time')->values()
            : collect();

        return Inertia::render('student/Dashboard', [
            'period' => $period ? ['name' => $period->name] : null,
            'enrollment' => $enrollment ? [
                'id' => $enrollment->id,
                'status' => $enrollment->status->value,
                'uc_inscritas' => $enrollment->uc_inscritas,
            ] : null,
            'subjects_count' => $enrollment?->details->count() ?? 0,
            'uc_pensum' => $ucPensum,
            'uc_aprobadas' => 0,
            'today_label' => $todayLabel,
            'today_schedules' => $todaySchedules,
        ]);
    }
}
