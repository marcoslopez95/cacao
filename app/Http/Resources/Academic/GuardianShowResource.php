<?php

namespace App\Http\Resources\Academic;

use App\Models\Catalogs\KinshipType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardianShowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $kinshipNames = KinshipType::pluck('name', 'id');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'students' => $this->students->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->user->name,
                'email' => $student->user->email,
                'educational_level' => $student->educational_level?->label(),
                'kinship' => $kinshipNames->get($student->pivot->kinship_type_id),
                'primary' => (bool) $student->pivot->is_primary,
                'emergency_contact' => (bool) $student->pivot->is_emergency_contact,
            ])->values(),
        ];
    }
}
