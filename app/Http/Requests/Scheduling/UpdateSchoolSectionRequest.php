<?php

namespace App\Http\Requests\Scheduling;

use App\Enums\SectionType;
use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateSchoolSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('section'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'letter'          => ['required', 'string', 'max:1', 'regex:/^[A-Za-z]$/'],
            'capacity'        => ['required', 'integer', 'min:1'],
            'main_teacher_id' => ['nullable', 'integer', 'exists:professors,id'],
            'classroom_id'    => ['nullable', 'integer', 'exists:classrooms,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            /** @var Section $section */
            $section = $this->route('section');

            $duplicate = Section::where('type', SectionType::School)
                ->where('period_id', $section->period_id)
                ->where('pensum_id', $section->pensum_id)
                ->where('grade', $section->grade)
                ->where('letter', strtoupper($this->string('letter')))
                ->where('id', '!=', $section->id)
                ->exists();

            if ($duplicate) {
                $v->errors()->add('letter', 'Ya existe una sección con este grado y letra para el período y pensum seleccionados.');
            }
        });
    }
}
