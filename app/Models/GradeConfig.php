<?php

namespace App\Models;

use App\Enums\GradeLevel;
use App\Enums\GradeScaleType;
use Database\Factories\GradeConfigFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['level', 'period_id', 'scale_type', 'scale_min', 'scale_max', 'passing_value'])]
class GradeConfig extends Model
{
    /** @use HasFactory<GradeConfigFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'level' => GradeLevel::class,
            'scale_type' => GradeScaleType::class,
            'scale_min' => 'decimal:2',
            'scale_max' => 'decimal:2',
            'passing_value' => 'decimal:2',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(GradeSlot::class)->orderBy('sort_order');
    }

    public function letterValues(): HasMany
    {
        return $this->hasMany(GradeLetterValue::class)->orderBy('sort_order');
    }
}
