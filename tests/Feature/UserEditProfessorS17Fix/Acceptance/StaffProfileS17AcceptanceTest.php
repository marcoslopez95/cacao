<?php

/**
 * Acceptance tests — user-edit-professor-s17-fix
 *
 * Contract: saving S17 with is_coordinator=true must succeed (RF-01, RF-06),
 * the coordinatedDepartment() relation must not crash (RF-02),
 * hire_date in the edit page Inertia props must arrive as YYYY-MM-DD (RF-03),
 * and dates must survive a round-trip save unchanged (RF-04).
 *
 * These tests are intentionally written in RED first (before the fix is applied).
 * They must never be modified by the implementer.
 */

use App\Models\Catalogs\ContractType;
use App\Models\Catalogs\DedicationType;
use App\Models\Catalogs\EmploymentStatus;
use App\Models\Coordination;
use App\Models\Professor;
use App\Models\StaffProfile;
use App\Models\User;
use Database\Seeders\Catalogs\StaffCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    (new StaffCatalogsSeeder)->run();

    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Coordinador',   'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Profesor',      'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Admin',         'guard_name' => 'web']);

    // Permissions required by UserPolicy (used in RF-03 edit page tests)
    Permission::firstOrCreate(['name' => 'users.update', 'guard_name' => 'web']);
});

/**
 * Creates an admin user with the Administrador role (as required by StaffProfilePolicy).
 */
function s17Admin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    return $user;
}

/**
 * Returns a valid base payload for upserting a staff profile (without coordinator fields).
 */
function s17BasePayload(): array
{
    return [
        'contract_type_id' => ContractType::first()->id,
        'dedication_type_id' => DedicationType::first()->id,
        'employment_status_id' => EmploymentStatus::first()->id,
        'hire_date' => '2014-09-09',
        'is_coordinator' => false,
    ];
}

// ---------------------------------------------------------------------------
// RF-01 / RF-06 — Guardar S17 con is_coordinator=true no devuelve 500 ni 422
// ---------------------------------------------------------------------------

it('RF-01: saving S17 with is_coordinator=true and valid coordinated_department_id returns 200, not 500', function () {
    $admin = s17Admin();
    $professor = Professor::factory()->create();
    $coordination = Coordination::factory()->create(['active' => true]);

    $payload = array_merge(s17BasePayload(), [
        'is_coordinator' => true,
        'coordinated_department_id' => $coordination->id,
        'coordinator_since' => '2020-01-01',
    ]);

    $response = $this->actingAs($admin)->putJson(
        route('academic.professors.staff-profile.upsert', $professor),
        $payload
    );

    // Must NOT be 500 (SQL error from nonexistent 'departments' table)
    // Must NOT be 422 (validation failure due to exists:departments,id)
    $response->assertSuccessful();
});

it('RF-01: saving S17 with is_coordinator=true creates a staff_profiles row with is_coordinator=true', function () {
    $admin = s17Admin();
    $professor = Professor::factory()->create();
    $coordination = Coordination::factory()->create(['active' => true]);

    $payload = array_merge(s17BasePayload(), [
        'is_coordinator' => true,
        'coordinated_department_id' => $coordination->id,
        'coordinator_since' => '2020-01-01',
    ]);

    $this->actingAs($admin)->putJson(
        route('academic.professors.staff-profile.upsert', $professor),
        $payload
    );

    $this->assertDatabaseHas('staff_profiles', [
        'professor_id' => $professor->id,
        'is_coordinator' => true,
        'coordinated_department_id' => $coordination->id,
    ]);
});

// ---------------------------------------------------------------------------
// RF-02 — Eager-load de coordinatedDepartment() no lanza excepción PHP
// ---------------------------------------------------------------------------

it('RF-02: eager-loading coordinatedDepartment() on a StaffProfile does not throw an exception', function () {
    $admin = s17Admin();
    $professor = Professor::factory()->create();
    $coordination = Coordination::factory()->create(['active' => true]);

    // Manually insert a staff profile pointing to a coordination
    StaffProfile::create([
        'professor_id' => $professor->id,
        'employee_code' => 'EMP-TEST-00001',
        'contract_type_id' => ContractType::first()->id,
        'dedication_type_id' => DedicationType::first()->id,
        'employment_status_id' => EmploymentStatus::first()->id,
        'hire_date' => '2014-09-09',
        'is_coordinator' => true,
        'coordinated_department_id' => $coordination->id,
        'coordinator_since' => '2020-01-01',
    ]);

    // This must NOT throw "Class App\Models\Department not found"
    $profile = StaffProfile::with('coordinatedDepartment')
        ->where('professor_id', $professor->id)
        ->first();

    expect($profile)->not->toBeNull();
    expect($profile->coordinatedDepartment)->not->toBeNull();
    expect($profile->coordinatedDepartment->id)->toBe($coordination->id);
});

it('RF-02: accessing coordinatedDepartment on a loaded StaffProfile returns a Coordination instance', function () {
    $admin = s17Admin();
    $professor = Professor::factory()->create();
    $coordination = Coordination::factory()->create(['active' => true]);

    StaffProfile::create([
        'professor_id' => $professor->id,
        'employee_code' => 'EMP-TEST-00002',
        'contract_type_id' => ContractType::first()->id,
        'dedication_type_id' => DedicationType::first()->id,
        'employment_status_id' => EmploymentStatus::first()->id,
        'hire_date' => '2014-09-09',
        'is_coordinator' => true,
        'coordinated_department_id' => $coordination->id,
        'coordinator_since' => '2020-01-01',
    ]);

    $profile = StaffProfile::where('professor_id', $professor->id)->first();

    // Accessing the dynamic property must resolve a Coordination, not throw
    expect($profile->coordinatedDepartment)->toBeInstanceOf(Coordination::class);
});

// ---------------------------------------------------------------------------
// RF-03 — hire_date en la prop Inertia llega en formato YYYY-MM-DD (10 chars)
// ---------------------------------------------------------------------------

it('RF-03: edit page Inertia prop professor.staffProfile.hire_date is exactly 10 characters (YYYY-MM-DD)', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('users.update');
    $professor = Professor::factory()->create();

    // Create a staff profile with a known hire_date
    StaffProfile::create([
        'professor_id' => $professor->id,
        'employee_code' => 'EMP-TEST-00003',
        'contract_type_id' => ContractType::first()->id,
        'dedication_type_id' => DedicationType::first()->id,
        'employment_status_id' => EmploymentStatus::first()->id,
        'hire_date' => '2014-09-09',
        'is_coordinator' => false,
    ]);

    // Ensure the admin can also update the professor's user (UserPolicy)
    $professorUser = $professor->user;

    $response = $this->actingAs($admin)
        ->get(route('security.users.edit', $professorUser));

    $response->assertOk();

    // Extract Inertia props from the response
    $pageData = $response->viewData('page');
    $hireDateValue = data_get($pageData, 'props.professor.staffProfile.hire_date');

    // Must be exactly 10 characters: YYYY-MM-DD — not ISO 8601 with time component
    expect($hireDateValue)->not->toBeNull();
    expect(strlen((string) $hireDateValue))->toBe(10);
    expect($hireDateValue)->toMatch('/^\d{4}-\d{2}-\d{2}$/');
});

it('RF-03: edit page Inertia prop professor.staffProfile.hire_date matches the stored date value', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('users.update');
    $professor = Professor::factory()->create();

    StaffProfile::create([
        'professor_id' => $professor->id,
        'employee_code' => 'EMP-TEST-00004',
        'contract_type_id' => ContractType::first()->id,
        'dedication_type_id' => DedicationType::first()->id,
        'employment_status_id' => EmploymentStatus::first()->id,
        'hire_date' => '2014-09-09',
        'is_coordinator' => false,
    ]);

    $professorUser = $professor->user;

    $response = $this->actingAs($admin)
        ->get(route('security.users.edit', $professorUser));

    $response->assertOk();

    $pageData = $response->viewData('page');
    $hireDateValue = data_get($pageData, 'props.professor.staffProfile.hire_date');

    // Must match the stored date (not an empty string or null due to ISO parsing)
    expect($hireDateValue)->toBe('2014-09-09');
});

// ---------------------------------------------------------------------------
// RF-04 — Fechas no se destruyen en un round-trip save sin modificar fechas
// ---------------------------------------------------------------------------

it('RF-04: hire_date in DB remains unchanged after saving S17 without touching date fields', function () {
    $admin = s17Admin();
    $professor = Professor::factory()->create();

    // Pre-create a staff profile with a known hire_date
    StaffProfile::create([
        'professor_id' => $professor->id,
        'employee_code' => 'EMP-TEST-00005',
        'contract_type_id' => ContractType::first()->id,
        'dedication_type_id' => DedicationType::first()->id,
        'employment_status_id' => EmploymentStatus::first()->id,
        'hire_date' => '2014-09-09',
        'is_coordinator' => false,
    ]);

    // Save again — using the same hire_date (round-trip from form field value)
    $this->actingAs($admin)->putJson(
        route('academic.professors.staff-profile.upsert', $professor),
        array_merge(s17BasePayload(), ['hire_date' => '2014-09-09'])
    );

    $this->assertDatabaseHas('staff_profiles', [
        'professor_id' => $professor->id,
        'hire_date' => '2014-09-09',
    ]);
});

it('RF-04: coordinator_since in DB remains unchanged after saving S17 without touching coordinator_since', function () {
    $admin = s17Admin();
    $professor = Professor::factory()->create();
    $coordination = Coordination::factory()->create(['active' => true]);

    StaffProfile::create([
        'professor_id' => $professor->id,
        'employee_code' => 'EMP-TEST-00006',
        'contract_type_id' => ContractType::first()->id,
        'dedication_type_id' => DedicationType::first()->id,
        'employment_status_id' => EmploymentStatus::first()->id,
        'hire_date' => '2014-09-09',
        'is_coordinator' => true,
        'coordinated_department_id' => $coordination->id,
        'coordinator_since' => '2020-03-15',
    ]);

    // Round-trip save passing the same dates back
    $this->actingAs($admin)->putJson(
        route('academic.professors.staff-profile.upsert', $professor),
        array_merge(s17BasePayload(), [
            'hire_date' => '2014-09-09',
            'is_coordinator' => true,
            'coordinated_department_id' => $coordination->id,
            'coordinator_since' => '2020-03-15',
        ])
    );

    $this->assertDatabaseHas('staff_profiles', [
        'professor_id' => $professor->id,
        'coordinator_since' => '2020-03-15',
    ]);
});
