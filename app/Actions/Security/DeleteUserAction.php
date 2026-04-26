<?php

namespace App\Actions\Security;

use App\Models\CoordinationAssignment;
use App\Models\Invitation;
use App\Models\TeamInvitation;
use App\Models\User;

class DeleteUserAction
{
    /**
     * Delete the user if it has no coordination assignments.
     *
     * Returns false when coordination assignments exist (active or historical),
     * so the controller can flash the appropriate error message without throwing an exception.
     * Cleans up team memberships and invitations before deletion to satisfy RESTRICT constraints.
     */
    public function handle(User $user): bool
    {
        if (CoordinationAssignment::where('user_id', $user->id)
            ->orWhere('assigned_by', $user->id)
            ->exists()) {
            return false;
        }

        $user->teamMemberships()->delete();
        TeamInvitation::where('invited_by', $user->id)->delete();
        Invitation::where('invited_by', $user->id)->delete();

        $user->delete();

        return true;
    }
}
