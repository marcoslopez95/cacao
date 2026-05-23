<?php

namespace App\Http\Controllers\Academic;

use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Academic\StudentListResource;
use App\Models\Career;
use App\Models\Period;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $activePeriod = Period::where('status', PeriodStatus::Active)->first();

        $search = $request->input('search');
        $careerIds = array_filter((array) $request->input('career_id', []));
        $years = array_filter((array) $request->input('academic_year', []));
        $statuses = array_filter((array) $request->input('enrollment_status', []));
        $sectionLetters = array_filter((array) $request->input('section_letter', []));
        $level = $request->input('level');
        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));

        $activePeriodId = $activePeriod?->id ?? -1;

        $query = Student::query()
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('pensums', 'pensums.id', '=', 'students.current_pensum_id')
            ->leftJoin('careers', 'careers.id', '=', 'pensums.career_id')
            ->leftJoin('enrollments', fn ($join) => $join
                ->on('enrollments.student_id', '=', 'students.id')
                ->where('enrollments.period_id', '=', $activePeriodId)
            )
            ->leftJoin('guardians', 'guardians.id', '=', 'students.guardian_id')
            ->leftJoin('enrollment_details AS ed_school', 'ed_school.enrollment_id', '=', 'enrollments.id')
            ->leftJoin('sections AS sec_school', fn ($join) => $join
                ->on('sec_school.id', '=', 'ed_school.section_id')
                ->where('sec_school.type', '=', 'school')
            )
            ->select([
                'students.id',
                'students.academic_year',
                'students.educational_level',
                'students.current_pensum_id',
                DB::raw('users.name  AS _user_name'),
                DB::raw('users.email AS _user_email'),
                DB::raw('careers.name AS _career_name'),
                DB::raw('careers.id  AS _career_id'),
                DB::raw('enrollments.status AS _enrollment_status'),
                DB::raw('enrollments.uc_inscritas AS _uc_inscritas'),
                DB::raw('guardians.name AS _guardian_name'),
                DB::raw('guardians.relation AS _guardian_relation'),
                DB::raw('sec_school.grade AS _section_grade'),
                DB::raw('sec_school.letter AS _section_letter'),
            ])
            ->when($level, fn ($q) => $q->where('students.educational_level', $level))
            ->when($search, fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('users.name', 'ilike', "%{$search}%")
                    ->orWhere('users.email', 'ilike', "%{$search}%");
            }))
            ->when($careerIds, fn ($q) => $q->whereIn('careers.id', $careerIds))
            ->when($years, fn ($q) => $q->whereIn('students.academic_year', $years))
            ->when($sectionLetters, fn ($q) => $q->whereIn('sec_school.letter', $sectionLetters))
            ->when($statuses, function ($q) use ($statuses, $activePeriod): void {
                $hasNone = in_array('none', $statuses, strict: true);
                $realStatuses = array_values(array_filter($statuses, fn ($s) => $s !== 'none'));

                $q->where(function ($q) use ($hasNone, $realStatuses, $activePeriod): void {
                    if ($realStatuses) {
                        $q->whereIn('enrollments.status', $realStatuses);
                    }
                    if ($hasNone) {
                        $condition = $activePeriod ? 'orWhereNull' : 'whereNull';
                        $q->$condition('enrollments.status');
                    }
                });
            })
            ->orderBy('users.name');

        $students = $query->paginate($perPage)->withQueryString();

        // Quick view counts (level-scoped when level param is set)
        $baseCount = fn () => Student::query()
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('enrollments', fn ($join) => $join
                ->on('enrollments.student_id', '=', 'students.id')
                ->where('enrollments.period_id', '=', $activePeriodId)
            )
            ->when($level, fn ($q) => $q->where('students.educational_level', $level));

        $quickCounts = [
            'all' => $baseCount()->count(),
            'pending' => $baseCount()
                ->where(fn ($q) => $q->where('enrollments.status', 'draft')
                    ->orWhereNull('enrollments.status'))
                ->count(),
            'top' => 0,
            'risk' => 0,
            'newcomers' => $baseCount()->where('students.academic_year', 1)->count(),
            'no_guardian' => Student::whereNull('guardian_id')
                ->when($level, fn ($q) => $q->where('educational_level', $level))
                ->count(),
        ];

        $careers = Career::where('active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/Students/Index', [
            'students' => StudentListResource::collection($students),
            'careers' => $careers->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
            'activePeriod' => $activePeriod?->name,
            'quickCounts' => $quickCounts,
            'filters' => $request->only('search', 'career_id', 'academic_year', 'enrollment_status', 'level', 'section_letter'),
        ]);
    }
}
