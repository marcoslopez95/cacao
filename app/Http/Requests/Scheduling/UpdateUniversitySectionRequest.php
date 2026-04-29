<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUniversitySectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('section')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => trim($this->input('code'))]);
        }
    }

    public function rules(): array
    {
        $section = $this->route('section');

        return [
            'code'                => [
                'required',
                'string',
                'max:10',
                Rule::unique('sections')->where(fn ($q) => $q
                    ->where('period_id', $section->period_id)
                    ->where('subject_id', $section->subject_id)
                )->ignore($section->id),
            ],
            'capacity'            => ['required', 'integer', 'min:1'],
            'theory_classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
            'lab_classroom_id'    => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'     => 'El código de sección es obligatorio.',
            'code.max'          => 'El código no puede superar 10 caracteres.',
            'code.unique'       => 'Ya existe una sección con ese código para esta materia en este período.',
            'capacity.required' => 'El cupo es obligatorio.',
            'capacity.min'      => 'El cupo debe ser al menos 1.',
        ];
    }
}
