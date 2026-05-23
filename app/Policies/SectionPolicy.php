<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

class SectionPolicy
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

    public function viewAny(User $user): bool
    {
        return $user->can('sections.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sections.create');
    }

    public function update(User $user, Section $section): bool
    {
        return $user->can('sections.update');
    }

    public function delete(User $user, Section $section): bool
    {
        return $user->can('sections.delete');
    }
}
