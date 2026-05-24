<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentBenefitRequest extends FormRequest
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
            'is_active' => ['nullable', 'boolean'],
            'since' => ['nullable', 'date'],
            'until' => ['nullable', 'date', 'after_or_equal:since'],
        ];
    }
}
