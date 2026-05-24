<?php

namespace App\Policies;

use App\Models\FamilyProfile;
use App\Models\User;

class FamilyProfilePolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function update(User $user, FamilyProfile $fp): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function view(User $user, FamilyProfile $fp): bool
    {
        if ($user->hasAnyRole(['Administrador', 'Coordinador'])) {
            return true;
        }

        return $user->student?->id === $fp->student_id;
    }
}
