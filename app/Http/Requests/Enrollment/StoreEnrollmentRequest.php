<?php

namespace App\Http\Requests\Enrollment;

use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($studentId = $this->integer('student_id')) {
            $student = Student::find($studentId);

            return $student !== null
                && ($this->user()?->can('create', [Enrollment::class, $student]) ?? false);
        }

        return $this->user()?->can('create', Enrollment::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
        ];
    }
}
