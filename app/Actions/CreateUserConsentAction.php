<?php

namespace App\Actions;

use App\Http\Wrappers\StoreUserConsentWrapper;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Http\Request;

class CreateUserConsentAction
{
    public function handle(User $user, StoreUserConsentWrapper $wrapper, Request $request): UserConsent
    {
        return UserConsent::create([
            'user_id' => $user->id,
            'policy_version' => $wrapper->getPolicyVersion(),
            'accepts_data_processing' => $wrapper->acceptsDataProcessing(),
            'accepts_image_use' => $wrapper->acceptsImageUse(),
            'accepts_whatsapp_contact' => $wrapper->acceptsWhatsappContact(),
            'accepts_email_contact' => $wrapper->acceptsEmailContact(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'granted_at' => now(),
            'revoked_at' => null,
        ]);
    }
}
