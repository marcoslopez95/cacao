<?php

use App\Models\Catalogs\KinshipType;
use App\Models\Guardian;
use App\Models\Student;
use Database\Seeders\Catalogs\SocialCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    (new SocialCatalogsSeeder)->run();
});

test('can attach a guardian to a student', function () {
    $student = Student::factory()->create();
    $guardian = Guardian::factory()->create();
    $kinshipTypeId = KinshipType::where('code', 'mother')->value('id');

    $student->guardians()->attach($guardian->id, [
        'kinship_type_id' => $kinshipTypeId,
        'is_primary' => true,
        'is_emergency_contact' => true,
    ]);

    expect($student->guardians()->count())->toBe(1);
});

test('attaching a second is_primary guardian does NOT auto-flip (that\'s the Action\'s job)', function () {
    $student = Student::factory()->create();
    $guardian1 = Guardian::factory()->create();
    $guardian2 = Guardian::factory()->create();
    $kinshipTypeId = KinshipType::where('code', 'mother')->value('id');

    $student->guardians()->attach($guardian1->id, [
        'kinship_type_id' => $kinshipTypeId,
        'is_primary' => true,
        'is_emergency_contact' => false,
    ]);

    $student->guardians()->attach($guardian2->id, [
        'kinship_type_id' => $kinshipTypeId,
        'is_primary' => true,
        'is_emergency_contact' => false,
    ]);

    $primaryCount = $student->guardians()
        ->wherePivot('is_primary', true)
        ->count();

    expect($primaryCount)->toBe(2);
});

test('can detach a guardian from a student', function () {
    $student = Student::factory()->create();
    $guardian = Guardian::factory()->create();
    $kinshipTypeId = KinshipType::where('code', 'mother')->value('id');

    $student->guardians()->attach($guardian->id, [
        'kinship_type_id' => $kinshipTypeId,
        'is_primary' => true,
        'is_emergency_contact' => true,
    ]);

    $student->guardians()->detach($guardian->id);

    expect($student->guardians()->count())->toBe(0);
});

test('primaryGuardian() returns the is_primary guardian', function () {
    $student = Student::factory()->create();
    $guardian1 = Guardian::factory()->create();
    $guardian2 = Guardian::factory()->create();
    $kinshipTypeId = KinshipType::where('code', 'mother')->value('id');

    $student->guardians()->attach($guardian1->id, [
        'kinship_type_id' => $kinshipTypeId,
        'is_primary' => false,
        'is_emergency_contact' => false,
    ]);

    $student->guardians()->attach($guardian2->id, [
        'kinship_type_id' => $kinshipTypeId,
        'is_primary' => true,
        'is_emergency_contact' => true,
    ]);

    expect($student->primaryGuardian()->id)->toBe($guardian2->id);
});
