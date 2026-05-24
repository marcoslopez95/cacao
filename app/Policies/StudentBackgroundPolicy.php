<?php

namespace App\Policies;

use App\Models\StudentBackground;
use App\Models\User;

class StudentBackgroundPolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function update(User $user, StudentBackground $bg): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function view(User $user, StudentBackground $bg): bool
    {
        if ($user->hasAnyRole(['Administrador', 'Coordinador'])) {
            return true;
        }

        return $user->student?->id === $bg->student_id;
    }
}
