<?php

namespace App\Http\Resources\Teams;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamInvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'email' => $this->email,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
