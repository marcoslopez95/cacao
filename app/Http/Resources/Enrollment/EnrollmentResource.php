<?php

namespace App\Http\Resources\Enrollment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student' => [
                'id' => $this->student->id,
                'name' => $this->student->user->name,
            ],
            'period_id' => $this->period_id,
            'period' => $this->period->name,
            'pensum_id' => $this->pensum_id,
            'pensum' => $this->pensum->name,
            'uc_disponibles' => $this->uc_disponibles,
            'uc_inscritas' => $this->uc_inscritas,
            'status' => $this->status->value,
            'details' => $this->whenLoaded('details', fn () => EnrollmentDetailResource::collection($this->details)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
