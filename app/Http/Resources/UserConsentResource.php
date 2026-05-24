<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserConsentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'policy_version' => $this->policy_version,
            'accepts_data_processing' => $this->accepts_data_processing,
            'accepts_image_use' => $this->accepts_image_use,
            'accepts_whatsapp_contact' => $this->accepts_whatsapp_contact,
            'accepts_email_contact' => $this->accepts_email_contact,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'granted_at' => $this->granted_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
        ];
    }
}
