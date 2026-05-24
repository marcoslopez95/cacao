<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentBackgroundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'previous_institution' => $this->previous_institution,
            'institution_type_id' => $this->institution_type_id,
            'graduation_year' => $this->graduation_year,
            'previous_gpa' => $this->previous_gpa,
            'repeated_grade' => $this->repeated_grade,
            'repeated_grade_description' => $this->repeated_grade_description,
            'transfer_reason_id' => $this->transfer_reason_id,
            'has_prior_studies' => $this->has_prior_studies,
            'prior_studies_description' => $this->prior_studies_description,
            'digital_level_id' => $this->digital_level_id,
            'mother_education_level_id' => $this->mother_education_level_id,
            'father_education_level_id' => $this->father_education_level_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'institution_type' => $this->whenLoaded('institutionType', fn () => $this->institutionType ? [
                'id' => $this->institutionType->id,
                'code' => $this->institutionType->code,
                'name' => $this->institutionType->name,
            ] : null),
            'transfer_reason' => $this->whenLoaded('transferReason', fn () => $this->transferReason ? [
                'id' => $this->transferReason->id,
                'code' => $this->transferReason->code,
                'name' => $this->transferReason->name,
            ] : null),
            'digital_level' => $this->whenLoaded('digitalLevel', fn () => $this->digitalLevel ? [
                'id' => $this->digitalLevel->id,
                'code' => $this->digitalLevel->code,
                'name' => $this->digitalLevel->name,
            ] : null),
            'mother_education_level' => $this->whenLoaded('motherEducationLevel', fn () => $this->motherEducationLevel ? [
                'id' => $this->motherEducationLevel->id,
                'code' => $this->motherEducationLevel->code,
                'name' => $this->motherEducationLevel->name,
            ] : null),
            'father_education_level' => $this->whenLoaded('fatherEducationLevel', fn () => $this->fatherEducationLevel ? [
                'id' => $this->fatherEducationLevel->id,
                'code' => $this->fatherEducationLevel->code,
                'name' => $this->fatherEducationLevel->name,
            ] : null),
        ];
    }
}
