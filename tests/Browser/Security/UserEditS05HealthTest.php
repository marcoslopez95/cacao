<?php

/**
 * Browser (Dusk) tests — S05 Salud
 *
 * Auditoría QA completa de la sección S05 (Salud) del formulario de edición de usuario.
 *
 * UC cubiertos:
 * - UC-S05-01: Carga inicial — S05 pre-llena blood_type_id, insurance_type_id desde DB
 * - UC-S05-02: Guardado exitoso — cambiar blood_type_id → guardar → recargar → valor persiste en DB y pantalla
 * - UC-S05-03: Guardado sin cambiar disability_type_id — el valor no se nullifica
 * - UC-S05-04: Dropdowns del catálogo muestran opciones de bloodTypes, insuranceTypes
 * - UC-S05-05: Toggle disability muestra/oculta el select de tipo de discapacidad
 * - UC-S05-06: Contacto de emergencia se guarda y pre-llena en reload
 * - UC-S05-07: Sin consentimiento — error se muestra al usuario (no swallow silencioso)
 *
 * Run with: vendor/bin/sail dusk tests/Browser/Security/UserEditS05HealthTest.php
 *
 * Usa DatabaseMigrations — corre contra laravel_dusk (DB separada, ver .env.dusk.local).
 */

use App\Enums\EducationalLevel;
use App\Models\Catalogs\BloodType;
use App\Models\Catalogs\DisabilityType;
use App\Models\Catalogs\InsuranceType;
use App\Models\HealthProfile;
use App\Models\Student;
use App\Models\User;
use App\Models\UserConsent;
use Database\Seeders\Catalogs\SocioeconomicCatalogsSeeder;
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
    $this->seed(SocioeconomicCatalogsSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Admin logueado — Gate::before cortocircuita autorización para el actor.
 */
function adminForS05(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Crea un usuario objetivo con rol Estudiante, consentimiento activo, sub-registro Student
 * y perfil de salud pre-cargado.
 */
function targetWithHealthProfile(array $healthData = []): User
{
    $target = User::factory()->create();
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

    if (! empty($healthData)) {
        HealthProfile::create(array_merge(['user_id' => $target->id], $healthData));
    }

    return $target;
}

/**
 * Navega a la página de edición del usuario, espera que los tabs carguen,
 * hace click en el tab "Salud" y espera que se muestre la sección 5.
 */
function navigateToS05(Browser $browser, User $admin, User $target): Browser
{
    return $browser
        ->loginAs($admin)
        ->visit(route('security.users.edit', $target))
        ->waitFor('[dusk="tab-health"]', 15)
        ->click('[dusk="tab-health"]')
        ->waitFor('#sec-5', 10)
        ->assertDontSee('500')
        ->assertDontSee('Whoops');
}

// ---------------------------------------------------------------------------
// UC-S05-01 — Carga inicial: S05 pre-llena blood_type_id e insurance_type_id desde DB
// ---------------------------------------------------------------------------

test('UC-S05-01: S05 pre-llena blood_type_id en el select al cargar la página', function () {
    $admin = adminForS05();
    $bloodType = BloodType::first();
    $target = targetWithHealthProfile(['blood_type_id' => $bloodType->id]);

    $this->browse(function (Browser $browser) use ($admin, $target, $bloodType) {
        navigateToS05($browser, $admin, $target);

        $browser->waitFor('[dusk="blood-type-select"]', 5);

        $selectedValue = $browser->value('[dusk="blood-type-select"]');
        expect((int) $selectedValue)->toBe($bloodType->id);
    });
});

test('UC-S05-01: S05 pre-llena insurance_type_id en el select cuando has_medical_insurance=true', function () {
    $admin = adminForS05();
    $insuranceType = InsuranceType::first();
    $target = targetWithHealthProfile([
        'has_medical_insurance' => true,
        'insurance_type_id' => $insuranceType->id,
    ]);

    $this->browse(function (Browser $browser) use ($admin, $target, $insuranceType) {
        navigateToS05($browser, $admin, $target);

        $browser->waitFor('[dusk="insurance-type-select"]', 5);

        $selectedValue = $browser->value('[dusk="insurance-type-select"]');
        expect((int) $selectedValue)->toBe($insuranceType->id);
    });
});

// ---------------------------------------------------------------------------
// UC-S05-02 — Guardado exitoso: cambiar blood_type_id → guardar → DB persiste
// ---------------------------------------------------------------------------

test('UC-S05-02: cambiar blood_type_id → guardar → recargar → nuevo valor persiste en DB y pantalla', function () {
    $admin = adminForS05();
    $allTypes = BloodType::orderBy('id')->get();
    $initial = $allTypes->first();
    $changed = $allTypes->skip(1)->first();
    $target = targetWithHealthProfile(['blood_type_id' => $initial->id]);

    $this->browse(function (Browser $browser) use ($admin, $target, $changed) {
        navigateToS05($browser, $admin, $target);

        $browser->waitFor('[dusk="blood-type-select"]', 5)
            ->select('[dusk="blood-type-select"]', (string) $changed->id)
            ->click('[dusk="save-section-5"]')
            ->waitForText('Guardado', 5);
    });

    $this->assertDatabaseHas('health_profiles', [
        'user_id' => $target->id,
        'blood_type_id' => $changed->id,
    ]);

    // Recargar: el browser debe mostrar el nuevo valor
    $this->browse(function (Browser $browser) use ($admin, $target, $changed) {
        navigateToS05($browser, $admin, $target);

        $browser->waitFor('[dusk="blood-type-select"]', 5);

        expect((int) $browser->value('[dusk="blood-type-select"]'))->toBe($changed->id);
    });
});

// ---------------------------------------------------------------------------
// UC-S05-03 — Guardar S05 sin tocar disability_type_id no lo nullifica
// ---------------------------------------------------------------------------

test('UC-S05-03: guardar S05 con disability existente no nullifica disability_type_id en DB', function () {
    $admin = adminForS05();
    $disType = DisabilityType::first();
    $target = targetWithHealthProfile([
        'has_disability' => true,
        'disability_type_id' => $disType->id,
    ]);

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS05($browser, $admin, $target);

        $browser->click('[dusk="save-section-5"]')
            ->waitForText('Guardado', 5);
    });

    $this->assertDatabaseHas('health_profiles', [
        'user_id' => $target->id,
        'disability_type_id' => $disType->id,
    ]);
});

// ---------------------------------------------------------------------------
// UC-S05-04 — Dropdowns muestran opciones del catálogo
// ---------------------------------------------------------------------------

test('UC-S05-04: el select de grupo sanguíneo tiene al menos 4 opciones del catálogo', function () {
    $admin = adminForS05();
    $target = targetWithHealthProfile();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS05($browser, $admin, $target);

        $browser->waitFor('[dusk="blood-type-select"]', 5);

        $options = $browser->elements('[dusk="blood-type-select"] option');
        expect(count($options))->toBeGreaterThan(4);
    });
});

// ---------------------------------------------------------------------------
// UC-S05-05 — Toggle discapacidad muestra/oculta el select de tipo
// ---------------------------------------------------------------------------

test('UC-S05-05: activar toggle discapacidad muestra el select de tipo de discapacidad', function () {
    $admin = adminForS05();
    $target = targetWithHealthProfile(['has_disability' => false]);

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS05($browser, $admin, $target);

        $browser->waitFor('[dusk="disability-toggle"]', 5);

        $browser->assertMissing('[dusk="disability-type-select"]');

        $browser->scrollIntoView('[dusk="disability-toggle"]')
            ->pause(300)
            ->click('[dusk="disability-toggle"]')
            ->pause(500);

        $browser->assertVisible('[dusk="disability-type-select"]');
    });
});

// ---------------------------------------------------------------------------
// UC-S05-06 — Contacto de emergencia: guardado y pre-llenado en reload
// ---------------------------------------------------------------------------

test('UC-S05-06: contacto de emergencia se guarda y pre-llena en reload', function () {
    $admin = adminForS05();
    $target = targetWithHealthProfile();
    $emergencyName = 'María González';
    $emergencyRel = 'Madre';

    $this->browse(function (Browser $browser) use ($admin, $target, $emergencyName, $emergencyRel) {
        navigateToS05($browser, $admin, $target);

        $browser->waitFor('[dusk="emergency-name-input"]', 5)
            ->clear('[dusk="emergency-name-input"]')
            ->type('[dusk="emergency-name-input"]', $emergencyName)
            ->clear('[dusk="emergency-rel-input"]')
            ->type('[dusk="emergency-rel-input"]', $emergencyRel)
            ->click('[dusk="save-section-5"]')
            ->waitForText('Guardado', 5);
    });

    $this->assertDatabaseHas('health_profiles', [
        'user_id' => $target->id,
        'emergency_contact_name' => $emergencyName,
        'emergency_contact_relation' => $emergencyRel,
    ]);

    // Recargar: el browser debe mostrar los valores guardados
    $this->browse(function (Browser $browser) use ($admin, $target, $emergencyName, $emergencyRel) {
        navigateToS05($browser, $admin, $target);

        $browser->waitFor('[dusk="emergency-name-input"]', 5);

        expect($browser->value('[dusk="emergency-name-input"]'))->toBe($emergencyName);
        expect($browser->value('[dusk="emergency-rel-input"]'))->toBe($emergencyRel);
    });
});

// ---------------------------------------------------------------------------
// UC-S05-07 — Sin consentimiento: el error se muestra al usuario (no swallow silencioso)
// ---------------------------------------------------------------------------

test('UC-S05-07: target sin consentimiento muestra error al guardar S05', function () {
    $admin = adminForS05();

    $target = User::factory()->create();
    $target->assignRole('Estudiante');
    Student::factory()->create([
        'user_id' => $target->id,
        'educational_level' => EducationalLevel::University,
    ]);
    // Sin UserConsent — condición de error intencional

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToS05($browser, $admin, $target);

        $browser->waitFor('[dusk="blood-type-select"]', 5)
            ->click('[dusk="save-section-5"]')
            ->pause(2000);

        // El browser debe mostrar el estado de error — no "Guardado" ni "Sin cambios"
        $browser->assertVisible('.uf-autosave.error');
        $browser->assertDontSeeIn('.uf-autosave', 'Guardado');

        expect(HealthProfile::where('user_id', $target->id)->exists())->toBeFalse();
    });
});
