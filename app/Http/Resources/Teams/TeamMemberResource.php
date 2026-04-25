<?php

namespace App\Http\Resources\Teams;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamMemberResource extends JsonResource
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
            'avatar' => $this->avatar ?? null,
            'role' => $this->pivot->role->value,
            'role_label' => $this->pivot->role?->label(),
        ];
    }
}
