<?php

namespace App\Policies;

use App\Models\Coordination;
use App\Models\User;

class CoordinationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('coordinations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('coordinations.create');
    }

    public function update(User $user, Coordination $coordination): bool
    {
        return $user->can('coordinations.edit');
    }

    public function delete(User $user, Coordination $coordination): bool
    {
        return $user->can('coordinations.delete');
    }

    public function assign(User $user, Coordination $coordination): bool
    {
        return $user->can('coordinations.assign');
    }

    public function viewHistory(User $user, Coordination $coordination): bool
    {
        return $user->can('coordinations.view_history');
    }
}
