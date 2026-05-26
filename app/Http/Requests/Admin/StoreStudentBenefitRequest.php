<?php

namespace App\Http\Requests\Admin;

use App\Models\StudentBenefit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreStudentBenefitRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('create', StudentBenefit::class);

        return true;
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
