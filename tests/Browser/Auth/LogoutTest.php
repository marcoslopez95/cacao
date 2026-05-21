<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->browse(function (Browser $browser) use ($user, $team) {
        $browser->loginAs($user)
            ->visit("/{$team->slug}/dashboard")
            ->waitFor('[data-test="logout-button"]')
            ->click('[data-test="logout-button"]')
            ->waitForLocation('/')
            ->assertGuest();
    });
});
