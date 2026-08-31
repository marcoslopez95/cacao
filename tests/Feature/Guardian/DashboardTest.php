<?php

use App\Models\Catalogs\KinshipType;
use App\Models\Guardian;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
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

    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Otro', 'active' => true, 'sort_order' => 99]);
    Student::factory()->secondary()->count(2)->create()->each(
        fn ($s) => $s->guardians()->attach($guardian->id, ['kinship_type_id' => $kinship->id, 'is_primary' => true, 'is_emergency_contact' => false])
    );

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

    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Otro', 'active' => true, 'sort_order' => 99]);
    $student = Student::factory()->secondary()->create();
    $student->guardians()->attach($guardian->id, ['kinship_type_id' => $kinship->id, 'is_primary' => true, 'is_emergency_contact' => false]);

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

test('returns 404 when user has no guardian profile', function () {
    $role = Role::firstOrCreate(['name' => 'Representante', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($user)->get(route('guardian.dashboard'));
    $response->assertStatus(404);
});

test('guardian cannot access professor portal', function () {
    $guardian = Guardian::factory()->create();
    $user = $guardian->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('professor.dashboard'))
        ->assertForbidden();
});

test('guardian cannot access student portal', function () {
    $guardian = Guardian::factory()->create();
    $user = $guardian->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});

test('professor cannot access guardian portal', function () {
    $professor = Professor::factory()->create();
    $user = $professor->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('guardian.dashboard'))
        ->assertForbidden();
});

test('student cannot access guardian portal', function () {
    $student = Student::factory()->create();
    $user = $student->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('guardian.dashboard'))
        ->assertForbidden();
});
