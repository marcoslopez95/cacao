<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);
});

test('show with valid token renders accept invitation page', function () {
    $invitation = Invitation::factory()->create(['role' => 'Profesor']);

    $this->get(route('invitation.show', $invitation->token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/AcceptInvitation', false)
            ->where('inviteEmail', $invitation->email)
            ->where('inviteRole', 'Profesor')
            ->where('token', $invitation->token)
            ->where('expired', false)
        );
});

test('show with expired token renders expired page', function () {
    $invitation = Invitation::factory()->expired()->create();

    $this->get(route('invitation.show', $invitation->token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('expired', true));
});

test('show with used token renders expired page', function () {
    $invitation = Invitation::factory()->used()->create();

    $this->get(route('invitation.show', $invitation->token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('expired', true));
});

test('show with unknown token returns 404', function () {
    $this->get(route('invitation.show', 'invalid-token'))->assertNotFound();
});

test('show with nonexistent uuid returns 404', function () {
    $this->get(route('invitation.show', Str::uuid()))->assertNotFound();
});

test('store creates user, assigns role, marks invitation used, and logs in', function () {
    $invitation = Invitation::factory()->create(['email' => 'nuevo@test.com', 'role' => 'Profesor']);

    $this->post(route('invitation.store', $invitation->token), [
        'name' => 'Nuevo Usuario',
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ])->assertRedirect();

    $user = User::where('email', 'nuevo@test.com')->firstOrFail();
    expect($user->hasRole('Profesor'))->toBeTrue()
        ->and($user->active)->toBeTrue();

    $invitation->refresh();
    expect($invitation->used_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('store on expired invitation returns 422', function () {
    $invitation = Invitation::factory()->expired()->create();

    $this->post(route('invitation.store', $invitation->token), [
        'name' => 'Test User',
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ])->assertStatus(422);
});

test('store on used invitation returns 422', function () {
    $invitation = Invitation::factory()->used()->create();

    $this->post(route('invitation.store', $invitation->token), [
        'name' => 'Test User',
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ])->assertStatus(422);
});

test('invitation token is single use', function () {
    $invitation = Invitation::factory()->create(['email' => 'once@test.com', 'role' => 'Profesor']);

    $this->post(route('invitation.store', $invitation->token), [
        'name' => 'First',
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ]);

    $this->post(route('invitation.store', $invitation->token), [
        'name' => 'Second',
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ])->assertStatus(422);
});
