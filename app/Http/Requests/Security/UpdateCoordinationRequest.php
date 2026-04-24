<?php

namespace App\Http\Requests\Security;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCoordinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $coordination = $this->route('coordination');

        return $this->user()?->can('update', $coordination) ?? false;
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
        $type = $this->input('type');

        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'type' => ['sometimes', 'required', Rule::in(['career', 'grade', 'academic'])],
            'education_level' => ['sometimes', 'required', Rule::in(['university', 'secondary'])],
            'secondary_type' => [
                Rule::requiredIf($type === 'grade'),
                'nullable',
                Rule::in(['media_general', 'bachillerato']),
            ],
            'career_id' => [
                'nullable',
                'integer',
            ],
            'grade_year' => [
                Rule::requiredIf($type === 'grade'),
                'nullable',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null) {
                        return;
                    }
                    $secondaryType = $this->input('secondary_type');
                    $max = $secondaryType === 'bachillerato' ? 6 : 5;
                    if ($value > $max) {
                        $fail("El año escolar no puede ser mayor a {$max} para el tipo seleccionado.");
                    }
                },
            ],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
