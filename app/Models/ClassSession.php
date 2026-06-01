<?php

namespace App\Models;

use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['section_id', 'uploaded_by_id', 'linked_session_id', 'type', 'status', 'professor_present', 'topic', 'held_at'])]
class ClassSession extends Model
{
    protected function casts(): array
    {
        return [
            'type' => ClassSessionType::class,
            'status' => ClassSessionStatus::class,
            'professor_present' => 'boolean',
            'held_at' => 'date',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function linkedSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'linked_session_id');
    }

    public function linkedFrom(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'linked_session_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
