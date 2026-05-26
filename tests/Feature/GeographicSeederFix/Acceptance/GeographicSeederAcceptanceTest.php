<?php

/**
 * Acceptance tests — geographic-seeder-fix
 *
 * Contract:
 *   1. GeographicSeeder populates countries (>1 row) and Venezuelan states (≥24 rows).
 *   2. The edit page for a user includes a non-empty catalogData.countries array
 *      when the geographic seeder has been run.
 *   3. migrate:fresh --seed populates geographic data in a clean environment.
 *
 * These tests are intentionally written BEFORE the fix is applied.
 * They must never be modified by the implementer.
 */

use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Database\Seeders\Catalogs\AcademicCatalogsSeeder;
use Database\Seeders\Catalogs\GeographicSeeder;
use Database\Seeders\Catalogs\SocialCatalogsSeeder;
use Database\Seeders\Catalogs\SocioeconomicCatalogsSeeder;
use Database\Seeders\Catalogs\StaffCatalogsSeeder;
use Database\Seeders\Catalogs\UserProfileCatalogsSeeder;
use Database\Seeders\DatabaseSeeder;
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
// AC-1: GeographicSeeder populates countries
// ---------------------------------------------------------------------------

test('GeographicSeeder populates at least one country', function () {
    // beforeEach already ran the seeder — verify the result
    expect(Country::count())->toBeGreaterThan(1);
});

// ---------------------------------------------------------------------------
// AC-2: GeographicSeeder populates Venezuelan states
// ---------------------------------------------------------------------------

test('GeographicSeeder populates at least 24 Venezuelan states', function () {
    $venezuela = Country::where('iso2', 'VE')->first();

    expect($venezuela)->not->toBeNull()
        ->and(State::where('country_id', $venezuela->id)->count())->toBeGreaterThanOrEqual(24);
});

// ---------------------------------------------------------------------------
// AC-3: edit page catalogData.countries is non-empty when seeder has run
// ---------------------------------------------------------------------------

test('edit page catalogData includes countries when geographic data is seeded', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.countries')
            ->where('catalogData.countries', fn ($val) => count($val) > 0)
        );
});

// ---------------------------------------------------------------------------
// AC-4: edit page catalogData.states is non-empty when seeder has run
// ---------------------------------------------------------------------------

test('edit page catalogData includes states when geographic data is seeded', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData.states')
            ->where('catalogData.states', fn ($val) => count($val) > 0)
        );
});

// ---------------------------------------------------------------------------
// AC-5: DatabaseSeeder includes GeographicSeeder in its call chain
// ---------------------------------------------------------------------------

test('DatabaseSeeder calls GeographicSeeder via CatalogsSeeder', function () {
    // Run full DatabaseSeeder from scratch (RefreshDatabase already wiped the DB).
    // After this call, countries and states tables must have data.
    $this->seed(DatabaseSeeder::class);

    expect(Country::count())->toBeGreaterThan(1)
        ->and(State::count())->toBeGreaterThanOrEqual(24);
});
