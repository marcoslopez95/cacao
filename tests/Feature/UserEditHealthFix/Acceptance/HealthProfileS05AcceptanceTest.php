<?php

/**
 * Acceptance tests — user-edit-health-fix
 *
 * These tests define the behavioural contract for the feature.
 * They MUST be RED before implementation and GREEN after.
 * The implementer MUST NOT modify this file.
 *
 * Contract:
 * RF-01 — Edit page loads with blood_type_id present in Inertia healthProfile prop.
 * RF-02 — Edit page loads with disability_type_id present in Inertia healthProfile prop.
 * RF-03 — Edit page loads with insurance_type_id present in Inertia healthProfile prop.
 * RF-04 — PUT /security/users/{user}/health-profile with blood_type_id persists in DB.
 * RF-05 — Saving S05 without sending blood_type_id does NOT nullify an existing blood_type_id in DB.
 * RF-06 — catalogData.bloodTypes is a non-empty array in Inertia props.
 */

use App\Models\Catalogs\BloodType;
use App\Models\Catalogs\DisabilityType;
use App\Models\Catalogs\InsuranceType;
use App\Models\HealthProfile;
use App\Models\User;
use App\Models\UserConsent;
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

    // Seed blood_types, disability_types, insurance_types
    $this->seed(SocioeconomicCatalogsSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Returns an Admin user. Gate::before short-circuits all authorization checks.
 */
function adminForHealthFix(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Creates an active consent record for the given user.
 * Required by the health profile endpoint (ConsentService::requireConsent).
 */
function consentForHealthFix(User $user): UserConsent
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
// RF-01 — blood_type_id is present in healthProfile Inertia prop
// ---------------------------------------------------------------------------

it('RF-01: healthProfile.blood_type_id is present as an integer when a blood_type_id is saved in DB', function () {
    $admin = adminForHealthFix();
    $target = User::factory()->create();

    $bloodType = BloodType::first();

    HealthProfile::create([
        'user_id' => $target->id,
        'blood_type_id' => $bloodType->id,
    ]);

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('healthProfile.blood_type_id', $bloodType->id)
        );
});

it('RF-01: healthProfile.blood_type_id is not null when a blood_type_id is saved in DB', function () {
    $admin = adminForHealthFix();
    $target = User::factory()->create();

    $bloodType = BloodType::first();

    HealthProfile::create([
        'user_id' => $target->id,
        'blood_type_id' => $bloodType->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk();

    $pageData = $response->viewData('page');
    $bloodTypeIdProp = data_get($pageData, 'props.healthProfile.blood_type_id');

    expect($bloodTypeIdProp)->not->toBeNull()
        ->and($bloodTypeIdProp)->toBe($bloodType->id);
});

// ---------------------------------------------------------------------------
// RF-02 — disability_type_id is present in healthProfile Inertia prop
// ---------------------------------------------------------------------------

it('RF-02: healthProfile.disability_type_id is present as an integer when a disability_type_id is saved in DB', function () {
    $admin = adminForHealthFix();
    $target = User::factory()->create();

    $disabilityType = DisabilityType::first();

    HealthProfile::create([
        'user_id' => $target->id,
        'has_disability' => true,
        'disability_type_id' => $disabilityType->id,
    ]);

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('healthProfile.disability_type_id', $disabilityType->id)
        );
});

// ---------------------------------------------------------------------------
// RF-03 — insurance_type_id is present in healthProfile Inertia prop
// ---------------------------------------------------------------------------

it('RF-03: healthProfile.insurance_type_id is present as an integer when an insurance_type_id is saved in DB', function () {
    $admin = adminForHealthFix();
    $target = User::factory()->create();

    $insuranceType = InsuranceType::first();

    HealthProfile::create([
        'user_id' => $target->id,
        'has_medical_insurance' => true,
        'insurance_type_id' => $insuranceType->id,
    ]);

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('healthProfile.insurance_type_id', $insuranceType->id)
        );
});

// ---------------------------------------------------------------------------
// RF-04 — PUT with blood_type_id persists in health_profiles table
// ---------------------------------------------------------------------------

it('RF-04: PUT health-profile with blood_type_id persists health_profiles.blood_type_id in DB', function () {
    $admin = adminForHealthFix();
    $target = User::factory()->create();
    consentForHealthFix($target);

    $bloodType = BloodType::first();

    $this->actingAs($admin)
        ->putJson(route('security.users.health-profile.upsert', $target), [
            'blood_type_id' => $bloodType->id,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('health_profiles', [
        'user_id' => $target->id,
        'blood_type_id' => $bloodType->id,
    ]);
});

it('RF-04: PUT health-profile returns blood_type_id in the JSON response body', function () {
    $admin = adminForHealthFix();
    $target = User::factory()->create();
    consentForHealthFix($target);

    $bloodType = BloodType::first();

    $response = $this->actingAs($admin)
        ->putJson(route('security.users.health-profile.upsert', $target), [
            'blood_type_id' => $bloodType->id,
        ])
        ->assertSuccessful();

    // HealthProfileResource wraps its output in {"data": {...}}
    expect($response->json('data.blood_type_id'))->toBe($bloodType->id);
});

// ---------------------------------------------------------------------------
// RF-05 — Saving S05 without blood_type_id does NOT nullify existing value in DB
// ---------------------------------------------------------------------------

it('RF-05: saving S05 without blood_type_id does not nullify an existing blood_type_id in DB', function () {
    $admin = adminForHealthFix();
    $target = User::factory()->create();
    consentForHealthFix($target);

    $bloodType = BloodType::first();

    // Pre-create health profile with a known blood_type_id
    HealthProfile::create([
        'user_id' => $target->id,
        'blood_type_id' => $bloodType->id,
    ]);

    // Save S05 payload that deliberately omits blood_type_id
    // (this simulates the old frontend sending blood_type: undefined → null
    //  with the wrong key, causing the backend to receive no valid blood_type_id)
    $this->actingAs($admin)
        ->putJson(route('security.users.health-profile.upsert', $target), [
            'weight_kg' => 70,
            // blood_type_id deliberately omitted — must not nullify existing value
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('health_profiles', [
        'user_id' => $target->id,
        'blood_type_id' => $bloodType->id,
    ]);
});

it('RF-05: saving S05 with disability_type_id absent does not nullify existing disability_type_id in DB', function () {
    $admin = adminForHealthFix();
    $target = User::factory()->create();
    consentForHealthFix($target);

    $disabilityType = DisabilityType::first();

    HealthProfile::create([
        'user_id' => $target->id,
        'has_disability' => true,
        'disability_type_id' => $disabilityType->id,
    ]);

    // Send payload WITHOUT disability_type_id
    $this->actingAs($admin)
        ->putJson(route('security.users.health-profile.upsert', $target), [
            'has_disability' => true,
            // disability_type_id deliberately omitted
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('health_profiles', [
        'user_id' => $target->id,
        'disability_type_id' => $disabilityType->id,
    ]);
});

// ---------------------------------------------------------------------------
// RF-06 — catalogData.bloodTypes is a non-empty array in Inertia props
// ---------------------------------------------------------------------------

it('RF-06: catalogData.bloodTypes is present in edit page Inertia props', function () {
    $admin = adminForHealthFix();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.bloodTypes')
        );
});

it('RF-06: catalogData.bloodTypes is a non-empty array', function () {
    $admin = adminForHealthFix();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.bloodTypes.0')
        );
});

it('RF-06: catalogData.bloodTypes items have id and name fields', function () {
    $admin = adminForHealthFix();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.bloodTypes.0', fn ($item) => $item
                ->has('id')
                ->has('name')
            )
        );
});

it('RF-06: catalogData.disabilityTypes and catalogData.insuranceTypes are also present', function () {
    $admin = adminForHealthFix();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.disabilityTypes')
            ->has('catalogData.insuranceTypes')
        );
});
