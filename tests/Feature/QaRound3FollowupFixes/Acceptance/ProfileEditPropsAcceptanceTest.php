<?php

/**
 * Acceptance tests — 22-qa-round3-followup-fixes (HLZ-47)
 *
 * These tests define the behavioural contract for RF-03/RF-04 of this feature.
 * They MUST be RED before implementation and GREEN after.
 * The implementer MUST NOT modify this file.
 *
 * Contract:
 * RF-03 — GET /settings/profile returns `user` (name, email) as an explicit, typed prop from
 *         ProfileController::edit(), not as a dependency on the auth.user shared Inertia prop.
 * RF-04 — the `user` prop includes `email_verified_at` so the "Correo verificado." badge in
 *         Profile.vue does not silently disappear (design.md note, 2026-09-13 — this field was
 *         added to ProfileUserResource after the tester blocked in `pre` mode on this exact gap;
 *         see requirements.md "Casos de error / comportamiento esperado").
 *
 * Covers specs/22-qa-round3-followup-fixes/qa.md UC-QA-03 (backend half, across all four roles).
 * The rendered-input half (actual browser values, desktop/mobile, visible verified badge) is
 * covered by tests/Browser/Settings/QaRound3ProfilePrefillTest.php.
 */

use App\Models\Guardian;
use App\Models\Professor;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    // Professor/Student/Guardian factories create their own role via firstOrCreate() in their
    // respective configure() hooks; a plain admin User needs the role created explicitly here.
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
});

test('RF-03/RF-04: admin sees their own real name and email as an explicit `user` prop on /settings/profile', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get('/settings/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Profile')
            ->where('user.id', $admin->id)
            ->where('user.name', $admin->name)
            ->where('user.email', $admin->email)
        );
});

test('RF-03/RF-04: professor sees their own real name and email as an explicit `user` prop on /settings/profile', function () {
    $professor = Professor::factory()->create();

    $this->actingAs($professor->user)
        ->get('/settings/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('user.id', $professor->user->id)
            ->where('user.name', $professor->user->name)
            ->where('user.email', $professor->user->email)
        );
});

test('RF-03/RF-04: student sees their own real name and email as an explicit `user` prop on /settings/profile', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get('/settings/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('user.id', $student->user->id)
            ->where('user.name', $student->user->name)
            ->where('user.email', $student->user->email)
        );
});

test('RF-03/RF-04: guardian sees their own real name and email as an explicit `user` prop on /settings/profile', function () {
    $guardian = Guardian::factory()->create();

    $this->actingAs($guardian->user)
        ->get('/settings/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('user.id', $guardian->user->id)
            ->where('user.name', $guardian->user->name)
            ->where('user.email', $guardian->user->email)
        );
});

test('RF-04: `email_verified_at` is included in the `user` prop so the "Correo verificado" badge does not silently disappear', function () {
    $admin = User::factory()->create(); // factory default: email_verified_at = now()
    $admin->assignRole('Admin');

    expect($admin->email_verified_at)->not->toBeNull(); // sanity check on the fixture itself

    $this->actingAs($admin)
        ->get('/settings/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('user.email_verified_at')
            ->where('user.email_verified_at', fn ($value) => $value !== null)
        );
});
