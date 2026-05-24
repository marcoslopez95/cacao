<?php

use App\Models\Catalogs\ContractType;
use App\Models\Catalogs\DedicationType;
use App\Models\Catalogs\EmploymentStatus;
use App\Models\Professor;
use App\Models\StaffProfile;
use App\Models\User;
use Database\Seeders\Catalogs\StaffCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    (new StaffCatalogsSeeder)->run();
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
});

/**
 * Returns a valid payload for upserting a staff profile.
 */
function validStaffPayload(): array
{
    return [
        'contract_type_id' => ContractType::first()->id,
        'dedication_type_id' => DedicationType::first()->id,
        'employment_status_id' => EmploymentStatus::first()->id,
        'hire_date' => '2020-01-15',
        'is_coordinator' => false,
    ];
}

/**
 * Returns an admin User with the Administrador role.
 */
function adminForStaff(): User
{
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    return $user;
}

test('coordinator fields required when is_coordinator is true', function () {
    $admin = adminForStaff();
    $professor = Professor::factory()->create();

    $response = $this->actingAs($admin)->putJson(
        route('academic.professors.staff-profile.upsert', $professor),
        array_merge(validStaffPayload(), [
            'is_coordinator' => true,
            // coordinated_department_id and coordinator_since intentionally omitted
        ])
    );

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['coordinated_department_id']);
});

test('termination_date before hire_date rejected', function () {
    $admin = adminForStaff();
    $professor = Professor::factory()->create();

    $response = $this->actingAs($admin)->putJson(
        route('academic.professors.staff-profile.upsert', $professor),
        array_merge(validStaffPayload(), [
            'hire_date' => '2020-06-01',
            'termination_date' => '2020-01-01',
        ])
    );

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['termination_date']);
});

test('employee_code is auto-generated', function () {
    $admin = adminForStaff();
    $professor = Professor::factory()->create();

    $this->actingAs($admin)->put(
        route('academic.professors.staff-profile.upsert', $professor),
        validStaffPayload()
    );

    $profile = StaffProfile::where('professor_id', $professor->id)->first();
    $year = now()->year;

    expect($profile)->not->toBeNull();
    expect($profile->employee_code)->toMatch('/^EMP-'.$year.'-\d{5}$/');
});

test('upsert is idempotent — employee_code not regenerated on update', function () {
    $admin = adminForStaff();
    $professor = Professor::factory()->create();

    $this->actingAs($admin)->put(
        route('academic.professors.staff-profile.upsert', $professor),
        validStaffPayload()
    );

    $firstCode = StaffProfile::where('professor_id', $professor->id)->value('employee_code');

    $this->actingAs($admin)->put(
        route('academic.professors.staff-profile.upsert', $professor),
        array_merge(validStaffPayload(), ['hire_date' => '2021-03-01'])
    );

    $secondCode = StaffProfile::where('professor_id', $professor->id)->value('employee_code');

    expect(StaffProfile::where('professor_id', $professor->id)->count())->toBe(1);
    expect($secondCode)->toBe($firstCode);
});
