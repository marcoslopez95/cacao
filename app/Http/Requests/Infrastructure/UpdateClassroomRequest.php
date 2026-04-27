<?php

namespace App\Http\Requests\Infrastructure;

use App\Enums\ClassroomType;
use App\Models\Classroom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Classroom $classroom */
        $classroom = $this->route('classroom');

        return $this->user()?->can('update', $classroom) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('identifier'))) {
            $this->merge(['identifier' => trim($this->input('identifier'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Classroom $classroom */
        $classroom = $this->route('classroom');

        return [
            'building_id' => ['required', 'integer', 'exists:buildings,id'],
            'identifier'  => [
                'required',
                'string',
                'max:50',
                Rule::unique('classrooms')->where('building_id', $this->input('building_id'))->ignore($classroom->id),
            ],
            'type'        => ['required', Rule::enum(ClassroomType::class)],
            'capacity'    => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'building_id.required' => 'El edificio es obligatorio.',
            'building_id.exists'   => 'El edificio seleccionado no existe.',
            'identifier.required'  => 'El identificador es obligatorio.',
            'identifier.max'       => 'El identificador no puede superar los 50 caracteres.',
            'identifier.unique'    => 'Ya existe un aula con este identificador en el edificio seleccionado.',
            'type.required'        => 'El tipo de aula es obligatorio.',
            'type.in'              => 'El tipo debe ser "Teórica" o "Laboratorio".',
            'capacity.required'    => 'La capacidad es obligatoria.',
            'capacity.min'         => 'La capacidad debe ser al menos 1.',
            'capacity.max'         => 'La capacidad no puede superar 500.',
        ];
    }
}
