<?php

namespace App\Models;

use Database\Factories\GradeSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['grade_config_id', 'name', 'weight', 'sort_order', 'is_remedial'])]
class GradeSlot extends Model
{
    /** @use HasFactory<GradeSlotFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'sort_order' => 'integer',
            'is_remedial' => 'boolean',
        ];
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(GradeConfig::class, 'grade_config_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(GradeEntry::class);
    }
}
