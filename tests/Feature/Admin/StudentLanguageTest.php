<?php

use App\Models\Catalogs\Language;
use App\Models\Catalogs\LanguageLevel;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\Catalogs\SocialCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
 * Returns an admin User with the Administrador role.
 */
function adminForLanguage(): User
{
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    return $user;
}

test('admin can attach a language to a student', function () {
    $admin = adminForLanguage();
    $student = Student::factory()->create();
    $language = Language::first();
    $level = LanguageLevel::first();

    $response = $this->actingAs($admin)->postJson(
        route('security.students.languages.store', $student),
        [
            'language_id' => $language->id,
            'language_level_id' => $level->id,
            'is_mother_tongue' => false,
        ]
    );

    $response->assertSuccessful();
    expect(
        DB::table('student_languages')
            ->where('student_id', $student->id)
            ->where('language_id', $language->id)
            ->exists()
    )->toBeTrue();
});

test('duplicate language attachment returns 422', function () {
    $admin = adminForLanguage();
    $student = Student::factory()->create();
    $language = Language::first();
    $level = LanguageLevel::first();

    $payload = [
        'language_id' => $language->id,
        'language_level_id' => $level->id,
        'is_mother_tongue' => false,
    ];

    $this->actingAs($admin)->postJson(
        route('security.students.languages.store', $student),
        $payload
    );

    $response = $this->actingAs($admin)->postJson(
        route('security.students.languages.store', $student),
        $payload
    );

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['language_id']);
});

test('attaching is_mother_tongue replaces previous mother_tongue', function () {
    $admin = adminForLanguage();
    $student = Student::factory()->create();
    $languages = Language::take(2)->get();
    $level = LanguageLevel::first();

    $langA = $languages->first();
    $langB = $languages->last();

    $this->actingAs($admin)->postJson(
        route('security.students.languages.store', $student),
        [
            'language_id' => $langA->id,
            'language_level_id' => $level->id,
            'is_mother_tongue' => true,
        ]
    );

    $this->actingAs($admin)->postJson(
        route('security.students.languages.store', $student),
        [
            'language_id' => $langB->id,
            'language_level_id' => $level->id,
            'is_mother_tongue' => true,
        ]
    );

    $langARow = DB::table('student_languages')
        ->where('student_id', $student->id)
        ->where('language_id', $langA->id)
        ->first();

    expect((bool) $langARow->is_mother_tongue)->toBeFalse();
});

test('admin can detach a language', function () {
    $admin = adminForLanguage();
    $student = Student::factory()->create();
    $language = Language::first();
    $level = LanguageLevel::first();

    $this->actingAs($admin)->postJson(
        route('security.students.languages.store', $student),
        [
            'language_id' => $language->id,
            'language_level_id' => $level->id,
            'is_mother_tongue' => false,
        ]
    );

    $response = $this->actingAs($admin)->deleteJson(
        route('security.students.languages.destroy', [$student, $language])
    );

    $response->assertNoContent();
    expect(
        DB::table('student_languages')
            ->where('student_id', $student->id)
            ->where('language_id', $language->id)
            ->exists()
    )->toBeFalse();
});
