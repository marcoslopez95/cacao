<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    /** @var list<string> */
    protected $fillable = ['iso2', 'iso3', 'name', 'active'];
    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = ['active' => 'boolean'];

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }
}
