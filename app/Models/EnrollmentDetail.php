<?php

namespace App\Models;

use App\Enums\EnrollmentDetailStatus;
use Database\Factories\EnrollmentDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['enrollment_id', 'subject_id', 'section_id', 'status'])]
class EnrollmentDetail extends Model
{
    /** @use HasFactory<EnrollmentDetailFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => EnrollmentDetailStatus::class,
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function gradeEntries(): HasMany
    {
        return $this->hasMany(GradeEntry::class);
    }
}
