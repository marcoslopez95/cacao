<?php

namespace App\Http\Wrappers\Admin;

use Illuminate\Support\Collection;

class StudentBenefitWrapper extends Collection
{
    public function __construct(array $validated)
    {
        parent::__construct($validated);
    }

    public function isActive(): bool
    {
        return (bool) $this->get('is_active', true);
    }

    public function getSince(): ?string
    {
        return $this->get('since');
    }

    public function getUntil(): ?string
    {
        return $this->get('until');
    }
}
