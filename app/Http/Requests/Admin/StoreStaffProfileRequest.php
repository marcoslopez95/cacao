<?php

namespace App\Http\Requests\Admin;

use App\Models\StaffProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreStaffProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('create', StaffProfile::class);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'academic_title' => ['nullable', 'string', 'max:100'],
            'specialty' => ['nullable', 'string', 'max:150'],
            'contract_type_id' => ['required', 'integer', 'exists:contract_types,id'],
            'dedication_type_id' => ['required', 'integer', 'exists:dedication_types,id'],
            'weekly_hour_load' => ['nullable', 'integer', 'min:1', 'max:40'],
            'hire_date' => ['required', 'date'],
            'termination_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'employment_status_id' => ['required', 'integer', 'exists:employment_statuses,id'],
            'is_coordinator' => ['boolean'],
            'coordinated_department_id' => ['nullable', 'integer', 'exists:departments,id', 'required_if:is_coordinator,true'],
            'coordinator_since' => ['nullable', 'date', 'required_if:is_coordinator,true'],
        ];
    }
}
