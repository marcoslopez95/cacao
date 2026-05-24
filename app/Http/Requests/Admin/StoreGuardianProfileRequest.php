<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuardianProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $guardian = $this->route('guardian');
        $user = $this->user();

        if ($user->hasAnyRole(['Administrador', 'Coordinador'])) {
            return true;
        }

        return $user->guardian?->id === $guardian->id;
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
            'education_level_id' => ['nullable', 'integer', Rule::exists('education_levels', 'id')],
            'marital_status_id' => ['nullable', 'integer', Rule::exists('marital_statuses', 'id')],
        ];
    }
}
