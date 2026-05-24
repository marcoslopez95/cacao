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

        return HealthProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'blood_type_id' => $wrapper->getBloodTypeId(),
                'weight_kg' => $wrapper->getWeightKg(),
                'height_cm' => $wrapper->getHeightCm(),
                'has_disability' => $wrapper->getHasDisability(),
                'disability_type_id' => $wrapper->getDisabilityTypeId(),
                'disability_description' => $wrapper->getDisabilityDescription(),
                'has_special_needs' => $wrapper->getHasSpecialNeeds(),
                'special_needs_description' => $wrapper->getSpecialNeedsDescription(),
                'chronic_condition' => $wrapper->getChronicCondition(),
                'regular_medication' => $wrapper->getRegularMedication(),
                'allergies' => $wrapper->getAllergies(),
                'has_medical_insurance' => $wrapper->getHasMedicalInsurance(),
                'insurance_type_id' => $wrapper->getInsuranceTypeId(),
                'emergency_contact_name' => $wrapper->getEmergencyContactName(),
                'emergency_contact_phone' => $wrapper->getEmergencyContactPhone(),
                'emergency_contact_relation' => $wrapper->getEmergencyContactRelation(),
            ]
        );
    }
}
