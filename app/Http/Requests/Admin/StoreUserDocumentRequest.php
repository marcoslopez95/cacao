<?php

namespace App\Http\Requests\Admin;

use App\Models\UserDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreUserDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('create', UserDocument::class);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attachment_type_id' => ['required', 'integer', Rule::exists('attachment_document_types', 'id')],
            'file' => ['required', 'file', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:10240'],
            'original_filename' => ['nullable', 'string', 'max:255'],
        ];
    }
}
