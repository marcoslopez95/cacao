<?php

namespace App\Http\Requests\Enrollment;

use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Enrollment $enrollment */
        $enrollment = $this->route('enrollment');

        return $this->user()?->can('update', $enrollment) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
        ];
    }
}
