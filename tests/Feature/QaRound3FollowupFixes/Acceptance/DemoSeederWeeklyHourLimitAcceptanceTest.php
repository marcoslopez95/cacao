<?php

/**
 * Acceptance tests — 22-qa-round3-followup-fixes (HLZ-48)
 *
 * These tests define the behavioural contract for RF-05 of this feature.
 * They MUST be RED before implementation and GREEN after.
 * The implementer MUST NOT modify this file.
 *
 * Contract:
 * RF-05 — after seeding demo data, no demo professor with real sections/schedules ends up with a
 *         weekly hour load above their own `weekly_hour_limit`.
 *
 * `DemoSeeder` (not the plain `DatabaseSeeder`) is what actually creates the demo professors,
 * sections and schedules — it is the seeder this feature's own design.md targets, and it is the
 * established pattern used by tests/Feature/DemoSeederTest.php for exercising "the demo dataset
 * after a fresh seed". `DemoSeeder::run()` calls `DatabaseSeeder` internally, so this is the full
 * equivalent of `migrate:fresh --seed` for this dataset.
 *
 * Covers specs/22-qa-round3-followup-fixes/qa.md UC-QA-04. Verified purely against seeded data
 * (Feature/Unit level, using the existing `ScheduleConflictService::professorCurrentWeeklyHours()`
 * — unchanged by this feature per design.md "Sin cambios en") — no Dusk test is needed for this UC.
 */

use App\Models\Professor;
use App\Models\Schedule;
use App\Services\Scheduling\ScheduleConflictService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('RF-05: no demo professor with real sections/schedules exceeds their own weekly_hour_limit after seeding', function () {
    (new DemoSeeder)->run();

    $service = app(ScheduleConflictService::class);

    $professorIds = Schedule::query()->whereNotNull('professor_id')->distinct()->pluck('professor_id');

    // Sanity check: the seeder must actually produce real schedules for this test to mean anything.
    expect($professorIds)->not->toBeEmpty();

    $offenders = [];

    foreach ($professorIds as $professorId) {
        $professor = Professor::find($professorId);
        $hours = $service->professorCurrentWeeklyHours($professor);

        if ($hours > $professor->weekly_hour_limit) {
            $offenders[] = sprintf(
                'professor #%d: %.1fh > limit %dh',
                $professorId,
                $hours,
                $professor->weekly_hour_limit,
            );
        }
    }

    expect($offenders)->toBe([]);
});

test('RF-05 (efecto colateral verificable): at least one professor with real sections has margin for a new 30-minute schedule block', function () {
    (new DemoSeeder)->run();

    $service = app(ScheduleConflictService::class);

    $professorIds = Schedule::query()->whereNotNull('professor_id')->distinct()->pluck('professor_id');
    expect($professorIds)->not->toBeEmpty();

    $hasMargin = $professorIds->contains(function ($professorId) use ($service) {
        $professor = Professor::find($professorId);
        $hours = $service->professorCurrentWeeklyHours($professor);

        return ($hours + 0.5) <= $professor->weekly_hour_limit;
    });

    expect($hasMargin)->toBeTrue();
});
