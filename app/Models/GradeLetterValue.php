<?php

namespace App\Models;

use Database\Factories\GradeLetterValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['grade_config_id', 'letter', 'numeric_equiv', 'is_passing', 'sort_order'])]
class GradeLetterValue extends Model
{
    /** @use HasFactory<GradeLetterValueFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'numeric_equiv' => 'decimal:2',
            'is_passing' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(GradeConfig::class, 'grade_config_id');
    }
}
