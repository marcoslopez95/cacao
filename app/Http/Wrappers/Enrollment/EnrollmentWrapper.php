<?php

namespace App\Http\Wrappers\Enrollment;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

class EnrollmentWrapper extends Collection
{
    public function getStudentId(): ?int
    {
        return $this->get('student_id') ? (int) $this->get('student_id') : null;
    }

    public function getStudent(User $user): Student
    {
        if ($studentId = $this->getStudentId()) {
            return Student::findOrFail($studentId);
        }

        return $user->student;
    }
}
