<?php

use App\Models\Catalogs\KinshipType;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Role::findOrCreate('Admin', 'web');
});

// ---------------------------------------------------------------------------
// Access
// ---------------------------------------------------------------------------

test('unauthenticated user is redirected to login', function () {
    $this->get('/academic/guardians')->assertRedirect('/login');
});

test('authenticated non-admin user gets 403 on the guardians index', function () {
    Role::findOrCreate('Profesor', 'web');

    $this->actingAs(User::factory()->create()->assignRole('Profesor'))
        ->get('/academic/guardians')
        ->assertForbidden();
});

test('authenticated non-admin user gets 403 on the guardians show route', function () {
    Role::findOrCreate('Estudiante', 'web');
    $guardian = Guardian::factory()->create();

    $this->actingAs(User::factory()->create()->assignRole('Estudiante'))
        ->get("/academic/guardians/{$guardian->id}")
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Index
// ---------------------------------------------------------------------------

test('authenticated admin can access the guardians index', function () {
    $this->actingAs(User::factory()->create()->assignRole('Admin'))
        ->get('/academic/guardians')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Guardians/Index')
            ->has('guardians')
            ->has('filters')
        );
});

test('guardians are listed with their student count', function () {
    $guardian = Guardian::factory()->create();
    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Otro', 'active' => true, 'sort_order' => 99]);

    $student = Student::factory()->create();
    $student->guardians()->attach($guardian->id, [
        'kinship_type_id' => $kinship->id,
        'is_primary' => true,
        'is_emergency_contact' => false,
    ]);

    $this->actingAs(User::factory()->create()->assignRole('Admin'))
        ->get('/academic/guardians')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('guardians.data', 1, fn ($row) => $row
                ->where('id', $guardian->id)
                ->where('name', $guardian->user->name)
                ->where('email', $guardian->user->email)
                ->where('students_count', 1)
                ->etc()
            )
        );
});

test('search by name returns matching guardians only', function () {
    $match = Guardian::factory()->create();
    $match->user->update(['first_name' => 'Marisol', 'last_name' => 'Contreras Especial']);

    $other = Guardian::factory()->create();
    $other->user->update(['first_name' => 'Julio', 'last_name' => 'Pérez']);

    $this->actingAs(User::factory()->create()->assignRole('Admin'))
        ->get('/academic/guardians?search=marisol')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('guardians.data', 1)
            ->where('guardians.data.0.name', 'Marisol Contreras Especial')
        );
});

// ---------------------------------------------------------------------------
// Show
// ---------------------------------------------------------------------------

test('show returns 404 for a non-existent guardian', function () {
    $this->actingAs(User::factory()->create()->assignRole('Admin'))
        ->get('/academic/guardians/999999')
        ->assertNotFound();
});

test('show returns the guardian with no students to charge', function () {
    $guardian = Guardian::factory()->create();

    $this->actingAs(User::factory()->create()->assignRole('Admin'))
        ->get("/academic/guardians/{$guardian->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Guardians/Show')
            ->where('guardian.id', $guardian->id)
            ->where('guardian.students', [])
        );
});

test('show returns the guardian with kinship resolved to a readable label', function () {
    $guardian = Guardian::factory()->create();
    $kinship = KinshipType::firstOrCreate(['code' => 'mother'], ['name' => 'Madre', 'active' => true, 'sort_order' => 1]);

    $student = Student::factory()->secondary()->create();
    $student->guardians()->attach($guardian->id, [
        'kinship_type_id' => $kinship->id,
        'is_primary' => true,
        'is_emergency_contact' => true,
    ]);

    $this->actingAs(User::factory()->create()->assignRole('Admin'))
        ->get("/academic/guardians/{$guardian->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('guardian.students', 1, fn ($row) => $row
                ->where('id', $student->id)
                ->where('name', $student->user->name)
                ->where('kinship', 'Madre')
                ->where('primary', true)
                ->where('emergency_contact', true)
                ->etc()
            )
        );
});
