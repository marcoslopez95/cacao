<?php

namespace App\Http\Wrappers;

use Illuminate\Support\Collection;

class StoreUserConsentWrapper extends Collection
{
    public function getPolicyVersion(): string
    {
        return $this->get('policy_version');
    }

    public function acceptsDataProcessing(): bool
    {
        return (bool) $this->get('accepts_data_processing');
    }

    public function acceptsImageUse(): bool
    {
        return (bool) $this->get('accepts_image_use');
    }

    public function acceptsWhatsappContact(): bool
    {
        return (bool) $this->get('accepts_whatsapp_contact');
    }

    public function acceptsEmailContact(): bool
    {
        return (bool) $this->get('accepts_email_contact');
    }
}
