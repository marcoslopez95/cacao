<?php

/**
 * Acceptance tests for feature: user-seeder-subrecord-fix
 *
 * These tests define the acceptance contract and are INTOCABLE by the implementer.
 * They must be RED before implementation begins and GREEN after.
 *
 * Acceptance criteria verified:
 *  RF-01: Tras UserSeeder, existe exactamente una fila en professors para el usuario Profesor
 *  RF-02: Tras UserSeeder, existe exactamente una fila en students para el usuario Estudiante
 *  RF-03: El seeder es idempotente — llamarlo dos veces no duplica filas
 *  RF-04: El seeder funciona para el path "usuario ya existente" (update path)
 */

use App\Models\Professor;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\CatalogsSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // UserSeeder depends on roles existing — replicate the DatabaseSeeder dependency order
    $this->seed(CatalogsSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// ---------------------------------------------------------------------------
// RF-01 — Profesor Demo tiene exactamente una fila en professors
// ---------------------------------------------------------------------------

it('RF-01: UserSeeder crea exactamente una fila en professors para el usuario con rol Profesor', function () {
    $this->seed(UserSeeder::class);

    $user = User::whereHas('roles', fn ($q) => $q->where('name', 'Profesor'))->first();

    expect($user)->not->toBeNull('El usuario con rol Profesor debe existir tras el seeder');

    expect(Professor::where('user_id', $user->id)->count())->toBe(1);
    $this->assertDatabaseHas('professors', ['user_id' => $user->id]);
});

// ---------------------------------------------------------------------------
// RF-02 — Estudiante Demo tiene exactamente una fila en students
// ---------------------------------------------------------------------------

it('RF-02: UserSeeder crea exactamente una fila en students para el usuario con rol Estudiante', function () {
    $this->seed(UserSeeder::class);

    $user = User::whereHas('roles', fn ($q) => $q->where('name', 'Estudiante'))->first();

    expect($user)->not->toBeNull('El usuario con rol Estudiante debe existir tras el seeder');

    expect(Student::where('user_id', $user->id)->count())->toBe(1);
    $this->assertDatabaseHas('students', ['user_id' => $user->id]);
});

// ---------------------------------------------------------------------------
// RF-03 — Idempotencia: llamar el seeder dos veces no duplica sub-registros
// ---------------------------------------------------------------------------

it('RF-03: ejecutar UserSeeder dos veces no duplica filas en professors', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    $user = User::whereHas('roles', fn ($q) => $q->where('name', 'Profesor'))->first();

    expect($user)->not->toBeNull();
    expect(Professor::where('user_id', $user->id)->count())->toBe(1);
});

it('RF-03: ejecutar UserSeeder dos veces no duplica filas en students', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    $user = User::whereHas('roles', fn ($q) => $q->where('name', 'Estudiante'))->first();

    expect($user)->not->toBeNull();
    expect(Student::where('user_id', $user->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// RF-04 — Path "usuario ya existente": si el usuario existe antes del seeder,
//          ensureSubRecord igual crea el sub-registro si no existe
// ---------------------------------------------------------------------------

it('RF-04: si el usuario Profesor ya existe sin sub-registro, el seeder lo crea', function () {
    // Pre-create the user exactly as the YAML defines, but WITHOUT the sub-record
    $user = User::factory()->create([
        'email' => 'profesor@cacao.edu.ve',
    ]);
    $user->syncRoles(['Profesor']);

    // Sanity: no sub-record yet
    expect(Professor::where('user_id', $user->id)->exists())->toBeFalse();

    // Run the seeder — it will hit the "existing user" path
    $this->seed(UserSeeder::class);

    // The seeder must have created the sub-record
    expect(Professor::where('user_id', $user->id)->exists())->toBeTrue();
    $this->assertDatabaseHas('professors', ['user_id' => $user->id]);
});

it('RF-04: si el usuario Estudiante ya existe sin sub-registro, el seeder lo crea', function () {
    $user = User::factory()->create([
        'email' => 'estudiante@cacao.edu.ve',
    ]);
    $user->syncRoles(['Estudiante']);

    expect(Student::where('user_id', $user->id)->exists())->toBeFalse();

    $this->seed(UserSeeder::class);

    expect(Student::where('user_id', $user->id)->exists())->toBeTrue();
    $this->assertDatabaseHas('students', ['user_id' => $user->id]);
});
