<?php

namespace App\Http\Requests\Admin;

use App\Models\StudentBackground;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreStudentBackgroundRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('create', StudentBackground::class);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'previous_institution' => ['nullable', 'string', 'max:200'],
            'institution_type_id' => ['nullable', 'integer', 'exists:institution_types,id'],
            'graduation_year' => ['nullable', 'integer', 'digits:4', 'min:1900', 'max:2099'],
            'previous_gpa' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'repeated_grade' => ['nullable', 'boolean'],
            'repeated_grade_description' => ['nullable', 'string', 'max:100'],
            'transfer_reason_id' => ['nullable', 'integer', 'exists:transfer_reasons,id'],
            'has_prior_studies' => ['nullable', 'boolean'],
            'prior_studies_description' => ['nullable', 'string'],
            'digital_level_id' => ['nullable', 'integer', 'exists:digital_levels,id'],
            'mother_education_level_id' => ['nullable', 'integer', 'exists:education_levels,id'],
            'father_education_level_id' => ['nullable', 'integer', 'exists:education_levels,id'],
        ];
    }
}
