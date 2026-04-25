<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Http\Wrappers\Teams\TeamWrapper;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTeamAction
{
    /**
     * Create a new team, add the user as owner, and switch to the new team.
     */
    public function handle(User $user, TeamWrapper $wrapper, bool $isPersonal = false): Team
    {
        return DB::transaction(function () use ($user, $wrapper, $isPersonal): Team {
            $team = Team::create(array_merge($wrapper->getStoreData(), ['is_personal' => $isPersonal]));

            $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::Owner,
            ]);

            $user->switchTeam($team);

            return $team;
        });
    }
}
