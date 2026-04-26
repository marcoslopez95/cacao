<?php

namespace App\Http\Wrappers\Academic;

use Illuminate\Support\Collection;

class CareerWrapper extends Collection
{
    public function getCategoryId(): int
    {
        return (int) $this->get('career_category_id');
    }

    public function getName(): string
    {
        return $this->get('name');
    }

    /** Used exclusively by UpdateCareerAction. Assumes key 'code' is present. */
    public function getCode(): string
    {
        return (string) $this->get('code');
    }

    public function isActive(): bool
    {
        return (bool) ($this->get('active') ?? true);
    }
}
