<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('classrooms.view');
    }

    public function create(User $user): bool
    {
        return $user->can('classrooms.create');
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return $user->can('classrooms.update');
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return $user->can('classrooms.delete');
    }
}
