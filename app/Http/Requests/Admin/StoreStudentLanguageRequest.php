<?php

namespace App\Http\Requests\Admin;

use App\Models\StudentLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreStudentLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('create', StudentLanguage::class);

        return true;
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
