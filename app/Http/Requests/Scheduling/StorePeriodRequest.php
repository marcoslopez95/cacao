<?php

namespace App\Http\Requests\Scheduling;

use App\Models\Period;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Period::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:20', 'unique:periods,name'],
            'type'       => ['required', Rule::in(['semester', 'year', 'trimester'])],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'El nombre del período es obligatorio.',
            'name.max'            => 'El nombre no puede superar 20 caracteres.',
            'name.unique'         => 'Ya existe un período con ese nombre.',
            'type.required'       => 'El tipo de período es obligatorio.',
            'type.in'             => 'El tipo seleccionado no es válido.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'end_date.required'   => 'La fecha de fin es obligatoria.',
            'end_date.after'      => 'La fecha de fin debe ser posterior a la de inicio.',
        ];
    }
}
