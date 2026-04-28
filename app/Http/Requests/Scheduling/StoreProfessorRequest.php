<?php

namespace App\Http\Requests\Scheduling;

use App\Models\Professor;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Professor::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id'           => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('professors', 'user_id'),
            ],
            'weekly_hour_limit' => ['required', 'integer', 'min:1', 'max:60'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v) {
            $userId = $this->input('user_id');

            if ($userId && ! User::find($userId)?->hasRole('Profesor')) {
                $v->errors()->add('user_id', 'El usuario seleccionado no tiene el rol Profesor.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'user_id.required'           => 'El usuario es obligatorio.',
            'user_id.exists'             => 'El usuario seleccionado no existe.',
            'user_id.unique'             => 'Este usuario ya tiene un perfil de profesor.',
            'weekly_hour_limit.required' => 'El límite de horas es obligatorio.',
            'weekly_hour_limit.min'      => 'El límite de horas debe ser al menos 1.',
            'weekly_hour_limit.max'      => 'El límite de horas no puede superar 60.',
        ];
    }
}
