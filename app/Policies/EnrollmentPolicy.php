<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Determine whether the user can view any enrollments.
     */
    public function viewAny(User $user): bool
    {
        return $user->student()->exists() || $user->guardian()->exists();
    }

    /**
     * Determine whether the user can view a specific enrollment.
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->student && $user->student->id === $enrollment->student_id) {
            return true;
        }

        if ($user->guardian) {
            return $user->guardian->students()
                ->where('id', $enrollment->student_id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create enrollments.
     * When $student is provided, checks that the guardian owns that student.
     */
    public function create(User $user, ?Student $student = null): bool
    {
        if ($student) {
            return $user->guardian?->students()
                ->where('id', $student->id)
                ->exists() ?? false;
        }

        return $user->student()->exists() || $user->guardian()->exists();
    }

    /**
     * Determine whether the user can update a specific enrollment.
     * Only draft enrollments can be updated.
     */
    public function update(User $user, Enrollment $enrollment): bool
    {
        if ($enrollment->status->value !== 'draft') {
            return false;
        }

        return $this->view($user, $enrollment);
    }

    /**
     * Determine whether the user can confirm a specific enrollment.
     * Only draft enrollments can be confirmed.
     */
    public function confirm(User $user, Enrollment $enrollment): bool
    {
        return $this->update($user, $enrollment);
    }

    /**
     * Determine whether the user can delete a specific enrollment.
     * Only draft enrollments can be deleted.
     */
    public function delete(User $user, Enrollment $enrollment): bool
    {
        if ($enrollment->status->value !== 'draft') {
            return false;
        }

        return $this->view($user, $enrollment);
    }
}
