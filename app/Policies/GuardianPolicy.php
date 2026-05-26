<?php

namespace App\Policies;

use App\Models\Guardian;
use App\Models\User;

class GuardianPolicy
{
    /**
     * Coordinadores may update guardian records.
     * Admins are short-circuited by Gate::before before this policy is reached.
     */
    public function update(User $user, Guardian $guardian): bool
    {
        return $user->hasRole('Coordinador');
    }
}
