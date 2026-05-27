<?php

/**
 * Acceptance tests — user-edit-s01-identity-fix
 *
 * These tests define the behavioural contract for the S01 identity section.
 * They verify the full round-trip: backend exposes fields, catalogs load,
 * and PATCH /identity saves correctly.
 *
 * RF-01a — GET /edit → props.user.document_number = value in DB
 * RF-01b — GET /edit → props.user.birth_date in YYYY-MM-DD format
 * RF-01c — GET /edit → props.user.gender_id = value in DB
 * RF-01d — GET /edit → props.user.nationality_id = value in DB
 * RF-01e — GET /edit → props.user.phone_primary = value in DB
 * RF-02a — GET /edit → catalogData.documentTypes not empty
 * RF-02b — GET /edit → catalogData.genders not empty
 * RF-02c — GET /edit → catalogData.nationalities not empty
 * RF-03a — PATCH /identity with document_type_id and document_number → DB updated
 * RF-03b — PATCH /identity with valid gender_id → DB updated
 * RF-03c — PATCH /identity with valid birth_date → DB updated
 * RF-04  — PATCH /identity with nationality_id → GET /edit → props.user.nationality_id correct
 * RF-05  — PATCH /identity without gender_id when one exists → gender_id in DB unchanged
 */

use App\Models\Catalogs\DocumentType;
use App\Models\Catalogs\Gender;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\Catalogs\GeographicSeeder;
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

    // Seed identity catalogs (DocumentTypes, Genders) and country data
    $this->seed(UserProfileCatalogsSeeder::class);
    $this->seed(GeographicSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Creates an Admin user. Gate::before in AppServiceProvider short-circuits
 * all authorization checks for Admins.
 */
function adminForS01(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Returns a valid Country ID (first active country in DB).
 */
function countryIdForS01(): int
{
    return Country::where('active', true)->value('id');
}

/**
 * Returns a valid DocumentType ID (first one, e.g. V = cedula venezolana).
 */
function documentTypeIdForS01(): int
{
    return DocumentType::where('active', true)->value('id');
}

/**
 * Returns a valid Gender ID (first active gender).
 */
function genderIdForS01(): int
{
    return Gender::where('active', true)->value('id');
}

/**
 * Builds the minimal required PATCH /identity payload for the given user.
 *
 * UpdateUserRequest requires first_name, last_name, and email as mandatory.
 * All S01 fields are nullable extras.
 *
 * @param  array<string, mixed>  $extras
 * @return array<string, mixed>
 */
function identityPayloadForS01(User $user, array $extras = []): array
{
    return array_merge([
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'roles' => [],
    ], $extras);
}

// ---------------------------------------------------------------------------
// RF-01a — document_number is present in props.user Inertia prop
// ---------------------------------------------------------------------------

it('RF-01a: props.user.document_number matches the value stored in DB', function () {
    $admin = adminForS01();
    $target = User::factory()->create([
        'document_number' => '12345678',
    ]);

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('security/Users/Edit')
            ->where('user.document_number', '12345678')
        );
});

// ---------------------------------------------------------------------------
// RF-01b — birth_date is returned in YYYY-MM-DD format (10 chars)
// ---------------------------------------------------------------------------

it('RF-01b: props.user.birth_date is in YYYY-MM-DD format (10 chars), not a datetime', function () {
    $admin = adminForS01();
    $target = User::factory()->create([
        'birth_date' => '1990-06-15',
    ]);

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('user.birth_date', '1990-06-15')
        );
});

// ---------------------------------------------------------------------------
// RF-01c — gender_id is present in props.user Inertia prop
// ---------------------------------------------------------------------------

it('RF-01c: props.user.gender_id matches the value stored in DB', function () {
    $admin = adminForS01();
    $genderId = genderIdForS01();
    $target = User::factory()->create([
        'gender_id' => $genderId,
    ]);

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('user.gender_id', $genderId)
        );
});

// ---------------------------------------------------------------------------
// RF-01d — nationality_id is present in props.user Inertia prop
// ---------------------------------------------------------------------------

it('RF-01d: props.user.nationality_id matches the value stored in DB', function () {
    $admin = adminForS01();
    $nationalityId = countryIdForS01();
    $target = User::factory()->create([
        'nationality_id' => $nationalityId,
    ]);

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('user.nationality_id', $nationalityId)
        );
});

// ---------------------------------------------------------------------------
// RF-01e — phone_primary is present in props.user Inertia prop
// ---------------------------------------------------------------------------

it('RF-01e: props.user.phone_primary matches the value stored in DB', function () {
    $admin = adminForS01();
    $target = User::factory()->create([
        'phone_primary' => '+58-212-555-0100',
    ]);

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('user.phone_primary', '+58-212-555-0100')
        );
});

// ---------------------------------------------------------------------------
// RF-02a — catalogData.documentTypes is a non-empty array
// ---------------------------------------------------------------------------

it('RF-02a: catalogData.documentTypes is present and non-empty in edit page props', function () {
    $admin = adminForS01();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.documentTypes')
            ->has('catalogData.documentTypes.0')
        );
});

// ---------------------------------------------------------------------------
// RF-02b — catalogData.genders is a non-empty array
// ---------------------------------------------------------------------------

it('RF-02b: catalogData.genders is present and non-empty in edit page props', function () {
    $admin = adminForS01();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.genders')
            ->has('catalogData.genders.0')
        );
});

// ---------------------------------------------------------------------------
// RF-02c — catalogData.nationalities is a non-empty array
// ---------------------------------------------------------------------------

it('RF-02c: catalogData.nationalities is present and non-empty in edit page props', function () {
    $admin = adminForS01();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.nationalities')
            ->has('catalogData.nationalities.0')
        );
});

// ---------------------------------------------------------------------------
// RF-03a — PATCH /identity with document_type_id and document_number persists
// ---------------------------------------------------------------------------

it('RF-03a: PATCH /identity with document_type_id and document_number updates users table', function () {
    $admin = adminForS01();
    $target = User::factory()->create();
    $docTypeId = documentTypeIdForS01();

    $this->actingAs($admin)
        ->patchJson(route('security.users.identity.update', $target), identityPayloadForS01($target, [
            'document_type_id' => $docTypeId,
            'document_number' => '98765432',
        ]))
        ->assertSuccessful();

    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'document_type_id' => $docTypeId,
        'document_number' => '98765432',
    ]);
});

// ---------------------------------------------------------------------------
// RF-03b — PATCH /identity with valid gender_id persists in DB
// ---------------------------------------------------------------------------

it('RF-03b: PATCH /identity with valid gender_id updates users.gender_id', function () {
    $admin = adminForS01();
    $target = User::factory()->create();
    $genderId = genderIdForS01();

    $this->actingAs($admin)
        ->patchJson(route('security.users.identity.update', $target), identityPayloadForS01($target, [
            'gender_id' => $genderId,
        ]))
        ->assertSuccessful();

    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'gender_id' => $genderId,
    ]);
});

// ---------------------------------------------------------------------------
// RF-03c — PATCH /identity with valid birth_date persists in DB
// ---------------------------------------------------------------------------

it('RF-03c: PATCH /identity with valid birth_date updates users.birth_date', function () {
    $admin = adminForS01();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->patchJson(route('security.users.identity.update', $target), identityPayloadForS01($target, [
            'birth_date' => '1985-03-22',
        ]))
        ->assertSuccessful();

    $this->assertDatabaseHas('users', [
        'id' => $target->id,
    ]);

    $updated = $target->fresh();
    expect($updated->birth_date?->toDateString())->toBe('1985-03-22');
});

// ---------------------------------------------------------------------------
// RF-04 — PATCH /identity with nationality_id → GET /edit shows correct value
// ---------------------------------------------------------------------------

it('RF-04: after PATCH /identity with nationality_id, GET /edit returns the correct nationality_id', function () {
    $admin = adminForS01();
    $target = User::factory()->create();
    $nationalityId = countryIdForS01();

    $this->actingAs($admin)
        ->patchJson(route('security.users.identity.update', $target), identityPayloadForS01($target, [
            'nationality_id' => $nationalityId,
        ]))
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('user.nationality_id', $nationalityId)
        );
});

// ---------------------------------------------------------------------------
// RF-05 — PATCH /identity without gender_id → existing gender_id unchanged
// ---------------------------------------------------------------------------

it('RF-05: PATCH /identity without gender_id in payload leaves existing users.gender_id unchanged', function () {
    $admin = adminForS01();
    $genderId = genderIdForS01();

    // Create user with a known gender_id already stored in DB
    $target = User::factory()->create([
        'gender_id' => $genderId,
    ]);

    // PATCH with a payload that deliberately omits gender_id
    // The wrapper's has() guard should prevent overwriting the existing value
    $this->actingAs($admin)
        ->patchJson(route('security.users.identity.update', $target), identityPayloadForS01($target))
        ->assertSuccessful();

    // gender_id must remain unchanged in DB
    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'gender_id' => $genderId,
    ]);
});
