<?php

namespace App\Models;

use Database\Factories\CareerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    // pensums() relation will be added when the Pensum model is implemented
}
