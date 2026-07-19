<?php

namespace App\Http\Resources\Security;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'active' => $this->active,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()),
            'student_level' => $this->whenLoaded('student', fn () => $this->student?->educational_level?->label()),
            'student_id' => $this->whenLoaded('student', fn () => $this->student?->id),
            'guardians_count' => $this->whenLoaded('student', fn () => $this->student?->guardians->count()),
            'guardian_id' => $this->whenLoaded('guardian', fn () => $this->guardian?->id),
            'students_count' => $this->whenLoaded('guardian', fn () => $this->guardian?->students->count()),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
