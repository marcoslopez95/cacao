<?php

namespace App\Http\Requests\Admin;

use App\Models\HealthProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreHealthProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('create', HealthProfile::class);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'blood_type_id' => ['nullable', 'integer', 'exists:blood_types,id'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'has_disability' => ['nullable', 'boolean'],
            'disability_type_id' => ['nullable', 'integer', 'exists:disability_types,id'],
            'disability_description' => ['nullable', 'string'],
            'has_special_needs' => ['nullable', 'boolean'],
            'special_needs_description' => ['nullable', 'string'],
            'chronic_condition' => ['nullable', 'string'],
            'regular_medication' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'has_medical_insurance' => ['nullable', 'boolean'],
            'insurance_type_id' => ['nullable', 'integer', 'exists:insurance_types,id'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_phone_dial' => ['nullable', 'string', 'max:10'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
        ];
    }
}
