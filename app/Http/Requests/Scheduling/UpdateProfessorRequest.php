<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('professor')) ?? false;
    }

    public function rules(): array
    {
        return [
            'weekly_hour_limit' => ['required', 'integer', 'min:1', 'max:60'],
            'active'            => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'weekly_hour_limit.required' => 'El límite de horas es obligatorio.',
            'weekly_hour_limit.min'      => 'El límite de horas debe ser al menos 1.',
            'weekly_hour_limit.max'      => 'El límite de horas no puede superar 60.',
            'active.required'            => 'El estado es obligatorio.',
            'active.boolean'             => 'El estado debe ser verdadero o falso.',
        ];
    }
}
