<?php

use App\Models\Catalogs\Religion;
use App\Models\DemographicProfile;
use App\Models\User;
use Database\Seeders\Catalogs\GeographicSeeder;
use Database\Seeders\Catalogs\SocialCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    (new SocialCatalogsSeeder)->run();
    (new GeographicSeeder)->run();
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Estudiante', 'guard_name' => 'web']);
});

/**
 * Returns an admin User with the Administrador role.
 */
function adminForDemographic(): User
{
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    return $user;
}

test('admin sees religion_id in response', function () {
    $admin = adminForDemographic();
    $user = User::factory()->create();
    $religion = Religion::first();

    $response = $this->actingAs($admin)->putJson(
        route('security.users.demographic-profile.upsert', $user),
        ['religion_id' => $religion->id]
    );

    $response->assertSuccessful();
    $response->assertJsonPath('data.religion_id', $religion->id);
});

test('non-admin student gets 403 on demographic-profile upsert', function () {
    $student = User::factory()->create();
    $student->assignRole('Estudiante');
    $target = User::factory()->create();

    $response = $this->actingAs($student)->putJson(
        route('security.users.demographic-profile.upsert', $target),
        ['birth_city' => 'Caracas']
    );

    $response->assertForbidden();
});

test('is_indigenous flag with community name is stored correctly', function () {
    $admin = adminForDemographic();
    $user = User::factory()->create();

    $this->actingAs($admin)->putJson(
        route('security.users.demographic-profile.upsert', $user),
        [
            'is_indigenous' => true,
            'indigenous_community' => 'Wayuu',
        ]
    );

    $profile = DemographicProfile::where('user_id', $user->id)->first();
    expect($profile->is_indigenous)->toBeTrue();
    expect($profile->indigenous_community)->toBe('Wayuu');
});

test('upsert updates on second call — no duplicate row', function () {
    $admin = adminForDemographic();
    $user = User::factory()->create();

    $this->actingAs($admin)->putJson(
        route('security.users.demographic-profile.upsert', $user),
        ['birth_city' => 'Caracas']
    );

    $this->actingAs($admin)->putJson(
        route('security.users.demographic-profile.upsert', $user),
        ['birth_city' => 'Maracaibo']
    );

    expect(DemographicProfile::where('user_id', $user->id)->count())->toBe(1);
    expect(DemographicProfile::where('user_id', $user->id)->first()->birth_city)->toBe('Maracaibo');
});
