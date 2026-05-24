<?php

namespace App\Policies;

use App\Models\HousingProfile;
use App\Models\User;

class HousingProfilePolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function update(User $user, HousingProfile $housingProfile): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function view(User $user, HousingProfile $housingProfile): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }
}
