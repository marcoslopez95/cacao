<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated user is redirected to login', function () {
    $this->withoutVite();

    $this->get('/enrollment')
        ->assertRedirect('/login');
});

test('authenticated unverified user can access enrollment page', function () {
    $this->withoutVite();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('enrollment/Index'));
});

test('authenticated verified user can access enrollment page', function () {
    $this->withoutVite();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('enrollment/Index'));
});
