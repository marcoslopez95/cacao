<?php

use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'roles.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'roles.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'roles.update', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'roles.delete', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'roles.assign-permissions', 'guard_name' => 'web']);
});

// ---------------------------------------------------------------------------
// viewAny
// ---------------------------------------------------------------------------

test('viewAny returns true for user with roles.view', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('roles.view');

    expect((new RolePolicy)->viewAny($user))->toBeTrue();
});

test('viewAny returns false for user without roles.view', function () {
    $user = User::factory()->create();

    expect((new RolePolicy)->viewAny($user))->toBeFalse();
});

// ---------------------------------------------------------------------------
// create
// ---------------------------------------------------------------------------

test('create returns true for user with roles.create', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('roles.create');

    expect((new RolePolicy)->create($user))->toBeTrue();
});

test('create returns false for user without roles.create', function () {
    $user = User::factory()->create();

    expect((new RolePolicy)->create($user))->toBeFalse();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('update returns true for normal role with roles.update', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('roles.update');
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);

    expect((new RolePolicy)->update($user, $role))->toBeTrue();
});

test('update returns false for normal role without roles.update', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);

    expect((new RolePolicy)->update($user, $role))->toBeFalse();
});

test('update returns false for Admin role even with roles.update', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('roles.update');
    $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);

    expect((new RolePolicy)->update($user, $adminRole))->toBeFalse();
});

test('update returns false for Admin role without any permission', function () {
    $user = User::factory()->create();
    $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);

    expect((new RolePolicy)->update($user, $adminRole))->toBeFalse();
});

// ---------------------------------------------------------------------------
// delete
// ---------------------------------------------------------------------------

test('delete returns true for normal role with roles.delete', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('roles.delete');
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);

    expect((new RolePolicy)->delete($user, $role))->toBeTrue();
});

test('delete returns false for normal role without roles.delete', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);

    expect((new RolePolicy)->delete($user, $role))->toBeFalse();
});

test('delete returns false for Admin role even with roles.delete', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('roles.delete');
    $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);

    expect((new RolePolicy)->delete($user, $adminRole))->toBeFalse();
});

test('delete returns false for Admin role without any permission', function () {
    $user = User::factory()->create();
    $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);

    expect((new RolePolicy)->delete($user, $adminRole))->toBeFalse();
});

// ---------------------------------------------------------------------------
// assignPermissions
// ---------------------------------------------------------------------------

test('assignPermissions returns true for normal role with roles.assign-permissions', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('roles.assign-permissions');
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);

    expect((new RolePolicy)->assignPermissions($user, $role))->toBeTrue();
});

test('assignPermissions returns false for Admin role even with roles.assign-permissions', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('roles.assign-permissions');
    $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);

    expect((new RolePolicy)->assignPermissions($user, $adminRole))->toBeFalse();
});
