<?php

namespace App\Http\Requests\Security;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('resetPassword', $this->route('user')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password_mode' => ['required', Rule::in(['link', 'manual', 'random'])],
            'password' => [
                Rule::requiredIf(fn () => $this->input('password_mode') === 'manual'),
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }
}
