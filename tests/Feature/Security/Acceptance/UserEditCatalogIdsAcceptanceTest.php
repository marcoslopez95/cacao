<?php

/**
 * Acceptance tests — user-edit-catalog-ids
 *
 * These tests define the behavioural contract for the feature.
 * They MUST be RED before implementation and GREEN after.
 * The implementer MUST NOT modify this file.
 *
 * Contract:
 * 1. Edit page catalogData includes the 8 new catalog keys (T01)
 * 2. S04 demographic profile persists birth_country_id and religion_id (T16)
 * 3. S09 student background persists institution_type_id and digital_level_id (T17)
 * 4. S16 guardian profile persists marital_status_id and education_level_id (T18)
 * 5. S17 staff profile persists FK ids and returns 200 — regression guard (T19)
 */

use App\Models\Catalogs\ContractType;
use App\Models\Catalogs\DedicationType;
use App\Models\Catalogs\DigitalLevel;
use App\Models\Catalogs\EducationLevel;
use App\Models\Catalogs\EmploymentStatus;
use App\Models\Catalogs\InstitutionType;
use App\Models\Catalogs\Language;
use App\Models\Catalogs\MaritalStatus;
use App\Models\Catalogs\Religion;
use App\Models\Country;
use App\Models\Guardian;
use App\Models\Professor;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\Catalogs\AcademicCatalogsSeeder;
use Database\Seeders\Catalogs\GeographicSeeder;
use Database\Seeders\Catalogs\SocialCatalogsSeeder;
use Database\Seeders\Catalogs\SocioeconomicCatalogsSeeder;
use Database\Seeders\Catalogs\StaffCatalogsSeeder;
use Database\Seeders\Catalogs\UserProfileCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'Admin',         'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Estudiante',    'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Profesor',      'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Representante', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Coordinador',   'guard_name' => 'web']);

    $this->seed(GeographicSeeder::class);
    $this->seed(SocialCatalogsSeeder::class);
    $this->seed(SocioeconomicCatalogsSeeder::class);
    $this->seed(UserProfileCatalogsSeeder::class);
    $this->seed(AcademicCatalogsSeeder::class);
    $this->seed(StaffCatalogsSeeder::class);
});

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function adminForCatalogIds(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

// ---------------------------------------------------------------------------
// T01 — catalogData includes new catalog keys
// ---------------------------------------------------------------------------

test('edit page catalogData includes religions, institutionTypes, transferReasons, digitalLevels, educationLevels, maritalStatuses, contractTypes, dedicationTypes, employmentStatuses', function () {
    $admin = adminForCatalogIds();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.religions')
            ->has('catalogData.institutionTypes')
            ->has('catalogData.transferReasons')
            ->has('catalogData.digitalLevels')
            ->has('catalogData.educationLevels')
            ->has('catalogData.maritalStatuses')
            ->has('catalogData.contractTypes')
            ->has('catalogData.dedicationTypes')
            ->has('catalogData.employmentStatuses')
        );
});

test('edit page catalogData religions has id and name fields', function () {
    $admin = adminForCatalogIds();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.religions.0', fn ($item) => $item
                ->has('id')
                ->has('name')
            )
        );
});

test('edit page catalogData contractTypes has id and name fields', function () {
    $admin = adminForCatalogIds();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.contractTypes.0', fn ($item) => $item
                ->has('id')
                ->has('name')
            )
        );
});

// ---------------------------------------------------------------------------
// T16 — S04 demographic profile persists FK ids
// ---------------------------------------------------------------------------

test('S04 demographic: birth_country_id persists in demographic_profiles', function () {
    $admin = adminForCatalogIds();
    $target = User::factory()->create();

    // Use a country from the seeded DB
    $country = Country::where('iso2', 'VE')->first();

    $this->actingAs($admin)
        ->putJson(route('security.users.demographic-profile.upsert', $target), [
            'birth_country_id' => $country->id,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('demographic_profiles', [
        'user_id' => $target->id,
        'birth_country_id' => $country->id,
    ]);
});

test('S04 demographic: religion_id persists in demographic_profiles', function () {
    $admin = adminForCatalogIds();
    $target = User::factory()->create();

    $religion = Religion::first();

    $this->actingAs($admin)
        ->putJson(route('security.users.demographic-profile.upsert', $target), [
            'religion_id' => $religion->id,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('demographic_profiles', [
        'user_id' => $target->id,
        'religion_id' => $religion->id,
    ]);
});

test('S04 demographic: native_language_id persists in demographic_profiles', function () {
    $admin = adminForCatalogIds();
    $target = User::factory()->create();

    $language = Language::first();

    $this->actingAs($admin)
        ->putJson(route('security.users.demographic-profile.upsert', $target), [
            'native_language_id' => $language->id,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('demographic_profiles', [
        'user_id' => $target->id,
        'native_language_id' => $language->id,
    ]);
});

// ---------------------------------------------------------------------------
// T17 — S09 student background persists FK ids
// ---------------------------------------------------------------------------

test('S09 background: institution_type_id persists in student_backgrounds', function () {
    $admin = adminForCatalogIds();
    $student = Student::factory()->create();

    $institutionType = InstitutionType::first();

    $this->actingAs($admin)
        ->putJson(route('security.students.background.upsert', $student), [
            'institution_type_id' => $institutionType->id,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('student_backgrounds', [
        'student_id' => $student->id,
        'institution_type_id' => $institutionType->id,
    ]);
});

test('S09 background: digital_level_id persists in student_backgrounds', function () {
    $admin = adminForCatalogIds();
    $student = Student::factory()->create();

    $digitalLevel = DigitalLevel::first();

    $this->actingAs($admin)
        ->putJson(route('security.students.background.upsert', $student), [
            'digital_level_id' => $digitalLevel->id,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('student_backgrounds', [
        'student_id' => $student->id,
        'digital_level_id' => $digitalLevel->id,
    ]);
});

test('S09 background: mother_education_level_id and father_education_level_id persist in student_backgrounds', function () {
    $admin = adminForCatalogIds();
    $student = Student::factory()->create();

    $eduLevel = EducationLevel::first();

    $this->actingAs($admin)
        ->putJson(route('security.students.background.upsert', $student), [
            'mother_education_level_id' => $eduLevel->id,
            'father_education_level_id' => $eduLevel->id,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('student_backgrounds', [
        'student_id' => $student->id,
        'mother_education_level_id' => $eduLevel->id,
        'father_education_level_id' => $eduLevel->id,
    ]);
});

// ---------------------------------------------------------------------------
// T18 — S16 guardian profile persists FK ids
// ---------------------------------------------------------------------------

test('S16 guardian profile: marital_status_id persists in guardian_profiles', function () {
    $admin = adminForCatalogIds();
    $guardian = Guardian::factory()->create();

    $maritalStatus = MaritalStatus::first();

    $this->actingAs($admin)
        ->putJson(route('security.guardians.profile.upsert', $guardian), [
            'marital_status_id' => $maritalStatus->id,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('guardian_profiles', [
        'guardian_id' => $guardian->id,
        'marital_status_id' => $maritalStatus->id,
    ]);
});

test('S16 guardian profile: education_level_id persists in guardian_profiles', function () {
    $admin = adminForCatalogIds();
    $guardian = Guardian::factory()->create();

    $eduLevel = EducationLevel::first();

    $this->actingAs($admin)
        ->putJson(route('security.guardians.profile.upsert', $guardian), [
            'education_level_id' => $eduLevel->id,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('guardian_profiles', [
        'guardian_id' => $guardian->id,
        'education_level_id' => $eduLevel->id,
    ]);
});

// ---------------------------------------------------------------------------
// T19 — S17 staff profile FK ids — regression guard (must NOT return 422)
// ---------------------------------------------------------------------------

test('S17 staff profile: saves with contract_type_id, dedication_type_id, employment_status_id and returns 200', function () {
    $admin = adminForCatalogIds();
    $professor = Professor::factory()->create();

    $contractType = ContractType::first();
    $dedicationType = DedicationType::first();
    $employmentStatus = EmploymentStatus::first();

    $this->actingAs($admin)
        ->putJson(route('academic.professors.staff-profile.upsert', $professor), [
            'contract_type_id' => $contractType->id,
            'dedication_type_id' => $dedicationType->id,
            'employment_status_id' => $employmentStatus->id,
            'hire_date' => '2020-01-15',
            'is_coordinator' => false,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('staff_profiles', [
        'professor_id' => $professor->id,
        'contract_type_id' => $contractType->id,
        'dedication_type_id' => $dedicationType->id,
        'employment_status_id' => $employmentStatus->id,
    ]);
});

test('S17 staff profile: sending string contract_type instead of contract_type_id returns 422', function () {
    // Regression guard: sending string values (old frontend behaviour) must fail validation.
    // After the fix the frontend sends IDs, but this test ensures the backend rejects the old format.
    $admin = adminForCatalogIds();
    $professor = Professor::factory()->create();

    $this->actingAs($admin)
        ->putJson(route('academic.professors.staff-profile.upsert', $professor), [
            'contract_type' => 'Tiempo completo',     // string, not ID
            'dedication_type' => 'Dedicación exclusiva', // string, not ID
            'employment_status' => 'Activo',              // string, not ID
            'hire_date' => '2020-01-15',
            'is_coordinator' => false,
        ])
        ->assertUnprocessable(); // 422 — required FK ids are missing
});
