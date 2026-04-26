<?php

namespace App\Models;

use Database\Factories\CareerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['career_category_id', 'name', 'code', 'active'])]
class Career extends Model
{
    /** @use HasFactory<CareerFactory> */
    use HasFactory;

    /** @var array<string, string> */
    protected $casts = ['active' => 'boolean'];

    public function careerCategory(): BelongsTo
    {
        return $this->belongsTo(CareerCategory::class);
    }

    public function pensums(): HasMany
    {
        return $this->hasMany(Pensum::class);
    }
}
