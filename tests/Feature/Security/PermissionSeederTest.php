<?php

use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Permission;

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
