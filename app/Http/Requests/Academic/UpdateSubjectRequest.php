<?php

namespace App\Http\Requests\Academic;

use App\Models\Pensum;
use App\Models\Subject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subject = $this->route('subject');

        return $this->user()?->can('update', $subject) ?? false;
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
        /** @var Subject $subject */
        $subject = $this->route('subject');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('subjects')->where('pensum_id', $subject->pensum_id)->ignore($subject->id)],
            'credits_uc' => ['required', 'integer', 'min:1', 'max:20'],
            'period_number' => ['required', 'integer', 'min:1', 'max:'.$pensum->total_periods],
            'description' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            /** @var Subject $subject */
            $subject = $this->route('subject');
            $newPeriod = (int) $this->input('period_number');

            if ($subject->period_number !== $newPeriod) {
                $hasIncompatible = $subject->prerequisites()
                    ->where('period_number', '>=', $newPeriod)
                    ->exists();

                if ($hasIncompatible) {
                    $v->errors()->add('period_number', 'Elimina los prerrequisitos incompatibles antes de cambiar el período.');
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'code.required' => 'El código es obligatorio.',
            'code.max' => 'El código no puede superar los 20 caracteres.',
            'code.unique' => 'Este código ya está en uso dentro del pensum.',
            'credits_uc.required' => 'Las unidades crédito son obligatorias.',
            'credits_uc.min' => 'Las unidades crédito deben ser al menos 1.',
            'credits_uc.max' => 'Las unidades crédito no pueden superar 20.',
            'period_number.required' => 'El período es obligatorio.',
            'period_number.min' => 'El período debe ser al menos 1.',
            'period_number.max' => 'El período supera el total de períodos del pensum.',
        ];
    }
}
