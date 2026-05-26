<?php

namespace App\Policies;

use App\Models\User;

class StudentLanguagePolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('Coordinador');
    }

    public function delete(User $user): bool
    {
        return $user->hasRole('Coordinador');
    }
}
