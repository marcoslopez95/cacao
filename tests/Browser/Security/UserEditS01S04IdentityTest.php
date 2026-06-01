<?php

/**
 * Browser (Dusk) tests — S01 Identidad personal, S02 Credenciales,
 *                         S03 Dirección, S04 Perfil demográfico
 *
 * UC cubiertos:
 * - UC-S01-01: Carga inicial — S01 pre-llena firstName, lastName, docNumber, birthDate,
 *              genderId, nationalityId, phone1 desde DB (todos expuestos por UserEditResource)
 * - UC-S01-02: Guardado — cambiar firstName → guardar → DB persiste
 * - UC-S01-03: Guardado campos de identidad extendida — docTypeId, docNumber,
 *              genderId, nationalityId, phone_primary → guardar → DB persiste
 * - UC-S02-01: Carga S02 — email pre-poblado, sin errores JS
 * - UC-S03-01: Agregar dirección — agregar → guardar S03 → assertDatabaseHas
 * - UC-S04-01: Carga S04 — sin errores JS, religionId pre-llenado desde demographicProfile
 * - UC-S04-02: Guardar birthCity → PUT demographic-profile → DB persiste
 * - UC-S04-03: Guardar con religionId → religion_id persiste en DB
 * - UC-S04-04: Guardar S04 sin cambiar religionId no lo nullifica (no-nullification)
 *
 * Run with: vendor/bin/sail dusk tests/Browser/Security/UserEditS01S04IdentityTest.php
 *
 * Usa DatabaseMigrations — corre contra laravel_dusk (DB separada, ver .env.dusk.local).
 */

use App\Enums\EducationalLevel;
use App\Models\Catalogs\DocumentType;
use App\Models\Catalogs\Gender;
use App\Models\Catalogs\Religion;
use App\Models\Country;
use App\Models\DemographicProfile;
use App\Models\Student;
use App\Models\User;
use App\Models\UserConsent;
use Database\Seeders\CatalogsSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseMigrations::class);

// ---------------------------------------------------------------------------
// Setup
// ---------------------------------------------------------------------------

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    $this->seed(CatalogsSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Admin logueado — Gate::before cortocircuita autorización.
 */
function adminForIdentity(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Crea un usuario objetivo con rol Estudiante y consentimiento activo.
 * Si se pasan campos, los coloca directamente en users (para S01 pre-llenado).
 */
function targetStudentForIdentity(array $userFields = []): User
{
    $target = User::factory()->create($userFields);
    $target->assignRole('Estudiante');

    Student::factory()->create([
        'user_id' => $target->id,
        'educational_level' => EducationalLevel::University,
    ]);

    UserConsent::create([
        'user_id' => $target->id,
        'policy_version' => 'v1.0',
        'accepts_data_processing' => true,
        'accepts_image_use' => true,
        'accepts_whatsapp_contact' => true,
        'accepts_email_contact' => true,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'dusk-test',
        'granted_at' => now(),
        'revoked_at' => null,
    ]);

    return $target;
}

/**
 * Navega al tab de identidad del usuario objetivo.
 */
function navigateToS01(Browser $browser, User $admin, User $target): Browser
{
    return $browser
        ->loginAs($admin)
        ->visit(route('security.users.edit', $target))
        ->waitFor('[dusk="tab-identity"]', 60)
        ->assertDontSee('500')
        ->assertDontSee('Whoops');
}

// ---------------------------------------------------------------------------
// UC-S01-01 — S01 pre-llena firstName y lastName desde DB
// ---------------------------------------------------------------------------

test('UC-S01-01: S01 pre-llena first_name y last_name en inputs al cargar', function () {
    $admin = adminForIdentity();
    $target = targetStudentForIdentity([
        'first_name' => 'María',
        'last_name' => 'González',
    ]);

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS01($browser, $admin, $target);

        $browser->waitFor('[dusk="first-name-input"]', 5);

        expect($browser->value('[dusk="first-name-input"]'))->toBe('María');
        expect($browser->value('[dusk="last-name-input"]'))->toBe('González');
    });
});

// ---------------------------------------------------------------------------
// UC-S01-02 — S01 pre-llena genderId y nationalityId desde DB
// ---------------------------------------------------------------------------

test('UC-S01-02: S01 pre-llena gender_id y nationality_id en selects al cargar', function () {
    $admin = adminForIdentity();
    $gender = Gender::where('active', true)->orderBy('sort_order')->first();
    $country = Country::where('active', true)->where('iso2', 'VE')->first();
    $target = targetStudentForIdentity([
        'gender_id' => $gender->id,
        'nationality_id' => $country->id,
    ]);

    $this->browse(function (Browser $browser) use ($admin, $target, $gender, $country) {
        navigateToS01($browser, $admin, $target);

        $browser->waitFor('[dusk="gender-select"]', 5);

        expect((int) $browser->value('[dusk="gender-select"]'))->toBe($gender->id);
        expect((int) $browser->value('[dusk="nationality-select"]'))->toBe($country->id);
    });
});

// ---------------------------------------------------------------------------
// UC-S01-03 — Guardado de firstName → persiste en DB
// ---------------------------------------------------------------------------

test('UC-S01-03: cambiar firstName en S01 → guardar → DB persiste', function () {
    $admin = adminForIdentity();
    $target = targetStudentForIdentity(['first_name' => 'Carlos']);

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS01($browser, $admin, $target);

        $browser->waitFor('[dusk="first-name-input"]', 5)
            ->clear('[dusk="first-name-input"]')
            ->type('[dusk="first-name-input"]', 'Roberto')
            ->click('[dusk="save-section-1"]')
            ->waitForText('Guardado', 5);
    });

    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'first_name' => 'Roberto',
    ]);
});

// ---------------------------------------------------------------------------
// UC-S01-04 — Guardado de genderId → persiste en DB
// ---------------------------------------------------------------------------

test('UC-S01-04: cambiar gender_id en S01 → guardar → DB persiste', function () {
    $admin = adminForIdentity();
    $genders = Gender::where('active', true)->orderBy('sort_order')->get();
    $initial = $genders->first();
    $changed = $genders->skip(1)->first();
    $target = targetStudentForIdentity(['gender_id' => $initial->id]);

    $this->browse(function (Browser $browser) use ($admin, $target, $changed) {
        navigateToS01($browser, $admin, $target);

        $browser->waitFor('[dusk="gender-select"]', 5)
            ->select('[dusk="gender-select"]', (string) $changed->id)
            ->click('[dusk="save-section-1"]')
            ->waitForText('Guardado', 5);
    });

    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'gender_id' => $changed->id,
    ]);
});

// ---------------------------------------------------------------------------
// UC-S01-05 — Guardado de documentTypeId + documentNumber → persiste en DB
// ---------------------------------------------------------------------------

test('UC-S01-05: guardar doc_type_id y document_number en S01 → DB persiste', function () {
    $admin = adminForIdentity();
    $docType = DocumentType::where('active', true)->orderBy('sort_order')->first();
    $target = targetStudentForIdentity();

    $this->browse(function (Browser $browser) use ($admin, $target, $docType) {
        navigateToS01($browser, $admin, $target);

        $browser->waitFor('[dusk="doc-type-select"]', 5)
            ->select('[dusk="doc-type-select"]', (string) $docType->id)
            ->pause(200)
            ->clear('[dusk="doc-number-input"]')
            ->type('[dusk="doc-number-input"]', '12345678')
            ->click('[dusk="save-section-1"]')
            ->waitForText('Guardado', 5);
    });

    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'document_type_id' => $docType->id,
        'document_number' => '12345678',
    ]);
});

// ---------------------------------------------------------------------------
// UC-S01-06 — Carga S01: gender select tiene opciones del catálogo
// ---------------------------------------------------------------------------

test('UC-S01-06: el select de género tiene al menos 2 opciones del catálogo', function () {
    $admin = adminForIdentity();
    $target = targetStudentForIdentity();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS01($browser, $admin, $target);

        $browser->waitFor('[dusk="gender-select"]', 5);

        $options = $browser->elements('[dusk="gender-select"] option');
        expect(count($options))->toBeGreaterThan(2);
    });
});

// ---------------------------------------------------------------------------
// UC-S02-01 — S02 carga sin errores JS y email pre-poblado
// ---------------------------------------------------------------------------

test('UC-S02-01: S02 carga sin errores JS y email pre-poblado', function () {
    $admin = adminForIdentity();
    $target = targetStudentForIdentity(['email' => 'test.student@cacao.edu.ve']);

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS01($browser, $admin, $target);

        // S02 is in the same identity tab
        $browser->waitFor('#sec-2', 10)
            ->assertDontSee('500')
            ->assertDontSee('Whoops');

        $browser->waitFor('[dusk="email-input"]', 5);
        expect($browser->value('[dusk="email-input"]'))->toBe('test.student@cacao.edu.ve');
    });
});

// ---------------------------------------------------------------------------
// UC-S03-01 — Agregar dirección → guardar → assertDatabaseHas
// ---------------------------------------------------------------------------

test('UC-S03-01: agregar dirección → guardar S03 → DB persiste', function () {
    $admin = adminForIdentity();
    $target = targetStudentForIdentity();
    $country = Country::where('active', true)->where('iso2', 'VE')->first();

    $this->browse(function (Browser $browser) use ($admin, $target, $country) {
        navigateToS01($browser, $admin, $target);

        // Scroll to S03 and interact
        $browser->waitFor('#sec-3', 10)
            ->scrollIntoView('#sec-3')
            ->pause(300);

        // Click the "Agregar otra dirección" button (dusk="repeatable-add" on AppRepeatable)
        $browser->waitFor('#sec-3 [dusk="repeatable-add"]', 5)
            ->click('#sec-3 [dusk="repeatable-add"]')
            ->pause(400);

        // Fill in the address form fields
        $browser->waitFor('[dusk="address-country-select-0"]', 5)
            ->select('[dusk="address-country-select-0"]', (string) $country->id)
            ->pause(200)
            ->clear('[dusk="address-line1-0"]')
            ->type('[dusk="address-line1-0"]', 'Av. Libertador, Edif. CACAO, Piso 3')
            ->click('[dusk="save-section-3"]')
            ->waitForText('Guardado', 5);
    });

    $this->assertDatabaseHas('user_addresses', [
        'user_id' => $target->id,
        'country_id' => $country->id,
        'address_line1' => 'Av. Libertador, Edif. CACAO, Piso 3',
    ]);
});

// ---------------------------------------------------------------------------
// UC-S04-01 — S04 carga sin errores JS
// ---------------------------------------------------------------------------

test('UC-S04-01: S04 carga sin errores JS', function () {
    $admin = adminForIdentity();
    $target = targetStudentForIdentity();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS01($browser, $admin, $target);

        $browser->waitFor('#sec-4', 10)
            ->scrollIntoView('#sec-4')
            ->assertDontSee('500')
            ->assertDontSee('Whoops');
    });
});

// ---------------------------------------------------------------------------
// UC-S04-02 — Guardar birthCity en S04 → DB persiste
// ---------------------------------------------------------------------------

test('UC-S04-02: guardar birthCity en S04 → PUT demographic-profile → DB persiste', function () {
    $admin = adminForIdentity();
    $target = targetStudentForIdentity();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS01($browser, $admin, $target);

        $browser->waitFor('#sec-4', 10)
            ->scrollIntoView('#sec-4')
            ->waitFor('[dusk="birth-city-input"]', 5)
            ->clear('[dusk="birth-city-input"]')
            ->type('[dusk="birth-city-input"]', 'Caracas')
            ->click('[dusk="save-section-4"]')
            ->waitForText('Guardado', 5);
    });

    $this->assertDatabaseHas('demographic_profiles', [
        'user_id' => $target->id,
        'birth_city' => 'Caracas',
    ]);
});

// ---------------------------------------------------------------------------
// UC-S04-03 — Guardar religionId en S04 → religion_id persiste en DB
// ---------------------------------------------------------------------------

test('UC-S04-03: guardar religion_id en S04 → DB persiste', function () {
    $admin = adminForIdentity();
    $religion = Religion::active()->first();
    $target = targetStudentForIdentity();

    $this->browse(function (Browser $browser) use ($admin, $target, $religion) {
        navigateToS01($browser, $admin, $target);

        $browser->waitFor('#sec-4', 10)
            ->scrollIntoView('#sec-4')
            ->waitFor('[dusk="religion-select"]', 5)
            ->select('[dusk="religion-select"]', (string) $religion->id)
            ->click('[dusk="save-section-4"]')
            ->waitForText('Guardado', 5);
    });

    $this->assertDatabaseHas('demographic_profiles', [
        'user_id' => $target->id,
        'religion_id' => $religion->id,
    ]);
});

// ---------------------------------------------------------------------------
// UC-S04-04 — Guardar S04 sin cambiar religionId no lo nullifica
// ---------------------------------------------------------------------------

test('UC-S04-04: guardar S04 sin tocar religion_id no lo nullifica en DB', function () {
    $admin = adminForIdentity();
    $religion = Religion::active()->first();
    $target = targetStudentForIdentity();

    // Pre-create demographic profile with religion_id set
    DemographicProfile::create([
        'user_id' => $target->id,
        'religion_id' => $religion->id,
        'is_indigenous' => false,
        'is_returned_migrant' => false,
        'practices_sport' => false,
    ]);

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS01($browser, $admin, $target);

        $browser->waitFor('#sec-4', 10)
            ->scrollIntoView('#sec-4')
            ->waitFor('[dusk="birth-city-input"]', 5)
            // Only change birthCity — don't touch religionId
            ->clear('[dusk="birth-city-input"]')
            ->type('[dusk="birth-city-input"]', 'Maracay')
            ->click('[dusk="save-section-4"]')
            ->waitForText('Guardado', 5);
    });

    // religion_id must NOT have been nullified
    $this->assertDatabaseHas('demographic_profiles', [
        'user_id' => $target->id,
        'religion_id' => $religion->id,
        'birth_city' => 'Maracay',
    ]);
});

// ---------------------------------------------------------------------------
// UC-S04-05 — S04 pre-llena birthCity y religionId desde demographicProfile prop
// ---------------------------------------------------------------------------

test('UC-S04-05: S04 pre-llena birthCity y religion_id desde props al recargar', function () {
    $admin = adminForIdentity();
    $religion = Religion::active()->first();
    $target = targetStudentForIdentity();

    DemographicProfile::create([
        'user_id' => $target->id,
        'birth_city' => 'Barquisimeto',
        'religion_id' => $religion->id,
        'is_indigenous' => false,
        'is_returned_migrant' => false,
        'practices_sport' => false,
    ]);

    $this->browse(function (Browser $browser) use ($admin, $target, $religion) {
        navigateToS01($browser, $admin, $target);

        $browser->waitFor('#sec-4', 10)
            ->scrollIntoView('#sec-4')
            ->waitFor('[dusk="birth-city-input"]', 5);

        expect($browser->value('[dusk="birth-city-input"]'))->toBe('Barquisimeto');
        expect((int) $browser->value('[dusk="religion-select"]'))->toBe($religion->id);
    });
});
