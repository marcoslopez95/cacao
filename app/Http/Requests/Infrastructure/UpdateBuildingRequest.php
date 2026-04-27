<?php

namespace App\Http\Requests\Infrastructure;

use App\Models\Building;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Building $building */
        $building = $this->route('building');

        return $this->user()?->can('update', $building) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Building $building */
        $building = $this->route('building');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('buildings', 'name')->ignore($building->id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max'      => 'El nombre no puede superar los 100 caracteres.',
            'name.unique'   => 'Ya existe un edificio con este nombre.',
        ];
    }
}
