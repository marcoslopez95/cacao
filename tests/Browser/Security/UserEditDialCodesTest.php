<?php

/**
 * Browser (Dusk) tests — Dial code fields (phone_primary_dial, phone_secondary_dial, emergency_contact_phone_dial)
 *
 * Audita el round-trip completo de los tres campos de dial code añadidos en la sesión
 * 2026-05-30:
 *
 * S01 (Identidad):
 *   - UC-DIAL-01: phone_primary_dial — pre-fill desde DB, modificar → guardar → DB persiste
 *   - UC-DIAL-02: phone_secondary_dial — pre-fill desde DB, modificar → guardar → DB persiste
 *   - UC-DIAL-03: phone_primary_dial y phone_secondary_dial pre-llenan valores correctos al recargar
 *
 * S05 (Salud):
 *   - UC-DIAL-04: emergency_contact_phone_dial — pre-fill desde DB, modificar → guardar → DB persiste
 *   - UC-DIAL-05: emergency_contact_phone_dial pre-llena valor correcto al recargar
 *
 * Run with: vendor/bin/sail dusk tests/Browser/Security/UserEditDialCodesTest.php
 *
 * Usa DatabaseMigrations — corre contra laravel_dusk (DB separada, ver .env.dusk.local).
 */

use App\Enums\EducationalLevel;
use App\Models\HealthProfile;
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
 * Admin logueado — Gate::before cortocircuita autorización para el actor.
 */
function adminForDial(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Crea un usuario objetivo con rol Estudiante, consentimiento activo y sub-registro Student.
 * Si se pasan userFields, se aplican a la tabla users.
 */
function targetForDial(array $userFields = []): User
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
 * Crea un usuario objetivo con perfil de salud pre-cargado.
 */
function targetWithHealth(array $userFields = [], array $healthData = []): User
{
    $target = targetForDial($userFields);

    if (! empty($healthData)) {
        HealthProfile::create(array_merge(['user_id' => $target->id], $healthData));
    }

    return $target;
}

/**
 * Navega al tab de identidad del usuario objetivo y espera que cargue.
 */
function navigateToDialS01(Browser $browser, User $admin, User $target): Browser
{
    return $browser
        ->loginAs($admin)
        ->visit(route('security.users.edit', $target))
        ->waitFor('[dusk="tab-identity"]', 60)
        ->assertDontSee('500')
        ->assertDontSee('Whoops')
        ->waitFor('[dusk="phone1-dial-select"]', 10);
}

/**
 * Navega al tab de salud del usuario objetivo y espera que cargue.
 */
function navigateToDialS05(Browser $browser, User $admin, User $target): Browser
{
    return $browser
        ->loginAs($admin)
        ->visit(route('security.users.edit', $target))
        ->waitFor('[dusk="tab-health"]', 15)
        ->click('[dusk="tab-health"]')
        ->waitFor('#sec-5', 10)
        ->assertDontSee('500')
        ->assertDontSee('Whoops')
        ->waitFor('[dusk="emergency-dial-select"]', 10);
}

// ---------------------------------------------------------------------------
// UC-DIAL-01 — phone_primary_dial: pre-fill y save
// ---------------------------------------------------------------------------

test('UC-DIAL-01: phone_primary_dial — pre-fill desde DB y persiste en DB al guardar', function () {
    $admin = adminForDial();
    $target = targetForDial([
        'phone_primary' => '4121234567',
        'phone_primary_dial' => '+58',
    ]);

    // Verificar pre-fill
    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDialS01($browser, $admin, $target);

        expect($browser->value('[dusk="phone1-dial-select"]'))->toBe('+58');
    });

    // Cambiar a +1 (EE.UU.), guardar y verificar en DB
    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDialS01($browser, $admin, $target);

        $browser->select('[dusk="phone1-dial-select"]', '+1')
            ->click('[dusk="save-section-1"]')
            ->waitForText('Guardado', 5);
    });

    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'phone_primary_dial' => '+1',
    ]);
});

// ---------------------------------------------------------------------------
// UC-DIAL-02 — phone_secondary_dial: pre-fill y save
// ---------------------------------------------------------------------------

test('UC-DIAL-02: phone_secondary_dial — pre-fill desde DB y persiste en DB al guardar', function () {
    $admin = adminForDial();
    $target = targetForDial([
        'phone_secondary' => '4142345678',
        'phone_secondary_dial' => '+58',
    ]);

    // Verificar pre-fill
    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDialS01($browser, $admin, $target);

        expect($browser->value('[dusk="phone2-dial-select"]'))->toBe('+58');
    });

    // Cambiar a +51 (Perú), guardar y verificar en DB
    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDialS01($browser, $admin, $target);

        $browser->select('[dusk="phone2-dial-select"]', '+51')
            ->click('[dusk="save-section-1"]')
            ->waitForText('Guardado', 5);
    });

    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'phone_secondary_dial' => '+51',
    ]);
});

// ---------------------------------------------------------------------------
// UC-DIAL-03 — phone_primary_dial y phone_secondary_dial: reload muestra nuevo valor
// ---------------------------------------------------------------------------

test('UC-DIAL-03: phone_primary_dial y phone_secondary_dial se pre-llenan tras reload', function () {
    $admin = adminForDial();
    $target = targetForDial([
        'phone_primary' => '4121234567',
        'phone_primary_dial' => '+58',
        'phone_secondary' => '4142345678',
        'phone_secondary_dial' => '+58',
    ]);

    // Cambiar ambos dials y guardar
    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDialS01($browser, $admin, $target);

        $browser->select('[dusk="phone1-dial-select"]', '+34')
            ->select('[dusk="phone2-dial-select"]', '+55')
            ->click('[dusk="save-section-1"]')
            ->waitForText('Guardado', 5);
    });

    // Recargar: el browser debe mostrar los nuevos valores
    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDialS01($browser, $admin, $target);

        expect($browser->value('[dusk="phone1-dial-select"]'))->toBe('+34');
        expect($browser->value('[dusk="phone2-dial-select"]'))->toBe('+55');
    });
});

// ---------------------------------------------------------------------------
// UC-DIAL-04 — emergency_contact_phone_dial: pre-fill y save
// ---------------------------------------------------------------------------

test('UC-DIAL-04: emergency_contact_phone_dial — pre-fill desde DB y persiste en DB al guardar', function () {
    $admin = adminForDial();
    $target = targetWithHealth([], [
        'emergency_contact_name' => 'Ana Torres',
        'emergency_contact_phone' => '4161234567',
        'emergency_contact_phone_dial' => '+58',
        'emergency_contact_relation' => 'Madre',
    ]);

    // Verificar pre-fill
    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDialS05($browser, $admin, $target);

        expect($browser->value('[dusk="emergency-dial-select"]'))->toBe('+58');
    });

    // Cambiar a +1 (EE.UU.), guardar y verificar en DB
    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDialS05($browser, $admin, $target);

        $browser->select('[dusk="emergency-dial-select"]', '+1')
            ->click('[dusk="save-section-5"]')
            ->waitForText('Guardado', 5);
    });

    $this->assertDatabaseHas('health_profiles', [
        'user_id' => $target->id,
        'emergency_contact_phone_dial' => '+1',
    ]);
});

// ---------------------------------------------------------------------------
// UC-DIAL-05 — emergency_contact_phone_dial: reload muestra nuevo valor
// ---------------------------------------------------------------------------

test('UC-DIAL-05: emergency_contact_phone_dial se pre-llena con valor nuevo tras reload', function () {
    $admin = adminForDial();
    $target = targetWithHealth([], [
        'emergency_contact_name' => 'Luis Pérez',
        'emergency_contact_phone' => '4241234567',
        'emergency_contact_phone_dial' => '+58',
        'emergency_contact_relation' => 'Padre',
    ]);

    // Cambiar dial y guardar
    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDialS05($browser, $admin, $target);

        $browser->select('[dusk="emergency-dial-select"]', '+57')
            ->click('[dusk="save-section-5"]')
            ->waitForText('Guardado', 5);
    });

    // DB check
    $this->assertDatabaseHas('health_profiles', [
        'user_id' => $target->id,
        'emergency_contact_phone_dial' => '+57',
    ]);

    // Recargar: el browser debe mostrar el nuevo valor
    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDialS05($browser, $admin, $target);

        expect($browser->value('[dusk="emergency-dial-select"]'))->toBe('+57');
    });
});

// ---------------------------------------------------------------------------
// UC-DIAL-06 — Guardar S01 sin modificar phone_primary_dial no lo nullifica
// ---------------------------------------------------------------------------

test('UC-DIAL-06: guardar S01 sin tocar phone_primary_dial no lo nullifica en DB', function () {
    $admin = adminForDial();
    $target = targetForDial([
        'first_name' => 'Carlos',
        'phone_primary' => '4121234567',
        'phone_primary_dial' => '+58',
    ]);

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDialS01($browser, $admin, $target);

        // Solo cambiar el nombre, sin tocar el dial
        $browser->waitFor('[dusk="first-name-input"]', 5)
            ->clear('[dusk="first-name-input"]')
            ->type('[dusk="first-name-input"]', 'Roberto')
            ->click('[dusk="save-section-1"]')
            ->waitForText('Guardado', 5);
    });

    // phone_primary_dial NO debe haberse nullificado
    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'first_name' => 'Roberto',
        'phone_primary_dial' => '+58',
    ]);
});

// ---------------------------------------------------------------------------
// UC-DIAL-07 — Guardar S05 sin modificar emergency_contact_phone_dial no lo nullifica
// ---------------------------------------------------------------------------

test('UC-DIAL-07: guardar S05 sin tocar emergency_contact_phone_dial no lo nullifica en DB', function () {
    $admin = adminForDial();
    $target = targetWithHealth([], [
        'emergency_contact_name' => 'Rosa Martínez',
        'emergency_contact_phone' => '4261234567',
        'emergency_contact_phone_dial' => '+58',
        'emergency_contact_relation' => 'Hermana',
    ]);

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDialS05($browser, $admin, $target);

        // Solo cambiar el nombre de contacto, sin tocar el dial
        $browser->waitFor('[dusk="emergency-name-input"]', 5)
            ->clear('[dusk="emergency-name-input"]')
            ->type('[dusk="emergency-name-input"]', 'Rosa Martínez Actualizada')
            ->click('[dusk="save-section-5"]')
            ->waitForText('Guardado', 5);
    });

    // emergency_contact_phone_dial NO debe haberse nullificado
    $this->assertDatabaseHas('health_profiles', [
        'user_id' => $target->id,
        'emergency_contact_name' => 'Rosa Martínez Actualizada',
        'emergency_contact_phone_dial' => '+58',
    ]);
});
