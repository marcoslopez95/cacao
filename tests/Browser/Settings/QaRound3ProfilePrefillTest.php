<?php

/**
 * Dusk acceptance tests — 22-qa-round3-followup-fixes (HLZ-47)
 *
 * Written in tester `pre` mode: the fix is not implemented yet, all tests below MUST fail (RED)
 * against current code — today `Profile.vue` reads `usePage().props.auth.user` inside the page
 * body (in violation of the project convention documented in CLAUDE.md) and binds it with
 * `:default-value` inside an Inertia `<Form>`; reproduced live, both inputs render empty. No
 * `dusk="..."` attributes were added to Profile.vue — the existing `#name`/`#email` ids and the
 * already-shipped `.sp-verif.ok` class are used instead.
 *
 * Covers specs/22-qa-round3-followup-fixes/qa.md UC-QA-03 across all four roles and both
 * desktop/mobile viewports, including the "Correo verificado." badge regression guard
 * (requirements.md, "Casos de error / comportamiento esperado", added 2026-09-13 after the tester
 * blocked in `pre` mode on the missing `email_verified_at` field — see design.md).
 */

use App\Models\Guardian;
use App\Models\Professor;
use App\Models\Student;
use App\Models\User;
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
});

/**
 * Shared assertion: the profile inputs show the user's real data and the verified badge is intact.
 */
function assertQaRound3ProfilePrefilled(Browser $browser, User $user): void
{
    $browser->waitFor('#name', 10);

    expect($browser->value('#name'))->toBe($user->name);
    expect($browser->value('#email'))->toBe($user->email);

    $browser->assertVisible('.sp-verif.ok')
        ->assertSeeIn('.sp-verif.ok', 'Correo verificado.');
}

// ---------------------------------------------------------------------------
// UC-QA-03 — every role sees their own real name/email prefilled (desktop)
// ---------------------------------------------------------------------------

test('admin sees their real name and email prefilled on /settings/profile, verified badge intact (UC-QA-03, desktop)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->browse(function (Browser $browser) use ($admin) {
        $browser->resize(1440, 900)
            ->loginAs($admin)
            ->visit('/settings/profile');

        assertQaRound3ProfilePrefilled($browser, $admin);
    });
});

test('professor sees their real name and email prefilled on /settings/profile, verified badge intact (UC-QA-03, desktop)', function () {
    $professor = Professor::factory()->create();

    $this->browse(function (Browser $browser) use ($professor) {
        $browser->resize(1440, 900)
            ->loginAs($professor->user)
            ->visit('/settings/profile');

        assertQaRound3ProfilePrefilled($browser, $professor->user);
    });
});

test('student sees their real name and email prefilled on /settings/profile, verified badge intact (UC-QA-03, desktop)', function () {
    $student = Student::factory()->create();

    $this->browse(function (Browser $browser) use ($student) {
        $browser->resize(1440, 900)
            ->loginAs($student->user)
            ->visit('/settings/profile');

        assertQaRound3ProfilePrefilled($browser, $student->user);
    });
});

test('guardian sees their real name and email prefilled on /settings/profile, verified badge intact (UC-QA-03, desktop)', function () {
    $guardian = Guardian::factory()->create();

    $this->browse(function (Browser $browser) use ($guardian) {
        $browser->resize(1440, 900)
            ->loginAs($guardian->user)
            ->visit('/settings/profile');

        assertQaRound3ProfilePrefilled($browser, $guardian->user);
    });
});

// ---------------------------------------------------------------------------
// UC-QA-03 — mobile viewport (regression across breakpoints)
// ---------------------------------------------------------------------------

test('student sees their real name and email prefilled on /settings/profile on a mobile viewport (UC-QA-03, mobile)', function () {
    $student = Student::factory()->create();

    $this->browse(function (Browser $browser) use ($student) {
        $browser->resize(375, 812)
            ->loginAs($student->user)
            ->visit('/settings/profile');

        assertQaRound3ProfilePrefilled($browser, $student->user);
    });
});
