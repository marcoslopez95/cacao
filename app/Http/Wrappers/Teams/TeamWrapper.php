<?php

namespace App\Http\Wrappers\Teams;

use Illuminate\Support\Collection;

class TeamWrapper extends Collection
{
    public function getName(): string
    {
        return $this->get('name');
    }

    /**
     * @return array<string, mixed>
     */
    public function getStoreData(): array
    {
        return [
            'name' => $this->getName(),
        ];
    }
}
