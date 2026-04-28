<?php

use App\Models\Professor;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);

    foreach (['professors.view', 'professors.create', 'professors.update', 'professors.delete'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWithProfessorPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

test('unauthenticated user cannot access professors', function () {
    $this->get('/scheduling/professors')->assertRedirect('/login');
});

test('user without permission cannot list professors', function () {
    $this->actingAs(User::factory()->create())
        ->get('/scheduling/professors')
        ->assertForbidden();
});
