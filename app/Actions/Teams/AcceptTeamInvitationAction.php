<?php

namespace App\Actions\Teams;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AcceptTeamInvitationAction
{
    /**
     * Add the user to the team (or reuse their existing membership) and mark the invitation as accepted.
     */
    public function handle(User $user, TeamInvitation $invitation): void
    {
        DB::transaction(function () use ($user, $invitation): void {
            $team = $invitation->team;

            $team->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $invitation->role],
            );

            $invitation->update(['accepted_at' => now()]);

            $user->switchTeam($team);
        });
    }
}
