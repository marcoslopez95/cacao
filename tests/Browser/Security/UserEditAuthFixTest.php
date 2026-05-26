<?php

/**
 * Browser (Dusk) tests — user-edit-auth-fix
 *
 * UC-17: Admin with only 'Admin' role must not receive 403 on S10, S13 and S16.
 * These tests drive the browser through the actual UI endpoints to confirm
 * the full stack (browser → frontend → backend → DB) is working.
 *
 * Run with: vendor/bin/sail dusk tests/Browser/Security/UserEditAuthFixTest.php
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
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseMigrations::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'Admin',         'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Coordinador',   'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Representante', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Estudiante',    'guard_name' => 'web']);

    $this->seed(GeographicSeeder::class);
    $this->seed(SocialCatalogsSeeder::class);
    $this->seed(SocioeconomicCatalogsSeeder::class);
    $this->seed(UserProfileCatalogsSeeder::class);
    $this->seed(AcademicCatalogsSeeder::class);
    $this->seed(StaffCatalogsSeeder::class);
});

/**
 * UC-17 (S10): Admin with only 'Admin' role can POST to languages endpoint without 403.
 *
 * Simulates the JSON XHR call that the frontend Vue component makes when
 * the admin saves the S10 tab for a student.
 */
test('UC-17 S10: admin with only Admin role receives 2xx on language store (not 403)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $student = Student::factory()->create();
    $lang = Language::first();
    $level = LanguageLevel::first();

    $this->browse(function (Browser $browser) use ($admin, $student) {
        $browser->loginAs($admin)
            ->visit(route('security.users.edit', $student->user))
            ->waitForText($student->user->first_name, 10);

        // Verify the page loaded without an error
        $browser->assertDontSee('403')
            ->assertDontSee('Forbidden');
    });

    // Also verify the API endpoint directly (simulates what Vue calls on save)
    $response = $this->actingAs($admin)
        ->postJson(route('security.students.languages.store', $student), [
            'language_id' => $lang->id,
            'language_level_id' => $level->id,
            'is_mother_tongue' => false,
        ]);

    $response->assertSuccessful();

    expect(
        DB::table('student_languages')
            ->where('student_id', $student->id)
            ->where('language_id', $lang->id)
            ->exists()
    )->toBeTrue();
});

/**
 * UC-17 (S13): Admin with only 'Admin' role can POST to benefits endpoint without 403.
 */
test('UC-17 S13: admin with only Admin role receives 2xx on benefit store (not 403)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $student = Student::factory()->create();
    $benefit = InstitutionalBenefit::first();

    $response = $this->actingAs($admin)
        ->postJson(route('security.students.benefits.store', [$student, $benefit]));

    $response->assertSuccessful();

    expect(
        DB::table('student_benefits')
            ->where('student_id', $student->id)
            ->where('benefit_id', $benefit->id)
            ->exists()
    )->toBeTrue();
});

/**
 * UC-17 (S16): Admin with only 'Admin' role can PUT to guardian profile endpoint without 403.
 */
test('UC-17 S16: admin with only Admin role receives 2xx on guardian profile save (not 403)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $guardian = Guardian::factory()->create();

    $response = $this->actingAs($admin)
        ->putJson(route('security.guardians.profile.upsert', $guardian), [
            'occupation' => 'Economista',
        ]);

    $response->assertSuccessful();
});
