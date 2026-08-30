<?php

use App\Models\Guardian;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Section;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions());

test('unauthenticated users are redirected from professor dashboard', function () {
    $this->get(route('professor.dashboard'))
        ->assertRedirect(route('login'));
});

test('professor dashboard returns 200 with required props', function () {
    $professor = Professor::factory()->create();
    $user = $professor->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('professor.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('professor/Dashboard')
            ->has('period')
            ->has('sections_count')
            ->has('total_students')
            ->has('hours_per_week')
            ->has('today_label')
            ->has('today_schedules')
        );
});

test('professor dashboard returns null period when no active period exists', function () {
    Period::where('status', 'active')->delete();

    $professor = Professor::factory()->create();
    $user = $professor->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('professor.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('professor/Dashboard')
            ->where('period', null)
            ->where('sections_count', 0)
            ->where('total_students', 0)
            ->where('hours_per_week', 0)
            ->has('today_schedules')
        );
});

test('professor dashboard includes sections count for active period', function () {
    $period = Period::factory()->active()->create();
    $professor = Professor::factory()->create();
    $user = $professor->user;

    Section::factory()->count(3)->create([
        'period_id' => $period->id,
        'main_teacher_id' => $professor->id,
    ]);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('professor.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('professor/Dashboard')
            ->where('sections_count', 3)
        );
});

test('professor dashboard returns empty today_schedules when no sections exist', function () {
    Period::factory()->active()->create();
    $professor = Professor::factory()->create();
    $user = $professor->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('professor.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('professor/Dashboard')
            ->where('today_schedules', [])
        );
});

test('professor dashboard returns 200 on sunday with no school-day label', function () {
    $this->travelTo(Carbon::parse('2024-01-07 09:00:00'));

    $professor = Professor::factory()->create();
    $user = $professor->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('professor.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('professor/Dashboard')
            ->where('today_label', 'Domingo')
            ->where('today_schedules', [])
        );
});

test('professor cannot access student portal', function () {
    $professor = Professor::factory()->create();
    $user = $professor->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});

test('professor cannot access guardian portal', function () {
    $professor = Professor::factory()->create();
    $user = $professor->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('guardian.dashboard'))
        ->assertForbidden();
});

test('student cannot access professor portal', function () {
    $student = Student::factory()->create();
    $user = $student->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('professor.dashboard'))
        ->assertForbidden();
});

test('guardian cannot access professor portal', function () {
    $guardian = Guardian::factory()->create();
    $user = $guardian->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('professor.dashboard'))
        ->assertForbidden();
});
