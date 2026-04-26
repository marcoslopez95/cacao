<?php

namespace App\Models;

use Database\Factories\PensumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['career_id', 'name', 'period_type', 'total_periods', 'is_active'])]
class Pensum extends Model
{
    /** @use HasFactory<PensumFactory> */
    use HasFactory;

    /** @var array<string, string> */
    protected $casts = ['is_active' => 'boolean'];

    public function career(): BelongsTo
    {
        return $this->belongsTo(Career::class);
    }

    // subjects() relation will be added when the Subject model is implemented (Part 4)
}
