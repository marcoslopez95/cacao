<?php

namespace App\Http\Requests\Admin;

use App\Models\State;
use App\Models\UserAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('create', UserAddress::class);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'country_id' => ['required', 'integer', Rule::exists('countries', 'id')],
            'state_id' => ['nullable', 'integer', Rule::exists('states', 'id')],
            'municipality_id' => ['nullable', 'integer', Rule::exists('municipalities', 'id')],
            'parish_id' => ['nullable', 'integer', Rule::exists('parishes', 'id')],
            'geographic_zone_id' => ['nullable', 'integer', Rule::exists('geographic_zones', 'id')],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'state_id.exists' => 'El estado seleccionado no pertenece al país indicado.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $stateId = $this->input('state_id');
            $countryId = $this->input('country_id');

            if ($stateId && $countryId) {
                $valid = State::where('id', $stateId)
                    ->where('country_id', $countryId)
                    ->exists();

                if (! $valid) {
                    $v->errors()->add('state_id', 'El estado seleccionado no pertenece al país indicado.');
                }
            }
        });
    }
}
