<?php

namespace App\Http\Controllers\Academic;

use App\Enums\GradeVisibility;
use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Academic\StudentListResource;
use App\Http\Resources\Academic\StudentShowResource;
use App\Http\Resources\Student\GradeCardResource;
use App\Models\Career;
use App\Models\Enrollment;
use App\Models\Period;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    /**
     * Display the academic profile of a single student.
     */
    public function show(Student $student): Response
    {
        $student->load([
            'user',
            'pensum.career',
            'academicStatus',
            'modality',
            'shift',
            'guardians' => fn ($q) => $q->withPivot(['kinship_type_id', 'is_primary', 'is_emergency_contact']),
            'guardians.user',
            'enrollments.period',
            'enrollments.details.subject',
            'enrollments.details.gradeEntries.children',
        ]);

        return Inertia::render('admin/Students/Show', [
            'student' => (new StudentShowResource($student))->resolve(),
        ]);
    }

    /**
     * Display the grade breakdown of a single enrollment, always in
     * real-time visibility — the admin sees every grade regardless of the
     * team's publication policy.
     */
    public function showEnrollment(Student $student, Enrollment $enrollment): Response
    {
        abort_unless($enrollment->student_id === $student->id, 404);
        Gate::authorize('view', $enrollment);

        $enrollment->load(['period', 'details.subject', 'details.gradeEntries.children']);

        return Inertia::render('admin/Students/EnrollmentGrades', [
            'student' => ['id' => $student->id, 'name' => $student->user->name],
            'grades' => (new GradeCardResource($enrollment, GradeVisibility::RealTime))->toArray(request()),
        ]);
    }

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
            ->leftJoin('enrollment_details AS ed_school', 'ed_school.enrollment_id', '=', 'enrollments.id')
            ->leftJoin('sections AS sec_school', fn ($join) => $join
                ->on('sec_school.id', '=', 'ed_school.section_id')
                ->where('sec_school.type', '=', 'school')
            )
            ->select([
                'students.id',
                'students.user_id',
                'students.academic_year',
                'students.educational_level',
                'students.current_pensum_id',
                DB::raw('users.name  AS _user_name'),
                DB::raw('users.email AS _user_email'),
                DB::raw('careers.name AS _career_name'),
                DB::raw('careers.id  AS _career_id'),
                DB::raw('enrollments.status AS _enrollment_status'),
                DB::raw('enrollments.uc_inscritas AS _uc_inscritas'),
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
            ->orderBy('users.name')
            ->distinct();

        $students = $query->paginate($perPage)->withQueryString();

        // Quick view counts (level-scoped when level param is set)
        $baseCount = fn () => Student::query()
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('enrollments', fn ($join) => $join
                ->on('enrollments.student_id', '=', 'students.id')
                ->where('enrollments.period_id', '=', $activePeriodId)
            )
            ->when($level, fn ($q) => $q->where('students.educational_level', $level));

        $year1Count = $baseCount()->where('students.academic_year', 1)->count();

        $quickCounts = [
            'all' => $baseCount()->count(),
            'pending' => $baseCount()
                ->where(fn ($q) => $q->where('enrollments.status', 'draft')
                    ->orWhereNull('enrollments.status'))
                ->count(),
            'top' => 0,
            'risk' => 0,
            'newcomers' => $year1Count,
            'year_1' => $year1Count,
            'grade_1' => $year1Count,
            'year_5' => $baseCount()->where('students.academic_year', 5)->count(),
            'grade_6' => $baseCount()->where('students.academic_year', 6)->count(),
            'enrolled' => $baseCount()
                ->whereIn('enrollments.status', ['confirmed', 'approved', 'draft'])
                ->count(),
            'no_guardian' => Student::whereDoesntHave('guardians')
                ->when($level, fn ($q) => $q->where('educational_level', $level))
                ->count(),
        ];

        $careers = Career::where('active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/Students/Index', [
            'students' => StudentListResource::collection($students),
            'careers' => $careers->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
            'activePeriod' => $activePeriod?->name,
            'quickCounts' => $quickCounts,
            'filters' => $request->only('search', 'career_id', 'academic_year', 'enrollment_status', 'level', 'section_letter', 'quick_view'),
        ]);
    }
}
