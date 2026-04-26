<?php

namespace App\Http\Requests\Academic;

use App\Models\Pensum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePensumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Pensum::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'period_type' => ['required', 'string', Rule::in(['semester', 'year'])],
            'total_periods' => ['required', 'integer', 'min:1', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'period_type.required' => 'El tipo de período es obligatorio.',
            'period_type.in' => 'El tipo de período debe ser semestral o anual.',
            'total_periods.required' => 'El total de períodos es obligatorio.',
            'total_periods.min' => 'El total de períodos debe ser al menos 1.',
            'total_periods.max' => 'El total de períodos no puede superar 20.',
        ];
    }
}
