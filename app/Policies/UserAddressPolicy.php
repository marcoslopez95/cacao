<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserAddress;

class UserAddressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function update(User $user, UserAddress $address): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }

    public function delete(User $user, UserAddress $address): bool
    {
        return $user->hasAnyRole(['Administrador', 'Coordinador']);
    }
}
