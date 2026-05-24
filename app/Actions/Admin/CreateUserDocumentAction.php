<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\UserDocumentWrapper;
use App\Models\UserDocument;
use Illuminate\Http\UploadedFile;

class CreateUserDocumentAction
{
    public function handle(int $userId, UploadedFile $file, UserDocumentWrapper $wrapper): UserDocument
    {
        $path = $file->store("documents/{$userId}", 'local');

        return UserDocument::create([
            'user_id' => $userId,
            'attachment_type_id' => $wrapper->getAttachmentTypeId(),
            'file_url' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
        ]);
    }
}
