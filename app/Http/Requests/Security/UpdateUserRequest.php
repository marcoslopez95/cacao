<?php

namespace App\Http\Requests\Security;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        if (! $target instanceof User) {
            return false;
        }

        return $this->user()?->can('update', $target) ?? false;
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
        /** @var User $target */
        $target = $this->route('user');

        return [
            'first_name' => ['required', 'string', 'min:1', 'max:100'],
            'last_name' => ['required', 'string', 'min:1', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($target->id)],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }
}
