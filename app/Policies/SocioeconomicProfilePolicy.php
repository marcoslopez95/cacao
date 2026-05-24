<?php

namespace App\Policies;

use App\Models\SocioeconomicProfile;
use App\Models\User;

class SocioeconomicProfilePolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function update(User $user, SocioeconomicProfile $profile): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function view(User $user, SocioeconomicProfile $profile): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }
}
