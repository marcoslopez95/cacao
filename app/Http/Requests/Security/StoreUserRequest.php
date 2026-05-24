<?php

namespace App\Http\Requests\Security;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Support legacy 'name' field by splitting it into first_name/last_name.
        if ($this->has('name') && ! $this->has('first_name')) {
            $name = trim((string) $this->input('name'));
            $parts = explode(' ', $name, 2);
            $this->merge([
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? '',
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:1', 'max:100'],
            'last_name' => ['required', 'string', 'min:1', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
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
