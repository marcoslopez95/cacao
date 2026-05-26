<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocioeconomicProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'income_range_id' => $this->income_range_id,
            'income_source_id' => $this->income_source_id,
            'household_earners' => $this->household_earners,
            'receives_remittances' => $this->receives_remittances,
            'remittance_country_id' => $this->remittance_country_id,
            'student_works' => $this->student_works,
            'employment_type_id' => $this->employment_type_id,
            'weekly_work_hours' => $this->weekly_work_hours,
            'has_scholarship' => $this->has_scholarship,
            'scholarship_name' => $this->scholarship_name,
            'has_institutional_benefit' => $this->has_institutional_benefit,
            'recorded_by' => $this->recorded_by,
            'study_date' => $this->study_date?->format('Y-m-d'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'income_range' => $this->whenLoaded('incomeRange', fn () => $this->incomeRange ? [
                'id' => $this->incomeRange->id,
                'code' => $this->incomeRange->code,
                'name' => $this->incomeRange->name,
            ] : null),
            'income_source' => $this->whenLoaded('incomeSource', fn () => $this->incomeSource ? [
                'id' => $this->incomeSource->id,
                'code' => $this->incomeSource->code,
                'name' => $this->incomeSource->name,
            ] : null),
            'remittance_country' => $this->whenLoaded('remittanceCountry', fn () => $this->remittanceCountry ? [
                'id' => $this->remittanceCountry->id,
                'name' => $this->remittanceCountry->name,
            ] : null),
            'employment_type' => $this->whenLoaded('employmentType', fn () => $this->employmentType ? [
                'id' => $this->employmentType->id,
                'code' => $this->employmentType->code,
                'name' => $this->employmentType->name,
            ] : null),
            'recorded_by_user' => $this->whenLoaded('recordedBy', fn () => $this->recordedBy ? [
                'id' => $this->recordedBy->id,
                'name' => $this->recordedBy->name,
            ] : null),
        ];
    }
}
