<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Administrador', 'Coordinador']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'language_id' => ['required', 'integer', 'exists:languages,id'],
            'language_level_id' => ['required', 'integer', 'exists:language_levels,id'],
            'is_mother_tongue' => ['nullable', 'boolean'],
        ];
    }
}
