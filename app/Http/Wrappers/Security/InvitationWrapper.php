<?php

namespace App\Http\Wrappers\Security;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InvitationWrapper extends Collection
{
    public function getEmail(): string
    {
        return $this->get('email');
    }

    public function getRole(): string
    {
        return $this->get('role');
    }

    /**
     * @return array<string, mixed>
     */
    public function getStoreData(int $invitedById): array
    {
        return [
            'email' => $this->getEmail(),
            'role' => $this->getRole(),
            'token' => Str::uuid()->toString(),
            'invited_by' => $invitedById,
            'expires_at' => now()->addHours(48),
        ];
    }
}
