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
            'document_type_id' => $this->document_type_id,
            'document_number' => $this->document_number,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender_id' => $this->gender_id,
            'nationality_id' => $this->nationality_id,
            'phone_primary' => $this->phone_primary,
            'phone_primary_dial' => $this->phone_primary_dial,
            'phone_secondary' => $this->phone_secondary,
            'phone_secondary_dial' => $this->phone_secondary_dial,
            'profile_photo_url' => $this->profile_photo_url,
        ];
    }
}
