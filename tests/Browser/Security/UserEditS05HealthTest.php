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
 * NOTA: No usa DatabaseMigrations — los tests corren sobre la DB de desarrollo sin borrarla.
 *
 * Los usuarios de test se crean con email *@dusk.test y se limpian en beforeEach.
 */

use App\Enums\EducationalLevel;
use App\Models\Catalogs\BloodType;
use App\Models\Catalogs\DisabilityType;
use App\Models\Catalogs\InsuranceType;
use App\Models\HealthProfile;
use App\Models\Student;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// ---------------------------------------------------------------------------
// Setup — idempotente, nunca borra la DB de desarrollo
// ---------------------------------------------------------------------------

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // Roles: idempotente, no falla si ya existen
    Role::firstOrCreate(['name' => 'Admin',         'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Estudiante',    'guard_name' => 'web']);

    // Limpiar usuarios de test de ejecuciones anteriores (email *@dusk.test)
    cleanDuskTestUsers();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Elimina todos los usuarios creados por tests Dusk (email *@dusk.test).
 * Se ejecuta en beforeEach para limpiar ejecuciones anteriores.
 * El orden respeta las FKs RESTRICT del proyecto.
 */
function cleanDuskTestUsers(): void
{
    $ids = User::where('email', 'like', '%@dusk.test')->pluck('id');

    if ($ids->isEmpty()) {
        return;
    }

    DB::table('health_profiles')->whereIn('user_id', $ids)->delete();
    DB::table('user_consents')->whereIn('user_id', $ids)->delete();
    DB::table('demographic_profiles')->whereIn('user_id', $ids)->delete();
    DB::table('user_addresses')->whereIn('user_id', $ids)->delete();
    DB::table('user_documents')->whereIn('user_id', $ids)->delete();
    DB::table('students')->whereIn('user_id', $ids)->delete();
    DB::table('professors')->whereIn('user_id', $ids)->delete();
    DB::table('guardians')->whereIn('user_id', $ids)->delete();
    DB::table('model_has_roles')
        ->where('model_type', User::class)
        ->whereIn('model_id', $ids)
        ->delete();
    DB::table('sessions')->whereIn('user_id', $ids)->delete();
    User::whereIn('id', $ids)->forceDelete();
}

/**
 * Admin con solo rol 'Admin' — Gate::before cortocircuita autorización.
 * Email con dominio @dusk.test para que sea limpiado en el próximo beforeEach.
 */
function adminForS05(): User
{
    $user = User::factory()->create(['email' => uniqid('admin.s05.').'@dusk.test']);
    $user->assignRole('Admin');

    return $user;
}

/**
 * Crea un usuario objetivo con rol Estudiante, consentimiento activo, sub-registro Student
 * y perfil de salud pre-cargado.
 *
 * Rol Estudiante — refleja el usuario real que usa el formulario S05 en producción.
 * UF_TABS renderiza la tab "Salud" para Estudiante igual que para Admin.
 * ConsentService::requireConsent() requiere consentimiento activo en el target.
 */
function targetWithHealthProfile(array $healthData = []): User
{
    $target = User::factory()->create(['email' => uniqid('target.s05.').'@dusk.test']);
    $target->assignRole('Estudiante');

    // Sub-registro requerido para que UF_TABS renderice tabs de Estudiante
    Student::firstOrCreate(
        ['user_id' => $target->id],
        ['educational_level' => EducationalLevel::University]
    );

    // Consentimiento activo — requerido por ConsentService::requireConsent()
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
    $target = User::factory()->create(['email' => uniqid('target.noconsent.').'@dusk.test']);
    $target->assignRole('Estudiante');
    Student::firstOrCreate(
        ['user_id' => $target->id],
        ['educational_level' => EducationalLevel::University]
    );
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
