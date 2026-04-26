<?php

namespace App\Http\Requests\Academic;

use App\Models\Pensum;
use App\Models\Subject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Subject::class) ?? false;
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
        /** @var Pensum $pensum */
        $pensum = $this->route('pensum');

        return [
            'name' => ['required', 'string', 'max:255'],
            'credits_uc' => ['required', 'integer', 'min:1', 'max:20'],
            'period_number' => ['required', 'integer', 'min:1', 'max:'.$pensum->total_periods],
            'description' => ['nullable', 'string'],
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
            'credits_uc.required' => 'Las unidades crédito son obligatorias.',
            'credits_uc.min' => 'Las unidades crédito deben ser al menos 1.',
            'credits_uc.max' => 'Las unidades crédito no pueden superar 20.',
            'period_number.required' => 'El período es obligatorio.',
            'period_number.min' => 'El período debe ser al menos 1.',
            'period_number.max' => 'El período supera el total de períodos del pensum.',
        ];
    }
}
