<?php

namespace App\Http\Wrappers\Infrastructure;

use Illuminate\Support\Collection;

class ClassroomWrapper extends Collection
{
    public function getBuildingId(): int
    {
        return (int) $this->get('building_id');
    }

    public function getIdentifier(): string
    {
        return (string) $this->get('identifier');
    }

    public function getType(): string
    {
        return (string) $this->get('type');
    }

    public function getCapacity(): int
    {
        return (int) $this->get('capacity');
    }
}
