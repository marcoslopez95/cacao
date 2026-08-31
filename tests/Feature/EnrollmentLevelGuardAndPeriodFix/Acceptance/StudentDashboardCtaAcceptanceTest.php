<?php

/**
 * Acceptance tests — 16-enrollment-level-guard-and-period-fix (HLZ-38, RF-07)
 *
 * These tests define the behavioural contract for the feature.
 * They MUST be RED before implementation and GREEN after.
 * The implementer MUST NOT modify this file.
 *
 * Contract:
 * RF-07 — The "Ir a inscripciones ->" CTA in student/Dashboard.vue is only meaningful for
 *         University students. The backend must expose `student.educational_level` in the
 *         Inertia props of Student\DashboardController::index() (per design.md, the frontend
 *         conditions the CTA on `props.student.educational_level === 'university'`) — this is
 *         the backend half of RF-07. The frontend rendering half (CTA hidden / explanatory
 *         message shown for non-university students) is covered by Dusk in
 *         tests/Browser/Enrollment/EnrollmentLevelGuardAndPeriodFixTest.php.
 */

use App\Models\Pensum;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('RF-07: student dashboard exposes student.educational_level = university in Inertia props', function () {
    $pensum = Pensum::factory()->create();
    $student = Student::factory()->create(['current_pensum_id' => $pensum->id]); // default: University

    $this->actingAs($student->user)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->where('student.educational_level', 'university')
        );
});

test('RF-07: student dashboard exposes student.educational_level = secondary in Inertia props', function () {
    $pensum = Pensum::factory()->create();
    $student = Student::factory()->secondary()->create(['current_pensum_id' => $pensum->id]);

    $this->actingAs($student->user)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->where('student.educational_level', 'secondary')
        );
});
