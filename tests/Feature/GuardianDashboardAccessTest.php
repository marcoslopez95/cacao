<?php

use App\Models\Guardian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions());

test('representante can access guardian dashboard', function () {
    $guardian = Guardian::factory()->create();
    $user = $guardian->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('guardian.dashboard'))
        ->assertStatus(200);
});
