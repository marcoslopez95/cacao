<?php

namespace App\Http\Controllers\Student;

use App\Enums\GradeVisibility;
use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Student\GradeCardResource;
use App\Models\Enrollment;
use App\Models\Period;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $student = $user->student;

        abort_unless($student !== null, 404);

        $period = Period::where('status', PeriodStatus::Active)->first();
        $team = $user->currentTeam;
        $visibility = $team?->grade_visibility ?? GradeVisibility::RealTime;

        $enrollment = $period
            ? Enrollment::where('student_id', $student->id)
                ->where('period_id', $period->id)
                ->with(['period', 'details.subject'])
                ->first()
            : null;

        return Inertia::render('student/Grades/Index', [
            'grades' => $enrollment
                ? (new GradeCardResource($enrollment, $visibility))->toArray($request)
                : null,
            'period' => $period?->name,
        ]);
    }
}
