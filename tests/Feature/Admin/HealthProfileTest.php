<?php

use App\Models\HealthProfile;
use App\Models\Student;
use App\Models\User;
use App\Models\UserConsent;
use Database\Seeders\Catalogs\SocioeconomicCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    (new SocioeconomicCatalogsSeeder)->run();
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Coordinador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Estudiante', 'guard_name' => 'web']);
});

/**
 * Returns an admin User with the Administrador role.
 */
function adminForHealth(): User
{
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    return $user;
}

/**
 * Returns a coordinator User with the Coordinador role.
 */
function coordinatorForHealth(): User
{
    $user = User::factory()->create();
    $user->assignRole('Coordinador');

    return $user;
}

/**
 * Returns a Student (with Estudiante role on linked User).
 */
function studentForHealth(): Student
{
    return Student::factory()->create();
}

/**
 * Creates an active consent record for the given user.
 */
function createActiveConsentForHealth(User $user): UserConsent
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
 * Valid health profile payload.
 *
 * @return array<string, mixed>
 */
function validHealthPayload(): array
{
    return [
        'weight_kg' => 65.5,
        'height_cm' => 170.0,
        'has_disability' => false,
    ];
}

test('admin can upsert health profile', function () {
    $admin = adminForHealth();
    $targetUser = User::factory()->create();
    createActiveConsentForHealth($targetUser);

    $response = $this->actingAs($admin)->putJson(
        route('security.users.health-profile.upsert', $targetUser),
        validHealthPayload()
    );

    $response->assertSuccessful();
    expect(HealthProfile::where('user_id', $targetUser->id)->exists())->toBeTrue();
});

test('coordinator gets 403 on health profile', function () {
    $coordinator = coordinatorForHealth();
    $targetUser = User::factory()->create();
    createActiveConsentForHealth($targetUser);

    $response = $this->actingAs($coordinator)->putJson(
        route('security.users.health-profile.upsert', $targetUser),
        validHealthPayload()
    );

    $response->assertForbidden();
});

test('student gets 403 on health profile', function () {
    $student = studentForHealth();
    createActiveConsentForHealth($student->user);

    $response = $this->actingAs($student->user)->putJson(
        route('security.users.health-profile.upsert', $student->user),
        validHealthPayload()
    );

    $response->assertForbidden();
});

test('no active consent returns 422', function () {
    $admin = adminForHealth();
    $targetUser = User::factory()->create();

    // No consent created

    $response = $this->actingAs($admin)->putJson(
        route('security.users.health-profile.upsert', $targetUser),
        validHealthPayload()
    );

    $response->assertUnprocessable();
});
