<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HousingProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'housing_type_id' => $this->housing_type_id,
            'tenure_type_id' => $this->tenure_type_id,
            'construction_material_id' => $this->construction_material_id,
            'room_count' => $this->room_count,
            'bathroom_count' => $this->bathroom_count,
            'household_members' => $this->household_members,
            'is_overcrowded' => $this->is_overcrowded,
            'commute_time_id' => $this->commute_time_id,
            'transport_type_id' => $this->transport_type_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'housing_type' => $this->whenLoaded('housingType', fn () => $this->housingType ? [
                'id' => $this->housingType->id,
                'code' => $this->housingType->code,
                'name' => $this->housingType->name,
            ] : null),
            'tenure_type' => $this->whenLoaded('tenureType', fn () => $this->tenureType ? [
                'id' => $this->tenureType->id,
                'code' => $this->tenureType->code,
                'name' => $this->tenureType->name,
            ] : null),
            'construction_material' => $this->whenLoaded('constructionMaterial', fn () => $this->constructionMaterial ? [
                'id' => $this->constructionMaterial->id,
                'code' => $this->constructionMaterial->code,
                'name' => $this->constructionMaterial->name,
            ] : null),
            'commute_time' => $this->whenLoaded('commuteTime', fn () => $this->commuteTime ? [
                'id' => $this->commuteTime->id,
                'code' => $this->commuteTime->code,
                'name' => $this->commuteTime->name,
            ] : null),
            'transport_type' => $this->whenLoaded('transportType', fn () => $this->transportType ? [
                'id' => $this->transportType->id,
                'code' => $this->transportType->code,
                'name' => $this->transportType->name,
            ] : null),
            'services' => $this->whenLoaded('services', fn () => $this->services->map(fn ($service) => [
                'id' => $service->id,
                'code' => $service->code,
                'name' => $service->name,
                'is_available' => $service->pivot->is_available,
            ])),
        ];
    }
}
