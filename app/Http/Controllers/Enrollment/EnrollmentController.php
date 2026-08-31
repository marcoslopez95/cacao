<?php

namespace App\Http\Controllers\Enrollment;

use App\Actions\Enrollment\AddEnrollmentDetailAction;
use App\Actions\Enrollment\BuildEnrollmentCatalogAction;
use App\Actions\Enrollment\ConfirmEnrollmentAction;
use App\Actions\Enrollment\CreateEnrollmentAction;
use App\Enums\EducationalLevel;
use App\Enums\EnrollmentDetailStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\PeriodStatus;
use App\Enums\PeriodType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\StoreEnrollmentDetailRequest;
use App\Http\Requests\Enrollment\StoreEnrollmentRequest;
use App\Http\Resources\Enrollment\EnrollmentCatalogSubjectResource;
use App\Http\Resources\Enrollment\EnrollmentDetailResource;
use App\Http\Resources\Enrollment\EnrollmentResource;
use App\Http\Wrappers\Enrollment\EnrollmentDetailWrapper;
use App\Http\Wrappers\Enrollment\EnrollmentWrapper;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use App\Services\Enrollment\EnrollmentCacheManager;
use App\Services\Enrollment\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $student = $this->resolveStudent($user, $request->integer('student_id') ?: null);

        Gate::authorize('create', [Enrollment::class, $student]);

        $period = $student
            ? Period::where('status', PeriodStatus::Active)
                ->where('type', $student->educational_level === EducationalLevel::University
                    ? PeriodType::Semester
                    : PeriodType::Year)
                ->first()
            : null;

        if (! $period || ! $student?->current_pensum_id) {
            return Inertia::render('enrollment/Index', [
                'enrollment' => null,
                'catalog' => [],
                'rules' => $this->buildRules(null, $student),
                'can' => ['confirm' => false],
            ]);
        }

        // Auto-crear draft si no existe; si ya hay confirmed/approved usar ese
        $draft = Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'period_id' => $period->id],
            [
                'pensum_id' => $student->current_pensum_id,
                'status' => EnrollmentStatus::Draft,
                'uc_disponibles' => 0,
                'uc_inscritas' => 0,
            ]
        );
        $draft->load(['details.subject', 'details.section', 'period', 'pensum', 'student.user']);

        $catalog = app(BuildEnrollmentCatalogAction::class)->handle($student, $period, $draft);

        return Inertia::render('enrollment/Index', [
            'enrollment' => (new EnrollmentResource($draft))->resolve(),
            'catalog' => EnrollmentCatalogSubjectResource::collection($catalog)->resolve(),
            'rules' => $this->buildRules($period, $student),
            'can' => ['confirm' => $user->can('confirm', $draft)],
        ]);
    }

    public function store(StoreEnrollmentRequest $request, CreateEnrollmentAction $action): JsonResponse
    {
        $wrapper = new EnrollmentWrapper($request->validated());
        $enrollment = $action->handle($wrapper->getStudent($request->user()));

        return response()->json(
            new EnrollmentResource($enrollment->load(['period', 'pensum', 'student.user'])),
            201
        );
    }

    public function addDetail(
        Enrollment $enrollment,
        StoreEnrollmentDetailRequest $request,
        AddEnrollmentDetailAction $action,
        EnrollmentService $service,
        EnrollmentCacheManager $cache,
    ): JsonResponse {
        $wrapper = new EnrollmentDetailWrapper($request->validated());
        $subject = $wrapper->getSubject();
        $section = $wrapper->getSection();

        // Detect section change: existing non-rejected detail for this subject
        $existingDetail = $enrollment->details()
            ->where('subject_id', $subject->id)
            ->where('status', '!=', EnrollmentDetailStatus::Rejected->value)
            ->first();

        if ($existingDetail) {
            if ($existingDetail->section_id === $section->id) {
                // Idempotent: already enrolled in this exact section
                return response()->json(
                    (new EnrollmentDetailResource($existingDetail->load(['subject', 'section'])))->resolve(),
                    200
                );
            }

            // Section change: validate new section, then swap in-place (unique constraint forbids two rows)
            if ($enrollment->status->value !== 'draft') {
                return response()->json(['error' => 'La inscripción ya fue confirmada.'], 422);
            }
            if (! $service->hasQuota($section)) {
                return response()->json(['error' => 'No hay cupos disponibles en esta sección.'], 422);
            }

            $cache->incrementQuota($existingDetail->section);
            $existingDetail->update(['section_id' => $section->id]);
            $cache->decrementQuota($section);
            $service->updateEnrolledCredits($enrollment->fresh());

            return response()->json(
                (new EnrollmentDetailResource($existingDetail->load(['subject', 'section'])))->resolve(),
                200
            );
        }

        $validation = $service->validateAddSubject($enrollment, $subject, $section);
        if (! $validation['valid']) {
            return response()->json(['error' => $validation['error']], 422);
        }

        $detail = $action->handle($enrollment, $subject, $section);
        $service->updateEnrolledCredits($enrollment->fresh());

        return response()->json(
            (new EnrollmentDetailResource($detail->load(['subject', 'section'])))->resolve(),
            201
        );
    }

    public function removeDetail(
        Enrollment $enrollment,
        EnrollmentDetail $enrollmentDetail,
        EnrollmentCacheManager $cache,
        EnrollmentService $service,
    ): JsonResponse {
        Gate::authorize('delete', $enrollmentDetail);

        $cache->incrementQuota($enrollmentDetail->section);
        $enrollmentDetail->update(['status' => EnrollmentDetailStatus::Rejected]);
        $service->updateEnrolledCredits($enrollment->fresh());

        return response()->json(['success' => true]);
    }

    public function confirm(Enrollment $enrollment, ConfirmEnrollmentAction $action): JsonResponse
    {
        Gate::authorize('confirm', $enrollment);

        try {
            $enrollment = $action->handle($enrollment);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(
            new EnrollmentResource($enrollment->load(['details.subject', 'details.section', 'period', 'pensum', 'student.user']))
        );
    }

    private function resolveStudent(User $user, ?int $studentId): ?Student
    {
        // Estudiante autenticado directamente
        if ($user->student) {
            return $user->student->load(['pensum.career', 'user']);
        }

        // Guardian
        if ($user->guardian) {
            if ($studentId) {
                $student = $user->guardian->students()->find($studentId);
                abort_if(! $student, 403);

                return $student->load(['pensum.career', 'user']);
            }
            // Sin student_id: primer asignado
            $first = $user->guardian->students()->first();

            return $first?->load(['pensum.career', 'user']);
        }

        abort(403);
    }

    private function buildRules(?Period $period, ?Student $student): array
    {
        $periodLabel = $student?->academic_year ? "{$student->academic_year}er trimestre" : '';

        if ($period?->type === PeriodType::Year) {
            $lapse = $period->lapses()
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->first();

            $periodLabel = $lapse?->name ?? $periodLabel;
        }

        return [
            'period' => $period?->name,
            'deadline' => null,
            'days_left' => 0,
            'credits_min' => 12,
            'credits_max' => 24,
            'student_name' => $student?->user?->name ?? '',
            'student_code' => $student?->user?->email ?? '',
            'career' => $student?->pensum?->career?->name ?? '',
            'trimester' => $periodLabel,
        ];
    }
}
