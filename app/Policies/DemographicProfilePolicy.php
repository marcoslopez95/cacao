<?php

namespace App\Policies;

use App\Models\DemographicProfile;
use App\Models\User;

class DemographicProfilePolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function update(User $user, DemographicProfile $dp): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function view(User $user, DemographicProfile $dp): bool
    {
        if ($user->hasAnyRole(['Administrador', 'Coordinador'])) {
            return true;
        }

        return $user->id === $dp->user_id;
    }
}
