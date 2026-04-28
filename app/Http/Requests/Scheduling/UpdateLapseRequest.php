<?php

namespace App\Http\Requests\Scheduling;

use App\Enums\PeriodStatus;
use App\Models\Lapse;
use App\Models\Period;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLapseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('lapse')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Period $period */
        $period = $this->route('period');

        /** @var Lapse $lapse */
        $lapse = $this->route('lapse');

        return [
            'number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('lapses', 'number')
                    ->where('period_id', $period->id)
                    ->ignore($lapse->id),
            ],
            'name'       => ['required', 'string', 'max:100'],
            'start_date' => [
                'required',
                'date',
                'after_or_equal:' . $period->start_date->toDateString(),
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
                'before_or_equal:' . $period->end_date->toDateString(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'number.unique'             => 'Ya existe un lapso con ese número en este período.',
            'start_date.after_or_equal' => 'La fecha de inicio debe estar dentro del período.',
            'end_date.after'            => 'La fecha de fin debe ser posterior a la de inicio.',
            'end_date.before_or_equal'  => 'La fecha de fin debe estar dentro del período.',
        ];
    }

    protected function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        /** @var Period $period */
        $period = $this->route('period');

        $validator->after(function (\Illuminate\Contracts\Validation\Validator $v) use ($period) {
            if ($period->status === PeriodStatus::Closed) {
                $v->errors()->add('period_id', 'No se pueden modificar lapsos en un período cerrado.');
            }
        });
    }
}
