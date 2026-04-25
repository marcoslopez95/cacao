<?php

namespace App\Http\Wrappers\Security;

use Illuminate\Support\Collection;

class RoleWrapper extends Collection
{
    public function getName(): string
    {
        return $this->get('name');
    }

    /**
     * @return array<int, string>
     */
    public function getPermissions(): array
    {
        return $this->get('permissions', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getStoreData(): array
    {
        return [
            'name' => $this->getName(),
            'guard_name' => 'web',
        ];
    }
}
