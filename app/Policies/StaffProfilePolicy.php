<?php

namespace App\Policies;

use App\Models\StaffProfile;
use App\Models\User;

class StaffProfilePolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function update(User $user, StaffProfile $profile): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }
}
