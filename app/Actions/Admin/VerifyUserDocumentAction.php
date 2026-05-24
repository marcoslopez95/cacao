<?php

namespace App\Actions\Admin;

use App\Models\UserDocument;

class VerifyUserDocumentAction
{
    public function handle(UserDocument $document, int $verifiedById): UserDocument
    {
        $document->update([
            'is_verified' => true,
            'verified_by' => $verifiedById,
            'verified_at' => now(),
        ]);

        return $document->fresh();
    }
}
