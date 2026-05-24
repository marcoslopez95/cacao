<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentBenefitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'student_id' => $this->student_id,
            'benefit_id' => $this->benefit_id,
            'is_active' => $this->is_active,
            'since' => $this->since?->toDateString(),
            'until' => $this->until?->toDateString(),
            'benefit' => $this->whenLoaded('benefit', fn () => $this->benefit ? [
                'id' => $this->benefit->id,
                'code' => $this->benefit->code,
                'name' => $this->benefit->name,
            ] : null),
        ];
    }
}
