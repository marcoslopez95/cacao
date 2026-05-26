<?php

/**
 * Acceptance tests — user-edit-student-sections-fix
 *
 * These tests define the behavioural contract for the feature.
 * They MUST be RED before implementation and GREEN after.
 * The implementer MUST NOT modify this file.
 *
 * Contract:
 * RF-01 — Edit page loads student with guardian_marital_status_id in DB
 *          → student.familyProfile.guardian_marital_status_id present as integer in Inertia props.
 * RF-02 — Edit page loads student with living_arrangement_id in DB
 *          → student.familyProfile.living_arrangement_id present as integer.
 * RF-03 — Edit page loads student with household_head_type_id in DB
 *          → student.familyProfile.household_head_type_id present as integer.
 * RF-04 — PUT /security/students/{student}/family-profile with guardian_marital_status_id
 *          → persists in family_profiles.guardian_marital_status_id.
 * RF-05 — Edit page loads student with income_range_id in DB
 *          → student.socioeconomicProfile.income_range_id present as integer.
 * RF-06 — Edit page loads student with employment_type_id in DB
 *          → student.socioeconomicProfile.employment_type_id present as integer.
 * RF-07 — PUT /security/students/{student}/socioeconomic-profile with income_range_id
 *          → persists in socioeconomic_profiles.income_range_id.
 * RF-08 — student.socioeconomicProfile.study_date arrives in format YYYY-MM-DD (10 chars).
 * RF-09 — PUT /security/students/{student}/housing-profile with housing_type_id
 *          → persists housing_profiles.housing_type_id with the correct integer ID.
 * RF-10 — Edit page: catalogData.livingArrangements and catalogData.housingTypes
 *          are present as non-empty arrays.
 */

use App\Models\Catalogs\EmploymentType;
use App\Models\Catalogs\HouseholdHeadType;
use App\Models\Catalogs\HousingType;
use App\Models\Catalogs\IncomeRange;
use App\Models\Catalogs\LivingArrangement;
use App\Models\Catalogs\MaritalStatus;
use App\Models\FamilyProfile;
use App\Models\SocioeconomicProfile;
use App\Models\Student;
use App\Models\User;
use App\Models\UserConsent;
use Database\Seeders\Catalogs\SocialCatalogsSeeder;
use Database\Seeders\Catalogs\SocioeconomicCatalogsSeeder;
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

    // Seed all catalogs needed for S11, S12, S14
    $this->seed(SocialCatalogsSeeder::class);
    $this->seed(SocioeconomicCatalogsSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Creates an Admin user. Gate::before in AppServiceProvider short-circuits
 * all authorization checks for Admins.
 */
function adminForSectionsFix(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Creates a Student (with its associated User) via the factory.
 * StudentFactory::configure() assigns the 'Estudiante' role automatically.
 * Returns the User so we can call route('security.users.edit', $user).
 */
function studentUserForSectionsFix(): User
{
    $student = Student::factory()->create();

    return $student->user()->first();
}

/**
 * Creates an active data-processing consent for the given user.
 * Required by ConsentService::requireConsent() which is called by
 * UpsertSocioeconomicProfileAction and UpsertHousingProfileAction.
 */
function consentForSectionsFix(User $user): UserConsent
{
    return UserConsent::create([
        'user_id' => $user->id,
        'policy_version' => 'v1.0',
        'accepts_data_processing' => true,
        'accepts_image_use' => true,
        'accepts_whatsapp_contact' => true,
        'accepts_email_contact' => true,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test-agent',
        'granted_at' => now(),
        'revoked_at' => null,
    ]);
}

// ---------------------------------------------------------------------------
// RF-01 — guardian_marital_status_id is present in familyProfile Inertia prop
// ---------------------------------------------------------------------------

it('RF-01: student.familyProfile.guardian_marital_status_id is present as integer when saved in DB', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();
    $student = $target->student;

    $maritalStatus = MaritalStatus::first();

    FamilyProfile::updateOrCreate(
        ['student_id' => $student->id],
        ['guardian_marital_status_id' => $maritalStatus->id],
    );

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student.familyProfile.guardian_marital_status_id', $maritalStatus->id)
        );
});

// ---------------------------------------------------------------------------
// RF-02 — living_arrangement_id is present in familyProfile Inertia prop
// ---------------------------------------------------------------------------

it('RF-02: student.familyProfile.living_arrangement_id is present as integer when saved in DB', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();
    $student = $target->student;

    $livingArrangement = LivingArrangement::first();

    FamilyProfile::updateOrCreate(
        ['student_id' => $student->id],
        ['living_arrangement_id' => $livingArrangement->id],
    );

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student.familyProfile.living_arrangement_id', $livingArrangement->id)
        );
});

// ---------------------------------------------------------------------------
// RF-03 — household_head_type_id is present in familyProfile Inertia prop
// ---------------------------------------------------------------------------

it('RF-03: student.familyProfile.household_head_type_id is present as integer when saved in DB', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();
    $student = $target->student;

    $householdHeadType = HouseholdHeadType::first();

    FamilyProfile::updateOrCreate(
        ['student_id' => $student->id],
        ['household_head_type_id' => $householdHeadType->id],
    );

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student.familyProfile.household_head_type_id', $householdHeadType->id)
        );
});

// ---------------------------------------------------------------------------
// RF-04 — PUT family-profile with guardian_marital_status_id persists in DB
// ---------------------------------------------------------------------------

it('RF-04: PUT family-profile with guardian_marital_status_id persists family_profiles.guardian_marital_status_id', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();
    $student = $target->student;

    $maritalStatus = MaritalStatus::first();

    $this->actingAs($admin)
        ->putJson(route('security.students.family-profile.upsert', $student), [
            'guardian_marital_status_id' => $maritalStatus->id,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('family_profiles', [
        'student_id' => $student->id,
        'guardian_marital_status_id' => $maritalStatus->id,
    ]);
});

it('RF-04: PUT family-profile with guardian_marital_status_id as string does NOT persist — only integer IDs are accepted', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();
    $student = $target->student;

    $maritalStatus = MaritalStatus::first();

    // Sending the wrong key (no _id suffix, sending name string) must fail validation
    // or silently ignore the value — the ID must NOT be stored.
    $this->actingAs($admin)
        ->putJson(route('security.students.family-profile.upsert', $student), [
            'guardian_marital_status' => $maritalStatus->name,  // wrong key, no _id suffix
        ]);

    $this->assertDatabaseMissing('family_profiles', [
        'student_id' => $student->id,
        'guardian_marital_status_id' => $maritalStatus->id,
    ]);
});

// ---------------------------------------------------------------------------
// RF-05 — income_range_id is present in socioeconomicProfile Inertia prop
// ---------------------------------------------------------------------------

it('RF-05: student.socioeconomicProfile.income_range_id is present as integer when saved in DB', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();
    $student = $target->student;

    $incomeRange = IncomeRange::first();

    // study_date is NOT NULL in DB — always required when creating the record.
    SocioeconomicProfile::updateOrCreate(
        ['student_id' => $student->id],
        ['income_range_id' => $incomeRange->id, 'study_date' => '2025-06-01'],
    );

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student.socioeconomicProfile.income_range_id', $incomeRange->id)
        );
});

// ---------------------------------------------------------------------------
// RF-06 — employment_type_id is present in socioeconomicProfile Inertia prop
// ---------------------------------------------------------------------------

it('RF-06: student.socioeconomicProfile.employment_type_id is present as integer when saved in DB', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();
    $student = $target->student;

    $employmentType = EmploymentType::first();

    // study_date is NOT NULL in DB — always required when creating the record.
    SocioeconomicProfile::updateOrCreate(
        ['student_id' => $student->id],
        ['employment_type_id' => $employmentType->id, 'study_date' => '2025-06-01'],
    );

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student.socioeconomicProfile.employment_type_id', $employmentType->id)
        );
});

// ---------------------------------------------------------------------------
// RF-07 — PUT socioeconomic-profile with income_range_id persists in DB
// ---------------------------------------------------------------------------

it('RF-07: PUT socioeconomic-profile with income_range_id persists socioeconomic_profiles.income_range_id', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();
    $student = $target->student;

    // UpsertSocioeconomicProfileAction calls ConsentService::requireConsent()
    consentForSectionsFix($target);

    $incomeRange = IncomeRange::first();

    // study_date is required by StoreSocioeconomicProfileRequest.
    $this->actingAs($admin)
        ->putJson(route('security.students.socioeconomic-profile.upsert', $student), [
            'income_range_id' => $incomeRange->id,
            'study_date' => '2025-06-01',
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('socioeconomic_profiles', [
        'student_id' => $student->id,
        'income_range_id' => $incomeRange->id,
    ]);
});

it('RF-07: PUT socioeconomic-profile with income_range as string does NOT persist the ID', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();
    $student = $target->student;

    // UpsertSocioeconomicProfileAction calls ConsentService::requireConsent()
    consentForSectionsFix($target);

    $incomeRange = IncomeRange::first();

    // Sending old wrong key (no _id suffix) must not store the FK.
    // study_date is still required by the form request.
    $this->actingAs($admin)
        ->putJson(route('security.students.socioeconomic-profile.upsert', $student), [
            'income_range' => $incomeRange->name,  // wrong key — no _id suffix
            'study_date' => '2025-06-01',
        ]);

    $this->assertDatabaseMissing('socioeconomic_profiles', [
        'student_id' => $student->id,
        'income_range_id' => $incomeRange->id,
    ]);
});

// ---------------------------------------------------------------------------
// RF-08 — study_date arrives in YYYY-MM-DD format (10 chars), not datetime
// ---------------------------------------------------------------------------

it('RF-08: student.socioeconomicProfile.study_date is in YYYY-MM-DD format, not datetime', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();
    $student = $target->student;

    SocioeconomicProfile::updateOrCreate(
        ['student_id' => $student->id],
        ['study_date' => '2025-03-15'],
    );

    $response = $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk();

    $pageData = $response->viewData('page');
    $studyDate = data_get($pageData, 'props.student.socioeconomicProfile.study_date');

    expect($studyDate)->not->toBeNull()
        ->and(strlen((string) $studyDate))->toBe(10)       // YYYY-MM-DD is exactly 10 chars
        ->and($studyDate)->toStartWith('2025-03-15');       // correct date, no time component
});

it('RF-08: study_date does not contain a time component (no colons in the value)', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();
    $student = $target->student;

    SocioeconomicProfile::updateOrCreate(
        ['student_id' => $student->id],
        ['study_date' => '2026-01-20'],
    );

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student.socioeconomicProfile.study_date', '2026-01-20')
        );
});

// ---------------------------------------------------------------------------
// RF-09 — PUT housing-profile with housing_type_id persists in DB
// ---------------------------------------------------------------------------

it('RF-09: PUT housing-profile with housing_type_id persists housing_profiles.housing_type_id', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();
    $student = $target->student;

    // UpsertHousingProfileAction calls ConsentService::requireConsent()
    consentForSectionsFix($target);

    $housingType = HousingType::first();

    $this->actingAs($admin)
        ->putJson(route('security.students.housing-profile.upsert', $student), [
            'housing_type_id' => $housingType->id,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('housing_profiles', [
        'student_id' => $student->id,
        'housing_type_id' => $housingType->id,
    ]);
});

it('RF-09: PUT housing-profile with housing_type as string does NOT persist the FK ID', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();
    $student = $target->student;

    // UpsertHousingProfileAction calls ConsentService::requireConsent()
    consentForSectionsFix($target);

    $housingType = HousingType::first();

    // Sending old wrong key (no _id suffix) must not store the FK
    $this->actingAs($admin)
        ->putJson(route('security.students.housing-profile.upsert', $student), [
            'housing_type' => $housingType->name,  // wrong key, no _id suffix
        ]);

    $this->assertDatabaseMissing('housing_profiles', [
        'student_id' => $student->id,
        'housing_type_id' => $housingType->id,
    ]);
});

// ---------------------------------------------------------------------------
// RF-10 — catalogData.livingArrangements and catalogData.housingTypes are present
// ---------------------------------------------------------------------------

it('RF-10: catalogData.livingArrangements is present in edit page Inertia props', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.livingArrangements')
        );
});

it('RF-10: catalogData.livingArrangements is a non-empty array', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.livingArrangements.0')
        );
});

it('RF-10: catalogData.housingTypes is present in edit page Inertia props', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.housingTypes')
        );
});

it('RF-10: catalogData.housingTypes is a non-empty array', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.housingTypes.0')
        );
});

it('RF-10: catalogData contains all new catalog keys needed for S11, S12, S14', function () {
    $admin = adminForSectionsFix();
    $target = studentUserForSectionsFix();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.livingArrangements')
            ->has('catalogData.householdHeadTypes')
            ->has('catalogData.incomeRanges')
            ->has('catalogData.employmentTypes')
            ->has('catalogData.housingTypes')
        );
});
