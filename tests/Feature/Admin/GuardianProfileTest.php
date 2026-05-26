<?php

use App\Models\Catalogs\EducationLevel;
use App\Models\Catalogs\MaritalStatus;
use App\Models\Guardian;
use App\Models\GuardianProfile;
use App\Models\User;
use Database\Seeders\Catalogs\AcademicCatalogsSeeder;
use Database\Seeders\Catalogs\SocialCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    (new AcademicCatalogsSeeder)->run();
    (new SocialCatalogsSeeder)->run();
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Representante', 'guard_name' => 'web']);
});

test('admin can upsert guardian profile', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $guardian = Guardian::factory()->create();

    $response = $this->actingAs($admin)->put(
        route('security.guardians.profile.upsert', $guardian),
        [
            'occupation' => 'Ingeniero',
            'employer' => 'ACME',
            'work_phone' => '+584121234567',
            'education_level_id' => EducationLevel::first()?->id,
            'marital_status_id' => MaritalStatus::first()?->id,
        ]
    );

    $response->assertSuccessful();
    expect(GuardianProfile::where('guardian_id', $guardian->id)->exists())->toBeTrue();
});

test('guardian can upsert their own profile', function () {
    $guardian = Guardian::factory()->create();

    $response = $this->actingAs($guardian->user)->put(
        route('security.guardians.profile.upsert', $guardian),
        [
            'occupation' => 'Docente',
            'employer' => 'Escuela Central',
            'work_phone' => '+584169876543',
            'education_level_id' => EducationLevel::first()?->id,
            'marital_status_id' => MaritalStatus::first()?->id,
        ]
    );

    $response->assertSuccessful();
});

test('guardian cannot upsert another guardian\'s profile (403)', function () {
    $guardianA = Guardian::factory()->create();
    $guardianB = Guardian::factory()->create();

    $response = $this->actingAs($guardianA->user)->put(
        route('security.guardians.profile.upsert', $guardianB),
        [
            'occupation' => 'Hacker',
            'education_level_id' => EducationLevel::first()?->id,
            'marital_status_id' => MaritalStatus::first()?->id,
        ]
    );

    $response->assertForbidden();
});

test('upsert is idempotent — second PUT updates existing profile', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $guardian = Guardian::factory()->create();

    $this->actingAs($admin)->put(
        route('security.guardians.profile.upsert', $guardian),
        [
            'occupation' => 'Médico',
            'education_level_id' => EducationLevel::first()?->id,
            'marital_status_id' => MaritalStatus::first()?->id,
        ]
    );

    $this->actingAs($admin)->put(
        route('security.guardians.profile.upsert', $guardian),
        [
            'occupation' => 'Abogado',
            'education_level_id' => EducationLevel::first()?->id,
            'marital_status_id' => MaritalStatus::first()?->id,
        ]
    );

    expect(GuardianProfile::where('guardian_id', $guardian->id)->count())->toBe(1);
    expect(GuardianProfile::where('guardian_id', $guardian->id)->first()->occupation)->toBe('Abogado');
});
