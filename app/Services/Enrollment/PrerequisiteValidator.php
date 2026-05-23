<?php

namespace App\Services\Enrollment;

use App\Enums\EnrollmentDetailStatus;
use App\Enums\EnrollmentStatus;
use App\Models\EnrollmentDetail;
use App\Models\Student;
use App\Models\Subject;

class PrerequisiteValidator
{
    public function canTake(Student $student, Subject $subject): bool
    {
        $prerequisites = $subject->prerequisites()->pluck('id')->toArray();

        if (empty($prerequisites)) {
            return true;
        }

        $passedIds = $this->getPassedSubjectIds($student, $prerequisites);

        return count($passedIds) === count($prerequisites);
    }

    public function getMissingPrerequisites(Student $student, Subject $subject): array
    {
        $prerequisites = $subject->prerequisites()->pluck('id')->toArray();

        if (empty($prerequisites)) {
            return [];
        }

        $passedIds = $this->getPassedSubjectIds($student, $prerequisites);

        return array_diff($prerequisites, $passedIds);
    }

    /**
     * @param  int[]  $subjectIds
     * @return int[]
     */
    private function getPassedSubjectIds(Student $student, array $subjectIds): array
    {
        return EnrollmentDetail::whereHas(
            'enrollment',
            fn ($q) => $q->where('student_id', $student->id)
                ->where('status', EnrollmentStatus::Approved->value)
        )
            ->whereIn('subject_id', $subjectIds)
            ->where('status', EnrollmentDetailStatus::Confirmed->value)
            ->pluck('subject_id')
            ->toArray();
    }
}
