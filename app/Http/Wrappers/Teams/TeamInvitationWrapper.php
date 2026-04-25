<?php

namespace App\Http\Wrappers\Teams;

use App\Enums\TeamRole;
use Illuminate\Support\Collection;

class TeamInvitationWrapper extends Collection
{
    public function getEmail(): string
    {
        return $this->get('email');
    }

    public function getRole(): TeamRole
    {
        return TeamRole::from($this->get('role'));
    }
}
