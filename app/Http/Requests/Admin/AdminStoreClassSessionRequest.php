<?php

namespace App\Http\Requests\Admin;

use App\Models\ClassSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminStoreClassSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $section = $this->route('section');

        return $this->user()?->can('create', [ClassSession::class, $section, $this->input('type')]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['regular', 'makeup', 'advance'])],
            'linked_session_id' => ['nullable', 'required_if:type,makeup,advance', 'integer', 'exists:class_sessions,id'],
            'topic' => ['nullable', 'string', 'max:255'],
            'held_at' => ['nullable', 'date'],
        ];
    }
}
