<?php

namespace App\Http\Wrappers\Academic;

use Illuminate\Support\Collection;

class SubjectWrapper extends Collection
{
    public function getPensumId(): int
    {
        return (int) $this->get('pensum_id');
    }

    public function getName(): string
    {
        return (string) $this->get('name');
    }

    public function getCode(): string
    {
        return (string) $this->get('code', '');
    }

    public function getCreditsUc(): int
    {
        return (int) $this->get('credits_uc');
    }

    public function getPeriodNumber(): int
    {
        return (int) $this->get('period_number');
    }

    public function getDescription(): ?string
    {
        return $this->get('description');
    }
}
