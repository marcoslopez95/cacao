<?php

use App\Models\Catalogs\InstitutionalBenefit;
use App\Models\Student;
use App\Models\User;
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
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
});

/**
 * Returns an admin User with the Admin role (Gate::before bypass).
 */
function adminForBenefit(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

test('admin can attach a benefit to a student', function () {
    $admin = adminForBenefit();
    $student = Student::factory()->create();
    $benefit = InstitutionalBenefit::first();

    $response = $this->actingAs($admin)->postJson(
        route('security.students.benefits.store', [$student, $benefit])
    );

    $response->assertSuccessful();
    expect(
        DB::table('student_benefits')
            ->where('student_id', $student->id)
            ->where('benefit_id', $benefit->id)
            ->exists()
    )->toBeTrue();
});

test('duplicate benefit attachment returns 422', function () {
    $admin = adminForBenefit();
    $student = Student::factory()->create();
    $benefit = InstitutionalBenefit::first();

    $this->actingAs($admin)->postJson(
        route('security.students.benefits.store', [$student, $benefit])
    );

    $response = $this->actingAs($admin)->postJson(
        route('security.students.benefits.store', [$student, $benefit])
    );

    $response->assertUnprocessable();
});

test('admin can detach a benefit', function () {
    $admin = adminForBenefit();
    $student = Student::factory()->create();
    $benefit = InstitutionalBenefit::first();

    $this->actingAs($admin)->postJson(
        route('security.students.benefits.store', [$student, $benefit])
    );

    $response = $this->actingAs($admin)->deleteJson(
        route('security.students.benefits.destroy', [$student, $benefit])
    );

    $response->assertNoContent();
    expect(
        DB::table('student_benefits')
            ->where('student_id', $student->id)
            ->where('benefit_id', $benefit->id)
            ->exists()
    )->toBeFalse();
});

test('until before since is rejected', function () {
    $admin = adminForBenefit();
    $student = Student::factory()->create();
    $benefit = InstitutionalBenefit::first();

    $response = $this->actingAs($admin)->postJson(
        route('security.students.benefits.store', [$student, $benefit]),
        [
            'since' => '2024-01-15',
            'until' => '2024-01-10',
        ]
    );

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['until']);
});
