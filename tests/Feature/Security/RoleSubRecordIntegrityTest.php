<?php

/**
 * Implementation tests for feature: role-sub-record-integrity
 *
 * Tests for CreateUserAction, UpdateUserAction sub-record creation,
 * and the users:fix-orphan-records artisan command.
 */

use App\Actions\Security\CreateUserAction;
use App\Actions\Security\UpdateUserAction;
use App\Enums\EducationalLevel;
use App\Http\Wrappers\Security\UserWrapper;
use App\Models\Professor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Estudiante', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Representante', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build a UserWrapper for creating a user with the given role.
 */
function makeCreateWrapper(string $roleName): UserWrapper
{
    return new UserWrapper(new Collection([
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'email' => fake()->unique()->safeEmail(),
        'password_mode' => 'manual',
        'password' => 'password',
        'role' => $roleName,
    ]));
}

/**
 * Build a UserWrapper for updating a user with the given roles array.
 *
 * @param  array<int, string>  $roles
 */
function makeUpdateWrapper(User $user, array $roles): UserWrapper
{
    return new UserWrapper(new Collection([
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'roles' => $roles,
    ]));
}

// ---------------------------------------------------------------------------
// T07 — CreateUserAction: rol Profesor crea fila en professors
// ---------------------------------------------------------------------------

test('T07: CreateUserAction con rol Profesor crea fila en professors', function () {
    $user = app(CreateUserAction::class)->handle(makeCreateWrapper('Profesor'));

    $this->assertDatabaseHas('professors', ['user_id' => $user->id]);
    $this->assertDatabaseCount('professors', 1);
});

// ---------------------------------------------------------------------------
// T08 — CreateUserAction: rol Estudiante crea fila en students con educational_level
// ---------------------------------------------------------------------------

test('T08: CreateUserAction con rol Estudiante crea fila en students con educational_level por defecto', function () {
    $user = app(CreateUserAction::class)->handle(makeCreateWrapper('Estudiante'));

    $this->assertDatabaseHas('students', [
        'user_id' => $user->id,
        'educational_level' => EducationalLevel::University->value,
    ]);
    $this->assertDatabaseCount('students', 1);
});

// ---------------------------------------------------------------------------
// T09 — CreateUserAction: rol Representante crea fila en guardians
// ---------------------------------------------------------------------------

test('T09: CreateUserAction con rol Representante crea fila en guardians', function () {
    $user = app(CreateUserAction::class)->handle(makeCreateWrapper('Representante'));

    $this->assertDatabaseHas('guardians', ['user_id' => $user->id]);
    $this->assertDatabaseCount('guardians', 1);
});

// ---------------------------------------------------------------------------
// T10 — UpdateUserAction: cambiar rol a Profesor crea fila en professors si no existe
// ---------------------------------------------------------------------------

test('T10: UpdateUserAction cambiando rol a Profesor crea fila en professors si no existe', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    // Precondition: no professor row
    $this->assertDatabaseMissing('professors', ['user_id' => $user->id]);

    app(UpdateUserAction::class)->handle($user, makeUpdateWrapper($user, ['Profesor']));

    $this->assertDatabaseHas('professors', ['user_id' => $user->id]);
});

test('T10b: UpdateUserAction con rol Profesor existente no duplica fila en professors', function () {
    // User already has a professor sub-record
    $professor = Professor::factory()->create();
    $user = $professor->user;
    $user->syncRoles(['Profesor']);

    app(UpdateUserAction::class)->handle($user, makeUpdateWrapper($user, ['Profesor']));

    // firstOrCreate must not duplicate
    $this->assertDatabaseCount('professors', 1);
});

// ---------------------------------------------------------------------------
// T11 — users:fix-orphan-records crea sub-registros faltantes
// ---------------------------------------------------------------------------

test('T11: users:fix-orphan-records crea sub-registros faltantes', function () {
    $professorUser = User::factory()->create();
    $professorUser->assignRole('Profesor');

    $studentUser = User::factory()->create();
    $studentUser->assignRole('Estudiante');

    $guardianUser = User::factory()->create();
    $guardianUser->assignRole('Representante');

    // Confirm orphan state
    $this->assertDatabaseMissing('professors', ['user_id' => $professorUser->id]);
    $this->assertDatabaseMissing('students', ['user_id' => $studentUser->id]);
    $this->assertDatabaseMissing('guardians', ['user_id' => $guardianUser->id]);

    $this->artisan('users:fix-orphan-records')->assertSuccessful();

    $this->assertDatabaseHas('professors', ['user_id' => $professorUser->id]);
    $this->assertDatabaseHas('students', [
        'user_id' => $studentUser->id,
        'educational_level' => EducationalLevel::University->value,
    ]);
    $this->assertDatabaseHas('guardians', ['user_id' => $guardianUser->id]);
});

// ---------------------------------------------------------------------------
// T12 — users:fix-orphan-records con --dry-run no modifica datos
// ---------------------------------------------------------------------------

test('T12: users:fix-orphan-records con --dry-run no crea registros', function () {
    $orphanProfessor = User::factory()->create();
    $orphanProfessor->assignRole('Profesor');

    $orphanStudent = User::factory()->create();
    $orphanStudent->assignRole('Estudiante');

    $orphanGuardian = User::factory()->create();
    $orphanGuardian->assignRole('Representante');

    $this->artisan('users:fix-orphan-records --dry-run')->assertSuccessful();

    $this->assertDatabaseMissing('professors', ['user_id' => $orphanProfessor->id]);
    $this->assertDatabaseMissing('students', ['user_id' => $orphanStudent->id]);
    $this->assertDatabaseMissing('guardians', ['user_id' => $orphanGuardian->id]);
});

// ---------------------------------------------------------------------------
// Edge case — rol sin sub-registro (Admin) no genera registros extra
// ---------------------------------------------------------------------------

test('CreateUserAction con rol Admin no crea filas en professors/students/guardians', function () {
    $user = app(CreateUserAction::class)->handle(makeCreateWrapper('Admin'));

    $this->assertDatabaseMissing('professors', ['user_id' => $user->id]);
    $this->assertDatabaseMissing('students', ['user_id' => $user->id]);
    $this->assertDatabaseMissing('guardians', ['user_id' => $user->id]);
});
