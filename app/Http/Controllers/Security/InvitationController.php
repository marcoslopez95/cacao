<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreInvitationRequest;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InvitationController extends Controller
{
    /**
     * Store a newly created invitation and send the invitation email.
     * Cancels any existing pending invitation for the same email first.
     */
    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        Gate::authorize('invite', User::class);

        $data = $request->validated();

        Invitation::pending()->where('email', $data['email'])->delete();

        $invitation = Invitation::create([
            'email' => $data['email'],
            'role' => $data['role'],
            'token' => Str::uuid()->toString(),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addHours(48),
        ]);

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Invitación enviada a {$data['email']}. Expira en 48 horas.",
        ]);

        return to_route('security.users.index');
    }

    /**
     * Delete a pending invitation. Used invitations cannot be deleted.
     */
    public function destroy(Request $request, Invitation $invitation): RedirectResponse
    {
        Gate::authorize('invite', User::class);

        abort_if($invitation->isUsed(), 403);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invitación cancelada.']);

        return to_route('security.users.index');
    }
}
