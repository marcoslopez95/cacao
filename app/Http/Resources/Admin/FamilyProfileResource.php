<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'guardian_marital_status_id' => $this->guardian_marital_status_id,
            'children_count' => $this->children_count,
            'sibling_position' => $this->sibling_position,
            'sibling_count' => $this->sibling_count,
            'living_arrangement_id' => $this->living_arrangement_id,
            'household_head_type_id' => $this->household_head_type_id,
            'household_head_name' => $this->household_head_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'guardian_marital_status' => $this->whenLoaded('guardianMaritalStatus', fn () => $this->guardianMaritalStatus ? [
                'id' => $this->guardianMaritalStatus->id,
                'code' => $this->guardianMaritalStatus->code,
                'name' => $this->guardianMaritalStatus->name,
            ] : null),
            'living_arrangement' => $this->whenLoaded('livingArrangement', fn () => $this->livingArrangement ? [
                'id' => $this->livingArrangement->id,
                'code' => $this->livingArrangement->code,
                'name' => $this->livingArrangement->name,
            ] : null),
            'household_head_type' => $this->whenLoaded('householdHeadType', fn () => $this->householdHeadType ? [
                'id' => $this->householdHeadType->id,
                'code' => $this->householdHeadType->code,
                'name' => $this->householdHeadType->name,
            ] : null),
        ];
    }
}
