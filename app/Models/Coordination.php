<?php

namespace App\Models;

use Database\Factories\CoordinationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'type', 'education_level', 'secondary_type', 'career_id', 'grade_year', 'active'])]
class Coordination extends Model
{
    /** @use HasFactory<CoordinationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'grade_year' => 'integer',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CoordinationAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(CoordinationAssignment::class)->whereNull('ended_at');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    public function scopeByLevel(Builder $query, string $level): void
    {
        $query->where('education_level', $level);
    }
}
