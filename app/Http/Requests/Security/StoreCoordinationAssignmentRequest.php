<?php

namespace App\Http\Requests\Security;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCoordinationAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $coordination = $this->route('coordination');

        return $this->user()?->can('assign', $coordination) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $user = User::find($value);
                    if (! $user || ! $user->hasRole(Role::Coordinator->value)) {
                        $fail('El usuario seleccionado no tiene el rol de Coordinador de Área.');
                    }
                },
            ],
        ];
    }
}
