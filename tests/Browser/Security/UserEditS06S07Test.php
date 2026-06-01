<?php

/**
 * Browser (Dusk) tests — S06 Consentimientos y S07 Documentos adjuntos
 *
 * Auditoría QA de las secciones S06 y S07 del formulario de edición de usuario.
 *
 * UC cubiertos:
 * - UC-S06-01: S06 carga sin errores JS (tab Documentos visible)
 * - UC-S06-02: S06 con consentimiento activo — sección arranca como complete
 * - UC-S06-03: S06 sin consentimiento — sección arranca como empty
 * - UC-S06-04: S06 checkboxes pre-llenados desde props.consent
 * - UC-S06-05: S06 save es stub — click guardar → sección marca complete
 * - UC-S06-06: S07 carga sin errores JS (lista vacía — no hay documentos)
 * - UC-S06-07: S07 save es stub — click guardar → sección marca complete
 *
 * Run with: vendor/bin/sail dusk tests/Browser/Security/UserEditS06S07Test.php
 *
 * Usa DatabaseMigrations — corre contra laravel_dusk (DB separada, ver .env.dusk.local).
 */

use App\Enums\EducationalLevel;
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
function adminForS06S07(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Crea un usuario objetivo con rol Admin y consentimiento activo.
 */
function targetAdminWithConsent(): User
{
    $target = User::factory()->create();
    $target->assignRole('Admin');

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
 * Crea un usuario objetivo con rol Admin sin consentimiento.
 */
function targetAdminWithoutConsent(): User
{
    $target = User::factory()->create();
    $target->assignRole('Admin');

    return $target;
}

/**
 * Crea un usuario objetivo con rol Estudiante, sub-registro y consentimiento parcial.
 * consent_image=false, consent_whatsapp=false — solo data y email activos.
 */
function targetStudentWithPartialConsent(): User
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
        'accepts_image_use' => false,
        'accepts_whatsapp_contact' => false,
        'accepts_email_contact' => true,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'dusk-test',
        'granted_at' => now(),
        'revoked_at' => null,
    ]);

    return $target;
}

/**
 * Navega al tab Documentos y espera que la sección 6 cargue.
 */
function navigateToDocsTab(Browser $browser, User $admin, User $target): Browser
{
    return $browser
        ->loginAs($admin)
        ->visit(route('security.users.edit', $target))
        ->waitFor('[dusk="tab-docs"]', 15)
        ->click('[dusk="tab-docs"]')
        ->waitFor('#sec-6', 10)
        ->assertDontSee('500')
        ->assertDontSee('Whoops');
}

// ---------------------------------------------------------------------------
// UC-S06-01 — S06 carga sin errores JS
// ---------------------------------------------------------------------------

test('UC-S06-01: S06 carga sin errores JS en el tab Documentos', function () {
    $admin = adminForS06S07();
    $target = targetAdminWithConsent();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDocsTab($browser, $admin, $target);

        $browser->assertVisible('#sec-6')
            ->assertDontSee('Whoops')
            ->assertDontSee('500');
    });
});

// ---------------------------------------------------------------------------
// UC-S06-02 — S06 con consentimiento activo arranca como complete
// ---------------------------------------------------------------------------

test('UC-S06-02: S06 con consentimiento activo arranca en estado complete', function () {
    $admin = adminForS06S07();
    $target = targetAdminWithConsent();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDocsTab($browser, $admin, $target);

        // Sección con status complete tiene clase CSS "complete"
        $browser->assertPresent('#sec-6.complete');
    });
});

// ---------------------------------------------------------------------------
// UC-S06-03 — S06 sin consentimiento arranca como empty
// ---------------------------------------------------------------------------

test('UC-S06-03: S06 sin consentimiento arranca en estado empty (no complete)', function () {
    $admin = adminForS06S07();
    $target = targetAdminWithoutConsent();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDocsTab($browser, $admin, $target);

        // Sin consentimiento no debe tener clase complete
        $browser->assertNotPresent('#sec-6.complete');
        $browser->assertPresent('#sec-6');
    });
});

// ---------------------------------------------------------------------------
// UC-S06-04 — S06 checkboxes pre-llenados desde props.consent
// ---------------------------------------------------------------------------

test('UC-S06-04: S06 checkboxes reflejan los valores de props.consent', function () {
    $admin = adminForS06S07();
    $target = targetStudentWithPartialConsent();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        // Student role uses the 'docs' tab key
        $browser->loginAs($admin)
            ->visit(route('security.users.edit', $target))
            ->waitFor('[dusk="tab-docs"]', 15)
            ->click('[dusk="tab-docs"]')
            ->waitFor('#sec-6', 10);

        // accepts_data_processing = true → checked
        $browser->assertChecked('[dusk="consent-consent_data"]');

        // accepts_image_use = false → not checked
        $browser->assertNotChecked('[dusk="consent-consent_image"]');

        // accepts_whatsapp_contact = false → not checked
        $browser->assertNotChecked('[dusk="consent-consent_whatsapp"]');

        // accepts_email_contact = true → checked
        $browser->assertChecked('[dusk="consent-consent_email"]');
    });
});

// ---------------------------------------------------------------------------
// UC-S06-05 — S06 save es stub → click guardar → sección marca complete
// ---------------------------------------------------------------------------

test('UC-S06-05: S06 save es stub — guardar marca la sección como complete', function () {
    $admin = adminForS06S07();
    $target = targetAdminWithoutConsent();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        navigateToDocsTab($browser, $admin, $target);

        // Sección inicia sin consentimiento → no complete
        $browser->assertNotPresent('#sec-6.complete');

        $browser->click('[dusk="save-section-6"]')
            ->waitForText('Guardado', 5);

        // Después del save stub, la sección debe marcarse complete
        $browser->assertPresent('#sec-6.complete');
    });
});

// ---------------------------------------------------------------------------
// UC-S06-06 — S07 carga sin errores JS (lista vacía)
// ---------------------------------------------------------------------------

test('UC-S06-06: S07 carga sin errores JS cuando la lista de documentos está vacía', function () {
    $admin = adminForS06S07();
    $target = targetAdminWithoutConsent(); // sin documentos adjuntos

    $this->browse(function (Browser $browser) use ($admin, $target) {
        $browser->loginAs($admin)
            ->visit(route('security.users.edit', $target))
            ->waitFor('[dusk="tab-docs"]', 15)
            ->click('[dusk="tab-docs"]')
            ->waitFor('#sec-7', 10)
            ->assertDontSee('500')
            ->assertDontSee('Whoops')
            ->assertVisible('#sec-7');
    });
});

// ---------------------------------------------------------------------------
// UC-S06-07 — S07 save es stub → click guardar → sección marca complete
// ---------------------------------------------------------------------------

test('UC-S06-07: S07 save es stub — guardar marca la sección como complete', function () {
    $admin = adminForS06S07();
    $target = targetAdminWithoutConsent();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        $browser->loginAs($admin)
            ->visit(route('security.users.edit', $target))
            ->waitFor('[dusk="tab-docs"]', 15)
            ->click('[dusk="tab-docs"]')
            ->waitFor('#sec-7', 10);

        // S07 sin documentos no inicia como complete
        $browser->assertNotPresent('#sec-7.complete');

        $browser->click('[dusk="save-section-7"]')
            ->waitForText('Guardado', 5);

        $browser->assertPresent('#sec-7.complete');
    });
});
