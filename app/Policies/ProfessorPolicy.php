<?php

namespace App\Policies;

use App\Models\Professor;
use App\Models\User;

class ProfessorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('professors.view');
    }

    public function create(User $user): bool
    {
        return $user->can('professors.create');
    }

    public function update(User $user, Professor $professor): bool
    {
        return $user->can('professors.update');
    }

    public function delete(User $user, Professor $professor): bool
    {
        return $user->can('professors.delete');
    }
}
