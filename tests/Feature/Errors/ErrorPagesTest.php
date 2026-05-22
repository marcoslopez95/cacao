<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Estudiante', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Representante', 'guard_name' => 'web']);
});

test('unknown route returns 404 with NotFound inertia page', function () {
    $this->get('/ruta-que-no-existe-en-cacao')
        ->assertStatus(404)
        ->assertInertia(fn ($page) => $page->component('errors/NotFound'));
});

test('404 preserves http status code', function () {
    $response = $this->get('/otra-ruta-inexistente-xyz');
    expect($response->getStatusCode())->toBe(404);
});

test('unauthenticated access to trigger-401 route returns 401 with AccessDenied page', function () {
    $this->get('/_test/trigger-401')
        ->assertStatus(401)
        ->assertInertia(fn ($page) => $page->component('errors/AccessDenied'));
});

test('401 page receives status prop 401', function () {
    $this->get('/_test/trigger-401')
        ->assertStatus(401)
        ->assertInertia(fn ($page) => $page
            ->component('errors/AccessDenied')
            ->where('status', 401)
        );
});

test('unauthorized role access returns 403 with AccessDenied page with status prop', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $this->actingAs($user)
        ->get('/enrollment')
        ->assertStatus(403)
        ->assertInertia(fn ($page) => $page
            ->component('errors/AccessDenied')
            ->where('status', 403)
        );
});

test('403 page receives status prop 403', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $this->actingAs($user)
        ->get('/enrollment')
        ->assertInertia(fn ($page) => $page->where('status', 403));
});
