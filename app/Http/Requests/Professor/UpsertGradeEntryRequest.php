<?php

namespace App\Http\Requests\Professor;

use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertGradeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $section = $this->route('section');

        return $this->user()?->can('upsert', [Section::class, $section]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enrollment_detail_id' => ['required', 'integer', Rule::exists('enrollment_details', 'id')],
            'grade_slot_id' => ['required', 'integer', Rule::exists('grade_slots', 'id')],
            'lapse_id' => ['nullable', 'integer', Rule::exists('lapses', 'id')],
            'parent_id' => ['nullable', 'integer', Rule::exists('grade_entries', 'id')],
            'name' => ['nullable', 'string', 'max:100', 'required_with:parent_id'],
            'weight' => ['nullable', 'numeric', 'min:0.01', 'max:100', 'required_with:parent_id'],
            'value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
