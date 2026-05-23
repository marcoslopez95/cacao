<?php

namespace App\Policies;

use App\Models\GradeConfig;
use App\Models\User;

class GradeConfigPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrador');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrador');
    }

    public function update(User $user, GradeConfig $gradeConfig): bool
    {
        return $user->hasRole('Administrador');
    }
}
