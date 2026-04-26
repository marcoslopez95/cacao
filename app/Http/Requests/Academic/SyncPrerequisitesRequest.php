<?php

namespace App\Http\Requests\Academic;

use App\Models\Subject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncPrerequisitesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subject = $this->route('subject');

        return $this->user()?->can('managePrerequisites', $subject) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prerequisites' => ['present', 'array'],
            'prerequisites.*' => ['integer', 'exists:subjects,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            /** @var Subject $subject */
            $subject = $this->route('subject');
            $ids = $this->input('prerequisites', []);

            if (empty($ids)) {
                return;
            }

            $hasInvalid = Subject::whereIn('id', $ids)
                ->where(function ($query) use ($subject) {
                    $query->where('pensum_id', '!=', $subject->pensum_id)
                        ->orWhere('period_number', '>=', $subject->period_number);
                })
                ->exists();

            if ($hasInvalid) {
                $v->errors()->add('prerequisites', 'Uno o más prerrequisitos son inválidos: deben pertenecer al mismo pensum y tener un período anterior.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prerequisites.present' => 'El campo prerrequisitos es obligatorio.',
            'prerequisites.*.integer' => 'Cada prerrequisito debe ser un ID válido.',
            'prerequisites.*.exists' => 'Uno o más prerrequisitos no existen.',
        ];
    }
}
