<?php

/**
 * Dusk — specs/user-form-views Task 22 (Polish y verificación visual), un usuario por rol.
 *
 * Cubre el checklist: tabs/secciones por rol, completion, scroll spy, coordinación (S17),
 * S16 vacío/con datos, pill "cambiar", botón "Crear usuario" (>=50%), autosave, hamburger mobile
 * y errores de consola del navegador.
 *
 * Los tests marcados "SPEC" assertan el comportamiento descrito en requirements.md/design.md; si
 * fallan es porque la implementación diverge del spec (ver HLZ-50 / HLZ-51 en specs/qa/backlog.md).
 *
 * Run with: vendor/bin/sail dusk tests/Browser/Security/UserFormTask22VisualVerificationTest.php
 */

use App\Models\Guardian;
use App\Models\GuardianProfile;
use App\Models\Professor;
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

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    $this->seed(CatalogsSeeder::class);
});

function t22Admin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

function t22Consent(User $user): void
{
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
}

/**
 * @return array<string, User>
 */
function t22Targets(): array
{
    return [
        'admin' => t22Admin(),
        'student' => Student::factory()->create()->user,
        'professor' => Professor::factory()->create()->user,
        'guardian' => Guardian::factory()->create()->user,
    ];
}

/**
 * Tabs esperadas por rol: key => [label, [sections]] (UF_TABS en userFormCatalogs.ts / tasks.md Task 22).
 *
 * @return array<string, array<string, array{0: string, 1: array<int>}>>
 */
function t22ExpectedTabs(): array
{
    $identity = ['identity' => ['Identidad', [1, 2, 3, 4]], 'health' => ['Salud', [5]]];
    $docs = ['docs' => ['Documentos', [7, 6]]];

    return [
        'admin' => $identity + $docs,
        'student' => $identity + [
            'academic' => ['Académico', [8, 9, 10]],
            'family' => ['Familia', [11, 15]],
            'socioecon' => ['Socioeconómico', [12, 13, 14]],
        ] + $docs,
        'professor' => $identity + ['job' => ['Profesional', [17]]] + $docs,
        'guardian' => $identity + ['guard' => ['Representante', [16]]] + $docs,
    ];
}

/**
 * Completa los campos que el endpoint de S17 exige (hire_date + catálogos) para que el guardado sea válido.
 */
function t22FillS17Required(Browser $browser): void
{
    $browser->script('
        var el = document.querySelector("[dusk=\'hire-date-input\']");
        el.value = "2020-01-15";
        el.dispatchEvent(new Event("input", { bubbles: true }));
    ');

    $browser->select('[dusk="contract-type-select"]', (string) \App\Models\Catalogs\ContractType::active()->first()->id)
        ->select('[dusk="dedication-type-select"]', (string) \App\Models\Catalogs\DedicationType::active()->first()->id)
        ->select('[dusk="employment-status-select"]', (string) \App\Models\Catalogs\EmploymentStatus::active()->first()->id);
}

function t22VisitEdit(Browser $browser, User $admin, User $target): Browser
{
    return $browser->resize(1440, 900)
        ->loginAs($admin)
        ->visit(route('security.users.edit', $target))
        ->waitFor('.uf-tabs', 15)
        ->assertDontSee('Whoops');
}

/**
 * @return array<int, string> mensajes SEVERE de la consola del navegador (sin favicon).
 */
function t22SevereConsole(Browser $browser): array
{
    $logs = $browser->driver->manage()->getLog('browser');

    return collect($logs)
        ->filter(fn (array $l) => ($l['level'] ?? '') === 'SEVERE' && ! str_contains($l['message'], 'favicon'))
        ->pluck('message')
        ->values()
        ->all();
}

// ---------------------------------------------------------------------------
// Tabs / secciones por rol (Admin 3, Estudiante 6, Profesor 4, Representante 4)
// ---------------------------------------------------------------------------

test('T22 tabs y secciones por rol: cada rol ve sus tabs y las secciones en el orden esperado', function () {
    $admin = t22Admin();
    $targets = t22Targets();

    foreach (t22ExpectedTabs() as $role => $tabs) {
        $this->browse(function (Browser $browser) use ($admin, $targets, $role, $tabs) {
            t22VisitEdit($browser, $admin, $targets[$role]);

            $keys = $browser->script('return Array.from(document.querySelectorAll(".uf-tab")).map(t => t.getAttribute("dusk"))')[0];
            expect($keys)->toBe(array_map(fn ($k) => 'tab-'.$k, array_keys($tabs)), "tabs del rol {$role}");

            foreach ($tabs as $key => [$label, $sections]) {
                $browser->click('[dusk="tab-'.$key.'"]')->pause(150);
                $browser->assertSeeIn('[dusk="tab-'.$key.'"]', $label);

                $ids = $browser->script('return Array.from(document.querySelectorAll(".uf-section")).map(s => parseInt(s.id.replace("sec-","")))')[0];
                expect($ids)->toBe($sections, "secciones del tab {$key} del rol {$role}");
            }
        });
    }
});

test('T22 admin: completion calcula correctamente (2/7 = 29%; con consentimiento 3/7 = 43%)', function () {
    $admin = t22Admin();
    $plain = User::factory()->create();
    $plain->assignRole('Admin');
    $withConsent = User::factory()->create();
    $withConsent->assignRole('Admin');
    t22Consent($withConsent);

    $this->browse(function (Browser $browser) use ($admin, $plain, $withConsent) {
        t22VisitEdit($browser, $admin, $plain)->waitFor('.uf-progress-pct');
        expect($browser->text('.uf-progress-pct'))->toBe('29%');

        t22VisitEdit($browser, $admin, $withConsent)->waitFor('.uf-progress-pct');
        expect($browser->text('.uf-progress-pct'))->toBe('43%');
    });
});

// ---------------------------------------------------------------------------
// Scroll spy por tab (estudiante: todos los tabs con >= 2 secciones)
// ---------------------------------------------------------------------------

test('T22 estudiante: scroll spy por tab (sección activa en el side-nav cambia al desplazarse)', function () {
    $admin = t22Admin();
    $student = Student::factory()->create()->user;

    $titles = [
        1 => 'Identidad personal', 2 => 'Credenciales', 3 => 'Dirección', 4 => 'Perfil demográfico',
        5 => 'Salud', 6 => 'Consentimientos', 7 => 'Documentos adjuntos',
        8 => 'Perfil académico', 9 => 'Antecedentes', 10 => 'Idiomas',
        11 => 'Perfil familiar', 15 => 'Representantes',
        12 => 'Perfil socioeconómico', 13 => 'Beneficios', 14 => 'Vivienda',
    ];

    $this->browse(function (Browser $browser) use ($admin, $student, $titles) {
        t22VisitEdit($browser, $admin, $student);
        $misses = [];

        foreach (t22ExpectedTabs()['student'] as $key => [$label, $sections]) {
            $browser->click('[dusk="tab-'.$key.'"]')->pause(400);

            foreach ($sections as $n) {
                $browser->script("document.getElementById('sec-{$n}').scrollIntoView({block:'start'})");
                $browser->pause(700);
                $active = $browser->script('return document.querySelector(".uf-side-link.active")?.textContent.trim() ?? null')[0];
                if ($active === null || ! str_contains($active, $titles[$n])) {
                    $misses[] = "tab {$key} sec-{$n}: activa=".json_encode($active);
                }
            }
        }

        expect($misses)->toBe([]);
    });
});

// ---------------------------------------------------------------------------
// Profesor: S17 + coordinación condicional
// ---------------------------------------------------------------------------

test('T22 profesor: S17 se renderiza y el toggle de coordinación revela/oculta departamento y fecha', function () {
    $admin = t22Admin();
    $professor = Professor::factory()->create()->user;

    $this->browse(function (Browser $browser) use ($admin, $professor) {
        t22VisitEdit($browser, $admin, $professor)
            ->click('[dusk="tab-job"]')
            ->waitFor('#sec-17', 10)
            ->assertSee('Es coordinador de departamento')
            ->assertDontSee('Coordinador desde');

        $browser->click('#sec-17 label.uf-toggle-card')->waitForText('Coordinador desde', 3);
        $browser->assertSee('Departamento');

        $browser->click('#sec-17 label.uf-toggle-card')->pause(400);
        $browser->assertDontSee('Coordinador desde');
    });
});

// ---------------------------------------------------------------------------
// Representante: S16 vacío y con datos
// ---------------------------------------------------------------------------

test('T22 representante: S16 vacío (campos sin valor) y con datos (pre-llenado)', function () {
    $admin = t22Admin();
    $empty = Guardian::factory()->create()->user;
    $guardian = Guardian::factory()->create();
    GuardianProfile::create([
        'guardian_id' => $guardian->id,
        'occupation' => 'Contadora',
        'employer' => 'Cacao C.A.',
    ]);

    $this->browse(function (Browser $browser) use ($admin, $empty, $guardian) {
        t22VisitEdit($browser, $admin, $empty)->click('[dusk="tab-guard"]')->waitFor('#sec-16', 10);
        $browser->waitFor('[dusk="occupation-input"]');
        expect($browser->value('[dusk="occupation-input"]'))->toBe('');
        expect($browser->value('[dusk="employer-input"]'))->toBe('');

        t22VisitEdit($browser, $admin, $guardian->user)->click('[dusk="tab-guard"]')->waitFor('#sec-16', 10);
        $browser->waitFor('[dusk="occupation-input"]');
        expect($browser->value('[dusk="occupation-input"]'))->toBe('Contadora');
        expect($browser->value('[dusk="employer-input"]'))->toBe('Cacao C.A.');
    });
});

// ---------------------------------------------------------------------------
// Pill "cambiar" (SPEC: vuelve al step 0 / selector de rol)
// ---------------------------------------------------------------------------

test('T22 SPEC pill "cambiar" en el header del formulario vuelve al step 0 (selector de rol)', function () {
    $admin = t22Admin();

    $this->browse(function (Browser $browser) use ($admin) {
        $browser->resize(1440, 900)
            ->loginAs($admin)
            ->visit(route('security.users.create'))
            ->waitForText('¿Qué tipo de usuario vas a crear?', 15)
            ->click('.uf-role-card:nth-child(3)')
            ->press('Continuar con Profesor')
            ->waitForText('Nuevo usuario', 5)
            ->assertSeeIn('.uf-role-pill', 'Profesor')
            ->assertPresent('.uf-mode-tag')
            ->click('.uf-role-pill-change')
            ->waitForText('¿Qué tipo de usuario vas a crear?', 5);
    });
});

test('T22 editar usuario: el pill del rol no ofrece "cambiar" (el rol de un usuario existente no se reemplaza desde el header)', function () {
    $admin = t22Admin();
    $professor = Professor::factory()->create()->user;

    $this->browse(function (Browser $browser) use ($admin, $professor) {
        t22VisitEdit($browser, $admin, $professor)
            ->assertSeeIn('.uf-role-pill', 'Profesor')
            ->assertMissing('.uf-role-pill-change');
    });
});

test('T22 crear usuario: "Cambiar rol" desde el formulario vuelve al selector de rol (step 0)', function () {
    $admin = t22Admin();

    $this->browse(function (Browser $browser) use ($admin) {
        $browser->resize(1440, 900)
            ->loginAs($admin)
            ->visit(route('security.users.create'))
            ->waitForText('¿Qué tipo de usuario vas a crear?', 15)
            ->click('.uf-role-card:nth-child(3)')
            ->press('Continuar con Profesor')
            ->waitForText('Nuevo usuario', 5)
            ->assertPresent('.uf-mode-tag')
            ->click('.uf-role-pill-change')
            ->waitForText('¿Qué tipo de usuario vas a crear?', 5);
    });
});

// ---------------------------------------------------------------------------
// Botón "Crear usuario" habilitado solo con >= 50% completitud (SPEC)
// ---------------------------------------------------------------------------

test('T22 SPEC "Crear usuario": deshabilitado bajo 50% de completitud, con indicador de progreso en el footer', function () {
    $admin = t22Admin();

    $this->browse(function (Browser $browser) use ($admin) {
        $browser->resize(1440, 900)
            ->loginAs($admin)
            ->visit(route('security.users.create'))
            ->waitForText('¿Qué tipo de usuario vas a crear?', 15)
            ->click('.uf-role-card:nth-child(3)')
            ->press('Continuar con Profesor')
            ->waitFor('[dusk="first-name-input"]', 5)
            ->type('[dusk="first-name-input"]', 'Ana')
            ->type('[dusk="last-name-input"]', 'Pérez')
            ->type('[dusk="email-input"]', 'ana.perez@example.test');

        // Solo 2 de 8 secciones tocadas (< 50%): el spec exige botón deshabilitado y % visible.
        $disabled = $browser->script('return Array.from(document.querySelectorAll("button.uf-btn.primary")).find(b => b.textContent.includes("Crear usuario")).disabled')[0];
        expect($disabled)->toBeTrue();
        $browser->assertVisible('.uf-footer-progress');
    });
});

// ---------------------------------------------------------------------------
// Autosave
// ---------------------------------------------------------------------------

test('T22 autosave (guardado explícito): "Guardando…" → "Guardado" en el header', function () {
    $admin = t22Admin();
    $professor = Professor::factory()->create()->user;
    t22Consent($professor);

    $this->browse(function (Browser $browser) use ($admin, $professor) {
        t22VisitEdit($browser, $admin, $professor)
            ->click('[dusk="tab-job"]')
            ->waitFor('[dusk="degree-input"]', 10)
            ->assertSeeIn('.uf-form-head .uf-autosave', 'Sin cambios')
            ->type('[dusk="degree-input"]', 'Lic. en Física');

        t22FillS17Required($browser);

        $browser->click('[dusk="save-section-17"]')
            ->waitFor('.uf-form-head .uf-autosave.saved', 8)
            ->assertSeeIn('.uf-form-head .uf-autosave', 'Guardado');
    });

    $this->assertDatabaseHas('staff_profiles', ['academic_title' => 'Lic. en Física']);
});

test('T22 SPEC autosave: al cambiar un campo (sin pulsar guardar) → "Guardando…" → "Guardado"', function () {
    $admin = t22Admin();
    $professor = Professor::factory()->create()->user;
    t22Consent($professor);

    $this->browse(function (Browser $browser) use ($admin, $professor) {
        t22VisitEdit($browser, $admin, $professor)
            ->click('[dusk="tab-job"]')
            ->waitFor('[dusk="degree-input"]', 10)
            ->type('[dusk="degree-input"]', 'Ing. Civil');

        $browser->waitFor('.uf-form-head .uf-autosave.saving, .uf-form-head .uf-autosave.saved', 3);
    });
});

test('T22 SPEC autosave: el indicador muestra "Guardado hace Xs"', function () {
    $admin = t22Admin();
    $professor = Professor::factory()->create()->user;
    t22Consent($professor);

    $this->browse(function (Browser $browser) use ($admin, $professor) {
        t22VisitEdit($browser, $admin, $professor)
            ->click('[dusk="tab-job"]')
            ->waitFor('[dusk="degree-input"]', 10)
            ->type('[dusk="degree-input"]', 'Ing. Civil');

        t22FillS17Required($browser);

        $browser->click('[dusk="save-section-17"]')
            ->waitFor('.uf-form-head .uf-autosave.saved', 8)
            ->assertSeeIn('.uf-form-head .uf-autosave', 'Guardado hace');
    });
});

// ---------------------------------------------------------------------------
// Mobile: topbar con hamburger < 768px
// ---------------------------------------------------------------------------

test('T22 mobile (375px): topbar con hamburger visible y el formulario carga sin overflow de errores', function () {
    $admin = t22Admin();
    $student = Student::factory()->create()->user;

    $this->browse(function (Browser $browser) use ($admin, $student) {
        $browser->resize(375, 812)
            ->loginAs($admin)
            ->visit(route('security.users.edit', $student))
            ->waitFor('.uf-tabs', 15)
            ->assertVisible('.topbar-menu-btn')
            ->assertDontSee('Whoops');
    });
});

test('T22 desktop (1440px): el hamburger no se muestra', function () {
    $admin = t22Admin();

    $this->browse(function (Browser $browser) use ($admin) {
        t22VisitEdit($browser, $admin, $admin)->assertMissing('.topbar-menu-btn');
    });
});

// ---------------------------------------------------------------------------
// Sin errores de consola, un usuario por rol
// ---------------------------------------------------------------------------

test('T22 sin errores SEVERE en consola del navegador al recorrer todos los tabs de cada rol', function () {
    $admin = t22Admin();
    $targets = t22Targets();
    $errors = [];

    foreach (t22ExpectedTabs() as $role => $tabs) {
        $this->browse(function (Browser $browser) use ($admin, $targets, $role, $tabs, &$errors) {
            t22VisitEdit($browser, $admin, $targets[$role]);

            foreach (array_keys($tabs) as $key) {
                $browser->click('[dusk="tab-'.$key.'"]')->pause(400);
            }

            foreach (t22SevereConsole($browser) as $msg) {
                $errors[] = "{$role}: {$msg}";
            }
        });
    }

    expect($errors)->toBe([]);
});
