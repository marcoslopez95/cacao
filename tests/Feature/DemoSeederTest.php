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
    expect(Guardian::count())->toBeGreaterThanOrEqual(20);

    $uni = User::where('email', 'est001@utcacao.edu.ve')->first();
    expect($uni)->not->toBeNull();
    expect($uni->hasRole('Estudiante'))->toBeTrue();
    expect($uni->student->current_pensum_id)->not->toBeNull();

    $sec = User::where('email', 'sec01@utcacao.edu.ve')->first();
    expect($sec)->not->toBeNull();
    expect($sec->student->guardian_id)->not->toBeNull();
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

    $draft = Enrollment::where('status', 'draft')->count();
    $confirmed = Enrollment::where('status', 'confirmed')->count();
    $approved = Enrollment::where('status', 'approved')->count();

    expect($draft)->toBeGreaterThanOrEqual(25);
    expect($confirmed)->toBeGreaterThanOrEqual(25);
    expect($approved)->toBeGreaterThanOrEqual(25);
});
