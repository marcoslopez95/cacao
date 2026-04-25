<?php

namespace App\Actions\Security;

use App\Http\Wrappers\Security\InvitationWrapper;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use Illuminate\Support\Facades\Mail;

class StoreInvitationAction
{
    /**
     * Cancel any pending invitation for the same email, create a new one, and send it.
     */
    public function handle(InvitationWrapper $wrapper, int $invitedById): Invitation
    {
        Invitation::pending()->where('email', $wrapper->getEmail())->delete();

        $invitation = Invitation::create($wrapper->getStoreData($invitedById));

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return $invitation;
    }
}
