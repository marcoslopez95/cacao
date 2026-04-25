<?php

namespace App\Models;

use Database\Factories\CareerCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class CareerCategory extends Model
{
    /** @use HasFactory<CareerCategoryFactory> */
    use HasFactory;

    public function careers(): HasMany
    {
        return $this->hasMany(Career::class);
    }
}
