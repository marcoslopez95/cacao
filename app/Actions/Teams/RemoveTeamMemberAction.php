<?php

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;

class RemoveTeamMemberAction
{
    /**
     * Remove the member from the team and switch them to their personal team if needed.
     */
    public function handle(Team $team, User $member): void
    {
        abort_if($team->owner()?->is($member), 403, __('The team owner cannot be removed.'));

        $team->memberships()
            ->where('user_id', $member->id)
            ->delete();

        if ($member->isCurrentTeam($team)) {
            $member->switchTeam($member->personalTeam());
        }
    }
}
