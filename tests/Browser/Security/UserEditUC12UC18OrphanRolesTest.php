<?php

/**
 * Browser (Dusk) tests — UC-12 y UC-18: roles sin sub-registro en DB
 *
 * UC cubiertos:
 * - UC-12-01: Página carga sin error JS cuando rol=guardian y NO hay fila en guardians
 * - UC-12-02: Tab "Representante" es visible cuando rol=guardian sin sub-registro
 * - UC-12-03: Guardar S16 sin sub-registro guardian → no hay crash (no 500, no Whoops)
 * - UC-12-04: Guardar S16 sin sub-registro guardian → muestra "Guardado" (early return silencioso)
 * - UC-12-05: Guardar S16 sin sub-registro guardian → NO crea fila en guardian_profiles
 * - UC-18-01: Página carga sin error JS cuando rol=professor y NO hay fila en professors
 * - UC-18-02: Tab "Profesional" es visible cuando rol=professor sin sub-registro
 * - UC-18-03: Guardar S17 sin sub-registro professor → no hay crash (no 500, no Whoops)
 * - UC-18-04: Guardar S17 sin sub-registro professor → muestra "Guardado" (early return silencioso)
 * - UC-18-05: Guardar S17 sin sub-registro professor → NO crea fila en staff_profiles
 *
 * Run with: vendor/bin/sail dusk tests/Browser/Security/UserEditUC12UC18OrphanRolesTest.php
 *
 * Usa DatabaseMigrations — corre contra laravel_dusk (DB separada, ver .env.dusk.local).
 */

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
function adminForOrphanTest(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Crea un usuario con rol "Representante" pero SIN fila en tabla guardians.
 * Esto simula el estado de "usuario huérfano" de UC-12.
 */
function guardianUserWithoutSubRecord(): User
{
    $user = User::factory()->create();
    $user->assignRole('Representante');

    UserConsent::create([
        'user_id' => $user->id,
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

    return $user;
}

/**
 * Crea un usuario con rol "Profesor" pero SIN fila en tabla professors.
 * Esto simula el estado de "usuario huérfano" de UC-18.
 */
function professorUserWithoutSubRecord(): User
{
    $user = User::factory()->create();
    $user->assignRole('Profesor');

    UserConsent::create([
        'user_id' => $user->id,
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

    return $user;
}

/**
 * Navega a la página de edición del usuario objetivo y espera que cargue.
 */
function visitEditPage(Browser $browser, User $admin, User $target): Browser
{
    return $browser
        ->loginAs($admin)
        ->visit(route('security.users.edit', $target))
        ->waitFor('.uf-sections', 15)
        ->assertDontSee('500')
        ->assertDontSee('Whoops');
}

// ---------------------------------------------------------------------------
// UC-12 — guardian SIN sub-registro en DB
// ---------------------------------------------------------------------------

test('UC-12-01: página carga sin error JS cuando rol=guardian y NO hay fila en guardians', function () {
    $admin = adminForOrphanTest();
    $target = guardianUserWithoutSubRecord();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        visitEditPage($browser, $admin, $target);

        // La página debe cargar sin 500 ni Whoops
        $browser->assertDontSee('500')
            ->assertDontSee('Whoops')
            ->assertDontSee('Server Error');
    });
});

test('UC-12-02: tab Representante es visible cuando rol=guardian sin sub-registro', function () {
    $admin = adminForOrphanTest();
    $target = guardianUserWithoutSubRecord();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        visitEditPage($browser, $admin, $target);

        // El tab "Representante" debe ser visible (UF_TABS['guardian'] lo incluye siempre)
        $browser->assertVisible('[dusk="tab-guard"]');
    });
});

test('UC-12-03: guardar S16 sin sub-registro guardian no produce crash (no 500, no Whoops)', function () {
    $admin = adminForOrphanTest();
    $target = guardianUserWithoutSubRecord();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        visitEditPage($browser, $admin, $target);

        $browser->click('[dusk="tab-guard"]')
            ->waitFor('#sec-16', 10)
            ->click('[dusk="save-section-16"]')
            ->pause(2000);

        $browser->assertDontSee('500')
            ->assertDontSee('Whoops')
            ->assertDontSee('Server Error');
    });
});

test('UC-12-04: guardar S16 sin sub-registro guardian muestra "Guardado" (early return silencioso)', function () {
    $admin = adminForOrphanTest();
    $target = guardianUserWithoutSubRecord();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        visitEditPage($browser, $admin, $target);

        $browser->click('[dusk="tab-guard"]')
            ->waitFor('#sec-16', 10)
            ->click('[dusk="save-section-16"]')
            ->waitForText('Guardado', 5);

        // El frontend debe mostrar "Guardado" — el early return devuelve Promise.resolve()
        // y saveSection() lo trata como éxito, marcando autosave.status='saved'
        $browser->assertSee('Guardado');
    });
});

test('UC-12-05: guardar S16 sin sub-registro guardian NO crea fila en guardian_profiles', function () {
    $admin = adminForOrphanTest();
    $target = guardianUserWithoutSubRecord();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        visitEditPage($browser, $admin, $target);

        $browser->click('[dusk="tab-guard"]')
            ->waitFor('#sec-16', 10)
            ->click('[dusk="save-section-16"]')
            ->waitForText('Guardado', 5);
    });

    // No se debe haber creado ninguna fila en guardian_profiles porque
    // saveGuardianProfile() hizo early return sin llamar al backend
    $this->assertDatabaseCount('guardian_profiles', 0);
    $this->assertDatabaseCount('guardians', 0);
});

// ---------------------------------------------------------------------------
// UC-18 — professor SIN sub-registro en DB
// ---------------------------------------------------------------------------

test('UC-18-01: página carga sin error JS cuando rol=professor y NO hay fila en professors', function () {
    $admin = adminForOrphanTest();
    $target = professorUserWithoutSubRecord();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        visitEditPage($browser, $admin, $target);

        // La página debe cargar sin 500 ni Whoops
        $browser->assertDontSee('500')
            ->assertDontSee('Whoops')
            ->assertDontSee('Server Error');
    });
});

test('UC-18-02: tab Profesional es visible cuando rol=professor sin sub-registro', function () {
    $admin = adminForOrphanTest();
    $target = professorUserWithoutSubRecord();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        visitEditPage($browser, $admin, $target);

        // El tab "Profesional" debe ser visible (UF_TABS['professor'] lo incluye siempre)
        $browser->assertVisible('[dusk="tab-job"]');
    });
});

test('UC-18-03: guardar S17 sin sub-registro professor no produce crash (no 500, no Whoops)', function () {
    $admin = adminForOrphanTest();
    $target = professorUserWithoutSubRecord();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        visitEditPage($browser, $admin, $target);

        $browser->click('[dusk="tab-job"]')
            ->waitFor('#sec-17', 10)
            ->scrollIntoView('[dusk="save-section-17"]')
            ->pause(300)
            ->click('[dusk="save-section-17"]')
            ->pause(2000);

        $browser->assertDontSee('500')
            ->assertDontSee('Whoops')
            ->assertDontSee('Server Error');
    });
});

test('UC-18-04: guardar S17 sin sub-registro professor muestra "Guardado" (early return silencioso)', function () {
    $admin = adminForOrphanTest();
    $target = professorUserWithoutSubRecord();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        visitEditPage($browser, $admin, $target);

        $browser->click('[dusk="tab-job"]')
            ->waitFor('#sec-17', 10)
            ->click('[dusk="save-section-17"]')
            ->waitForText('Guardado', 5);

        // El frontend debe mostrar "Guardado" — el early return devuelve Promise.resolve()
        // y saveSection() lo trata como éxito, marcando autosave.status='saved'
        $browser->assertSee('Guardado');
    });
});

test('UC-18-05: guardar S17 sin sub-registro professor NO crea fila en staff_profiles', function () {
    $admin = adminForOrphanTest();
    $target = professorUserWithoutSubRecord();

    $this->browse(function (Browser $browser) use ($admin, $target) {
        visitEditPage($browser, $admin, $target);

        $browser->click('[dusk="tab-job"]')
            ->waitFor('#sec-17', 10)
            ->click('[dusk="save-section-17"]')
            ->waitForText('Guardado', 5);
    });

    // No se debe haber creado ninguna fila en staff_profiles porque
    // saveStaffProfile() hizo early return sin llamar al backend
    $this->assertDatabaseCount('staff_profiles', 0);
    $this->assertDatabaseCount('professors', 0);
});
