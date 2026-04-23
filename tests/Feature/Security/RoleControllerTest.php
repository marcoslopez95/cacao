<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'roles.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'roles.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'roles.update', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'roles.delete', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'roles.assign-permissions', 'guard_name' => 'web']);
});

/**
 * Return a user with the given permission.
 */
function userWith(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('unauthenticated user is redirected to login', function () {
    $this->get('/security/roles')
        ->assertRedirect('/login');
});

test('authenticated user without roles.view gets 403', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/security/roles')
        ->assertForbidden();
});

test('user with roles.view sees the roles index', function () {
    $user = userWith('roles.view');

    $this->actingAs($user)
        ->get('/security/roles')
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('security/Roles/Index', false)
                ->has('roles')
                ->has('permissions')
        );
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('user without roles.create cannot store a role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/security/roles', ['name' => 'Editor', 'permissions' => []])
        ->assertForbidden();
});

test('store fails with validation error when name is duplicate', function () {
    Role::create(['name' => 'Editor', 'guard_name' => 'web']);

    $user = userWith('roles.create');

    $this->actingAs($user)
        ->post('/security/roles', ['name' => 'Editor', 'permissions' => []])
        ->assertSessionHasErrors('name');
});

test('user with roles.create can store a new role', function () {
    $user = userWith('roles.create');

    $this->actingAs($user)
        ->post('/security/roles', ['name' => 'Editor', 'permissions' => []])
        ->assertRedirect(route('security.roles.index'));

    expect(Role::where('name', 'Editor')->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('update on Admin role returns 403', function () {
    $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
    $user = userWith('roles.update');

    $this->actingAs($user)
        ->patch("/security/roles/{$adminRole->id}", ['name' => 'SuperAdmin', 'permissions' => []])
        ->assertForbidden();
});

test('user with roles.update can update a normal role', function () {
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $user = userWith('roles.update');

    $this->actingAs($user)
        ->patch("/security/roles/{$role->id}", ['name' => 'Writer', 'permissions' => []])
        ->assertRedirect(route('security.roles.index'));

    expect($role->fresh()->name)->toBe('Writer');
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('destroy on Admin role returns 403', function () {
    $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
    $user = userWith('roles.delete');

    $this->actingAs($user)
        ->delete("/security/roles/{$adminRole->id}")
        ->assertForbidden();
});

test('destroy fails with flash error when role has users assigned', function () {
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $user = userWith('roles.delete');
    $user->assignRole($role);

    $this->actingAs($user)
        ->delete("/security/roles/{$role->id}")
        ->assertRedirect(route('security.roles.index'));

    expect(Role::where('name', 'Editor')->exists())->toBeTrue();
});

test('destroy deletes role when no users are assigned', function () {
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $user = userWith('roles.delete');

    $this->actingAs($user)
        ->delete("/security/roles/{$role->id}")
        ->assertRedirect(route('security.roles.index'));

    expect(Role::where('name', 'Editor')->exists())->toBeFalse();
});

// ---------------------------------------------------------------------------
// Inertia shared auth.roles prop
// ---------------------------------------------------------------------------

test('authenticated user sees their roles in shared auth prop', function () {
    $user = userWith('roles.view');

    $this->actingAs($user)
        ->get('/security/roles')
        ->assertInertia(
            fn ($page) => $page->has('auth.roles')
        );
});
