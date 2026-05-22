<?php

use App\Models\Guardian;
use App\Models\Period;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions());

test('unauthenticated user is redirected from guardian dashboard', function () {
    $this->get(route('guardian.dashboard'))
        ->assertRedirect(route('login'));
});

test('guardian dashboard returns 200 with correct props', function () {
    $guardian = Guardian::factory()->create();
    $user = $guardian->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('guardian.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('guardian/Dashboard')
            ->has('period')
            ->has('students')
        );
});

test('guardian dashboard returns students array with one entry per representado', function () {
    $guardian = Guardian::factory()->create();
    $user = $guardian->user;

    Student::factory()->count(2)->create(['guardian_id' => $guardian->id]);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('guardian.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('guardian/Dashboard')
            ->has('students', 2)
        );
});

test('guardian dashboard returns null period when no active period exists', function () {
    Period::where('status', 'active')->delete();

    $guardian = Guardian::factory()->create();
    $user = $guardian->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('guardian.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('guardian/Dashboard')
            ->where('period', null)
        );
});

test('guardian dashboard student entry has nota_promedio and inasistencias as null', function () {
    $guardian = Guardian::factory()->create();
    $user = $guardian->user;

    Student::factory()->create(['guardian_id' => $guardian->id]);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('guardian.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('guardian/Dashboard')
            ->has('students', 1)
            ->where('students.0.nota_promedio', null)
            ->where('students.0.inasistencias', null)
            ->where('students.0.subjects', [])
        );
});

test('guardian without linked guardian record gets 404', function () {
    // A user with Representante role but no Guardian model should get a 404
    $guardian = Guardian::factory()->create();
    $user = $guardian->user;

    // Delete the guardian record so the user has no guardian
    $guardian->students()->delete();
    $guardian->delete();

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('guardian.dashboard'))
        ->assertNotFound();
});
