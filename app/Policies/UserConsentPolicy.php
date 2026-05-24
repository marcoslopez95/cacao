<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserConsent;

class UserConsentPolicy
{
    public function create(User $user, UserConsent $model): bool
    {
        return true;
    }

    public function revoke(User $user, UserConsent $consent): bool
    {
        return $user->id === $consent->user_id || $user->hasRole('Administrador');
    }
}
