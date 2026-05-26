<?php

/**
 * Acceptance tests for feature: role-sub-record-integrity
 *
 * These tests define the acceptance contract and are INTOCABLE by the implementer.
 * They must be RED before implementation begins and GREEN after.
 *
 * Acceptance criteria verified:
 *  AC-1: CreateUserAction con rol Profesor → fila en professors creada automáticamente
 *  AC-2: CreateUserAction con rol Estudiante → fila en students con educational_level por defecto
 *  AC-3: CreateUserAction con rol Representante → fila en guardians creada automáticamente
 *  AC-4: UpdateUserAction cambia rol a Profesor → crea fila en professors si no existe
 *  AC-5: users:fix-orphan-records repara usuarios con rol sin sub-registro
 *  AC-6: users:fix-orphan-records con --dry-run no modifica datos
 */

use App\Actions\Security\CreateUserAction;
use App\Actions\Security\UpdateUserAction;
use App\Enums\EducationalLevel;
use App\Http\Wrappers\Security\UserWrapper;
use App\Models\Guardian;
use App\Models\Professor;
use App\Models\Student;
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
function createWrapperFor(string $roleName): UserWrapper
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
function updateWrapperFor(User $user, array $roles): UserWrapper
{
    return new UserWrapper(new Collection([
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'roles' => $roles,
    ]));
}

// ---------------------------------------------------------------------------
// AC-1: CreateUserAction con rol Profesor crea fila en professors
// ---------------------------------------------------------------------------

test('AC-1: crear usuario con rol Profesor crea fila en professors', function () {
    $wrapper = createWrapperFor('Profesor');
    $user = app(CreateUserAction::class)->handle($wrapper);

    expect(Professor::where('user_id', $user->id)->exists())->toBeTrue();
    $this->assertDatabaseHas('professors', ['user_id' => $user->id]);
});

// ---------------------------------------------------------------------------
// AC-2: CreateUserAction con rol Estudiante crea fila en students con educational_level
// ---------------------------------------------------------------------------

test('AC-2: crear usuario con rol Estudiante crea fila en students con educational_level por defecto', function () {
    $wrapper = createWrapperFor('Estudiante');
    $user = app(CreateUserAction::class)->handle($wrapper);

    expect(Student::where('user_id', $user->id)->exists())->toBeTrue();
    $this->assertDatabaseHas('students', [
        'user_id' => $user->id,
        'educational_level' => EducationalLevel::University->value,
    ]);
});

// ---------------------------------------------------------------------------
// AC-3: CreateUserAction con rol Representante crea fila en guardians
// ---------------------------------------------------------------------------

test('AC-3: crear usuario con rol Representante crea fila en guardians', function () {
    $wrapper = createWrapperFor('Representante');
    $user = app(CreateUserAction::class)->handle($wrapper);

    expect(Guardian::where('user_id', $user->id)->exists())->toBeTrue();
    $this->assertDatabaseHas('guardians', ['user_id' => $user->id]);
});

// ---------------------------------------------------------------------------
// AC-4: UpdateUserAction cambia rol a Profesor crea fila en professors si no existe
// ---------------------------------------------------------------------------

test('AC-4: actualizar usuario cambiando rol a Profesor crea fila en professors si no existe', function () {
    // User starts with no role-specific sub-record
    $user = User::factory()->create();
    $user->assignRole('Admin');

    // Sanity: no professor row yet
    expect(Professor::where('user_id', $user->id)->exists())->toBeFalse();

    $wrapper = updateWrapperFor($user, ['Profesor']);
    app(UpdateUserAction::class)->handle($user, $wrapper);

    expect(Professor::where('user_id', $user->id)->exists())->toBeTrue();
    $this->assertDatabaseHas('professors', ['user_id' => $user->id]);
});

// ---------------------------------------------------------------------------
// AC-5: users:fix-orphan-records repara usuarios con rol sin sub-registro
// ---------------------------------------------------------------------------

test('AC-5: users:fix-orphan-records crea sub-registros faltantes', function () {
    // Create orphan users: have role but no sub-record
    $professorUser = User::factory()->create();
    $professorUser->assignRole('Profesor');

    $studentUser = User::factory()->create();
    $studentUser->assignRole('Estudiante');

    $guardianUser = User::factory()->create();
    $guardianUser->assignRole('Representante');

    // Confirm no sub-records exist yet
    expect(Professor::where('user_id', $professorUser->id)->exists())->toBeFalse();
    expect(Student::where('user_id', $studentUser->id)->exists())->toBeFalse();
    expect(Guardian::where('user_id', $guardianUser->id)->exists())->toBeFalse();

    // Run the repair command
    $this->artisan('users:fix-orphan-records')->assertSuccessful();

    // Assert all sub-records were created
    $this->assertDatabaseHas('professors', ['user_id' => $professorUser->id]);
    $this->assertDatabaseHas('students', [
        'user_id' => $studentUser->id,
        'educational_level' => EducationalLevel::University->value,
    ]);
    $this->assertDatabaseHas('guardians', ['user_id' => $guardianUser->id]);
});

// ---------------------------------------------------------------------------
// AC-6: users:fix-orphan-records con --dry-run no modifica datos
// ---------------------------------------------------------------------------

test('AC-6: users:fix-orphan-records con --dry-run no crea registros', function () {
    $orphanProfessor = User::factory()->create();
    $orphanProfessor->assignRole('Profesor');

    $orphanStudent = User::factory()->create();
    $orphanStudent->assignRole('Estudiante');

    // Run with dry-run flag
    $this->artisan('users:fix-orphan-records --dry-run')->assertSuccessful();

    // Assert nothing was created
    $this->assertDatabaseMissing('professors', ['user_id' => $orphanProfessor->id]);
    $this->assertDatabaseMissing('students', ['user_id' => $orphanStudent->id]);
});
