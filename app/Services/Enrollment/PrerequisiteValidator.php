<?php

namespace App\Services\Enrollment;

use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class PrerequisiteValidator
{
    public function canTake(Student $student, Subject $subject): bool
    {
        $prerequisites = $subject->prerequisites()->pluck('id')->toArray();

        if (empty($prerequisites)) {
            return true;
        }

        $passedSubjects = DB::table('grades')
            ->where('student_id', $student->id)
            ->whereIn('subject_id', $prerequisites)
            ->where('grade', '>=', 10)
            ->pluck('subject_id')
            ->toArray();

        return count($passedSubjects) === count($prerequisites);
    }

    public function getMissingPrerequisites(Student $student, Subject $subject): array
    {
        $prerequisites = $subject->prerequisites()->pluck('id')->toArray();

        if (empty($prerequisites)) {
            return [];
        }

        $passedSubjects = DB::table('grades')
            ->where('student_id', $student->id)
            ->whereIn('subject_id', $prerequisites)
            ->where('grade', '>=', 10)
            ->pluck('subject_id')
            ->toArray();

        return array_diff($prerequisites, $passedSubjects);
    }
}
