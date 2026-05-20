<?php

namespace App\Models;

use App\Enums\EducationalLevel;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'guardian_id', 'educational_level', 'current_pensum_id', 'academic_year'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'educational_level' => EducationalLevel::class,
            'academic_year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function pensum(): BelongsTo
    {
        return $this->belongsTo(Pensum::class, 'current_pensum_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
