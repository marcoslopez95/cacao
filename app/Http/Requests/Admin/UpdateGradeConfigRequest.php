<?php

namespace App\Http\Requests\Admin;

use App\Enums\GradeScaleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateGradeConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        $gradeConfig = $this->route('gradeConfig');

        return $this->user()?->can('update', $gradeConfig) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period_id' => ['nullable', 'integer', Rule::exists('periods', 'id')],
            'scale_type' => ['required', Rule::enum(GradeScaleType::class)],
            'scale_min' => ['nullable', 'required_if:scale_type,numeric', 'numeric'],
            'scale_max' => ['nullable', 'required_if:scale_type,numeric', 'numeric', 'gt:scale_min'],
            'passing_value' => ['required', 'numeric', 'min:0'],

            'slots' => ['required', 'array', 'min:1'],
            'slots.*.name' => ['required', 'string', 'max:100'],
            'slots.*.weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'slots.*.sort_order' => ['required', 'integer', 'min:1'],
            'slots.*.is_remedial' => ['boolean'],

            'letter_values' => ['required_if:scale_type,letter', 'nullable', 'array'],
            'letter_values.*.letter' => ['required', 'string', 'max:2'],
            'letter_values.*.numeric_equiv' => ['required', 'numeric', 'min:0'],
            'letter_values.*.is_passing' => ['required', 'boolean'],
            'letter_values.*.sort_order' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $slots = $this->input('slots', []);
            $nonRemedial = collect($slots)->where('is_remedial', false);
            $total = $nonRemedial->sum('weight');

            if (abs($total - 100) > 0.01) {
                $v->errors()->add('slots', 'Los pesos de los slots no-remedial deben sumar exactamente 100%.');
            }
        });
    }
}
