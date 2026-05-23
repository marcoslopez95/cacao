<?php

namespace App\Models;

use Database\Factories\GradeEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['enrollment_detail_id', 'grade_slot_id', 'lapse_id', 'parent_id', 'name', 'weight', 'value', 'is_published'])]
class GradeEntry extends Model
{
    /** @use HasFactory<GradeEntryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'value' => 'decimal:2',
            'is_published' => 'boolean',
        ];
    }

    public function enrollmentDetail(): BelongsTo
    {
        return $this->belongsTo(EnrollmentDetail::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(GradeSlot::class, 'grade_slot_id');
    }

    public function lapse(): BelongsTo
    {
        return $this->belongsTo(Lapse::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(GradeEntry::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(GradeEntry::class, 'parent_id');
    }

    /** @param Builder<GradeEntry> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param Builder<GradeEntry> $query */
    public function scopeSlotLevel(Builder $query): void
    {
        $query->whereNull('parent_id');
    }
}
