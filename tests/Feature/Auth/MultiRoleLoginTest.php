<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['Admin', 'Profesor', 'Estudiante', 'Representante', 'Coordinador de Area'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

// ---------------------------------------------------------------------------
// Login redirect tests
// ---------------------------------------------------------------------------

test('admin with team is redirected to team dashboard after login', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    // UserFactory::configure() already creates a personal team and sets current_team_id
    $team = $user->currentTeam;
    expect($team)->not->toBeNull();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect("/{$team->slug}/dashboard");
    $this->assertAuthenticatedAs($user);
});

test('professor is redirected to professor dashboard after login', function () {
    // Professor users do not have a team so redirect resolves via role
    $user = User::factory()->create();

    // Remove the personal team created by the factory so redirect falls through to role-based
    $user->teams()->detach();
    $user->forceFill(['current_team_id' => null])->save();

    $user->assignRole('Profesor');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('professor.dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('student is redirected to student dashboard after login', function () {
    $user = User::factory()->create();
    $user->teams()->detach();
    $user->forceFill(['current_team_id' => null])->save();
    $user->assignRole('Estudiante');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('student.dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('guardian is redirected to guardian dashboard after login', function () {
    $user = User::factory()->create();
    $user->teams()->detach();
    $user->forceFill(['current_team_id' => null])->save();
    $user->assignRole('Representante');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('guardian.dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('coordinador de area is redirected to professor dashboard after login', function () {
    $user = User::factory()->create();
    $user->teams()->detach();
    $user->forceFill(['current_team_id' => null])->save();
    $user->assignRole('Coordinador de Area');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('professor.dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('inactive user cannot login', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

// ---------------------------------------------------------------------------
// Cross-portal access tests
// ---------------------------------------------------------------------------

test('professor cannot access student portal', function () {
    $user = User::factory()->create();
    $user->assignRole('Profesor');

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});

test('student cannot access professor portal', function () {
    $user = User::factory()->create();
    $user->assignRole('Estudiante');

    $this->actingAs($user)
        ->get(route('professor.dashboard'))
        ->assertForbidden();
});

test('guardian cannot access student portal', function () {
    $user = User::factory()->create();
    $user->assignRole('Representante');

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Unauthenticated redirect tests
// ---------------------------------------------------------------------------

test('unauthenticated user is redirected to login from professor portal', function () {
    $this->get(route('professor.dashboard'))
        ->assertRedirect(route('login'));
});

test('unauthenticated user is redirected to login from student portal', function () {
    $this->get(route('student.dashboard'))
        ->assertRedirect(route('login'));
});

test('unauthenticated user is redirected to login from guardian portal', function () {
    $this->get(route('guardian.dashboard'))
        ->assertRedirect(route('login'));
});
