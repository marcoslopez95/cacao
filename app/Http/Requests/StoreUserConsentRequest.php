<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === (int) $this->route('user')->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'policy_version' => ['required', 'string', 'max:20'],
            'accepts_data_processing' => ['required', 'boolean'],
            'accepts_image_use' => ['required', 'boolean'],
            'accepts_whatsapp_contact' => ['required', 'boolean'],
            'accepts_email_contact' => ['required', 'boolean'],
        ];
    }
}
