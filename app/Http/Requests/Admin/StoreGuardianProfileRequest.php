<?php

namespace App\Http\Requests\Admin;

use App\Models\Guardian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreGuardianProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Guardian $guardian */
        $guardian = $this->route('guardian');
        $user = $this->user();

        // The guardian may edit their own profile without going through the Gate.
        if ($user->guardian?->id === $guardian->id) {
            return true;
        }

        // Admins are short-circuited by Gate::before; coordinators pass via GuardianPolicy.
        Gate::authorize('update', $guardian);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'occupation' => ['nullable', 'string', 'max:150'],
            'employer' => ['nullable', 'string', 'max:200'],
            'work_phone' => ['nullable', 'string', 'max:20'],
            'work_phone_dial' => ['nullable', 'string', 'max:10'],
            'education_level_id' => ['nullable', 'integer', Rule::exists('education_levels', 'id')],
            'marital_status_id' => ['nullable', 'integer', Rule::exists('marital_statuses', 'id')],
        ];
    }
}
