<?php

namespace App\Actions\Teams;

use App\Http\Wrappers\Teams\TeamMemberWrapper;
use App\Models\Team;
use App\Models\User;

class UpdateTeamMemberAction
{
    /**
     * Update the role of a team member.
     */
    public function handle(Team $team, User $member, TeamMemberWrapper $wrapper): void
    {
        $team->memberships()
            ->where('user_id', $member->id)
            ->firstOrFail()
            ->update(['role' => $wrapper->getRole()]);
    }
}
