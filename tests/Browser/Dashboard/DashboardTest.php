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

test('admin can visit dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');
    $team = $user->currentTeam;

    $this->browse(function (Browser $browser) use ($user, $team) {
        $browser->loginAs($user)
            ->visit("/{$team->slug}/dashboard")
            ->waitForText('Bienvenido')
            ->assertSee('Bienvenido')
            ->assertSee('Inscripciones recientes');
    });
});

test('student can visit dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('Estudiante');
    $team = $user->currentTeam;

    $this->browse(function (Browser $browser) use ($user, $team) {
        $browser->loginAs($user)
            ->visit("/{$team->slug}/dashboard")
            ->waitForText('Bienvenido')
            ->assertSee('Bienvenido')
            ->assertSee('Período académico 2025-2');
    });
});

test('guest is redirected from dashboard to login', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->browse(function (Browser $browser) use ($team) {
        $browser->logout()
            ->visit("/{$team->slug}/dashboard")
            ->waitForLocation('/login')
            ->assertPathIs('/login');
    });
});
