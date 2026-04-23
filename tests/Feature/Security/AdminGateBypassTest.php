<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('admin role bypasses any gate check', function () {
    Role::findOrCreate('Admin', 'web');

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    expect($admin->can('any.arbitrary.ability'))->toBeTrue();
    expect($admin->can('roles.delete'))->toBeTrue();
});

test('non admin user does not bypass the gate', function () {
    $user = User::factory()->create();

    expect($user->can('any.arbitrary.ability'))->toBeFalse();
});
