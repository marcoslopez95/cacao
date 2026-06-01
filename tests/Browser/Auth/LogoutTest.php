<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseMigrations::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

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
