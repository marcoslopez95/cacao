<?php

/**
 * Acceptance tests — user-edit-admin-fix
 *
 * Contract: DemographicProfileResource must include religion_id for users
 * with the 'Admin' role (HLZ-14). These tests are RED until the implementer
 * adds 'Admin' to the hasAnyRole() list in DemographicProfileResource.
 *
 * RF-01 — Admin loads edit page → religion_id is present in the Inertia prop.
 * RF-02 — Admin saves S04 without changing religion → religion_id stays in DB.
 * RF-03 — Admin saves S04 with a new religion_id → religion_id is updated in DB.
 *
 * These tests must NOT be modified by the implementer.
 */

use App\Models\Catalogs\Religion;
use App\Models\DemographicProfile;
use App\Models\User;
use Database\Seeders\Catalogs\SocialCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // Register all roles needed for policies and resources
    Role::firstOrCreate(['name' => 'Admin',         'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Coordinador',   'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Estudiante',    'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Profesor',      'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Representante', 'guard_name' => 'web']);

    // Seed religions catalog
    $this->seed(SocialCatalogsSeeder::class);
});

/**
 * Creates an admin user with only the 'Admin' role (mirrors production admin).
 * Gate::before in AppServiceProvider short-circuits all Gate::authorize() calls.
 */
function adminForDemographicFix(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Creates a target user with a demographicProfile that has a non-null religion_id.
 */
function targetUserWithReligion(): User
{
    $religion = Religion::first();
    $target = User::factory()->create();

    DemographicProfile::create([
        'user_id' => $target->id,
        'religion_id' => $religion->id,
    ]);

    return $target;
}

// ---------------------------------------------------------------------------
// RF-01 — Admin loads edit page: religion_id is present in Inertia props
// ---------------------------------------------------------------------------

it('RF-01: demographicProfile.religion_id is present (not absent) in Inertia props for an Admin user', function () {
    $admin = adminForDemographicFix();
    $target = targetUserWithReligion();

    $response = $this->actingAs($admin)
        ->get(route('security.users.edit', $target));

    $response->assertOk();

    $pageData = $response->viewData('page');

    // Key must exist in the array (when() removes it entirely when false)
    expect(array_key_exists('religion_id', $pageData['props']['demographicProfile']))
        ->toBeTrue('religion_id must be present in demographicProfile prop for Admin role');
});

it('RF-01: demographicProfile.religion_id matches the stored value when loaded by an Admin', function () {
    $admin = adminForDemographicFix();
    $religion = Religion::first();
    $target = User::factory()->create();

    DemographicProfile::create([
        'user_id' => $target->id,
        'religion_id' => $religion->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('security.users.edit', $target));

    $response->assertOk();

    $pageData = $response->viewData('page');
    $religionIdProp = data_get($pageData, 'props.demographicProfile.religion_id');

    expect($religionIdProp)->toBe($religion->id);
});

// ---------------------------------------------------------------------------
// RF-02 — Admin saves S04 without touching religion → religion_id stays in DB
// ---------------------------------------------------------------------------

it('RF-02: saving S04 without providing religion_id does not nullify existing religion_id in DB', function () {
    $admin = adminForDemographicFix();
    $religion = Religion::first();
    $target = User::factory()->create();

    // Pre-create a demographic profile with a known religion_id
    DemographicProfile::create([
        'user_id' => $target->id,
        'religion_id' => $religion->id,
    ]);

    // Save S04 payload that does NOT include religion_id
    // (simulates the frontend sending undefined → null when Resource omits the field)
    $response = $this->actingAs($admin)->putJson(
        route('security.users.demographic-profile.upsert', $target),
        [
            'birth_city' => 'Caracas',
            'is_indigenous' => false,
            'is_returned_migrant' => false,
            'practices_sport' => false,
            // religion_id deliberately omitted (as the frontend does when it never received it)
        ]
    );

    $response->assertSuccessful();

    // The existing religion_id must NOT have been overwritten with null
    $this->assertDatabaseHas('demographic_profiles', [
        'user_id' => $target->id,
        'religion_id' => $religion->id,
    ]);
});

it('RF-02: saving S04 with religion_id explicitly null nullifies religion_id in DB (baseline check)', function () {
    $admin = adminForDemographicFix();
    $religion = Religion::first();
    $target = User::factory()->create();

    DemographicProfile::create([
        'user_id' => $target->id,
        'religion_id' => $religion->id,
    ]);

    // Explicitly sending null is intentional — this is a legitimate user action
    $response = $this->actingAs($admin)->putJson(
        route('security.users.demographic-profile.upsert', $target),
        ['religion_id' => null]
    );

    $response->assertSuccessful();

    $this->assertDatabaseHas('demographic_profiles', [
        'user_id' => $target->id,
        'religion_id' => null,
    ]);
});

// ---------------------------------------------------------------------------
// RF-03 — Admin saves S04 with a new religion_id → DB is updated correctly
// ---------------------------------------------------------------------------

it('RF-03: saving S04 with a new religion_id updates demographic_profiles.religion_id in DB', function () {
    $admin = adminForDemographicFix();
    $religions = Religion::orderBy('id')->take(2)->get();

    // Need at least two religions to test an update
    expect($religions->count())->toBeGreaterThanOrEqual(2);

    $initialReligion = $religions->first();
    $newReligion = $religions->last();

    $target = User::factory()->create();

    DemographicProfile::create([
        'user_id' => $target->id,
        'religion_id' => $initialReligion->id,
    ]);

    // Admin sends the new religion explicitly
    $response = $this->actingAs($admin)->putJson(
        route('security.users.demographic-profile.upsert', $target),
        ['religion_id' => $newReligion->id]
    );

    $response->assertSuccessful();

    $this->assertDatabaseHas('demographic_profiles', [
        'user_id' => $target->id,
        'religion_id' => $newReligion->id,
    ]);

    $this->assertDatabaseMissing('demographic_profiles', [
        'user_id' => $target->id,
        'religion_id' => $initialReligion->id,
    ]);
});

it('RF-03: DemographicProfileResource includes religion_id in its response for an Admin saving S04', function () {
    $admin = adminForDemographicFix();
    $religion = Religion::first();
    $target = User::factory()->create();

    DemographicProfile::create([
        'user_id' => $target->id,
        'religion_id' => $religion->id,
    ]);

    $response = $this->actingAs($admin)->putJson(
        route('security.users.demographic-profile.upsert', $target),
        ['religion_id' => $religion->id]
    );

    $response->assertSuccessful();

    // The resource response must also include religion_id for the Admin role
    $json = $response->json();

    expect(array_key_exists('religion_id', $json))
        ->toBeTrue('DemographicProfileResource must return religion_id for Admin role');
    expect($json['religion_id'])->toBe($religion->id);
});
