<?php

namespace App\Http\Resources\Enrollment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'enrollment_id' => $this->enrollment_id,
            'subject_id' => $this->subject_id,
            'subject' => [
                'id' => $this->subject->id,
                'code' => $this->subject->code,
                'name' => $this->subject->name,
                'credits_uc' => $this->subject->credits_uc,
            ],
            'section_id' => $this->section_id,
            'section' => [
                'id' => $this->section->id,
                'code' => $this->section->code,
                'capacity' => $this->section->capacity,
            ],
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
