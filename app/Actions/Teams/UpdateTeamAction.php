<?php

namespace App\Actions\Teams;

use App\Http\Wrappers\Teams\TeamWrapper;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class UpdateTeamAction
{
    /**
     * Update the team name using a pessimistic lock to prevent race conditions.
     */
    public function handle(Team $team, TeamWrapper $wrapper): Team
    {
        return DB::transaction(function () use ($team, $wrapper): Team {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();

            $team->update(['name' => $wrapper->getName()]);

            return $team;
        });
    }
}
