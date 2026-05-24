<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DemographicProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isPrivileged = $request->user()->hasAnyRole(['Administrador', 'Coordinador']);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'birth_city' => $this->birth_city,
            'birth_state_id' => $this->birth_state_id,
            'birth_country_id' => $this->birth_country_id,
            'is_indigenous' => $this->is_indigenous,
            'indigenous_community' => $this->indigenous_community,
            'native_language_id' => $this->native_language_id,
            'is_returned_migrant' => $this->is_returned_migrant,
            'previous_country_id' => $this->previous_country_id,
            'religion_id' => $this->when($isPrivileged, $this->religion_id),
            'practices_sport' => $this->practices_sport,
            'sport' => $this->sport,
            'cultural_activities' => $this->cultural_activities,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'birth_state' => $this->whenLoaded('birthState', fn () => $this->birthState ? [
                'id' => $this->birthState->id,
                'code' => $this->birthState->code,
                'name' => $this->birthState->name,
            ] : null),
            'birth_country' => $this->whenLoaded('birthCountry', fn () => $this->birthCountry ? [
                'id' => $this->birthCountry->id,
                'iso2' => $this->birthCountry->iso2,
                'name' => $this->birthCountry->name,
            ] : null),
            'native_language' => $this->whenLoaded('nativeLanguage', fn () => $this->nativeLanguage ? [
                'id' => $this->nativeLanguage->id,
                'code' => $this->nativeLanguage->code,
                'name' => $this->nativeLanguage->name,
            ] : null),
            'previous_country' => $this->whenLoaded('previousCountry', fn () => $this->previousCountry ? [
                'id' => $this->previousCountry->id,
                'iso2' => $this->previousCountry->iso2,
                'name' => $this->previousCountry->name,
            ] : null),
            'religion' => $this->when(
                $isPrivileged,
                $this->whenLoaded('religion', fn () => $this->religion ? [
                    'id' => $this->religion->id,
                    'code' => $this->religion->code,
                    'name' => $this->religion->name,
                ] : null)
            ),
        ];
    }
}
