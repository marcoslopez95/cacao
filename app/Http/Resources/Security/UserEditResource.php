<?php

namespace App\Http\Resources\Security;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserEditResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'email' => $this->email,
            'active' => $this->active,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->map(fn ($name) => match ($name) {
                'Admin' => 'admin',
                'Estudiante' => 'student',
                'Profesor' => 'professor',
                'Representante' => 'guardian',
                default => strtolower($name),
            })->values()),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
