<?php

use App\Models\Catalogs\BasicService;
use App\Models\HousingProfile;
use App\Models\Student;
use App\Models\User;
use App\Models\UserConsent;
use Database\Seeders\Catalogs\SocioeconomicCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    (new SocioeconomicCatalogsSeeder)->run();
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
});

/**
 * Returns an admin User with the Administrador role.
 */
function adminForHousing(): User
{
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    return $user;
}

/**
 * Creates an active consent record for the given user.
 */
function createActiveConsentForHousing(User $user): UserConsent
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

test('admin can upsert housing profile', function () {
    $admin = adminForHousing();
    $student = Student::factory()->create();
    createActiveConsentForHousing($student->user);

    $response = $this->actingAs($admin)->putJson(
        route('security.students.housing-profile.upsert', $student),
        [
            'room_count' => 2,
            'household_members' => 3,
        ]
    );

    $response->assertSuccessful();
    expect(HousingProfile::where('student_id', $student->id)->exists())->toBeTrue();
});

test('is_overcrowded computed automatically: 4 members in 1 room = overcrowded', function () {
    $admin = adminForHousing();
    $student = Student::factory()->create();
    createActiveConsentForHousing($student->user);

    $this->actingAs($admin)->putJson(
        route('security.students.housing-profile.upsert', $student),
        [
            'room_count' => 1,
            'household_members' => 4,
        ]
    );

    $profile = HousingProfile::where('student_id', $student->id)->first();
    expect($profile->is_overcrowded)->toBeTrue();
});

test('not overcrowded: 2 members in 2 rooms', function () {
    $admin = adminForHousing();
    $student = Student::factory()->create();
    createActiveConsentForHousing($student->user);

    $this->actingAs($admin)->putJson(
        route('security.students.housing-profile.upsert', $student),
        [
            'room_count' => 2,
            'household_members' => 2,
        ]
    );

    $profile = HousingProfile::where('student_id', $student->id)->first();
    expect($profile->is_overcrowded)->toBeFalse();
});

test('sync services sets is_available pivot', function () {
    $admin = adminForHousing();
    $student = Student::factory()->create();
    createActiveConsentForHousing($student->user);

    // First upsert profile so it exists
    $this->actingAs($admin)->putJson(
        route('security.students.housing-profile.upsert', $student),
        ['room_count' => 2, 'household_members' => 3]
    );

    $service = BasicService::first();

    $response = $this->actingAs($admin)->patchJson(
        route('security.students.housing-profile.services.sync', $student),
        [
            'services' => [
                ['basic_service_id' => $service->id, 'is_available' => true],
            ],
        ]
    );

    $response->assertSuccessful();

    $profile = HousingProfile::where('student_id', $student->id)->first();
    expect(
        DB::table('housing_services')
            ->where('housing_profile_id', $profile->id)
            ->where('basic_service_id', $service->id)
            ->where('is_available', true)
            ->exists()
    )->toBeTrue();
});
