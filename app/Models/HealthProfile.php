<?php

namespace App\Models;

use App\Models\Catalogs\BloodType;
use App\Models\Catalogs\DisabilityType;
use App\Models\Catalogs\InsuranceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'blood_type_id',
        'weight_kg',
        'height_cm',
        'has_disability',
        'disability_type_id',
        'disability_description',
        'has_special_needs',
        'special_needs_description',
        'chronic_condition',
        'regular_medication',
        'allergies',
        'has_medical_insurance',
        'insurance_type_id',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_phone_dial',
        'emergency_contact_relation',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'has_disability' => 'boolean',
        'has_special_needs' => 'boolean',
        'has_medical_insurance' => 'boolean',
        'weight_kg' => 'decimal:2',
        'height_cm' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bloodType(): BelongsTo
    {
        return $this->belongsTo(BloodType::class);
    }

    public function disabilityType(): BelongsTo
    {
        return $this->belongsTo(DisabilityType::class);
    }

    public function insuranceType(): BelongsTo
    {
        return $this->belongsTo(InsuranceType::class);
    }
}
