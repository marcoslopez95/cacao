<?php

namespace App\Policies\Academic;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('subjects.view');
    }

    public function create(User $user): bool
    {
        return $user->can('subjects.create');
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->can('subjects.update');
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->can('subjects.delete');
    }

    public function managePrerequisites(User $user, Subject $subject): bool
    {
        return $user->can('subjects.manage-prerequisites');
    }
}
