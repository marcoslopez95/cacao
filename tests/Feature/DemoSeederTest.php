<?php

use App\Models\Building;
use App\Models\Career;
use App\Models\CareerCategory;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Guardian;
use App\Models\Lapse;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds academic structure', function () {
    (new DemoSeeder)->run();

    expect(CareerCategory::count())->toBeGreaterThanOrEqual(3);
    expect(Career::count())->toBeGreaterThanOrEqual(5);
    expect(Pensum::count())->toBeGreaterThanOrEqual(5);
    expect(Subject::count())->toBeGreaterThanOrEqual(40);
});

it('seeds infrastructure', function () {
    (new DemoSeeder)->run();

    expect(Building::count())->toBeGreaterThanOrEqual(2);
    expect(Classroom::count())->toBeGreaterThanOrEqual(15);
});

it('seeds periods', function () {
    (new DemoSeeder)->run();

    expect(Period::count())->toBeGreaterThanOrEqual(2);
    expect(Lapse::count())->toBeGreaterThanOrEqual(4);

    $active = Period::where('status', 'active')->first();
    expect($active)->not->toBeNull();
    expect($active->name)->toBe('2026-I');
});

it('seeds professors', function () {
    (new DemoSeeder)->run();

    expect(Professor::count())->toBeGreaterThanOrEqual(12);

    $prof = User::where('email', 'prof01@utcacao.edu.ve')->first();
    expect($prof)->not->toBeNull();
    expect($prof->hasRole('Profesor'))->toBeTrue();
});

it('seeds students', function () {
    (new DemoSeeder)->run();

    expect(Student::count())->toBeGreaterThanOrEqual(140);

    // Guardians now cover 1-6 students each (fewer guardians than students by design)
    expect(Guardian::count())->toBeGreaterThan(0);
    $studentsPerGuardian = Guardian::withCount('students')->pluck('students_count');
    expect($studentsPerGuardian->min())->toBeGreaterThanOrEqual(1);
    expect($studentsPerGuardian->max())->toBeLessThanOrEqual(6);

    $uni = User::where('email', 'est001@utcacao.edu.ve')->first();
    expect($uni)->not->toBeNull();
    expect($uni->hasRole('Estudiante'))->toBeTrue();
    expect($uni->student->current_pensum_id)->not->toBeNull();

    $sec = User::where('email', 'sec01@utcacao.edu.ve')->first();
    expect($sec)->not->toBeNull();
    expect($sec->student->guardians()->exists())->toBeTrue();
});

it('seeds sections and schedules', function () {
    (new DemoSeeder)->run();

    expect(Section::count())->toBeGreaterThanOrEqual(60);
    expect(Schedule::count())->toBeGreaterThanOrEqual(120);
});

it('seeds enrollments', function () {
    (new DemoSeeder)->run();

    expect(Enrollment::count())->toBeGreaterThanOrEqual(75);
    expect(EnrollmentDetail::count())->toBeGreaterThanOrEqual(270);

    // Draft/confirmed only occur in the current period (varied enrollment states);
    // approved dominates globally because historical backfill is always approved.
    $draft = Enrollment::where('status', 'draft')->count();
    $confirmed = Enrollment::where('status', 'confirmed')->count();
    $approved = Enrollment::where('status', 'approved')->count();

    expect($draft)->toBeGreaterThan(0);
    expect($confirmed)->toBeGreaterThan(0);
    expect($approved)->toBeGreaterThan(0);
});

it('is idempotent — running twice yields the same counts', function () {
    $seeder = new DemoSeeder;
    $seeder->run();

    // Captured after the first run rather than hardcoded: Enrollment/EnrollmentDetail
    // counts depend on a hash of each student's auto-increment id (deterministic
    // within an environment, but not portable across environments/DBs), so what
    // this test actually needs to prove is "unchanged by a second run", not a
    // specific absolute number.
    $countsAfterFirstRun = [
        'CareerCategory' => CareerCategory::count(),
        'Career' => Career::count(),
        'Pensum' => Pensum::count(),
        'Subject' => Subject::count(),
        'Building' => Building::count(),
        'Classroom' => Classroom::count(),
        'Period' => Period::count(),
        'Lapse' => Lapse::count(),
        'Professor' => Professor::count(),
        'Student' => Student::count(),
        'Guardian' => Guardian::count(),
        'Section' => Section::count(),
        'Schedule' => Schedule::count(),
        'Enrollment' => Enrollment::count(),
        'EnrollmentDetail' => EnrollmentDetail::count(),
    ];

    $seeder->run();  // second run must not duplicate records

    foreach ($countsAfterFirstRun as $model => $count) {
        expect(match ($model) {
            'CareerCategory' => CareerCategory::count(),
            'Career' => Career::count(),
            'Pensum' => Pensum::count(),
            'Subject' => Subject::count(),
            'Building' => Building::count(),
            'Classroom' => Classroom::count(),
            'Period' => Period::count(),
            'Lapse' => Lapse::count(),
            'Professor' => Professor::count(),
            'Student' => Student::count(),
            'Guardian' => Guardian::count(),
            'Section' => Section::count(),
            'Schedule' => Schedule::count(),
            'Enrollment' => Enrollment::count(),
            'EnrollmentDetail' => EnrollmentDetail::count(),
        })->toBe($count, "{$model} count changed after a second seeder run");
    }
});
