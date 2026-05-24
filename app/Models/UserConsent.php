<?php

namespace App\Models;

use App\Services\ConsentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConsent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'policy_version',
        'accepts_data_processing',
        'accepts_image_use',
        'accepts_whatsapp_contact',
        'accepts_email_contact',
        'ip_address',
        'user_agent',
        'granted_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'accepts_data_processing' => 'boolean',
            'accepts_image_use' => 'boolean',
            'accepts_whatsapp_contact' => 'boolean',
            'accepts_email_contact' => 'boolean',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to only active (non-revoked) consents.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public static function hasActiveConsentFor(User $user): bool
    {
        return app(ConsentService::class)->hasActiveConsent($user);
    }
}
