<?php

namespace App\Models;

use App\Models\Catalogs\GeographicZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'country_id',
        'state_id',
        'municipality_id',
        'parish_id',
        'geographic_zone_id',
        'address_line1',
        'address_line2',
        'is_primary',
        'created_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function geographicZone(): BelongsTo
    {
        return $this->belongsTo(GeographicZone::class);
    }
}
