<?php

namespace App\Actions\Teams;

use App\Http\Wrappers\Teams\TeamInvitationWrapper;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use Illuminate\Support\Facades\Notification;

class StoreTeamInvitationAction
{
    /**
     * Create a team invitation and send the notification email.
     */
    public function handle(Team $team, TeamInvitationWrapper $wrapper, int $invitedById): TeamInvitation
    {
        $invitation = $team->invitations()->create([
            'email' => $wrapper->getEmail(),
            'role' => $wrapper->getRole(),
            'invited_by' => $invitedById,
            'expires_at' => now()->addDays(3),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation));

        return $invitation;
    }
}
