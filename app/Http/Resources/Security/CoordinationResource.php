<?php

namespace App\Http\Resources\Security;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoordinationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'education_level' => $this->education_level,
            'secondary_type' => $this->secondary_type,
            'career_id' => $this->career_id,
            'grade_year' => $this->grade_year,
            'active' => $this->active,
            'current_coordinator' => $this->whenLoaded(
                'currentAssignment',
                fn () => $this->currentAssignment?->user
                    ? ['id' => $this->currentAssignment->user->id, 'name' => $this->currentAssignment->user->name]
                    : null,
            ),
        ];
    }
}
