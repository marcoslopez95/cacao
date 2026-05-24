<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'attachment_type_id' => $this->attachment_type_id,
            'file_url' => $this->file_url,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'file_size_bytes' => $this->file_size_bytes,
            'is_verified' => $this->is_verified,
            'verified_by' => $this->verified_by,
            'verified_at' => $this->verified_at,
            'created_at' => $this->created_at,
            'attachment_type' => $this->whenLoaded('attachmentType', fn () => [
                'id' => $this->attachmentType->id,
                'code' => $this->attachmentType->code,
                'name' => $this->attachmentType->name,
            ]),
            'verified_by_user' => $this->whenLoaded('verifiedBy', fn () => $this->verifiedBy ? [
                'id' => $this->verifiedBy->id,
                'name' => $this->verifiedBy->name,
            ] : null),
        ];
    }
}
