<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'country_id' => $this->country_id,
            'state_id' => $this->state_id,
            'municipality_id' => $this->municipality_id,
            'parish_id' => $this->parish_id,
            'geographic_zone_id' => $this->geographic_zone_id,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at,
            'country' => $this->whenLoaded('country', fn () => [
                'id' => $this->country->id,
                'name' => $this->country->name,
            ]),
            'state' => $this->whenLoaded('state', fn () => $this->state ? [
                'id' => $this->state->id,
                'name' => $this->state->name,
            ] : null),
            'municipality' => $this->whenLoaded('municipality', fn () => $this->municipality ? [
                'id' => $this->municipality->id,
                'name' => $this->municipality->name,
            ] : null),
            'parish' => $this->whenLoaded('parish', fn () => $this->parish ? [
                'id' => $this->parish->id,
                'name' => $this->parish->name,
            ] : null),
            'geographic_zone' => $this->whenLoaded('geographicZone', fn () => $this->geographicZone ? [
                'id' => $this->geographicZone->id,
                'name' => $this->geographicZone->name,
            ] : null),
        ];
    }
}
