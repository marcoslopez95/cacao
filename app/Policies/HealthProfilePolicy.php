<?php

namespace App\Policies;

use App\Models\HealthProfile;
use App\Models\User;

class HealthProfilePolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('Administrador');
    }

    public function update(User $user, HealthProfile $healthProfile): bool
    {
        return $user->hasRole('Administrador');
    }

    public function view(User $user, HealthProfile $healthProfile): bool
    {
        return $user->hasRole('Administrador');
    }
}
