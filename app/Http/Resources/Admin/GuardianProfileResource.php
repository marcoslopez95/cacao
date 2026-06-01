<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardianProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guardian_id' => $this->guardian_id,
            'occupation' => $this->occupation,
            'employer' => $this->employer,
            'work_phone' => $this->work_phone,
            'work_phone_dial' => $this->work_phone_dial,
            'education_level_id' => $this->education_level_id,
            'marital_status_id' => $this->marital_status_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'education_level' => $this->whenLoaded('educationLevel', fn () => $this->educationLevel ? [
                'id' => $this->educationLevel->id,
                'code' => $this->educationLevel->code,
                'name' => $this->educationLevel->name,
            ] : null),
            'marital_status' => $this->whenLoaded('maritalStatus', fn () => $this->maritalStatus ? [
                'id' => $this->maritalStatus->id,
                'code' => $this->maritalStatus->code,
                'name' => $this->maritalStatus->name,
            ] : null),
        ];
    }
}
