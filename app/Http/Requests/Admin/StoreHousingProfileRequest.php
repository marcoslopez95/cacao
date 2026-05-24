<?php

namespace App\Http\Requests\Admin;

use App\Models\HousingProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreHousingProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('create', HousingProfile::class);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'housing_type_id' => ['nullable', 'integer', 'exists:housing_types,id'],
            'tenure_type_id' => ['nullable', 'integer', 'exists:tenure_types,id'],
            'construction_material_id' => ['nullable', 'integer', 'exists:construction_materials,id'],
            'room_count' => ['nullable', 'integer', 'min:0'],
            'bathroom_count' => ['nullable', 'integer', 'min:0'],
            'household_members' => ['nullable', 'integer', 'min:0'],
            'commute_time_id' => ['nullable', 'integer', 'exists:commute_times,id'],
            'transport_type_id' => ['nullable', 'integer', 'exists:transport_types,id'],
            'services' => ['nullable', 'array'],
            'services.*' => ['array'],
            'services.*.basic_service_id' => ['required', 'integer', 'exists:basic_services,id'],
            'services.*.is_available' => ['required', 'boolean'],
        ];
    }
}
