<?php

namespace App\Http\Requests\Admin;

use App\Models\DemographicProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDemographicProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('create', DemographicProfile::class);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'birth_city' => ['nullable', 'string', 'max:100'],
            'birth_state_id' => ['nullable', 'integer', 'exists:states,id'],
            'birth_country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'is_indigenous' => ['nullable', 'boolean'],
            'indigenous_community' => ['nullable', 'string', 'max:100'],
            'native_language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'is_returned_migrant' => ['nullable', 'boolean'],
            'previous_country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'religion_id' => ['nullable', 'integer', 'exists:religions,id'],
            'practices_sport' => ['nullable', 'boolean'],
            'sport' => ['nullable', 'string', 'max:100'],
            'cultural_activities' => ['nullable', 'string'],
        ];
    }
}
