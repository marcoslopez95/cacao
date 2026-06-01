<?php

/**
 * Acceptance tests — HLZ-29 Socioeconomic Fix
 *
 * These tests define the behavioural contract for the fix.
 * Contract:
 * RF-01 — PUT without study_date → HTTP 200 (no 500 error).
 * RF-02 — PUT without study_date → DB stores study_date = today().
 * RF-03 — PUT with a valid study_date → DB stores that exact value.
 * RF-04 — PUT with an invalid study_date (non-date string) → HTTP 422.
 */

use App\Models\Student;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'Admin',         'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Estudiante',    'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Profesor',      'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Representante', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Coordinador',   'guard_name' => 'web']);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Returns an Admin user. Gate::before short-circuits all authorization checks.
 */
function adminForSocioFix(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Creates an active consent record for the given user.
 * Required by UpsertSocioeconomicProfileAction (ConsentService::requireConsent).
 */
function consentForSocioFix(User $user): UserConsent
{
    return UserConsent::create([
        'user_id' => $user->id,
        'policy_version' => 'v1.0',
        'accepts_data_processing' => true,
        'accepts_image_use' => true,
        'accepts_whatsapp_contact' => true,
        'accepts_email_contact' => true,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test-agent',
        'granted_at' => now(),
        'revoked_at' => null,
    ]);
}

// ---------------------------------------------------------------------------
// RF-01 — PUT sin study_date → HTTP 200 (no 500)
// ---------------------------------------------------------------------------

it('RF-01: PUT socioeconomic-profile without study_date returns HTTP 200', function () {
    $admin = adminForSocioFix();
    $student = Student::factory()->create();
    consentForSocioFix($student->user);

    $this->actingAs($admin)
        ->putJson(route('security.students.socioeconomic-profile.upsert', $student), [
            'household_earners' => 3,
            // study_date deliberately omitted — this triggered the 500 before the fix
        ])
        ->assertSuccessful();
});

// ---------------------------------------------------------------------------
// RF-02 — PUT sin study_date → DB almacena study_date = today()
// ---------------------------------------------------------------------------

it('RF-02: PUT socioeconomic-profile without study_date stores today as study_date in DB', function () {
    $admin = adminForSocioFix();
    $student = Student::factory()->create();
    consentForSocioFix($student->user);

    $this->actingAs($admin)
        ->putJson(route('security.students.socioeconomic-profile.upsert', $student), [
            'household_earners' => 2,
            // study_date deliberately omitted
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('socioeconomic_profiles', [
        'student_id' => $student->id,
        'study_date' => now()->toDateString(),
    ]);
});

// ---------------------------------------------------------------------------
// RF-03 — PUT con study_date válido → DB almacena ese valor exacto
// ---------------------------------------------------------------------------

it('RF-03: PUT socioeconomic-profile with a valid study_date stores that exact date in DB', function () {
    $admin = adminForSocioFix();
    $student = Student::factory()->create();
    consentForSocioFix($student->user);

    $date = '2025-03-15';

    $this->actingAs($admin)
        ->putJson(route('security.students.socioeconomic-profile.upsert', $student), [
            'study_date' => $date,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('socioeconomic_profiles', [
        'student_id' => $student->id,
        'study_date' => $date,
    ]);
});

// ---------------------------------------------------------------------------
// RF-04 — PUT con study_date inválido → HTTP 422
// ---------------------------------------------------------------------------

it('RF-04: PUT socioeconomic-profile with an invalid study_date returns HTTP 422', function () {
    $admin = adminForSocioFix();
    $student = Student::factory()->create();
    consentForSocioFix($student->user);

    $this->actingAs($admin)
        ->putJson(route('security.students.socioeconomic-profile.upsert', $student), [
            'study_date' => 'not-a-date',
        ])
        ->assertUnprocessable();
});
