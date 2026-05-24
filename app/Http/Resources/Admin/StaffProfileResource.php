<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'professor_id' => $this->professor_id,
            'employee_code' => $this->employee_code,
            'academic_title' => $this->academic_title,
            'specialty' => $this->specialty,
            'contract_type_id' => $this->contract_type_id,
            'dedication_type_id' => $this->dedication_type_id,
            'weekly_hour_load' => $this->weekly_hour_load,
            'hire_date' => $this->hire_date,
            'termination_date' => $this->termination_date,
            'employment_status_id' => $this->employment_status_id,
            'is_coordinator' => $this->is_coordinator,
            'coordinated_department_id' => $this->coordinated_department_id,
            'coordinator_since' => $this->coordinator_since,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'contract_type' => $this->whenLoaded('contractType', fn () => $this->contractType ? [
                'id' => $this->contractType->id,
                'code' => $this->contractType->code,
                'name' => $this->contractType->name,
            ] : null),
            'dedication_type' => $this->whenLoaded('dedicationType', fn () => $this->dedicationType ? [
                'id' => $this->dedicationType->id,
                'code' => $this->dedicationType->code,
                'name' => $this->dedicationType->name,
            ] : null),
            'employment_status' => $this->whenLoaded('employmentStatus', fn () => $this->employmentStatus ? [
                'id' => $this->employmentStatus->id,
                'code' => $this->employmentStatus->code,
                'name' => $this->employmentStatus->name,
            ] : null),
            'coordinated_department' => $this->whenLoaded('coordinatedDepartment', fn () => $this->coordinatedDepartment ? [
                'id' => $this->coordinatedDepartment->id,
                'name' => $this->coordinatedDepartment->name,
            ] : null),
        ];
    }
}
