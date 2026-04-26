<?php

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteTeamAction
{
    /**
     * Transfer all other members to their personal team, then delete the team and all its data.
     */
    public function handle(Team $team, User $actor): void
    {
        DB::transaction(function () use ($team): void {
            User::where('current_team_id', $team->id)
                ->each(fn (User $affectedUser) => $affectedUser->switchTeam($affectedUser->personalTeam()));

            $team->invitations()->delete();
            $team->memberships()->delete();
            $team->delete();
        });
    }
}
