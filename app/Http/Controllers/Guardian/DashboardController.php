<?php

namespace App\Http\Controllers\Guardian;

use App\Enums\EducationalLevel;
use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $guardian = $user->guardian;
        abort_unless($guardian !== null, 404);

        $period = Period::where('status', PeriodStatus::Active)->first();

        $studentsQuery = $guardian->students()
            ->where('educational_level', '!=', EducationalLevel::University)
            ->with(['user:id,name', 'pensum']);

        if ($period) {
            $studentsQuery->with([
                'enrollments' => fn ($q) => $q->where('period_id', $period->id)
                    ->with('details.section.subject:id,name'),
            ]);
        }

        $students = $studentsQuery->get()->map(function (Student $student) use ($period) {
            $enrollment = $period ? $student->enrollments->first() : null;
            $ucPensum = $student->pensum?->subjects()->sum('credits_uc') ?? 0;

            return [
                'id' => $student->id,
                'name' => $student->user->name,
                'educational_level' => $student->educational_level->value,
                'academic_year' => $student->academic_year,
                'pensum_name' => $student->pensum?->name,
                'uc_pensum' => $ucPensum,
                'uc_aprobadas' => 0,
                'enrollment_status' => $enrollment?->status->value,
                'uc_inscritas' => $enrollment?->uc_inscritas ?? 0,
                'nota_promedio' => null,
                'inasistencias' => null,
                'subjects' => $enrollment
                    ? $enrollment->details->map(fn ($d) => [
                        'id' => $d->section->subject->id,
                        'name' => $d->section->subject->name,
                    ])->unique('id')->values()
                    : [],
            ];
        });

        return Inertia::render('guardian/Dashboard', [
            'period' => $period ? ['name' => $period->name] : null,
            'students' => $students,
        ]);
    }
}
