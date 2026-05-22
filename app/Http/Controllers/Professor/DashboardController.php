<?php

namespace App\Http\Controllers\Professor;

use App\Enums\DayOfWeek;
use App\Enums\EnrollmentDetailStatus;
use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $professor = $user->professor;
        $period = Period::where('status', PeriodStatus::Active)->first();

        $now = now();
        $todayValue = strtolower($now->format('l'));
        $todayLabel = DayOfWeek::from($todayValue)->label();
        $currentTime = $now->format('H:i:s');

        $sections = $period
            ? Section::where('main_teacher_id', $professor->id)
                ->where('period_id', $period->id)
                ->with([
                    'subject',
                    'schedules' => fn ($q) => $q->where('day_of_week', $todayValue)
                        ->with('classroom')
                        ->orderBy('start_time'),
                    'enrollmentDetails' => fn ($q) => $q->where('status', EnrollmentDetailStatus::Confirmed),
                ])
                ->get()
            : collect();

        $hoursPerWeek = $period
            ? Schedule::where('professor_id', $professor->id)
                ->whereHas('section', fn ($q) => $q->where('period_id', $period->id))
                ->get()
                ->sum(fn ($s) => Carbon::parse($s->end_time)->diffInMinutes(Carbon::parse($s->start_time)) / 60)
            : 0;

        $todaySchedules = $sections->flatMap(fn ($section) => $section->schedules->map(fn ($schedule) => [
            'section_id' => $section->id,
            'subject_name' => $section->subject->name,
            'section_code' => $section->code,
            'classroom_name' => $schedule->classroom?->name ?? '—',
            'students_count' => $section->enrollmentDetails->count(),
            'start_time' => substr($schedule->start_time, 0, 5),
            'end_time' => substr($schedule->end_time, 0, 5),
            'is_current' => $currentTime >= $schedule->start_time
                                 && $currentTime <= $schedule->end_time,
        ]))->sortBy('start_time')->values();

        return Inertia::render('professor/Dashboard', [
            'period' => $period ? ['name' => $period->name, 'type' => $period->type] : null,
            'sections_count' => $sections->count(),
            'total_students' => $sections->sum(fn ($s) => $s->enrollmentDetails->count()),
            'hours_per_week' => round($hoursPerWeek, 1),
            'today_label' => $todayLabel,
            'today_schedules' => $todaySchedules,
        ]);
    }
}
