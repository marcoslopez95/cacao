<?php

use App\Models\FamilyProfile;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\Catalogs\SocialCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    (new SocialCatalogsSeeder)->run();
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
});

/**
 * Returns a valid minimal payload for upserting a family profile.
 */
function validFamilyPayload(): array
{
    return [
        'sibling_count' => 3,
        'sibling_position' => 2,
    ];
}

/**
 * Returns an admin User with the Administrador role.
 */
function adminForFamily(): User
{
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    return $user;
}

test('upsert creates family profile on first call', function () {
    $admin = adminForFamily();
    $student = Student::factory()->create();

    $response = $this->actingAs($admin)->putJson(
        route('security.students.family-profile.upsert', $student),
        validFamilyPayload()
    );

    $response->assertSuccessful();
    expect(FamilyProfile::where('student_id', $student->id)->exists())->toBeTrue();
});

test('upsert updates on second call — no duplicate row', function () {
    $admin = adminForFamily();
    $student = Student::factory()->create();

    $this->actingAs($admin)->putJson(
        route('security.students.family-profile.upsert', $student),
        validFamilyPayload()
    );

    $this->actingAs($admin)->putJson(
        route('security.students.family-profile.upsert', $student),
        array_merge(validFamilyPayload(), ['sibling_count' => 5, 'sibling_position' => 4])
    );

    expect(FamilyProfile::where('student_id', $student->id)->count())->toBe(1);
    expect(FamilyProfile::where('student_id', $student->id)->first()->sibling_count)->toBe(5);
});

test('sibling_position greater than sibling_count is rejected', function () {
    $admin = adminForFamily();
    $student = Student::factory()->create();

    $response = $this->actingAs($admin)->putJson(
        route('security.students.family-profile.upsert', $student),
        ['sibling_count' => 3, 'sibling_position' => 5]
    );

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['sibling_position']);
});

test('sibling_position equal to sibling_count is accepted', function () {
    $admin = adminForFamily();
    $student = Student::factory()->create();

    $response = $this->actingAs($admin)->putJson(
        route('security.students.family-profile.upsert', $student),
        ['sibling_count' => 3, 'sibling_position' => 3]
    );

    $response->assertSuccessful();
});
