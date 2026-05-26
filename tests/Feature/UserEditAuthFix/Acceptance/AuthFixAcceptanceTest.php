<?php

/**
 * Acceptance tests — user-edit-auth-fix
 *
 * Contract: an Admin user with ONLY the 'Admin' role (no 'Administrador' role)
 * must be able to save S10 (languages), S13 (benefits) and S16 (guardian profile).
 *
 * These tests are intentionally written in RED first (before the fix is applied).
 * They will remain in this file and must never be modified by the implementer.
 */

use App\Models\Catalogs\InstitutionalBenefit;
use App\Models\Catalogs\Language;
use App\Models\Catalogs\LanguageLevel;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\Catalogs\AcademicCatalogsSeeder;
use Database\Seeders\Catalogs\GeographicSeeder;
use Database\Seeders\Catalogs\SocialCatalogsSeeder;
use Database\Seeders\Catalogs\SocioeconomicCatalogsSeeder;
use Database\Seeders\Catalogs\StaffCatalogsSeeder;
use Database\Seeders\Catalogs\UserProfileCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'Admin',         'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Coordinador',   'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Representante', 'guard_name' => 'web']);

    $this->seed(GeographicSeeder::class);
    $this->seed(SocialCatalogsSeeder::class);
    $this->seed(SocioeconomicCatalogsSeeder::class);
    $this->seed(UserProfileCatalogsSeeder::class);
    $this->seed(AcademicCatalogsSeeder::class);
    $this->seed(StaffCatalogsSeeder::class);
});

/**
 * Creates an admin user with ONLY the 'Admin' role — no 'Administrador' alias.
 * This mirrors a real production admin account.
 */
function pureAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

// ---------------------------------------------------------------------------
// S10 — Idiomas
// ---------------------------------------------------------------------------

test('S10 acceptance: Admin with only Admin role can store a language (not 403)', function () {
    $admin = pureAdminUser();
    $student = Student::factory()->create();
    $lang = Language::first();
    $level = LanguageLevel::first();

    $this->actingAs($admin)
        ->postJson(route('security.students.languages.store', $student), [
            'language_id' => $lang->id,
            'language_level_id' => $level->id,
            'is_mother_tongue' => false,
        ])
        ->assertSuccessful(); // must NOT return 403

    expect(
        DB::table('student_languages')
            ->where('student_id', $student->id)
            ->where('language_id', $lang->id)
            ->exists()
    )->toBeTrue();
});

test('S10 acceptance: Admin with only Admin role can delete a language (not 403)', function () {
    $admin = pureAdminUser();
    $student = Student::factory()->create();
    $lang = Language::first();
    $level = LanguageLevel::first();

    // Seed the pivot row directly to avoid testing the store endpoint here
    DB::table('student_languages')->insert([
        'student_id' => $student->id,
        'language_id' => $lang->id,
        'language_level_id' => $level->id,
        'is_mother_tongue' => false,
    ]);

    $this->actingAs($admin)
        ->deleteJson(route('security.students.languages.destroy', [$student, $lang]))
        ->assertSuccessful(); // must NOT return 403

    expect(
        DB::table('student_languages')
            ->where('student_id', $student->id)
            ->where('language_id', $lang->id)
            ->exists()
    )->toBeFalse();
});

// ---------------------------------------------------------------------------
// S13 — Beneficios
// ---------------------------------------------------------------------------

test('S13 acceptance: Admin with only Admin role can attach a benefit (not 403)', function () {
    $admin = pureAdminUser();
    $student = Student::factory()->create();
    $benefit = InstitutionalBenefit::first();

    $this->actingAs($admin)
        ->postJson(route('security.students.benefits.store', [$student, $benefit]))
        ->assertSuccessful(); // must NOT return 403

    expect(
        DB::table('student_benefits')
            ->where('student_id', $student->id)
            ->where('benefit_id', $benefit->id)
            ->exists()
    )->toBeTrue();
});

test('S13 acceptance: Admin with only Admin role can detach a benefit (not 403)', function () {
    $admin = pureAdminUser();
    $student = Student::factory()->create();
    $benefit = InstitutionalBenefit::first();

    // Seed the pivot row directly
    DB::table('student_benefits')->insert([
        'student_id' => $student->id,
        'benefit_id' => $benefit->id,
    ]);

    $this->actingAs($admin)
        ->deleteJson(route('security.students.benefits.destroy', [$student, $benefit]))
        ->assertSuccessful(); // must NOT return 403

    expect(
        DB::table('student_benefits')
            ->where('student_id', $student->id)
            ->where('benefit_id', $benefit->id)
            ->exists()
    )->toBeFalse();
});

// ---------------------------------------------------------------------------
// S16 — Perfil de representante
// ---------------------------------------------------------------------------

test('S16 acceptance: Admin with only Admin role can save guardian profile (not 403)', function () {
    $admin = pureAdminUser();
    $guardian = Guardian::factory()->create();

    $this->actingAs($admin)
        ->putJson(route('security.guardians.profile.upsert', $guardian), [
            'occupation' => 'Ingeniero Civil',
        ])
        ->assertSuccessful(); // must NOT return 403
});

test('S16 acceptance: Coordinador can still save guardian profile (not 403)', function () {
    $coordinator = User::factory()->create();
    $coordinator->assignRole('Coordinador');
    $guardian = Guardian::factory()->create();

    $this->actingAs($coordinator)
        ->putJson(route('security.guardians.profile.upsert', $guardian), [
            'occupation' => 'Médico',
        ])
        ->assertSuccessful(); // must NOT return 403
});

test('S16 acceptance: a guardian can save their own profile (not 403)', function () {
    $guardian = Guardian::factory()->create();
    $guardianUser = $guardian->user;

    // Guardian needs the Representante role so Fortify doesn't redirect on login
    Role::firstOrCreate(['name' => 'Representante', 'guard_name' => 'web']);
    $guardianUser->assignRole('Representante');

    $this->actingAs($guardianUser)
        ->putJson(route('security.guardians.profile.upsert', $guardian), [
            'occupation' => 'Abogado',
        ])
        ->assertSuccessful(); // guardian editing own profile must NOT return 403
});
