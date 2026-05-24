<?php

namespace App\Http\Wrappers\Admin;

use Illuminate\Support\Collection;

class HealthProfileWrapper extends Collection
{
    public function __construct(array $validated)
    {
        parent::__construct($validated);
    }

    public function getBloodTypeId(): ?int
    {
        $value = $this->get('blood_type_id');

        return $value !== null ? (int) $value : null;
    }

    public function getWeightKg(): ?float
    {
        $value = $this->get('weight_kg');

        return $value !== null ? (float) $value : null;
    }

    public function getHeightCm(): ?float
    {
        $value = $this->get('height_cm');

        return $value !== null ? (float) $value : null;
    }

    public function getHasDisability(): ?bool
    {
        $value = $this->get('has_disability');

        return $value !== null ? (bool) $value : null;
    }

    public function getDisabilityTypeId(): ?int
    {
        $value = $this->get('disability_type_id');

        return $value !== null ? (int) $value : null;
    }

    public function getDisabilityDescription(): ?string
    {
        return $this->get('disability_description');
    }

    public function getHasSpecialNeeds(): ?bool
    {
        $value = $this->get('has_special_needs');

        return $value !== null ? (bool) $value : null;
    }

    public function getSpecialNeedsDescription(): ?string
    {
        return $this->get('special_needs_description');
    }

    public function getChronicCondition(): ?string
    {
        return $this->get('chronic_condition');
    }

    public function getRegularMedication(): ?string
    {
        return $this->get('regular_medication');
    }

    public function getAllergies(): ?string
    {
        return $this->get('allergies');
    }

    public function getHasMedicalInsurance(): ?bool
    {
        $value = $this->get('has_medical_insurance');

        return $value !== null ? (bool) $value : null;
    }

    public function getInsuranceTypeId(): ?int
    {
        $value = $this->get('insurance_type_id');

        return $value !== null ? (int) $value : null;
    }

    public function getEmergencyContactName(): ?string
    {
        return $this->get('emergency_contact_name');
    }

    public function getEmergencyContactPhone(): ?string
    {
        return $this->get('emergency_contact_phone');
    }

    public function getEmergencyContactRelation(): ?string
    {
        return $this->get('emergency_contact_relation');
    }
}
