<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_id', 'period_id', 'pensum_id', 'uc_disponibles', 'uc_inscritas', 'status'])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'uc_disponibles' => 'integer',
            'uc_inscritas' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function pensum(): BelongsTo
    {
        return $this->belongsTo(Pensum::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(EnrollmentDetail::class);
    }

    public function draftDetails(): HasMany
    {
        return $this->details()->where('status', 'draft');
    }

    public function confirmedDetails(): HasMany
    {
        return $this->details()->where('status', 'confirmed');
    }
}
