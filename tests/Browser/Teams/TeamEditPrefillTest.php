<?php

/**
 * Dusk acceptance test — HLZ-49: the team name input on the team settings page
 * must be prefilled with the current team name.
 */

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

test('team owner sees the current team name prefilled on the team settings page (HLZ-49)', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Prefill Check Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $this->browse(function (Browser $browser) use ($owner, $team) {
        $browser->resize(1440, 900)
            ->loginAs($owner)
            ->visit(route('teams.edit', $team, false))
            ->waitFor('[data-test="team-name-input"]', 10);

        expect($browser->value('[data-test="team-name-input"]'))->toBe('Prefill Check Team');
    });
});
