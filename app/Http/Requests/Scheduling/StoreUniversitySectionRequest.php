<?php

namespace App\Http\Requests\Scheduling;

use App\Enums\PeriodType;
use App\Models\Period;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUniversitySectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Section::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => trim($this->input('code'))]);
        }
    }

    public function rules(): array
    {
        return [
            'period_id'           => ['required', 'integer', Rule::exists('periods', 'id')],
            'subject_id'          => ['required', 'integer', Rule::exists('subjects', 'id')],
            'code'                => [
                'required',
                'string',
                'max:10',
                Rule::unique('sections')->where(fn ($q) => $q
                    ->where('period_id', $this->input('period_id'))
                    ->where('subject_id', $this->input('subject_id'))
                ),
            ],
            'capacity'            => ['required', 'integer', 'min:1'],
            'theory_classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
            'lab_classroom_id'    => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v) {
            $period = Period::find($this->input('period_id'));

            if (! $period) {
                return;
            }

            if (! in_array($period->type, [PeriodType::Semester, PeriodType::Trimester])) {
                $v->errors()->add('period_id', 'Las secciones universitarias solo pueden pertenecer a períodos semestrales o trimestrales.');

                return;
            }

            $subject = Subject::with('pensum')->find($this->input('subject_id'));

            if ($subject?->pensum && $subject->pensum->period_type !== $period->type->value) {
                $v->errors()->add('subject_id', 'La materia no corresponde al tipo de período seleccionado.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'period_id.required'  => 'El período es obligatorio.',
            'period_id.exists'    => 'El período seleccionado no existe.',
            'subject_id.required' => 'La materia es obligatoria.',
            'subject_id.exists'   => 'La materia seleccionada no existe.',
            'code.required'       => 'El código de sección es obligatorio.',
            'code.max'            => 'El código no puede superar 10 caracteres.',
            'code.unique'         => 'Ya existe una sección con ese código para esta materia en este período.',
            'capacity.required'   => 'El cupo es obligatorio.',
            'capacity.min'        => 'El cupo debe ser al menos 1.',
        ];
    }
}
