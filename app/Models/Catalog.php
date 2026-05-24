<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

abstract class Catalog extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = ['code', 'name', 'description', 'active', 'sort_order'];

    /** @var array<string, string> */
    protected $casts = ['active' => 'boolean'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function cached(): Collection
    {
        return Cache::remember(
            'catalog.'.(new static)->getTable(),
            3600,
            fn () => static::query()->active()->ordered()->get()->keyBy('code')
        );
    }
}
