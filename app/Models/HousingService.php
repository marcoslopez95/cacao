<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class HousingService extends Pivot
{
    /** @var bool */
    public $timestamps = false;

    /** @var bool */
    public $incrementing = false;

    /** @var null */
    protected $primaryKey = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'housing_profile_id',
        'basic_service_id',
        'is_available',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_available' => 'boolean',
    ];
}
