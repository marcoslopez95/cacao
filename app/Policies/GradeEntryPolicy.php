<?php

namespace App\Policies;

use App\Models\GradeEntry;
use App\Models\Section;
use App\Models\User;

class GradeEntryPolicy
{
    public function upsert(User $user, Section $section): bool
    {
        $professor = $user->professor;

        return $professor !== null
            && $section->main_teacher_id === $professor->id;
    }

    public function publish(User $user, Section $section): bool
    {
        return $this->upsert($user, $section);
    }

    public function view(User $user, GradeEntry $entry): bool
    {
        $student = $user->student;

        return $student !== null
            && $entry->enrollmentDetail->enrollment->student_id === $student->id;
    }
}
