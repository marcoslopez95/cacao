<?php

namespace App\Http\Requests\Academic;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCareerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $career = $this->route('career');

        return $this->user()?->can('update', $career) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }

        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $career = $this->route('career');

        return [
            'career_category_id' => ['required', 'integer', Rule::exists('career_categories', 'id')],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'code' => ['required', 'string', 'max:10', Rule::unique('careers', 'code')->ignore($career)],
            'active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'career_category_id.required' => 'Debes seleccionar una categoría.',
            'career_category_id.exists' => 'La categoría seleccionada no existe.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'code.unique' => 'Ya existe una carrera con ese código.',
            'code.max' => 'El código no puede superar los 10 caracteres.',
        ];
    }
}
