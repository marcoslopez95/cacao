<?php

namespace App\Http\Requests\Academic;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCareerCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $careerCategory = $this->route('careerCategory');

        return $this->user()?->can('update', $careerCategory) ?? false;
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
        $careerCategory = $this->route('careerCategory');

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('career_categories', 'name')->ignore($careerCategory),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe una categoría con ese nombre.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
        ];
    }
}
