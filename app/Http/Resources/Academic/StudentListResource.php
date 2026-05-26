<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->_user_name,
            'email' => $this->_user_email,
            'career_name' => $this->_career_name,
            'career_id' => $this->_career_id,
            'academic_year' => $this->academic_year,
            'educational_level' => $this->educational_level,
            'enrollment_status' => $this->_enrollment_status,
            'uc_inscritas' => (int) ($this->_uc_inscritas ?? 0),
            'gpa' => null,
            'cedula' => null,
            'code' => null,
            'guardian_name' => null,
            'guardian_relation' => null,
            'section_grade' => $this->_section_grade !== null ? (int) $this->_section_grade : null,
            'section_letter' => $this->_section_letter,
        ];
    }
}
