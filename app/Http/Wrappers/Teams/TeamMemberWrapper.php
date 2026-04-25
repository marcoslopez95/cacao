<?php

namespace App\Http\Wrappers\Teams;

use App\Enums\TeamRole;
use Illuminate\Support\Collection;

class TeamMemberWrapper extends Collection
{
    public function getRole(): TeamRole
    {
        return TeamRole::from($this->get('role'));
    }
}
