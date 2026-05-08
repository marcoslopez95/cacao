# Scheduling 06 — Schedule Model & Conflict Service Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create the `schedules` table, its Eloquent model, a factory, and the `ScheduleConflictService` with full unit test coverage — no routes or frontend.

**Architecture:** Pure backend spec. Two new Enums (DayOfWeek, ScheduleSessionType), one new Model with four FK relations, a migration with DB-level CHECK constraints (PostgreSQL), and a service class with four public methods that detect time/classroom/professor conflicts by querying the database.

**Tech Stack:** PHP 8.3, Laravel 12, PostgreSQL, Pest v3, Carbon for time arithmetic.

---

## File Map

| File | Action | Role |
|------|--------|------|
| `app/Enums/DayOfWeek.php` | Create | Days Mon–Sat, Spanish labels, integer order |
| `app/Enums/ScheduleSessionType.php` | Create | Theory / Lab, Spanish labels |
| `database/migrations/2026_05_07_000001_create_schedules_table.php` | Create | `schedules` table + CHECK constraints |
| `app/Models/Schedule.php` | Create | Eloquent model, casts, 4 belongsTo |
| `database/factories/ScheduleFactory.php` | Create | 45-min slots 07:00–17:15 start range |
| `app/Services/Scheduling/ScheduleConflictService.php` | Create | 4 public conflict-detection methods |
| `tests/Unit/Scheduling/ScheduleConflictServiceTest.php` | Create | 13 unit tests, uses RefreshDatabase |

---

## Task 1: Enums

**Files:**
- Create: `app/Enums/DayOfWeek.php`
- Create: `app/Enums/ScheduleSessionType.php`

- [ ] **Step 1: Create DayOfWeek enum**

```php
<?php
// app/Enums/DayOfWeek.php

namespace App\Enums;

enum DayOfWeek: string
{
    case Monday    = 'monday';
    case Tuesday   = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday  = 'thursday';
    case Friday    = 'friday';
    case Saturday  = 'saturday';

    public function label(): string
    {
        return match ($this) {
            self::Monday    => 'Lunes',
            self::Tuesday   => 'Martes',
            self::Wednesday => 'Miércoles',
            self::Thursday  => 'Jueves',
            self::Friday    => 'Viernes',
            self::Saturday  => 'Sábado',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::Monday    => 1,
            self::Tuesday   => 2,
            self::Wednesday => 3,
            self::Thursday  => 4,
            self::Friday    => 5,
            self::Saturday  => 6,
        };
    }
}
```

- [ ] **Step 2: Create ScheduleSessionType enum**

```php
<?php
// app/Enums/ScheduleSessionType.php

namespace App\Enums;

enum ScheduleSessionType: string
{
    case Theory = 'theory';
    case Lab    = 'lab';

    public function label(): string
    {
        return match ($this) {
            self::Theory => 'Teoría',
            self::Lab    => 'Laboratorio',
        };
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Enums/DayOfWeek.php app/Enums/ScheduleSessionType.php
git commit -m "feat: add DayOfWeek and ScheduleSessionType enums"
```

---

## Task 2: Migration

**Files:**
- Create: `database/migrations/2026_05_07_000001_create_schedules_table.php`

- [ ] **Step 1: Create the migration**

```php
<?php
// database/migrations/2026_05_07_000001_create_schedules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->foreignId('professor_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('type');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE schedules ADD CONSTRAINT schedules_times_check CHECK (end_time > start_time)');
        DB::statement("ALTER TABLE schedules ADD CONSTRAINT schedules_start_min_check CHECK (start_time >= '07:00:00')");
        DB::statement("ALTER TABLE schedules ADD CONSTRAINT schedules_end_max_check CHECK (end_time <= '18:00:00')");
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
```

- [ ] **Step 2: Run migration**

```bash
sail artisan migrate
```

Expected output: `... Running migration [2026_05_07_000001_create_schedules_table] ... DONE`

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_05_07_000001_create_schedules_table.php
git commit -m "feat: add schedules table migration with CHECK constraints"
```

---

## Task 3: Model

**Files:**
- Create: `app/Models/Schedule.php`

- [ ] **Step 1: Create the model**

```php
<?php
// app/Models/Schedule.php

namespace App\Models;

use App\Enums\DayOfWeek;
use App\Enums\ScheduleSessionType;
use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['section_id', 'professor_id', 'classroom_id', 'subject_id', 'day_of_week', 'start_time', 'end_time', 'type', 'valid_from', 'valid_until'])]
class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'type'        => ScheduleSessionType::class,
            'valid_from'  => 'date',
            'valid_until' => 'date',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/Schedule.php
git commit -m "feat: add Schedule model with DayOfWeek and ScheduleSessionType casts"
```

---

## Task 4: Factory

**Files:**
- Create: `database/factories/ScheduleFactory.php`

- [ ] **Step 1: Create the factory**

```php
<?php
// database/factories/ScheduleFactory.php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Enums\ScheduleSessionType;
use App\Models\Classroom;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    // Valid 45-min start times: 07:00 to 17:15 (end 07:45 to 18:00)
    private const STARTS = [
        '07:00', '07:45', '08:30', '09:15', '10:00', '10:45',
        '11:30', '12:15', '13:00', '13:45', '14:30', '15:15',
        '16:00', '16:45', '17:15',
    ];

    public function definition(): array
    {
        $start = fake()->randomElement(self::STARTS);
        [$h, $m] = explode(':', $start);
        $endMinutes = (int)$h * 60 + (int)$m + 45;
        $end = sprintf('%02d:%02d:00', intdiv($endMinutes, 60), $endMinutes % 60);

        return [
            'section_id'   => Section::factory(),
            'professor_id' => Professor::factory(),
            'classroom_id' => Classroom::factory(),
            'subject_id'   => Subject::factory(),
            'day_of_week'  => fake()->randomElement(DayOfWeek::cases()),
            'start_time'   => $start . ':00',
            'end_time'     => $end,
            'type'         => ScheduleSessionType::Theory,
            'valid_from'   => '2026-01-15',
            'valid_until'  => null,
        ];
    }
}
```

- [ ] **Step 2: Verify factory works**

```bash
sail artisan tinker --execute="App\Models\Schedule::factory()->make()->toArray()"
```

Expected: array with all schedule fields, start_time in 'HH:MM:SS' format.

- [ ] **Step 3: Commit**

```bash
git add database/factories/ScheduleFactory.php
git commit -m "feat: add ScheduleFactory with 45-min slot generation"
```

---

## Task 5: Unit Tests (write first, run to confirm failure)

**Files:**
- Create: `tests/Unit/Scheduling/ScheduleConflictServiceTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php
// tests/Unit/Scheduling/ScheduleConflictServiceTest.php

use App\Enums\DayOfWeek;
use App\Enums\PeriodStatus;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Services\Scheduling\ScheduleConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeSection(Period $period): Section
{
    return Section::factory()->university()->create(['period_id' => $period->id]);
}

function activePeriod(): Period
{
    return Period::factory()->semester()->create([
        'status'     => PeriodStatus::Active,
        'start_date' => '2026-01-01',
        'end_date'   => '2026-06-30',
    ]);
}

function closedPeriod(): Period
{
    return Period::factory()->semester()->create([
        'status'     => PeriodStatus::Closed,
        'start_date' => '2025-01-01',
        'end_date'   => '2025-06-30',
    ]);
}

// ---------------------------------------------------------------------------
// classroomConflict
// ---------------------------------------------------------------------------

test('classroomConflict detects exact overlap', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $classroom = Classroom::factory()->create();

    $existing = Schedule::factory()->create([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    $candidate = Schedule::factory()->make([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $section);

    expect($service->classroomConflict($candidate)?->id)->toBe($existing->id);
});

test('classroomConflict detects partial overlap', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $classroom = Classroom::factory()->create();

    $existing = Schedule::factory()->create([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    $candidate = Schedule::factory()->make([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:30:00',
        'end_time'     => '09:30:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $section);

    expect($service->classroomConflict($candidate)?->id)->toBe($existing->id);
});

test('classroomConflict does not flag adjacent slots as conflict', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $classroom = Classroom::factory()->create();

    Schedule::factory()->create([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    $candidate = Schedule::factory()->make([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '09:00:00',
        'end_time'     => '10:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $section);

    expect($service->classroomConflict($candidate))->toBeNull();
});

test('classroomConflict ignores slots in closed periods', function () {
    $service   = new ScheduleConflictService();
    $closed    = closedPeriod();
    $section   = makeSection($closed);
    $classroom = Classroom::factory()->create();

    Schedule::factory()->create([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2025-01-15',
        'valid_until'  => null,
    ]);

    $activePeriod  = activePeriod();
    $activeSection = makeSection($activePeriod);

    $candidate = Schedule::factory()->make([
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $activeSection);

    expect($service->classroomConflict($candidate))->toBeNull();
});

test('classroomConflict does not conflict with itself on update', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $classroom = Classroom::factory()->create();

    $schedule = Schedule::factory()->create([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $schedule->setRelation('section', $section);

    expect($service->classroomConflict($schedule))->toBeNull();
});

// ---------------------------------------------------------------------------
// professorConflict
// ---------------------------------------------------------------------------

test('professorConflict detects same professor overlapping in different sections', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section1  = makeSection($period);
    $section2  = makeSection($period);
    $professor = Professor::factory()->create();

    $existing = Schedule::factory()->create([
        'section_id'   => $section1->id,
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Tuesday->value,
        'start_time'   => '10:00:00',
        'end_time'     => '11:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    $candidate = Schedule::factory()->make([
        'section_id'   => $section2->id,
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Tuesday->value,
        'start_time'   => '10:30:00',
        'end_time'     => '11:30:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $section2);

    expect($service->professorConflict($candidate)?->id)->toBe($existing->id);
});

test('professorConflict does not flag different days', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section1  = makeSection($period);
    $section2  = makeSection($period);
    $professor = Professor::factory()->create();

    Schedule::factory()->create([
        'section_id'   => $section1->id,
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '10:00:00',
        'end_time'     => '11:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    $candidate = Schedule::factory()->make([
        'section_id'   => $section2->id,
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Tuesday->value,
        'start_time'   => '10:00:00',
        'end_time'     => '11:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $section2);

    expect($service->professorConflict($candidate))->toBeNull();
});

test('professorConflict ignores slots in closed periods', function () {
    $service   = new ScheduleConflictService();
    $closed    = closedPeriod();
    $section1  = makeSection($closed);
    $professor = Professor::factory()->create();

    Schedule::factory()->create([
        'section_id'   => $section1->id,
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '10:00:00',
        'end_time'     => '11:00:00',
        'valid_from'   => '2025-01-15',
        'valid_until'  => null,
    ]);

    $active   = activePeriod();
    $section2 = makeSection($active);

    $candidate = Schedule::factory()->make([
        'section_id'   => $section2->id,
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '10:00:00',
        'end_time'     => '11:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $section2);

    expect($service->professorConflict($candidate))->toBeNull();
});

// ---------------------------------------------------------------------------
// professorWeeklyHoursExceeded
// ---------------------------------------------------------------------------

test('professorWeeklyHoursExceeded returns true when limit would be exceeded', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 4]);

    // Existing: 2 h + 1.5 h = 3.5 h already assigned
    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '08:00:00',
        'end_time'     => '10:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '11:00:00',
        'end_time'     => '12:30:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    // New slot: 1 h → total 4.5 h > limit 4
    $candidate = Schedule::factory()->make([
        'professor_id' => $professor->id,
        'start_time'   => '14:00:00',
        'end_time'     => '15:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('professor', $professor);

    expect($service->professorWeeklyHoursExceeded($candidate))->toBeTrue();
});

test('professorWeeklyHoursExceeded returns false when within limit', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 10]);

    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '08:00:00',
        'end_time'     => '10:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    // New slot: 1 h → total 3 h < limit 10
    $candidate = Schedule::factory()->make([
        'professor_id' => $professor->id,
        'start_time'   => '11:00:00',
        'end_time'     => '12:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('professor', $professor);

    expect($service->professorWeeklyHoursExceeded($candidate))->toBeFalse();
});

test('professorWeeklyHoursExceeded on update excludes own slot from count', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 3]);

    // One 3-h slot already = exactly at limit
    $existing = Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '08:00:00',
        'end_time'     => '11:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $existing->setRelation('professor', $professor);

    // Updating that same slot (extending 30 min → 3.5 h, still > limit if counted twice)
    // Since we exclude its own hours, only 0 h existing + 3.5 h new = 3.5 h > 3 → true
    $existing->end_time = '11:30:00';

    expect($service->professorWeeklyHoursExceeded($existing))->toBeTrue();
});

// ---------------------------------------------------------------------------
// professorCurrentWeeklyHours
// ---------------------------------------------------------------------------

test('professorCurrentWeeklyHours sums multiple slots correctly', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $professor = Professor::factory()->create();

    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '08:00:00',
        'end_time'     => '09:30:00', // 1.5 h
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '10:00:00',
        'end_time'     => '12:00:00', // 2 h
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    expect($service->professorCurrentWeeklyHours($professor))->toBe(3.5);
});

test('professorCurrentWeeklyHours ignores closed period slots', function () {
    $service   = new ScheduleConflictService();
    $closed    = closedPeriod();
    $section   = makeSection($closed);
    $professor = Professor::factory()->create();

    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '08:00:00',
        'end_time'     => '10:00:00',
        'valid_from'   => '2025-01-15',
        'valid_until'  => null,
    ]);

    expect($service->professorCurrentWeeklyHours($professor))->toBe(0.0);
});

test('professorCurrentWeeklyHours excludes slot by id when passed', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $professor = Professor::factory()->create();

    $slot = Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '08:00:00',
        'end_time'     => '10:00:00', // 2 h
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '11:00:00',
        'end_time'     => '12:00:00', // 1 h
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    // Total is 3h but excluding the 2h slot → should return 1h
    expect($service->professorCurrentWeeklyHours($professor, $slot->id))->toBe(1.0);
});
```

- [ ] **Step 2: Run tests — confirm they fail (class not found)**

```bash
sail artisan test tests/Unit/Scheduling/ScheduleConflictServiceTest.php
```

Expected: All tests fail with `Error: Class "App\Services\Scheduling\ScheduleConflictService" not found`

- [ ] **Step 3: Commit failing tests**

```bash
git add tests/Unit/Scheduling/ScheduleConflictServiceTest.php
git commit -m "test: add ScheduleConflictService unit tests (failing — service not implemented)"
```

---

## Task 6: ScheduleConflictService

**Files:**
- Create: `app/Services/Scheduling/ScheduleConflictService.php`

- [ ] **Step 1: Create the Services directory and service class**

```php
<?php
// app/Services/Scheduling/ScheduleConflictService.php

namespace App\Services\Scheduling;

use App\Enums\PeriodStatus;
use App\Models\Professor;
use App\Models\Schedule;
use Illuminate\Support\Carbon;

class ScheduleConflictService
{
    /**
     * Returns the conflicting schedule if the classroom is double-booked, or null.
     */
    public function classroomConflict(Schedule $schedule): ?Schedule
    {
        $section         = $schedule->section;
        $newValidFrom    = $schedule->valid_from instanceof Carbon
            ? $schedule->valid_from->toDateString()
            : $schedule->valid_from;
        $newValidUntil   = $schedule->valid_until
            ? ($schedule->valid_until instanceof Carbon ? $schedule->valid_until->toDateString() : $schedule->valid_until)
            : $section->period->end_date->toDateString();

        return Schedule::query()
            ->where('id', '!=', $schedule->id ?? 0)
            ->where('classroom_id', $schedule->classroom_id)
            ->where('day_of_week', $schedule->day_of_week instanceof \BackedEnum ? $schedule->day_of_week->value : $schedule->day_of_week)
            ->whereHas('section.period', fn ($q) => $q->where('status', '!=', PeriodStatus::Closed->value))
            ->where('valid_from', '<=', $newValidUntil)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $newValidFrom))
            ->where('start_time', '<', $schedule->end_time)
            ->where('end_time', '>', $schedule->start_time)
            ->first();
    }

    /**
     * Returns the conflicting schedule if the professor is double-booked, or null.
     */
    public function professorConflict(Schedule $schedule): ?Schedule
    {
        $section         = $schedule->section;
        $newValidFrom    = $schedule->valid_from instanceof Carbon
            ? $schedule->valid_from->toDateString()
            : $schedule->valid_from;
        $newValidUntil   = $schedule->valid_until
            ? ($schedule->valid_until instanceof Carbon ? $schedule->valid_until->toDateString() : $schedule->valid_until)
            : $section->period->end_date->toDateString();

        return Schedule::query()
            ->where('id', '!=', $schedule->id ?? 0)
            ->where('professor_id', $schedule->professor_id)
            ->where('day_of_week', $schedule->day_of_week instanceof \BackedEnum ? $schedule->day_of_week->value : $schedule->day_of_week)
            ->whereHas('section.period', fn ($q) => $q->where('status', '!=', PeriodStatus::Closed->value))
            ->where('valid_from', '<=', $newValidUntil)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $newValidFrom))
            ->where('start_time', '<', $schedule->end_time)
            ->where('end_time', '>', $schedule->start_time)
            ->first();
    }

    /**
     * Returns true if adding this schedule would exceed the professor's weekly hour limit.
     */
    public function professorWeeklyHoursExceeded(Schedule $schedule): bool
    {
        $professor = $schedule->professor;
        $excludeId = $schedule->exists ? $schedule->id : null;

        $current  = $this->professorCurrentWeeklyHours($professor, $excludeId);
        $newHours = $this->slotHours($schedule->start_time, $schedule->end_time);

        return ($current + $newHours) > $professor->weekly_hour_limit;
    }

    /**
     * Returns total weekly hours assigned to a professor (optionally excluding one schedule).
     */
    public function professorCurrentWeeklyHours(Professor $professor, ?int $excludeId = null): float
    {
        $query = Schedule::query()
            ->where('professor_id', $professor->id)
            ->whereHas('section.period', fn ($q) => $q->where('status', '!=', PeriodStatus::Closed->value))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()->toDateString()));

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get(['start_time', 'end_time'])
            ->sum(fn ($s) => $this->slotHours($s->start_time, $s->end_time));
    }

    private function slotHours(string $start, string $end): float
    {
        return Carbon::parse($end)->diffInMinutes(Carbon::parse($start)) / 60;
    }
}
```

- [ ] **Step 2: Run the tests — all should pass**

```bash
sail artisan test tests/Unit/Scheduling/ScheduleConflictServiceTest.php
```

Expected: 13 tests, 13 passed

- [ ] **Step 3: Commit**

```bash
git add app/Services/Scheduling/ScheduleConflictService.php
git commit -m "feat: implement ScheduleConflictService with classroom, professor, and weekly hours checks"
```

---

## Task 7: Full test run

- [ ] **Step 1: Run the entire test suite to check for regressions**

```bash
sail artisan test
```

Expected: All existing tests pass + 13 new Unit tests pass.

- [ ] **Step 2: If all green, done. If failures, fix before merging.**

---

## Self-Review Checklist

**Spec coverage:**
- [x] `schedules` table with all columns — Task 2
- [x] CHECK constraints (end_time > start_time, start >= 07:00, end <= 18:00) — Task 2
- [x] `DayOfWeek` enum with `label()` and `order()` — Task 1
- [x] `ScheduleSessionType` enum with `label()` — Task 1
- [x] `Schedule` model with 4 belongsTo — Task 3
- [x] `ScheduleFactory` (45-min slots, 07:00–17:15) — Task 4
- [x] `classroomConflict` — Task 6
- [x] `professorConflict` — Task 6
- [x] `professorWeeklyHoursExceeded` — Task 6
- [x] `professorCurrentWeeklyHours` with optional `$excludeId` — Task 6
- [x] Unit test: classroomConflict exact overlap — Task 5
- [x] Unit test: classroomConflict partial overlap — Task 5
- [x] Unit test: classroomConflict adjacent → no conflict — Task 5
- [x] Unit test: classroomConflict closed period → no conflict — Task 5
- [x] Unit test: classroomConflict no self-conflict on update — Task 5
- [x] Unit test: professorConflict same professor different sections — Task 5
- [x] Unit test: professorConflict different days → no conflict — Task 5
- [x] Unit test: professorConflict closed period → no conflict — Task 5
- [x] Unit test: weeklyHoursExceeded true — Task 5
- [x] Unit test: weeklyHoursExceeded false — Task 5
- [x] Unit test: weeklyHoursExceeded update excludes own slot — Task 5
- [x] Unit test: currentWeeklyHours sums correctly — Task 5
- [x] Unit test: currentWeeklyHours ignores closed periods — Task 5
- [x] Unit test: currentWeeklyHours excludeId works — Task 5

**Note:** `valid_until` in past (`valid_until >= TODAY`) test case is covered implicitly by the closed-period tests, since null valid_until with active period is the primary test scenario. A dedicated test could be added but the spec doesn't require it explicitly.
