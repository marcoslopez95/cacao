<?php

use App\Models\SocioeconomicProfile;
use App\Models\Student;
use App\Models\User;
use App\Models\UserConsent;
use Database\Seeders\Catalogs\GeographicSeeder;
use Database\Seeders\Catalogs\SocioeconomicCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    (new SocioeconomicCatalogsSeeder)->run();
    (new GeographicSeeder)->run();
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Coordinador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Estudiante', 'guard_name' => 'web']);
});

/**
 * Returns an admin User with the Administrador role.
 */
function adminForSocio(): User
{
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    return $user;
}

/**
 * Returns a coordinator User with the Coordinador role.
 */
function coordinatorForSocio(): User
{
    $user = User::factory()->create();
    $user->assignRole('Coordinador');

    return $user;
}

/**
 * Returns a Student (with Estudiante role on linked User).
 */
function studentForSocio(): Student
{
    return Student::factory()->create();
}

/**
 * Creates an active consent record for the given user.
 */
function createActiveConsentForSocio(User $user): UserConsent
{
    return UserConsent::create([
        'user_id' => $user->id,
        'policy_version' => 'v1.0',
        'accepts_data_processing' => true,
        'accepts_image_use' => true,
        'accepts_whatsapp_contact' => true,
        'accepts_email_contact' => true,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'granted_at' => now(),
        'revoked_at' => null,
    ]);
}

/**
 * Valid socioeconomic profile payload.
 *
 * @return array<string, mixed>
 */
function validSocioPayload(): array
{
    return [
        'study_date' => '2024-01-15',
        'household_earners' => 2,
        'receives_remittances' => false,
    ];
}

test('admin can upsert socioeconomic profile', function () {
    $admin = adminForSocio();
    $student = studentForSocio();
    createActiveConsentForSocio($student->user);

    $response = $this->actingAs($admin)->putJson(
        route('security.students.socioeconomic-profile.upsert', $student),
        validSocioPayload()
    );

    $response->assertSuccessful();
    expect(SocioeconomicProfile::where('student_id', $student->id)->exists())->toBeTrue();
});

test('coordinator can upsert socioeconomic profile', function () {
    $coordinator = coordinatorForSocio();
    $student = studentForSocio();
    createActiveConsentForSocio($student->user);

    $response = $this->actingAs($coordinator)->putJson(
        route('security.students.socioeconomic-profile.upsert', $student),
        validSocioPayload()
    );

    $response->assertSuccessful();
    expect(SocioeconomicProfile::where('student_id', $student->id)->exists())->toBeTrue();
});

test('student gets 403 on socioeconomic profile', function () {
    $student = studentForSocio();
    createActiveConsentForSocio($student->user);

    $response = $this->actingAs($student->user)->putJson(
        route('security.students.socioeconomic-profile.upsert', $student),
        validSocioPayload()
    );

    $response->assertForbidden();
});

test('no active consent returns 422', function () {
    $admin = adminForSocio();
    $student = studentForSocio();

    // No consent created for student

    $response = $this->actingAs($admin)->putJson(
        route('security.students.socioeconomic-profile.upsert', $student),
        validSocioPayload()
    );

    $response->assertUnprocessable();
});

test('adding consent then upserting succeeds', function () {
    $admin = adminForSocio();
    $student = studentForSocio();

    createActiveConsentForSocio($student->user);

    $response = $this->actingAs($admin)->putJson(
        route('security.students.socioeconomic-profile.upsert', $student),
        validSocioPayload()
    );

    $response->assertSuccessful();
    expect(SocioeconomicProfile::where('student_id', $student->id)->exists())->toBeTrue();
});
