<?php

namespace App\Http\Resources\Security;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'isAdmin' => $this->name === 'Admin',
            'usersCount' => $this->users_count ?? 0,
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name')->values()),
        ];
    }
}
