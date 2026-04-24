<?php

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'users.view', 'users.create', 'users.update', 'users.delete',
        'users.deactivate', 'users.reset-password', 'users.invite',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWithPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// viewAny
// ---------------------------------------------------------------------------

test('viewAny returns true for user with users.view', function () {
    $policy = new UserPolicy;

    expect($policy->viewAny(userWithPerm('users.view')))->toBeTrue();
});

test('viewAny returns false for user without users.view', function () {
    $policy = new UserPolicy;

    expect($policy->viewAny(User::factory()->create()))->toBeFalse();
});

// ---------------------------------------------------------------------------
// create
// ---------------------------------------------------------------------------

test('create returns true for user with users.create', function () {
    $policy = new UserPolicy;

    expect($policy->create(userWithPerm('users.create')))->toBeTrue();
});

test('create returns false for user without users.create', function () {
    $policy = new UserPolicy;

    expect($policy->create(User::factory()->create()))->toBeFalse();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('update returns true for user with users.update targeting another user', function () {
    $policy = new UserPolicy;
    $actor = userWithPerm('users.update');
    $other = User::factory()->create();

    expect($policy->update($actor, $other))->toBeTrue();
});

test('update returns false for user without users.update', function () {
    $policy = new UserPolicy;
    $actor = User::factory()->create();
    $other = User::factory()->create();

    expect($policy->update($actor, $other))->toBeFalse();
});

test('update returns false when targeting self', function () {
    $policy = new UserPolicy;
    $actor = userWithPerm('users.update');

    expect($policy->update($actor, $actor))->toBeFalse();
});

// ---------------------------------------------------------------------------
// delete
// ---------------------------------------------------------------------------

test('delete returns true for user with users.delete', function () {
    $policy = new UserPolicy;
    $actor = userWithPerm('users.delete');
    $other = User::factory()->create();

    expect($policy->delete($actor, $other))->toBeTrue();
});

test('delete returns false for user without users.delete', function () {
    $policy = new UserPolicy;
    $actor = User::factory()->create();
    $other = User::factory()->create();

    expect($policy->delete($actor, $other))->toBeFalse();
});

// ---------------------------------------------------------------------------
// deactivate
// ---------------------------------------------------------------------------

test('deactivate returns true for user with users.deactivate targeting another user', function () {
    $policy = new UserPolicy;
    $actor = userWithPerm('users.deactivate');
    $other = User::factory()->create();

    expect($policy->deactivate($actor, $other))->toBeTrue();
});

test('deactivate returns false for user without users.deactivate', function () {
    $policy = new UserPolicy;
    $actor = User::factory()->create();
    $other = User::factory()->create();

    expect($policy->deactivate($actor, $other))->toBeFalse();
});

test('deactivate returns false when targeting self', function () {
    $policy = new UserPolicy;
    $actor = userWithPerm('users.deactivate');

    expect($policy->deactivate($actor, $actor))->toBeFalse();
});

// ---------------------------------------------------------------------------
// resetPassword
// ---------------------------------------------------------------------------

test('resetPassword returns true for user with users.reset-password', function () {
    $policy = new UserPolicy;

    expect($policy->resetPassword(userWithPerm('users.reset-password')))->toBeTrue();
});

test('resetPassword returns false for user without users.reset-password', function () {
    $policy = new UserPolicy;

    expect($policy->resetPassword(User::factory()->create()))->toBeFalse();
});

// ---------------------------------------------------------------------------
// invite
// ---------------------------------------------------------------------------

test('invite returns true for user with users.invite', function () {
    $policy = new UserPolicy;

    expect($policy->invite(userWithPerm('users.invite')))->toBeTrue();
});

test('invite returns false for user without users.invite', function () {
    $policy = new UserPolicy;

    expect($policy->invite(User::factory()->create()))->toBeFalse();
});
