<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipality extends Model
{
    /** @var list<string> */
    protected $fillable = ['state_id', 'code', 'name', 'active'];
    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = ['active' => 'boolean'];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function parishes(): HasMany
    {
        return $this->hasMany(Parish::class);
    }
}
