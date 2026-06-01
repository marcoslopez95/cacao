<?php

/**
 * Browser (Dusk) tests — S16 Perfil del representante (guardian),
 *                         S17 Perfil del personal (professor)
 *
 * UC cubiertos:
 * - UC-S16-01: Carga S16 — tab "Representante" visible, sin errores JS
 * - UC-S16-02: Guardar occupation → PUT guardians/{guardian}/profile → DB persiste
 * - UC-S16-03: Guardar marital_status_id → DB persiste (FK integer, no string)
 * - UC-S16-04: Pre-llenado — occupation y marital_status_id cargados en reload
 * - UC-S17-01: Carga S17 — tab "Profesional" visible, sin errores JS
 * - UC-S17-02: Guardar degree y contract_type_id → DB persiste
 * - UC-S17-03: Guardar hire_date → DB persiste en formato YYYY-MM-DD
 * - UC-S17-04: Pre-llenado — degree, contract_type_id, hire_date cargados en reload
 *
 * Run with: vendor/bin/sail dusk tests/Browser/Security/UserEditS16S17ProfilesTest.php
 *
 * Usa DatabaseMigrations — corre contra laravel_dusk (DB separada, ver .env.dusk.local).
 */

use App\Models\Catalogs\ContractType;
use App\Models\Catalogs\DedicationType;
use App\Models\Catalogs\EmploymentStatus;
use App\Models\Catalogs\MaritalStatus;
use App\Models\Guardian;
use App\Models\GuardianProfile;
use App\Models\Professor;
use App\Models\StaffProfile;
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
function adminForProfiles(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Crea usuario objetivo con rol Representante + registro Guardian.
 * Opcionalmente crea GuardianProfile con los datos dados.
 */
function guardianTargetWithProfile(array $profileData = []): User
{
    $guardian = Guardian::factory()->create();
    $target = $guardian->user;

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

    if (! empty($profileData)) {
        GuardianProfile::create(array_merge(['guardian_id' => $guardian->id], $profileData));
    }

    return $target;
}

/**
 * Crea usuario objetivo con rol Profesor + registro Professor.
 * Opcionalmente crea StaffProfile con los datos dados.
 */
function professorTargetWithProfile(array $profileData = []): User
{
    $professor = Professor::factory()->create();
    $target = $professor->user;

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

    if (! empty($profileData)) {
        StaffProfile::create(array_merge(['professor_id' => $professor->id], $profileData));
    }

    return $target;
}

/**
 * Navega al tab "Representante" (guard) del usuario objetivo.
 */
function navigateToS16(Browser $browser, User $admin, User $target): Browser
{
    return $browser
        ->loginAs($admin)
        ->visit(route('security.users.edit', $target))
        ->waitFor('[dusk="tab-guard"]', 15)
        ->click('[dusk="tab-guard"]')
        ->waitFor('#sec-16', 10)
        ->assertDontSee('500')
        ->assertDontSee('Whoops');
}

/**
 * Navega al tab "Profesional" (job) del usuario objetivo.
 */
function navigateToS17(Browser $browser, User $admin, User $target): Browser
{
    return $browser
        ->loginAs($admin)
        ->visit(route('security.users.edit', $target))
        ->waitFor('[dusk="tab-job"]', 15)
        ->click('[dusk="tab-job"]')
        ->waitFor('#sec-17', 10)
        ->assertDontSee('500')
        ->assertDontSee('Whoops');
}

// ---------------------------------------------------------------------------
// UC-S16-01 — S16 carga sin errores JS
// ---------------------------------------------------------------------------

test('UC-S16-01: S16 carga sin errores JS y tab Representante es visible', function () {
    $admin = adminForProfiles();
    $target = guardianTargetWithProfile();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS16($browser, $admin, $target);

        $browser->assertVisible('#sec-16');
    });
});

// ---------------------------------------------------------------------------
// UC-S16-02 — Guardar occupation → DB persiste
// ---------------------------------------------------------------------------

test('UC-S16-02: guardar occupation en S16 → PUT guardian profile → DB persiste', function () {
    $admin = adminForProfiles();
    $target = guardianTargetWithProfile();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS16($browser, $admin, $target);

        $browser->waitFor('[dusk="occupation-input"]', 5)
            ->clear('[dusk="occupation-input"]')
            ->type('[dusk="occupation-input"]', 'Economista')
            ->click('[dusk="save-section-16"]')
            ->waitForText('Guardado', 5);
    });

    $guardian = Guardian::where('user_id', $target->id)->first();

    $this->assertDatabaseHas('guardian_profiles', [
        'guardian_id' => $guardian->id,
        'occupation' => 'Economista',
    ]);
});

// ---------------------------------------------------------------------------
// UC-S16-03 — Guardar marital_status_id en S16 → FK integer persiste en DB
// ---------------------------------------------------------------------------

test('UC-S16-03: guardar marital_status_id en S16 → FK entero persiste en DB', function () {
    $admin = adminForProfiles();
    $marital = MaritalStatus::active()->first();
    $target = guardianTargetWithProfile();

    $this->browse(function (Browser $browser) use ($admin, $target, $marital) {
        navigateToS16($browser, $admin, $target);

        $browser->waitFor('[dusk="guardian-marital-select"]', 5)
            ->select('[dusk="guardian-marital-select"]', (string) $marital->id)
            ->click('[dusk="save-section-16"]')
            ->waitForText('Guardado', 5);
    });

    $guardian = Guardian::where('user_id', $target->id)->first();

    $this->assertDatabaseHas('guardian_profiles', [
        'guardian_id' => $guardian->id,
        'marital_status_id' => $marital->id,
    ]);
});

// ---------------------------------------------------------------------------
// UC-S16-04 — S16 pre-llena occupation y marital_status_id al recargar
// ---------------------------------------------------------------------------

test('UC-S16-04: S16 pre-llena occupation y marital_status_id desde props al recargar', function () {
    $admin = adminForProfiles();
    $marital = MaritalStatus::active()->first();
    $target = guardianTargetWithProfile([
        'occupation' => 'Ingeniero Civil',
        'marital_status_id' => $marital->id,
    ]);

    $this->browse(function (Browser $browser) use ($admin, $target, $marital) {
        navigateToS16($browser, $admin, $target);

        $browser->waitFor('[dusk="occupation-input"]', 5);

        expect($browser->value('[dusk="occupation-input"]'))->toBe('Ingeniero Civil');
        expect((int) $browser->value('[dusk="guardian-marital-select"]'))->toBe($marital->id);
    });
});

// ---------------------------------------------------------------------------
// UC-S17-01 — S17 carga sin errores JS
// ---------------------------------------------------------------------------

test('UC-S17-01: S17 carga sin errores JS y tab Profesional es visible', function () {
    $admin = adminForProfiles();
    $target = professorTargetWithProfile();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS17($browser, $admin, $target);

        $browser->assertVisible('#sec-17');
    });
});

// ---------------------------------------------------------------------------
// UC-S17-02 — Guardar degree → DB persiste
// ---------------------------------------------------------------------------

test('UC-S17-02: guardar degree en S17 → PUT staff-profile → DB persiste', function () {
    $admin = adminForProfiles();
    $contractType = ContractType::active()->first();
    $dedicationType = DedicationType::active()->first();
    $emplStatus = EmploymentStatus::active()->first();
    $target = professorTargetWithProfile();

    $this->browse(function (Browser $browser) use ($admin, $target, $contractType, $dedicationType, $emplStatus) {
        navigateToS17($browser, $admin, $target);

        $browser->waitFor('[dusk="degree-input"]', 5)
            ->clear('[dusk="degree-input"]')
            ->type('[dusk="degree-input"]', 'Lic. en Ciencias de la Educación')
            ->select('[dusk="contract-type-select"]', (string) $contractType->id)
            ->select('[dusk="dedication-type-select"]', (string) $dedicationType->id);

        $browser->script('
            var el = document.querySelector("[dusk=\'hire-date-input\']");
            el.value = "2020-01-15";
            el.dispatchEvent(new Event("input", { bubbles: true }));
        ');

        $browser->select('[dusk="employment-status-select"]', (string) $emplStatus->id)
            ->scrollIntoView('[dusk="save-section-17"]')
            ->pause(300)
            ->click('[dusk="save-section-17"]')
            ->waitForText('Guardado', 5);
    });

    $professor = Professor::where('user_id', $target->id)->first();

    $this->assertDatabaseHas('staff_profiles', [
        'professor_id' => $professor->id,
        'academic_title' => 'Lic. en Ciencias de la Educación',
        'contract_type_id' => $contractType->id,
    ]);
});

// ---------------------------------------------------------------------------
// UC-S17-03 — Guardar hire_date → formato date correcto en DB
// ---------------------------------------------------------------------------

test('UC-S17-03: guardar hire_date en S17 → fecha persiste correctamente en DB', function () {
    $admin = adminForProfiles();
    $contractType = ContractType::active()->first();
    $dedicationType = DedicationType::active()->first();
    $emplStatus = EmploymentStatus::active()->first();
    $target = professorTargetWithProfile();

    $this->browse(function (Browser $browser) use ($admin, $target, $contractType, $dedicationType, $emplStatus) {
        navigateToS17($browser, $admin, $target);

        $browser->waitFor('[dusk="hire-date-input"]', 5);

        $browser->script('
            var el = document.querySelector("[dusk=\'hire-date-input\']");
            el.value = "2019-03-01";
            el.dispatchEvent(new Event("input", { bubbles: true }));
        ');

        $browser->select('[dusk="contract-type-select"]', (string) $contractType->id)
            ->select('[dusk="dedication-type-select"]', (string) $dedicationType->id)
            ->select('[dusk="employment-status-select"]', (string) $emplStatus->id)
            ->scrollIntoView('[dusk="save-section-17"]')
            ->pause(300)
            ->click('[dusk="save-section-17"]')
            ->waitForText('Guardado', 5);
    });

    $professor = Professor::where('user_id', $target->id)->first();

    $this->assertDatabaseHas('staff_profiles', [
        'professor_id' => $professor->id,
        'contract_type_id' => $contractType->id,
        'dedication_type_id' => $dedicationType->id,
    ]);

    $profile = StaffProfile::where('professor_id', $professor->id)->first();
    expect($profile->hire_date->toDateString())->toBe('2019-03-01');
});

// ---------------------------------------------------------------------------
// UC-S17-04 — S17 pre-llena degree y contract_type_id al recargar
// ---------------------------------------------------------------------------

test('UC-S17-04: S17 pre-llena degree y contract_type_id desde props al recargar', function () {
    $admin = adminForProfiles();
    $contractType = ContractType::active()->first();
    $dedicationType = DedicationType::active()->first();
    $emplStatus = EmploymentStatus::active()->first();

    $target = professorTargetWithProfile();
    $professor = Professor::where('user_id', $target->id)->first();

    StaffProfile::create([
        'professor_id' => $professor->id,
        'employee_code' => 'EMP-001',
        'academic_title' => 'Dr. en Física',
        'contract_type_id' => $contractType->id,
        'dedication_type_id' => $dedicationType->id,
        'hire_date' => '2018-05-10',
        'employment_status_id' => $emplStatus->id,
        'is_coordinator' => false,
    ]);

    $this->browse(function (Browser $browser) use ($admin, $target, $contractType) {
        navigateToS17($browser, $admin, $target);

        $browser->waitFor('[dusk="degree-input"]', 5);

        expect($browser->value('[dusk="degree-input"]'))->toBe('Dr. en Física');
        expect((int) $browser->value('[dusk="contract-type-select"]'))->toBe($contractType->id);
    });
});

// ---------------------------------------------------------------------------
// UC-S17-05 — Catálogos S17 muestran opciones (contractTypes, dedicationTypes)
// ---------------------------------------------------------------------------

test('UC-S17-05: selects de tipo de contrato y dedicación tienen opciones del catálogo', function () {
    $admin = adminForProfiles();
    $target = professorTargetWithProfile();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS17($browser, $admin, $target);

        $browser->waitFor('[dusk="contract-type-select"]', 5);

        $contractOptions = $browser->elements('[dusk="contract-type-select"] option');
        $dedicationOptions = $browser->elements('[dusk="dedication-type-select"] option');

        expect(count($contractOptions))->toBeGreaterThan(1);
        expect(count($dedicationOptions))->toBeGreaterThan(1);
    });
});
