<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'blood_type_id' => $this->blood_type_id,
            'weight_kg' => $this->weight_kg,
            'height_cm' => $this->height_cm,
            'has_disability' => $this->has_disability,
            'disability_type_id' => $this->disability_type_id,
            'disability_description' => $this->disability_description,
            'has_special_needs' => $this->has_special_needs,
            'special_needs_description' => $this->special_needs_description,
            'chronic_condition' => $this->chronic_condition,
            'regular_medication' => $this->regular_medication,
            'allergies' => $this->allergies,
            'has_medical_insurance' => $this->has_medical_insurance,
            'insurance_type_id' => $this->insurance_type_id,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'emergency_contact_relation' => $this->emergency_contact_relation,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'blood_type' => $this->whenLoaded('bloodType', fn () => $this->bloodType ? [
                'id' => $this->bloodType->id,
                'code' => $this->bloodType->code,
                'name' => $this->bloodType->name,
            ] : null),
            'disability_type' => $this->whenLoaded('disabilityType', fn () => $this->disabilityType ? [
                'id' => $this->disabilityType->id,
                'code' => $this->disabilityType->code,
                'name' => $this->disabilityType->name,
            ] : null),
            'insurance_type' => $this->whenLoaded('insuranceType', fn () => $this->insuranceType ? [
                'id' => $this->insuranceType->id,
                'code' => $this->insuranceType->code,
                'name' => $this->insuranceType->name,
            ] : null),
        ];
    }
}
