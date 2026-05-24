<?php

namespace App\Models;

use App\Models\Catalogs\AttachmentDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDocument extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'attachment_type_id',
        'file_url',
        'original_filename',
        'mime_type',
        'file_size_bytes',
        'is_verified',
        'verified_by',
        'verified_at',
        'created_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachmentType(): BelongsTo
    {
        return $this->belongsTo(AttachmentDocumentType::class, 'attachment_type_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
