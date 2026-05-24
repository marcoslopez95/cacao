<?php

namespace App\Models;

use App\Models\Catalogs\Language;
use App\Models\Catalogs\Religion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemographicProfile extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'birth_city',
        'birth_state_id',
        'birth_country_id',
        'is_indigenous',
        'indigenous_community',
        'native_language_id',
        'is_returned_migrant',
        'previous_country_id',
        'religion_id',
        'practices_sport',
        'sport',
        'cultural_activities',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_indigenous' => 'boolean',
        'is_returned_migrant' => 'boolean',
        'practices_sport' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function birthState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'birth_state_id');
    }

    public function birthCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'birth_country_id');
    }

    public function nativeLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'native_language_id');
    }

    public function previousCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'previous_country_id');
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class, 'religion_id');
    }
}
