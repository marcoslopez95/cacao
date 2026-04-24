<?php

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

test('permission seeder creates the module permissions', function () {
    (new PermissionSeeder)->run();

    foreach (['roles.view', 'roles.create', 'roles.update', 'roles.delete', 'roles.assign-permissions'] as $name) {
        expect(Permission::where('name', $name)->where('guard_name', 'web')->exists())->toBeTrue();
    }
});

test('permission seeder is idempotent', function () {
    (new PermissionSeeder)->run();
    $countAfterFirst = Permission::count();

    (new PermissionSeeder)->run();

    expect(Permission::count())->toBe($countAfterFirst);
});

test('all users permissions are seeded', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->artisan('db:seed', ['--class' => 'PermissionSeeder'])->assertSuccessful();

    $expected = [
        'users.view', 'users.create', 'users.update', 'users.delete',
        'users.deactivate', 'users.reset-password', 'users.invite',
    ];

    foreach ($expected as $name) {
        expect(Permission::where('name', $name)->exists())->toBeTrue("Missing permission: {$name}");
    }
});

test('Admin role has all users permissions after seeding', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->artisan('db:seed', ['--class' => 'PermissionSeeder'])->assertSuccessful();
    $this->artisan('db:seed', ['--class' => 'RoleSeeder'])->assertSuccessful();

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $admin = Role::findByName('Admin');

    $expectedPerms = [
        'users.view', 'users.create', 'users.update', 'users.delete',
        'users.deactivate', 'users.reset-password', 'users.invite',
    ];

    foreach ($expectedPerms as $permission) {
        expect($admin->hasPermissionTo($permission))->toBeTrue("Admin missing permission: {$permission}");
    }
});
