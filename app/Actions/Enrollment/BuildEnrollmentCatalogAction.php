<?php

namespace App\Actions\Enrollment;

use App\Enums\EnrollmentDetailStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Period;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Enrollment\PrerequisiteValidator;
use Illuminate\Support\Collection;

class BuildEnrollmentCatalogAction
{
    public function __construct(
        private PrerequisiteValidator $validator,
    ) {}

    /**
     * Build the enrollment catalog: every pensum subject with at least one
     * available section (direct FK, University) or school section (pivot,
     * Primary/Secondary), enriched with prerequisite and selection state.
     *
     * @param  Student  $student  the student the catalog is built for
     * @param  Period  $period  the active period sections/schoolSections are filtered by
     * @param  Enrollment  $draft  the student's draft enrollment, used to resolve already-selected sections
     * @return Collection<int, array{
     *     subject: Subject,
     *     prereqs_ok: bool,
     *     completed: bool,
     *     recommended_trim: bool,
     *     selected_section_id: int|null,
     *     selected_detail_id: int|null,
     *     sections: Collection<int, Section>,
     * }>
     */
    public function handle(Student $student, Period $period, Enrollment $draft): Collection
    {
        $constraints = fn ($q) => $q
            ->where('period_id', $period->id)
            ->withCount([
                'enrollmentDetails as enrolled' => fn ($q) => $q->whereIn('status', [
                    EnrollmentDetailStatus::Draft->value,
                    EnrollmentDetailStatus::Confirmed->value,
                ]),
            ])
            ->with([
                'schedules.professor.user',
                'theoryClassroom',
                'labClassroom',
                'mainTeacher.user',
            ]);

        $subjects = $student->pensum->subjects()
            ->with(['sections' => $constraints, 'schoolSections' => $constraints])
            ->orderBy('period_number')
            ->get();

        $completedSubjectIds = EnrollmentDetail::whereHas(
            'enrollment',
            fn ($q) => $q->where('student_id', $student->id)->where('status', EnrollmentStatus::Approved->value)
        )
            ->where('status', EnrollmentDetailStatus::Confirmed->value)
            ->pluck('subject_id')
            ->toArray();

        $draft->loadMissing('details');
        $selectedSubjectIds = $draft->details
            ->whereNotIn('status', [EnrollmentDetailStatus::Rejected->value])
            ->keyBy('subject_id');

        return $subjects
            ->filter(fn ($subject) => $subject->sections->isNotEmpty() || $subject->schoolSections->isNotEmpty())
            ->map(function ($subject) use ($student, $completedSubjectIds, $selectedSubjectIds) {
                $allSections = $subject->sections->concat($subject->schoolSections);
                $selectedDetail = $selectedSubjectIds->get($subject->id);

                return [
                    'subject' => $subject,
                    'prereqs_ok' => $this->validator->canTake($student, $subject),
                    'completed' => in_array($subject->id, $completedSubjectIds),
                    'recommended_trim' => $subject->period_number === $student->academic_year,
                    'selected_section_id' => $selectedDetail?->section_id,
                    'selected_detail_id' => $selectedDetail?->id,
                    'sections' => $allSections,
                ];
            })
            ->values();
    }
}
