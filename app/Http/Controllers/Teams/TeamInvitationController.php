<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\AcceptTeamInvitationAction;
use App\Actions\Teams\StoreTeamInvitationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\AcceptTeamInvitationRequest;
use App\Http\Requests\Teams\CreateTeamInvitationRequest;
use App\Http\Wrappers\Teams\TeamInvitationWrapper;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamInvitationController extends Controller
{
    /**
     * Store a newly created invitation.
     */
    public function store(CreateTeamInvitationRequest $request, Team $team, StoreTeamInvitationAction $action): RedirectResponse
    {
        Gate::authorize('inviteMember', $team);

        $action->handle($team, new TeamInvitationWrapper($request->validated()), $request->user()->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Cancel the specified invitation.
     */
    public function destroy(Team $team, TeamInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->team_id === $team->id, 404);

        Gate::authorize('cancelInvitation', $team);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation cancelled.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(AcceptTeamInvitationRequest $request, TeamInvitation $invitation, AcceptTeamInvitationAction $action): RedirectResponse
    {
        $action->handle($request->user(), $invitation);

        return to_route('dashboard');
    }
}
