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

    public function getCode(): string
    {
        return $this->get('code');
    }

    public function isActive(): bool
    {
        return (bool) ($this->get('active') ?? true);
    }
}
