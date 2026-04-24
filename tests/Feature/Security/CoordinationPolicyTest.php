<?php

use App\Models\Coordination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'coordinations.view', 'coordinations.create', 'coordinations.edit',
        'coordinations.delete', 'coordinations.assign', 'coordinations.view_history',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function coordinationUserWith(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

test('viewAny requires coordinations.view', function () {
    $noPermUser = User::factory()->create();
    $permUser = coordinationUserWith('coordinations.view');

    expect($noPermUser->can('viewAny', Coordination::class))->toBeFalse();
    expect($permUser->can('viewAny', Coordination::class))->toBeTrue();
});

test('create requires coordinations.create', function () {
    $noPermUser = User::factory()->create();
    $permUser = coordinationUserWith('coordinations.create');

    expect($noPermUser->can('create', Coordination::class))->toBeFalse();
    expect($permUser->can('create', Coordination::class))->toBeTrue();
});

test('update requires coordinations.edit', function () {
    $noPermUser = User::factory()->create();
    $permUser = coordinationUserWith('coordinations.edit');
    $coordination = Coordination::factory()->create();

    expect($noPermUser->can('update', $coordination))->toBeFalse();
    expect($permUser->can('update', $coordination))->toBeTrue();
});

test('delete requires coordinations.delete', function () {
    $noPermUser = User::factory()->create();
    $permUser = coordinationUserWith('coordinations.delete');
    $coordination = Coordination::factory()->create();

    expect($noPermUser->can('delete', $coordination))->toBeFalse();
    expect($permUser->can('delete', $coordination))->toBeTrue();
});

test('assign requires coordinations.assign', function () {
    $noPermUser = User::factory()->create();
    $permUser = coordinationUserWith('coordinations.assign');
    $coordination = Coordination::factory()->create();

    expect($noPermUser->can('assign', $coordination))->toBeFalse();
    expect($permUser->can('assign', $coordination))->toBeTrue();
});

test('viewHistory requires coordinations.view_history', function () {
    $noPermUser = User::factory()->create();
    $permUser = coordinationUserWith('coordinations.view_history');
    $coordination = Coordination::factory()->create();

    expect($noPermUser->can('viewHistory', $coordination))->toBeFalse();
    expect($permUser->can('viewHistory', $coordination))->toBeTrue();
});
