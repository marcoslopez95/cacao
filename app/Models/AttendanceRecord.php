<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['class_session_id', 'enrollment_detail_id', 'status'])]
class AttendanceRecord extends Model
{
    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
        ];
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function enrollmentDetail(): BelongsTo
    {
        return $this->belongsTo(EnrollmentDetail::class);
    }
}
