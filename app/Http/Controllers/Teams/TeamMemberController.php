<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\RemoveTeamMemberAction;
use App\Actions\Teams\UpdateTeamMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\UpdateTeamMemberRequest;
use App\Http\Wrappers\Teams\TeamMemberWrapper;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamMemberController extends Controller
{
    /**
     * Update the specified team member's role.
     */
    public function update(UpdateTeamMemberRequest $request, Team $team, User $user, UpdateTeamMemberAction $action): RedirectResponse
    {
        Gate::authorize('updateMember', $team);

        $action->handle($team, $user, new TeamMemberWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member role updated.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Remove the specified team member.
     */
    public function destroy(Team $team, User $user, RemoveTeamMemberAction $action): RedirectResponse
    {
        Gate::authorize('removeMember', $team);

        $action->handle($team, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }
}
