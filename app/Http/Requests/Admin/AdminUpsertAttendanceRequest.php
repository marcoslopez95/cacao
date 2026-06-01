<?php

namespace App\Http\Requests\Admin;

use App\Models\ClassSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpsertAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ClassSession|null $classSession */
        $classSession = $this->route('classSession');

        return $this->user()?->can('takeAttendance', $classSession) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'marks' => ['required', 'array'],
            'marks.*' => ['required', Rule::in(['present', 'absent'])],
        ];
    }
}
