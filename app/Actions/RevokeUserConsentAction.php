<?php

namespace App\Actions;

use App\Models\UserConsent;

class RevokeUserConsentAction
{
    public function handle(UserConsent $consent): UserConsent
    {
        $consent->revoked_at = now();
        $consent->save();

        return $consent;
    }
}
