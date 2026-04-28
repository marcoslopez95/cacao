<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('period')) ?? false;
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
            'name'       => ['required', 'string', 'max:20', Rule::unique('periods', 'name')->ignore($this->route('period'))],
            'type'       => ['required', Rule::in(['semester', 'year', 'trimester'])],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'    => 'Ya existe un período con ese nombre.',
            'end_date.after' => 'La fecha de fin debe ser posterior a la de inicio.',
        ];
    }
}
