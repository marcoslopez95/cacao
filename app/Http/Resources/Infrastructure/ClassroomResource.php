<?php

namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassroomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'identifier' => $this->identifier,
            'type'       => $this->type->value,
            'capacity'   => $this->capacity,
            'building'   => [
                'id'   => $this->building->id,
                'name' => $this->building->name,
            ],
        ];
    }
}
