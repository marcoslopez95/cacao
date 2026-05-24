<?php

namespace App\Http\Wrappers\Admin;

use Illuminate\Support\Collection;

class UserAddressWrapper extends Collection
{
    public function __construct(array $validated)
    {
        parent::__construct($validated);
    }

    public function getUserId(): int
    {
        return (int) $this->get('user_id');
    }

    public function getCountryId(): int
    {
        return (int) $this->get('country_id');
    }

    public function getStateId(): ?int
    {
        $value = $this->get('state_id');

        return $value !== null ? (int) $value : null;
    }

    public function getMunicipalityId(): ?int
    {
        $value = $this->get('municipality_id');

        return $value !== null ? (int) $value : null;
    }

    public function getParishId(): ?int
    {
        $value = $this->get('parish_id');

        return $value !== null ? (int) $value : null;
    }

    public function getGeographicZoneId(): ?int
    {
        $value = $this->get('geographic_zone_id');

        return $value !== null ? (int) $value : null;
    }

    public function getAddressLine1(): string
    {
        return (string) $this->get('address_line1');
    }

    public function getAddressLine2(): ?string
    {
        return $this->get('address_line2');
    }

    public function isPrimary(): bool
    {
        return (bool) $this->get('is_primary', true);
    }
}
