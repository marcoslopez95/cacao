<?php

namespace App\Http\Requests\Admin;

use App\Models\FamilyProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreFamilyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('create', FamilyProfile::class);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'guardian_marital_status_id' => ['nullable', 'integer', 'exists:marital_statuses,id'],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'sibling_position' => ['nullable', 'integer', 'min:1', 'max:20'],
            'sibling_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'living_arrangement_id' => ['nullable', 'integer', 'exists:living_arrangements,id'],
            'household_head_type_id' => ['nullable', 'integer', 'exists:household_head_types,id'],
            'household_head_name' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $position = $this->input('sibling_position');
            $count = $this->input('sibling_count');

            if ($position !== null && $count !== null && (int) $position > (int) $count) {
                $validator->errors()->add(
                    'sibling_position',
                    'La posición entre hermanos no puede ser mayor que la cantidad de hermanos.'
                );
            }
        });
    }
}
