<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\HealthProfileWrapper;
use App\Models\HealthProfile;
use App\Models\User;
use App\Services\ConsentService;

class UpsertHealthProfileAction
{
    public function __construct(
        private readonly ConsentService $consentService,
    ) {}

    public function handle(User $user, HealthProfileWrapper $wrapper): HealthProfile
    {
        $this->consentService->requireConsent($user);

        $attributes = [
            'weight_kg' => $wrapper->getWeightKg(),
            'height_cm' => $wrapper->getHeightCm(),
            'has_disability' => $wrapper->getHasDisability(),
            'disability_description' => $wrapper->getDisabilityDescription(),
            'has_special_needs' => $wrapper->getHasSpecialNeeds(),
            'special_needs_description' => $wrapper->getSpecialNeedsDescription(),
            'chronic_condition' => $wrapper->getChronicCondition(),
            'regular_medication' => $wrapper->getRegularMedication(),
            'allergies' => $wrapper->getAllergies(),
            'has_medical_insurance' => $wrapper->getHasMedicalInsurance(),
            'emergency_contact_name' => $wrapper->getEmergencyContactName(),
            'emergency_contact_phone' => $wrapper->getEmergencyContactPhone(),
            'emergency_contact_relation' => $wrapper->getEmergencyContactRelation(),
        ];

        // Only overwrite *_id fields when explicitly included in the validated payload.
        // An absent key means the frontend did not send it, so the existing DB value
        // must be preserved (prevents accidental nullification).
        if ($wrapper->hasBloodTypeId()) {
            $attributes['blood_type_id'] = $wrapper->getBloodTypeId();
        }

        if ($wrapper->hasDisabilityTypeId()) {
            $attributes['disability_type_id'] = $wrapper->getDisabilityTypeId();
        }

        if ($wrapper->hasInsuranceTypeId()) {
            $attributes['insurance_type_id'] = $wrapper->getInsuranceTypeId();
        }

        return HealthProfile::updateOrCreate(
            ['user_id' => $user->id],
            $attributes
        );
    }
}
