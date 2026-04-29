<?php

namespace App\Http\Requests\Scheduling;

use App\Enums\PeriodType;
use App\Enums\SectionType;
use App\Models\Period;
use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreSchoolSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Section::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period_id'       => ['required', 'integer', 'exists:periods,id'],
            'pensum_id'       => ['required', 'integer', 'exists:pensums,id'],
            'grade'           => ['required', 'integer', 'min:1', 'max:12'],
            'letter'          => ['required', 'string', 'max:1', 'regex:/^[A-Za-z]$/'],
            'capacity'        => ['required', 'integer', 'min:1'],
            'main_teacher_id' => ['nullable', 'integer', 'exists:professors,id'],
            'classroom_id'    => ['nullable', 'integer', 'exists:classrooms,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $period = Period::find($this->integer('period_id'));

            if ($period && $period->type !== PeriodType::Year) {
                $v->errors()->add('period_id', 'Las secciones escolares solo pueden pertenecer a períodos anuales.');
            }

            $duplicate = Section::where('type', SectionType::School)
                ->where('period_id', $this->integer('period_id'))
                ->where('pensum_id', $this->integer('pensum_id'))
                ->where('grade', $this->integer('grade'))
                ->where('letter', strtoupper($this->string('letter')))
                ->exists();

            if ($duplicate) {
                $v->errors()->add('letter', 'Ya existe una sección con este grado y letra para el período y pensum seleccionados.');
            }
        });
    }
}
