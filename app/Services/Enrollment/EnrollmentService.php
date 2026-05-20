<?php

namespace App\Services\Enrollment;

use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Subject;

class EnrollmentService
{
    public function __construct(
        private PrerequisiteValidator $validator,
        private EnrollmentCacheManager $cache,
    ) {}

    public function calculateEnrolledCredits(Enrollment $enrollment): int
    {
        return (int) $enrollment->confirmedDetails()
            ->join('subjects', 'enrollment_details.subject_id', '=', 'subjects.id')
            ->sum('subjects.credits_uc');
    }

    public function hasQuota(Section $section): bool
    {
        return $this->cache->getAvailableQuota($section) > 0;
    }

    public function isAlreadyEnrolled(Enrollment $enrollment, Subject $subject): bool
    {
        return $enrollment->details()
            ->where('subject_id', $subject->id)
            ->exists();
    }

    /**
     * Validate if a student can add a subject to their enrollment.
     *
     * @return array{valid: bool, error: ?string}
     */
    public function validateAddSubject(
        Enrollment $enrollment,
        Subject $subject,
        Section $section
    ): array {
        if ($enrollment->status->value !== 'draft') {
            return ['valid' => false, 'error' => 'La inscripción ya fue confirmada.'];
        }

        if (! $this->hasQuota($section)) {
            return ['valid' => false, 'error' => 'No hay cupos disponibles en esta sección.'];
        }

        if ($this->isAlreadyEnrolled($enrollment, $subject)) {
            return ['valid' => false, 'error' => 'Ya estás inscrito en esta materia.'];
        }

        if (! $this->validator->canTake($enrollment->student, $subject)) {
            $missing = $this->validator->getMissingPrerequisites($enrollment->student, $subject);
            $missingCodes = Subject::whereIn('id', $missing)->pluck('code')->join(', ');

            return ['valid' => false, 'error' => "Prerrequisitos no cumplidos: {$missingCodes}"];
        }

        $pensum = $this->cache->getActivePensum($enrollment->student);
        if (! $pensum || ! in_array($subject->id, $pensum['subjects'])) {
            return ['valid' => false, 'error' => 'La materia no está en el pensum del estudiante.'];
        }

        return ['valid' => true, 'error' => null];
    }

    public function updateEnrolledCredits(Enrollment $enrollment): void
    {
        $enrollment->update(['uc_inscritas' => $this->calculateEnrolledCredits($enrollment)]);
    }
}
