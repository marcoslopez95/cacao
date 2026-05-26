<?php

namespace App\Http\Wrappers\Admin;

use Illuminate\Support\Collection;

class DemographicProfileWrapper extends Collection
{
    public function __construct(array $validated)
    {
        parent::__construct($validated);
    }

    public function getBirthCity(): ?string
    {
        return $this->get('birth_city');
    }

    public function getBirthStateId(): ?int
    {
        $value = $this->get('birth_state_id');

        return $value !== null ? (int) $value : null;
    }

    public function getBirthCountryId(): ?int
    {
        $value = $this->get('birth_country_id');

        return $value !== null ? (int) $value : null;
    }

    public function getIsIndigenous(): ?bool
    {
        $value = $this->get('is_indigenous');

        return $value !== null ? (bool) $value : null;
    }

    public function getIndigenousCommunity(): ?string
    {
        return $this->get('indigenous_community');
    }

    public function getNativeLanguageId(): ?int
    {
        $value = $this->get('native_language_id');

        return $value !== null ? (int) $value : null;
    }

    public function getIsReturnedMigrant(): ?bool
    {
        $value = $this->get('is_returned_migrant');

        return $value !== null ? (bool) $value : null;
    }

    public function getPreviousCountryId(): ?int
    {
        $value = $this->get('previous_country_id');

        return $value !== null ? (int) $value : null;
    }

    /**
     * Returns true when the 'religion_id' key was present in the validated payload,
     * regardless of whether its value is null or a valid integer.
     */
    public function hasReligionId(): bool
    {
        return $this->has('religion_id');
    }

    public function getReligionId(): ?int
    {
        $value = $this->get('religion_id');

        return $value !== null ? (int) $value : null;
    }

    public function getPracticesSport(): ?bool
    {
        $value = $this->get('practices_sport');

        return $value !== null ? (bool) $value : null;
    }

    public function getSport(): ?string
    {
        return $this->get('sport');
    }

    public function getCulturalActivities(): ?string
    {
        return $this->get('cultural_activities');
    }
}
