<?php

namespace App\Services;

use App\Exceptions\ConsentRequiredException;
use App\Models\User;
use App\Models\UserConsent;

class ConsentService
{
    public function hasActiveConsent(User $user): bool
    {
        return UserConsent::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('accepts_data_processing', true)
            ->exists();
    }

    public function requireConsent(User $user): void
    {
        if (! $this->hasActiveConsent($user)) {
            throw new ConsentRequiredException;
        }
    }
}
