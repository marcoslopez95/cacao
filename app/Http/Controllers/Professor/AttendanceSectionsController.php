<?php

namespace App\Http\Controllers\Professor;

use App\Enums\EnrollmentDetailStatus;
use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceSectionsController extends Controller
{
    public function index(Request $request): Response
    {
        $professor = $request->user()->professor;
        abort_unless($professor !== null, 404);

        $period = Period::where('status', PeriodStatus::Active)->first();

        $sections = $period
            ? Section::where('main_teacher_id', $professor->id)
                ->where('period_id', $period->id)
                ->with([
                    'subject',
                    'enrollmentDetails' => fn ($q) => $q->where('status', EnrollmentDetailStatus::Confirmed),
                    'classSessions',
                ])
                ->get()
                ->map(fn (Section $s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'subject' => $s->subject->name,
                    'roster_count' => $s->enrollmentDetails->count(),
                    'sessions_held' => $s->classSessions->whereIn('status', ['held', 'advanced', 'recovered'])->count(),
                    'sessions_pending' => $s->classSessions->where('status', 'scheduled')->count(),
                ])
            : collect();

        return Inertia::render('professor/attendance/Sections', [
            'period' => $period?->name,
            'sections' => $sections->values(),
        ]);
    }
}
