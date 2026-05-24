<?php

namespace App\Http\Requests\Admin;

use App\Models\SocioeconomicProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreSocioeconomicProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('create', SocioeconomicProfile::class);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'study_date' => ['required', 'date'],
            'income_range_id' => ['nullable', 'integer', 'exists:income_ranges,id'],
            'income_source_id' => ['nullable', 'integer', 'exists:income_sources,id'],
            'household_earners' => ['nullable', 'integer', 'min:0', 'max:50'],
            'receives_remittances' => ['nullable', 'boolean'],
            'remittance_country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'student_works' => ['nullable', 'boolean'],
            'employment_type_id' => ['nullable', 'integer', 'exists:employment_types,id'],
            'weekly_work_hours' => ['nullable', 'integer', 'min:0', 'max:168'],
            'has_scholarship' => ['nullable', 'boolean'],
            'scholarship_name' => ['nullable', 'string', 'max:150'],
            'has_institutional_benefit' => ['nullable', 'boolean'],
        ];
    }
}
