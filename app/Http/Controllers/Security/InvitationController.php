<?php

namespace App\Http\Controllers\Security;

use App\Actions\Security\DestroyInvitationAction;
use App\Actions\Security\StoreInvitationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreInvitationRequest;
use App\Http\Wrappers\Security\InvitationWrapper;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class InvitationController extends Controller
{
    /**
     * Store a newly created invitation and send the invitation email.
     * Cancels any existing pending invitation for the same email first.
     */
    public function store(StoreInvitationRequest $request, StoreInvitationAction $action): RedirectResponse
    {
        Gate::authorize('invite', User::class);

        $wrapper = new InvitationWrapper($request->validated());
        $invitation = $action->handle($wrapper, $request->user()->id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Invitación enviada a {$wrapper->getEmail()}. Expira en 48 horas.",
        ]);

        return to_route('security.users.index');
    }

    /**
     * Delete a pending invitation. Used invitations cannot be deleted.
     */
    public function destroy(Request $request, Invitation $invitation, DestroyInvitationAction $action): RedirectResponse
    {
        Gate::authorize('invite', User::class);

        $action->handle($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invitación cancelada.']);

        return to_route('security.users.index');
    }
}
