<?php

namespace App\Http\Requests\Security;

use App\Models\Coordination;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoordinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Coordination::class) ?? false;
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
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['required', Rule::in(['career', 'grade', 'academic'])],
            'education_level' => ['required', Rule::in(['university', 'secondary'])],
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => 'El tipo de coordinación no es válido.',
            'education_level.in' => 'El nivel educativo no es válido.',
            'secondary_type.in' => 'El tipo de educación media no es válido.',
            'grade_year.min' => 'El año escolar debe ser al menos 1.',
        ];
    }
}
