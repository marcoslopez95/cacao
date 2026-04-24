<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AcceptInvitationController extends Controller
{
    /**
     * Render the invitation acceptance page.
     */
    public function show(string $token): Response
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            return Inertia::render('auth/AcceptInvitation', [
                'expired' => true,
                'inviteEmail' => $invitation->email,
                'inviteRole' => $invitation->role,
                'token' => $token,
            ]);
        }

        return Inertia::render('auth/AcceptInvitation', [
            'expired' => false,
            'inviteEmail' => $invitation->email,
            'inviteRole' => $invitation->role,
            'inviteExpiresIn' => $invitation->expires_at->diffForHumans(),
            'token' => $token,
        ]);
    }

    /**
     * Create the user account from the invitation and log them in.
     */
    public function store(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            abort(422, 'Esta invitación ya fue usada o ha expirado.');
        }

        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $invitation->email,
            'password' => Hash::make($data['password']),
            'active' => true,
        ]);

        $user->syncRoles([$invitation->role]);

        $invitation->markAsUsed();

        Auth::login($user);

        return to_route('home');
    }
}
