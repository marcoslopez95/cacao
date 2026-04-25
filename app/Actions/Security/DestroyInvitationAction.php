<?php

namespace App\Actions\Security;

use App\Models\Invitation;

class DestroyInvitationAction
{
    /**
     * Delete a pending invitation. Aborts with 403 if the invitation has already been used.
     */
    public function handle(Invitation $invitation): void
    {
        abort_if($invitation->isUsed(), 403);

        $invitation->delete();
    }
}
