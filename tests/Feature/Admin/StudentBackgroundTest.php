<?php

use App\Models\Student;
use App\Models\StudentBackground;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
});

/**
 * Returns a valid payload for upserting a student background.
 */
function validBackgroundPayload(): array
{
    return [
        'previous_institution' => 'Liceo Nacional',
        'graduation_year' => 2022,
        'previous_gpa' => '15.50',
    ];
}

/**
 * Returns an admin User with the Administrador role.
 */
function adminForBackground(): User
{
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    return $user;
}

test('upsert creates background on first call', function () {
    $admin = adminForBackground();
    $student = Student::factory()->create();

    $response = $this->actingAs($admin)->putJson(
        route('security.students.background.upsert', $student),
        validBackgroundPayload()
    );

    $response->assertSuccessful();
    expect(StudentBackground::where('student_id', $student->id)->exists())->toBeTrue();
});

test('upsert updates background on second call — no duplicate row', function () {
    $admin = adminForBackground();
    $student = Student::factory()->create();

    $this->actingAs($admin)->putJson(
        route('security.students.background.upsert', $student),
        validBackgroundPayload()
    );

    $this->actingAs($admin)->putJson(
        route('security.students.background.upsert', $student),
        array_merge(validBackgroundPayload(), ['previous_institution' => 'Universidad Central'])
    );

    expect(StudentBackground::where('student_id', $student->id)->count())->toBe(1);
    expect(StudentBackground::where('student_id', $student->id)->first()->previous_institution)->toBe('Universidad Central');
});

test('previous_gpa above 20 is rejected', function () {
    $admin = adminForBackground();
    $student = Student::factory()->create();

    $response = $this->actingAs($admin)->putJson(
        route('security.students.background.upsert', $student),
        array_merge(validBackgroundPayload(), ['previous_gpa' => 21])
    );

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['previous_gpa']);
});

test('student_id is unique — second upsert updates not creates', function () {
    $admin = adminForBackground();
    $student = Student::factory()->create();

    $this->actingAs($admin)->putJson(
        route('security.students.background.upsert', $student),
        validBackgroundPayload()
    );

    $this->actingAs($admin)->putJson(
        route('security.students.background.upsert', $student),
        array_merge(validBackgroundPayload(), ['graduation_year' => 2023])
    );

    expect(StudentBackground::where('student_id', $student->id)->count())->toBe(1);
});
