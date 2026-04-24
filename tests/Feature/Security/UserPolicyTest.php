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

test('viewAny requires users.view', function () {
    $policy = new UserPolicy;

    expect($policy->viewAny(userWithPerm('users.view')))->toBeTrue()
        ->and($policy->viewAny(User::factory()->create()))->toBeFalse();
});

test('create requires users.create', function () {
    $policy = new UserPolicy;

    expect($policy->create(userWithPerm('users.create')))->toBeTrue()
        ->and($policy->create(User::factory()->create()))->toBeFalse();
});

test('update requires users.update and cannot edit self', function () {
    $policy = new UserPolicy;
    $actor = userWithPerm('users.update');
    $other = User::factory()->create();

    expect($policy->update($actor, $other))->toBeTrue()
        ->and($policy->update($actor, $actor))->toBeFalse();
});

test('delete requires users.delete', function () {
    $policy = new UserPolicy;
    $actor = userWithPerm('users.delete');
    $other = User::factory()->create();

    expect($policy->delete($actor, $other))->toBeTrue()
        ->and($policy->delete(User::factory()->create(), $other))->toBeFalse();
});

test('deactivate requires users.deactivate and cannot deactivate self', function () {
    $policy = new UserPolicy;
    $actor = userWithPerm('users.deactivate');
    $other = User::factory()->create();

    expect($policy->deactivate($actor, $other))->toBeTrue()
        ->and($policy->deactivate($actor, $actor))->toBeFalse();
});

test('resetPassword requires users.reset-password', function () {
    $policy = new UserPolicy;

    expect($policy->resetPassword(userWithPerm('users.reset-password')))->toBeTrue()
        ->and($policy->resetPassword(User::factory()->create()))->toBeFalse();
});

test('invite requires users.invite', function () {
    $policy = new UserPolicy;

    expect($policy->invite(userWithPerm('users.invite')))->toBeTrue()
        ->and($policy->invite(User::factory()->create()))->toBeFalse();
});
