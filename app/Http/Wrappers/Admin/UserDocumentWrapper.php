<?php

namespace App\Http\Wrappers\Admin;

use Illuminate\Support\Collection;

class UserDocumentWrapper extends Collection
{
    public function __construct(array $validated)
    {
        parent::__construct($validated);
    }

    public function getAttachmentTypeId(): int
    {
        return (int) $this->get('attachment_type_id');
    }

    public function getFileUrl(): string
    {
        return (string) $this->get('file_url');
    }

    public function getOriginalFilename(): string
    {
        return (string) $this->get('original_filename');
    }

    public function getMimeType(): string
    {
        return (string) $this->get('mime_type');
    }

    public function getFileSizeBytes(): ?int
    {
        $value = $this->get('file_size_bytes');

        return $value !== null ? (int) $value : null;
    }
}
