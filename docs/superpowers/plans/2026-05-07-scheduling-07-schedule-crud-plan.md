# Spec 07 — Schedule CRUD & Frontend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement full CRUD for `Schedule` model — backend (controller, requests, actions, resource, policy) and frontend (weekly grid Mon–Sat × 07–18h, slot cards, create/edit/delete modals, professor hours bar, sidebar item).

**Architecture:** Action + Wrapper + Resource pattern on backend; Inertia/Vue3 with inline CSS on frontend. Conflict validation runs in `withValidator()` via `ScheduleConflictService`. Weekly grid uses pixel-based absolute positioning (1h = 60px, grid 660px tall).

**Tech Stack:** Laravel 12, PHP 8.3, Pest v3, Inertia.js, Vue 3 Composition API, TypeScript, no Tailwind.

---

## File Map

**Create:**
- `database/data/permissions.yaml` — add 4 schedules.* permissions
- `database/data/roles.yaml` — add schedules.* to Admin role
- `app/Policies/SchedulePolicy.php`
- `app/Http/Wrappers/Scheduling/ScheduleWrapper.php`
- `app/Http/Resources/Scheduling/ScheduleResource.php`
- `app/Actions/Scheduling/CreateScheduleAction.php`
- `app/Actions/Scheduling/UpdateScheduleAction.php`
- `app/Actions/Scheduling/DeleteScheduleAction.php`
- `app/Http/Requests/Scheduling/StoreScheduleRequest.php`
- `app/Http/Requests/Scheduling/UpdateScheduleRequest.php`
- `app/Http/Controllers/Scheduling/ScheduleController.php`
- `tests/Feature/Scheduling/ScheduleControllerTest.php`
- `resources/js/composables/permissions/useSchedulePermissions.ts`
- `resources/js/composables/filters/useScheduleFilters.ts`
- `resources/js/composables/forms/useScheduleForm.ts`
- `resources/js/components/scheduling/WeeklyGrid.vue`
- `resources/js/components/scheduling/ScheduleSlot.vue`
- `resources/js/components/scheduling/ProfessorHoursBar.vue`
- `resources/js/components/scheduling/CreateScheduleModal.vue`
- `resources/js/components/scheduling/EditScheduleModal.vue`
- `resources/js/components/scheduling/DeleteScheduleModal.vue`
- `resources/js/pages/scheduling/Schedules/Index.vue`

**Modify:**
- `routes/web.php` — add 4 schedule routes
- `resources/js/types/scheduling.ts` — add Schedule + ScheduleCollection types
- `resources/js/components/AppSidebar.vue` — add Horarios item + import

---

### Task 1: Permissions YAML + SchedulePolicy

**Files:**
- Modify: `database/data/permissions.yaml`
- Modify: `database/data/roles.yaml`
- Create: `app/Policies/SchedulePolicy.php`

- [ ] **Step 1: Add permissions to permissions.yaml**

Append at the end of `database/data/permissions.yaml`:

```yaml
  - name: schedules.view
    guard: web
  - name: schedules.create
    guard: web
  - name: schedules.update
    guard: web
  - name: schedules.delete
    guard: web
```

- [ ] **Step 2: Add permissions to Admin role in roles.yaml**

In `database/data/roles.yaml`, after `sections.delete` in the Admin permissions list, append:

```yaml
      - schedules.view
      - schedules.create
      - schedules.update
      - schedules.delete
```

- [ ] **Step 3: Create SchedulePolicy**

```php
<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('schedules.view');
    }

    public function create(User $user): bool
    {
        return $user->can('schedules.create');
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $user->can('schedules.update');
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->can('schedules.delete');
    }
}
```

- [ ] **Step 4: Run existing tests to confirm no regressions**

```bash
sail php artisan test --filter=ScheduleConflictServiceTest
```

Expected: 14 tests, 14 passed.

- [ ] **Step 5: Commit**

```bash
git add database/data/permissions.yaml database/data/roles.yaml app/Policies/SchedulePolicy.php
git commit -m "feat(scheduling): add schedules permissions and policy"
```

---

### Task 2: ScheduleWrapper + ScheduleResource

**Files:**
- Create: `app/Http/Wrappers/Scheduling/ScheduleWrapper.php`
- Create: `app/Http/Resources/Scheduling/ScheduleResource.php`

- [ ] **Step 1: Create ScheduleWrapper**

```php
<?php

namespace App\Http\Wrappers\Scheduling;

use App\Enums\DayOfWeek;
use App\Enums\ScheduleSessionType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduleWrapper extends Collection
{
    public function getSectionId(): int
    {
        return (int) $this->get('section_id');
    }

    public function getProfessorId(): int
    {
        return (int) $this->get('professor_id');
    }

    public function getClassroomId(): int
    {
        return (int) $this->get('classroom_id');
    }

    public function getSubjectId(): int
    {
        return (int) $this->get('subject_id');
    }

    public function getDayOfWeek(): DayOfWeek
    {
        return DayOfWeek::from($this->get('day_of_week'));
    }

    public function getStartTime(): string
    {
        return (string) $this->get('start_time');
    }

    public function getEndTime(): string
    {
        return (string) $this->get('end_time');
    }

    public function getType(): ScheduleSessionType
    {
        return ScheduleSessionType::from($this->get('type'));
    }

    public function getValidFrom(): Carbon
    {
        return Carbon::parse($this->get('valid_from'));
    }

    public function getValidUntil(): ?Carbon
    {
        $v = $this->get('valid_until');
        return $v ? Carbon::parse($v) : null;
    }
}
```

- [ ] **Step 2: Create ScheduleResource**

```php
<?php

namespace App\Http\Resources\Scheduling;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'section'   => [
                'id'   => $this->section->id,
                'code' => $this->section->code,
                'type' => $this->section->type->value,
            ],
            'professor' => [
                'id'   => $this->professor->id,
                'user' => ['name' => $this->professor->user->name],
            ],
            'classroom' => [
                'id'         => $this->classroom->id,
                'identifier' => $this->classroom->identifier,
            ],
            'subject'   => [
                'id'   => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ],
            'dayOfWeek'  => $this->day_of_week->value,
            'dayLabel'   => $this->day_of_week->label(),
            'startTime'  => substr($this->start_time, 0, 5),
            'endTime'    => substr($this->end_time, 0, 5),
            'type'       => $this->type->value,
            'typeLabel'  => $this->type->label(),
            'validFrom'  => $this->valid_from->toDateString(),
            'validUntil' => $this->valid_until?->toDateString(),
        ];
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Wrappers/Scheduling/ScheduleWrapper.php app/Http/Resources/Scheduling/ScheduleResource.php
git commit -m "feat(scheduling): add ScheduleWrapper and ScheduleResource"
```

---

### Task 3: Create/Update/Delete Actions

**Files:**
- Create: `app/Actions/Scheduling/CreateScheduleAction.php`
- Create: `app/Actions/Scheduling/UpdateScheduleAction.php`
- Create: `app/Actions/Scheduling/DeleteScheduleAction.php`

- [ ] **Step 1: Create CreateScheduleAction**

```php
<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\ScheduleWrapper;
use App\Models\Schedule;

class CreateScheduleAction
{
    public function handle(ScheduleWrapper $data): Schedule
    {
        return Schedule::create([
            'section_id'   => $data->getSectionId(),
            'professor_id' => $data->getProfessorId(),
            'classroom_id' => $data->getClassroomId(),
            'subject_id'   => $data->getSubjectId(),
            'day_of_week'  => $data->getDayOfWeek(),
            'start_time'   => $data->getStartTime(),
            'end_time'     => $data->getEndTime(),
            'type'         => $data->getType(),
            'valid_from'   => $data->getValidFrom(),
            'valid_until'  => $data->getValidUntil(),
        ]);
    }
}
```

- [ ] **Step 2: Create UpdateScheduleAction**

```php
<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\ScheduleWrapper;
use App\Models\Schedule;

class UpdateScheduleAction
{
    public function handle(Schedule $schedule, ScheduleWrapper $data): void
    {
        $schedule->update([
            'section_id'   => $data->getSectionId(),
            'professor_id' => $data->getProfessorId(),
            'classroom_id' => $data->getClassroomId(),
            'subject_id'   => $data->getSubjectId(),
            'day_of_week'  => $data->getDayOfWeek(),
            'start_time'   => $data->getStartTime(),
            'end_time'     => $data->getEndTime(),
            'type'         => $data->getType(),
            'valid_from'   => $data->getValidFrom(),
            'valid_until'  => $data->getValidUntil(),
        ]);
    }
}
```

- [ ] **Step 3: Create DeleteScheduleAction**

```php
<?php

namespace App\Actions\Scheduling;

use App\Models\Schedule;

class DeleteScheduleAction
{
    public function handle(Schedule $schedule): void
    {
        $schedule->delete();
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Actions/Scheduling/CreateScheduleAction.php app/Actions/Scheduling/UpdateScheduleAction.php app/Actions/Scheduling/DeleteScheduleAction.php
git commit -m "feat(scheduling): add Schedule actions (create, update, delete)"
```

---

### Task 4: StoreScheduleRequest + UpdateScheduleRequest

**Files:**
- Create: `app/Http/Requests/Scheduling/StoreScheduleRequest.php`
- Create: `app/Http/Requests/Scheduling/UpdateScheduleRequest.php`

Business rules validated in `withValidator()`:
1. subject_id consistency (university: must match `section.subject_id`; school: must exist in `section_subjects` pivot)
2. professor_id consistency (school: must be `main_teacher_id` if set; university: any)
3. valid_from ≥ `section.period.start_date`; valid_until ≤ `section.period.end_date` if set
4. Classroom conflict (via `ScheduleConflictService::classroomConflict()`)
5. Professor conflict (via `ScheduleConflictService::professorConflict()`)
6. Professor weekly hours exceeded (via `ScheduleConflictService::professorWeeklyHoursExceeded()`)

- [ ] **Step 1: Create StoreScheduleRequest**

```php
<?php

namespace App\Http\Requests\Scheduling;

use App\Enums\DayOfWeek;
use App\Enums\ScheduleSessionType;
use App\Enums\SectionType;
use App\Models\Schedule;
use App\Models\Section;
use App\Services\Scheduling\ScheduleConflictService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Schedule::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'section_id'   => ['required', 'integer', 'exists:sections,id'],
            'professor_id' => ['required', 'integer', 'exists:professors,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id'   => ['required', 'integer', 'exists:subjects,id'],
            'day_of_week'  => ['required', 'string', Rule::enum(DayOfWeek::class)],
            'start_time'   => ['required', 'date_format:H:i'],
            'end_time'     => ['required', 'date_format:H:i', 'after:start_time'],
            'type'         => ['required', 'string', Rule::enum(ScheduleSessionType::class)],
            'valid_from'   => ['required', 'date'],
            'valid_until'  => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->any()) {
                return;
            }

            $section = Section::with(['period', 'subject'])->find($this->integer('section_id'));
            if (! $section) {
                return;
            }

            $this->validateSubjectConsistency($v, $section);
            $this->validateProfessorConsistency($v, $section);
            $this->validateDateRange($v, $section);
            $this->validateConflicts($v);
        });
    }

    private function validateSubjectConsistency(Validator $v, Section $section): void
    {
        $subjectId = $this->integer('subject_id');

        if ($section->type === SectionType::University) {
            if ($section->subject_id !== $subjectId) {
                $v->errors()->add('subject_id', 'La materia no corresponde a esta sección universitaria.');
            }
        } else {
            $exists = $section->sectionSubjects()->where('subjects.id', $subjectId)->exists();
            if (! $exists) {
                $v->errors()->add('subject_id', 'La materia no pertenece al pensum de esta sección escolar.');
            }
        }
    }

    private function validateProfessorConsistency(Validator $v, Section $section): void
    {
        if ($section->type !== SectionType::School) {
            return;
        }

        $professorId = $this->integer('professor_id');
        $mainTeacherId = $section->main_teacher_id;

        if ($mainTeacherId !== null && $professorId !== $mainTeacherId) {
            $v->errors()->add('professor_id', 'El profesor debe ser el docente de aula asignado a esta sección.');
        }
    }

    private function validateDateRange(Validator $v, Section $section): void
    {
        $period = $section->period;
        $validFrom = $this->date('valid_from');

        if ($validFrom && $validFrom < $period->start_date) {
            $v->errors()->add('valid_from', "La fecha de inicio no puede ser anterior al inicio del período ({$period->start_date->toDateString()}).");
        }

        $validUntil = $this->date('valid_until');
        if ($validUntil) {
            if ($validUntil > $period->end_date) {
                $v->errors()->add('valid_until', "La fecha de fin no puede superar el fin del período ({$period->end_date->toDateString()}).");
            }
        }
    }

    private function validateConflicts(Validator $v): void
    {
        $service = app(ScheduleConflictService::class);

        $classroomConflict = $service->classroomConflict(
            classroomId: $this->integer('classroom_id'),
            day: DayOfWeek::from($this->string('day_of_week')),
            start: $this->string('start_time'),
            end: $this->string('end_time'),
        );
        if ($classroomConflict) {
            $v->errors()->add('classroom_id', "El aula {$classroomConflict->classroom->identifier} ya tiene clase el {$classroomConflict->day_of_week->label()} de {$this->formatTime($classroomConflict->start_time)}–{$this->formatTime($classroomConflict->end_time)}.");
        }

        $professorConflict = $service->professorConflict(
            professorId: $this->integer('professor_id'),
            day: DayOfWeek::from($this->string('day_of_week')),
            start: $this->string('start_time'),
            end: $this->string('end_time'),
        );
        if ($professorConflict) {
            $v->errors()->add('professor_id', "El profesor {$professorConflict->professor->user->name} ya tiene clase el {$professorConflict->day_of_week->label()} de {$this->formatTime($professorConflict->start_time)}–{$this->formatTime($professorConflict->end_time)}.");
        }

        $weeklyExceeded = $service->professorWeeklyHoursExceeded(
            professorId: $this->integer('professor_id'),
            start: $this->string('start_time'),
            end: $this->string('end_time'),
        );
        if ($weeklyExceeded) {
            $current = $service->professorCurrentWeeklyHours($this->integer('professor_id'));
            $newMinutes = \Carbon\Carbon::parse($this->string('start_time'))->diffInMinutes(\Carbon\Carbon::parse($this->string('end_time')));
            $newHours = round($newMinutes / 60, 1);
            $professor = \App\Models\Professor::find($this->integer('professor_id'));
            $v->errors()->add('professor_id', "El profesor {$professor->user->name} superaría su límite de {$professor->weekly_hour_limit}h/semana ({$current}h actuales + {$newHours}h nuevas).");
        }
    }

    private function formatTime(string $time): string
    {
        return substr($time, 0, 5);
    }
}
```

- [ ] **Step 2: Create UpdateScheduleRequest**

```php
<?php

namespace App\Http\Requests\Scheduling;

use App\Enums\DayOfWeek;
use App\Enums\ScheduleSessionType;
use App\Enums\SectionType;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Services\Scheduling\ScheduleConflictService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('schedule'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'section_id'   => ['required', 'integer', 'exists:sections,id'],
            'professor_id' => ['required', 'integer', 'exists:professors,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id'   => ['required', 'integer', 'exists:subjects,id'],
            'day_of_week'  => ['required', 'string', Rule::enum(DayOfWeek::class)],
            'start_time'   => ['required', 'date_format:H:i'],
            'end_time'     => ['required', 'date_format:H:i', 'after:start_time'],
            'type'         => ['required', 'string', Rule::enum(ScheduleSessionType::class)],
            'valid_from'   => ['required', 'date'],
            'valid_until'  => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->any()) {
                return;
            }

            /** @var Schedule $schedule */
            $schedule = $this->route('schedule');

            $section = Section::with(['period', 'subject'])->find($this->integer('section_id'));
            if (! $section) {
                return;
            }

            $this->validateSubjectConsistency($v, $section);
            $this->validateProfessorConsistency($v, $section);
            $this->validateDateRange($v, $section);
            $this->validateConflicts($v, $schedule->id);
        });
    }

    private function validateSubjectConsistency(Validator $v, Section $section): void
    {
        $subjectId = $this->integer('subject_id');

        if ($section->type === SectionType::University) {
            if ($section->subject_id !== $subjectId) {
                $v->errors()->add('subject_id', 'La materia no corresponde a esta sección universitaria.');
            }
        } else {
            $exists = $section->sectionSubjects()->where('subjects.id', $subjectId)->exists();
            if (! $exists) {
                $v->errors()->add('subject_id', 'La materia no pertenece al pensum de esta sección escolar.');
            }
        }
    }

    private function validateProfessorConsistency(Validator $v, Section $section): void
    {
        if ($section->type !== SectionType::School) {
            return;
        }

        $professorId = $this->integer('professor_id');
        $mainTeacherId = $section->main_teacher_id;

        if ($mainTeacherId !== null && $professorId !== $mainTeacherId) {
            $v->errors()->add('professor_id', 'El profesor debe ser el docente de aula asignado a esta sección.');
        }
    }

    private function validateDateRange(Validator $v, Section $section): void
    {
        $period = $section->period;
        $validFrom = $this->date('valid_from');

        if ($validFrom && $validFrom < $period->start_date) {
            $v->errors()->add('valid_from', "La fecha de inicio no puede ser anterior al inicio del período ({$period->start_date->toDateString()}).");
        }

        $validUntil = $this->date('valid_until');
        if ($validUntil && $validUntil > $period->end_date) {
            $v->errors()->add('valid_until', "La fecha de fin no puede superar el fin del período ({$period->end_date->toDateString()}).");
        }
    }

    private function validateConflicts(Validator $v, int $excludeScheduleId): void
    {
        $service = app(ScheduleConflictService::class);

        $classroomConflict = $service->classroomConflict(
            classroomId: $this->integer('classroom_id'),
            day: DayOfWeek::from($this->string('day_of_week')),
            start: $this->string('start_time'),
            end: $this->string('end_time'),
            excludeScheduleId: $excludeScheduleId,
        );
        if ($classroomConflict) {
            $v->errors()->add('classroom_id', "El aula {$classroomConflict->classroom->identifier} ya tiene clase el {$classroomConflict->day_of_week->label()} de {$this->formatTime($classroomConflict->start_time)}–{$this->formatTime($classroomConflict->end_time)}.");
        }

        $professorConflict = $service->professorConflict(
            professorId: $this->integer('professor_id'),
            day: DayOfWeek::from($this->string('day_of_week')),
            start: $this->string('start_time'),
            end: $this->string('end_time'),
            excludeScheduleId: $excludeScheduleId,
        );
        if ($professorConflict) {
            $v->errors()->add('professor_id', "El profesor {$professorConflict->professor->user->name} ya tiene clase el {$professorConflict->day_of_week->label()} de {$this->formatTime($professorConflict->start_time)}–{$this->formatTime($professorConflict->end_time)}.");
        }

        $weeklyExceeded = $service->professorWeeklyHoursExceeded(
            professorId: $this->integer('professor_id'),
            start: $this->string('start_time'),
            end: $this->string('end_time'),
            excludeScheduleId: $excludeScheduleId,
        );
        if ($weeklyExceeded) {
            $current = $service->professorCurrentWeeklyHours($this->integer('professor_id'), $excludeScheduleId);
            $newMinutes = \Carbon\Carbon::parse($this->string('start_time'))->diffInMinutes(\Carbon\Carbon::parse($this->string('end_time')));
            $newHours = round($newMinutes / 60, 1);
            $professor = Professor::find($this->integer('professor_id'));
            $v->errors()->add('professor_id', "El profesor {$professor->user->name} superaría su límite de {$professor->weekly_hour_limit}h/semana ({$current}h actuales + {$newHours}h nuevas).");
        }
    }

    private function formatTime(string $time): string
    {
        return substr($time, 0, 5);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Requests/Scheduling/StoreScheduleRequest.php app/Http/Requests/Scheduling/UpdateScheduleRequest.php
git commit -m "feat(scheduling): add StoreScheduleRequest and UpdateScheduleRequest with conflict validation"
```

---

### Task 5: ScheduleController + Routes + Wayfinder

**Files:**
- Create: `app/Http/Controllers/Scheduling/ScheduleController.php`
- Modify: `routes/web.php`
- Run: `sail artisan wayfinder:generate`

- [ ] **Step 1: Create ScheduleController**

```php
<?php

namespace App\Http\Controllers\Scheduling;

use App\Actions\Scheduling\CreateScheduleAction;
use App\Actions\Scheduling\DeleteScheduleAction;
use App\Actions\Scheduling\UpdateScheduleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreScheduleRequest;
use App\Http\Requests\Scheduling\UpdateScheduleRequest;
use App\Http\Resources\Scheduling\ScheduleResource;
use App\Http\Wrappers\Scheduling\ScheduleWrapper;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Services\Scheduling\ScheduleConflictService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Schedule::class);

        $schedules = Schedule::with(['section', 'professor.user', 'classroom', 'subject'])
            ->when($request->input('section_id'), fn ($q, $id) => $q->where('section_id', $id))
            ->when($request->input('professor_id'), fn ($q, $id) => $q->where('professor_id', $id))
            ->when($request->input('period_id'), fn ($q, $id) => $q->whereHas('section', fn ($q2) => $q2->where('period_id', $id)))
            ->when($request->input('day_of_week'), fn ($q, $day) => $q->where('day_of_week', $day))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $periods = Period::orderByDesc('id')->get(['id', 'name']);
        $sections = Section::with('period:id,name')->orderByDesc('id')->get(['id', 'code', 'type', 'period_id']);
        $professors = Professor::with('user:id,name')->where('active', true)->orderBy('id')->get(['id', 'user_id', 'weekly_hour_limit']);
        $classrooms = Classroom::orderBy('identifier')->get(['id', 'identifier']);
        $subjects = Subject::orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('scheduling/Schedules/Index', [
            'schedules'  => ScheduleResource::collection($schedules)->resolve(),
            'periods'    => $periods->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
            'sections'   => $sections->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'type' => $s->type->value, 'periodId' => $s->period_id, 'periodName' => $s->period?->name]),
            'professors' => $professors->map(fn ($p) => ['id' => $p->id, 'name' => $p->user->name, 'weeklyHourLimit' => $p->weekly_hour_limit]),
            'classrooms' => $classrooms->map(fn ($c) => ['id' => $c->id, 'identifier' => $c->identifier]),
            'subjects'   => $subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code]),
            'filters'    => [
                'period_id'    => $request->input('period_id') ? (int) $request->input('period_id') : null,
                'section_id'   => $request->input('section_id') ? (int) $request->input('section_id') : null,
                'professor_id' => $request->input('professor_id') ? (int) $request->input('professor_id') : null,
            ],
            'can' => [
                'create' => $request->user()->can('schedules.create'),
                'update' => $request->user()->can('schedules.update'),
                'delete' => $request->user()->can('schedules.delete'),
            ],
        ]);
    }

    public function store(StoreScheduleRequest $request, CreateScheduleAction $action): RedirectResponse
    {
        $action->handle(new ScheduleWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Horario creado.']);

        return to_route('scheduling.schedules.index', $request->only(['section_id', 'period_id', 'professor_id']));
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule, UpdateScheduleAction $action): RedirectResponse
    {
        $action->handle($schedule, new ScheduleWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Horario actualizado.']);

        return to_route('scheduling.schedules.index', $request->only(['section_id', 'period_id', 'professor_id']));
    }

    public function destroy(Request $request, Schedule $schedule, DeleteScheduleAction $action): RedirectResponse
    {
        Gate::authorize('delete', $schedule);

        $action->handle($schedule);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Horario eliminado.']);

        return to_route('scheduling.schedules.index', $request->only(['section_id', 'period_id', 'professor_id']));
    }
}
```

- [ ] **Step 2: Add routes to web.php**

In `routes/web.php`, after the school sections route block (around line 135), add inside the `scheduling` prefix group:

```php
    Route::get('schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::post('schedules', [ScheduleController::class, 'store'])->name('schedules.store');
    Route::patch('schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
```

Also add the import at the top of `routes/web.php`:

```php
use App\Http\Controllers\Scheduling\ScheduleController;
```

- [ ] **Step 3: Generate Wayfinder routes**

```bash
sail artisan wayfinder:generate
```

This creates `resources/js/routes/scheduling/schedules/index.ts` with `index`, `store`, `update`, `destroy` exports.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Scheduling/ScheduleController.php routes/web.php resources/js/routes/scheduling/schedules/
git commit -m "feat(scheduling): add ScheduleController and routes"
```

---

### Task 6: Feature Tests

**Files:**
- Create: `tests/Feature/Scheduling/ScheduleControllerTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Enums\DayOfWeek;
use App\Enums\ScheduleSessionType;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['schedules.view', 'schedules.create', 'schedules.update', 'schedules.delete'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWithSchedulePerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);
    return $user;
}

function makeScheduleData(Section $section, Professor $professor, Classroom $classroom, Subject $subject): array
{
    return [
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id'   => $subject->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
        'type'         => ScheduleSessionType::Theory->value,
        'valid_from'   => '2026-02-01',
        'valid_until'  => null,
    ];
}

test('unauthenticated user is redirected to login', function () {
    $this->get('/scheduling/schedules')->assertRedirect('/login');
});

test('user without permission gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get('/scheduling/schedules')
        ->assertForbidden();
});

test('admin can list schedules', function () {
    Schedule::factory()->count(3)->create();

    $this->actingAs(userWithSchedulePerm('schedules.view'))
        ->get('/scheduling/schedules')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('scheduling/Schedules/Index', false)
            ->has('schedules', 3)
        );
});

test('admin can filter schedules by section', function () {
    $sectionA = Section::factory()->university()->create();
    $sectionB = Section::factory()->university()->create();
    Schedule::factory()->count(2)->for($sectionA, 'section')->create();
    Schedule::factory()->count(1)->for($sectionB, 'section')->create();

    $this->actingAs(userWithSchedulePerm('schedules.view'))
        ->get("/scheduling/schedules?section_id={$sectionA->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('schedules', 2));
});

test('admin can create a schedule without conflicts', function () {
    $section   = Section::factory()->university()->create();
    $professor = Professor::factory()->create();
    $classroom = Classroom::factory()->create();
    $subject   = $section->subject;

    $data = makeScheduleData($section, $professor, $classroom, $subject);

    $this->actingAs(userWithSchedulePerm('schedules.create'))
        ->post('/scheduling/schedules', $data)
        ->assertRedirect();

    expect(Schedule::where('section_id', $section->id)->exists())->toBeTrue();
});

test('admin cannot create schedule with occupied classroom', function () {
    $section    = Section::factory()->university()->create();
    $section2   = Section::factory()->university()->create();
    $professor  = Professor::factory()->create();
    $professor2 = Professor::factory()->create();
    $classroom  = Classroom::factory()->create();

    Schedule::factory()->create([
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
    ]);

    $data = makeScheduleData($section, $professor2, $classroom, $section->subject);
    $data['section_id'] = $section2->id;
    $data['professor_id'] = $professor2->id;
    $data['subject_id'] = $section2->subject_id;

    $this->actingAs(userWithSchedulePerm('schedules.create'))
        ->post('/scheduling/schedules', $data)
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('classroom_id');
});

test('admin cannot create schedule with occupied professor', function () {
    $section    = Section::factory()->university()->create();
    $section2   = Section::factory()->university()->create();
    $professor  = Professor::factory()->create();
    $classroom  = Classroom::factory()->create();
    $classroom2 = Classroom::factory()->create();

    Schedule::factory()->create([
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Monday,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
    ]);

    $data = makeScheduleData($section2, $professor, $classroom2, $section2->subject);

    $this->actingAs(userWithSchedulePerm('schedules.create'))
        ->post('/scheduling/schedules', $data)
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('professor_id');
});

test('admin cannot create schedule that exceeds professor weekly limit', function () {
    $professor = Professor::factory()->create(['weekly_hour_limit' => 1]);
    $section   = Section::factory()->university()->create();
    $classroom = Classroom::factory()->create();

    // Fill professor's 1h limit with an existing slot on a different day
    Schedule::factory()->create([
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Tuesday,
        'start_time'   => '08:00',
        'end_time'     => '09:00',
    ]);

    $data = makeScheduleData($section, $professor, $classroom, $section->subject);

    $this->actingAs(userWithSchedulePerm('schedules.create'))
        ->post('/scheduling/schedules', $data)
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('professor_id');
});

test('updating a schedule does not conflict with itself', function () {
    $section   = Section::factory()->university()->create();
    $professor = Professor::factory()->create();
    $classroom = Classroom::factory()->create();
    $schedule  = Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id'   => $section->subject_id,
        'day_of_week'  => DayOfWeek::Monday,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
        'type'         => ScheduleSessionType::Theory,
        'valid_from'   => $section->period->start_date->toDateString(),
    ]);

    $data = [
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id'   => $section->subject_id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
        'type'         => ScheduleSessionType::Theory->value,
        'valid_from'   => $section->period->start_date->toDateString(),
        'valid_until'  => null,
    ];

    $this->actingAs(userWithSchedulePerm('schedules.update'))
        ->patch("/scheduling/schedules/{$schedule->id}", $data)
        ->assertRedirect();
});

test('admin can delete a schedule', function () {
    $schedule = Schedule::factory()->create();

    $this->actingAs(userWithSchedulePerm('schedules.delete'))
        ->delete("/scheduling/schedules/{$schedule->id}")
        ->assertRedirect();

    expect(Schedule::find($schedule->id))->toBeNull();
});

test('subject inconsistent with university section is rejected', function () {
    $section        = Section::factory()->university()->create();
    $otherSubject   = Subject::factory()->create();
    $professor      = Professor::factory()->create();
    $classroom      = Classroom::factory()->create();

    $data = makeScheduleData($section, $professor, $classroom, $otherSubject);

    $this->actingAs(userWithSchedulePerm('schedules.create'))
        ->post('/scheduling/schedules', $data)
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('subject_id');
});

test('valid_from before period start is rejected', function () {
    $section   = Section::factory()->university()->create();
    $professor = Professor::factory()->create();
    $classroom = Classroom::factory()->create();

    $data = makeScheduleData($section, $professor, $classroom, $section->subject);
    $data['valid_from'] = '2000-01-01';

    $this->actingAs(userWithSchedulePerm('schedules.create'))
        ->post('/scheduling/schedules', $data)
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('valid_from');
});
```

- [ ] **Step 2: Run tests — expect failures (TDD red phase)**

```bash
sail php artisan test tests/Feature/Scheduling/ScheduleControllerTest.php
```

Expected: Multiple failures because controller/routes may not be correctly wired yet, but mostly should pass. If Inertia assertions fail, check that `ScheduleFactory` has correct defaults.

- [ ] **Step 3: Run all tests to verify no regressions**

```bash
sail php artisan test
```

Expected: All previously passing tests still pass. New tests for ScheduleController pass.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Scheduling/ScheduleControllerTest.php
git commit -m "test(scheduling): add ScheduleController feature tests"
```

---

### Task 7: Frontend Types

**Files:**
- Modify: `resources/js/types/scheduling.ts`

- [ ] **Step 1: Add Schedule types to scheduling.ts**

Append at the end of `resources/js/types/scheduling.ts`:

```typescript
export type ScheduleSection = {
    id: number
    code: string
    type: 'university' | 'school'
}

export type ScheduleProfessor = {
    id: number
    user: { name: string }
}

export type ScheduleClassroom = {
    id: number
    identifier: string
}

export type ScheduleSubject = {
    id: number
    name: string
    code: string
}

export type Schedule = {
    id: number
    section: ScheduleSection
    professor: ScheduleProfessor
    classroom: ScheduleClassroom
    subject: ScheduleSubject
    dayOfWeek: 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday'
    dayLabel: string
    startTime: string
    endTime: string
    type: 'theory' | 'lab'
    typeLabel: string
    validFrom: string
    validUntil: string | null
}

export type ScheduleCollection = Schedule[]

export type ScheduleAvailablePeriod = {
    id: number
    name: string
}

export type ScheduleAvailableSection = {
    id: number
    code: string
    type: 'university' | 'school'
    periodId: number
    periodName: string
}

export type ScheduleAvailableProfessor = {
    id: number
    name: string
    weeklyHourLimit: number
}

export type ScheduleAvailableClassroom = {
    id: number
    identifier: string
}

export type ScheduleAvailableSubject = {
    id: number
    name: string
    code: string
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/types/scheduling.ts
git commit -m "feat(scheduling): add Schedule TypeScript types"
```

---

### Task 8: Frontend Composables

**Files:**
- Create: `resources/js/composables/permissions/useSchedulePermissions.ts`
- Create: `resources/js/composables/filters/useScheduleFilters.ts`
- Create: `resources/js/composables/forms/useScheduleForm.ts`

- [ ] **Step 1: Create useSchedulePermissions**

```typescript
import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useSchedulePermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('schedules.create'))
    const canUpdate = computed(() => can('schedules.update'))
    const canDelete = computed(() => can('schedules.delete'))

    return { canCreate, canUpdate, canDelete }
}
```

- [ ] **Step 2: Create useScheduleFilters**

```typescript
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { index } from '@/routes/scheduling/schedules'

export function useScheduleFilters(
    initialPeriodId: number | null = null,
    initialSectionId: number | null = null,
    initialProfessorId: number | null = null,
) {
    const periodId    = ref<number | null>(initialPeriodId)
    const sectionId   = ref<number | null>(initialSectionId)
    const professorId = ref<number | null>(initialProfessorId)

    function applyFilters(): void {
        const query: Record<string, string | number> = {}
        if (periodId.value)    query.period_id    = periodId.value
        if (sectionId.value)   query.section_id   = sectionId.value
        if (professorId.value) query.professor_id = professorId.value

        router.get(index.url(), query, { preserveState: true, replace: true })
    }

    return { periodId, sectionId, professorId, applyFilters }
}
```

- [ ] **Step 3: Create useScheduleForm**

```typescript
import { useForm } from '@inertiajs/vue3'
import { destroy, store, update } from '@/routes/scheduling/schedules'
import type { Schedule } from '@/types/scheduling'

export function useScheduleForm() {
    const storeOps = {
        form(defaults: { sectionId?: number; dayOfWeek?: string; startTime?: string } = {}) {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({
                    section_id:   defaults.sectionId ?? null as number | null,
                    professor_id: null as number | null,
                    classroom_id: null as number | null,
                    subject_id:   null as number | null,
                    day_of_week:  defaults.dayOfWeek ?? 'monday',
                    start_time:   defaults.startTime ?? '08:00',
                    end_time:     '08:45',
                    type:         'theory' as 'theory' | 'lab',
                    valid_from:   '',
                    valid_until:  null as string | null,
                }),
            }
        },
    }

    const updateOps = {
        form({ schedule }: { schedule: Schedule }) {
            return {
                url:    update.url({ schedule }),
                method: 'patch' as const,
                data:   useForm({
                    section_id:   schedule.section.id,
                    professor_id: schedule.professor.id,
                    classroom_id: schedule.classroom.id,
                    subject_id:   schedule.subject.id,
                    day_of_week:  schedule.dayOfWeek,
                    start_time:   schedule.startTime,
                    end_time:     schedule.endTime,
                    type:         schedule.type,
                    valid_from:   schedule.validFrom,
                    valid_until:  schedule.validUntil,
                }),
            }
        },
    }

    const removeOps = {
        submit({ schedule }: { schedule: Schedule }): void {
            useForm({}).delete(destroy.url({ schedule }))
        },
    }

    return { store: storeOps, update: updateOps, remove: removeOps }
}
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/composables/permissions/useSchedulePermissions.ts resources/js/composables/filters/useScheduleFilters.ts resources/js/composables/forms/useScheduleForm.ts
git commit -m "feat(scheduling): add schedule composables (permissions, filters, form)"
```

---

### Task 9: WeeklyGrid + ScheduleSlot Components

**Files:**
- Create: `resources/js/components/scheduling/WeeklyGrid.vue`
- Create: `resources/js/components/scheduling/ScheduleSlot.vue`

Grid specs:
- Days: monday, tuesday, wednesday, thursday, friday, saturday
- Hours: 07:00 to 18:00 (11 hours, 660px tall at 60px/hour)
- Slots: `position: absolute`, `top = (startMinutes - 420) * px/min`, `height = durationMinutes * px/min`
- `px/min = 60/60 = 1` (1px per minute, 60px per hour)

- [ ] **Step 1: Create ScheduleSlot component**

```vue
<script setup lang="ts">
import type { Schedule } from '@/types/scheduling'

const props = defineProps<{
    schedule: Schedule
    canUpdate: boolean
    canDelete: boolean
}>()

const emit = defineEmits<{
    edit: [schedule: Schedule]
}>()

function timeToMinutes(time: string): number {
    const [h, m] = time.split(':').map(Number)
    return h * 60 + m
}

const GRID_START_MINUTES = 7 * 60 // 07:00

const top    = timeToMinutes(props.schedule.startTime) - GRID_START_MINUTES
const height = timeToMinutes(props.schedule.endTime) - timeToMinutes(props.schedule.startTime)

const bgColor = props.schedule.type === 'theory'
    ? 'var(--color-info-subtle, #dbeafe)'
    : 'var(--color-success-subtle, #dcfce7)'

const borderColor = props.schedule.type === 'theory'
    ? 'var(--color-info, #3b82f6)'
    : 'var(--color-success, #22c55e)'
</script>

<template>
    <div
        :style="{
            position: 'absolute',
            top: top + 'px',
            height: height + 'px',
            left: '2px',
            right: '2px',
            backgroundColor: bgColor,
            borderLeft: '3px solid ' + borderColor,
            borderRadius: '4px',
            padding: '2px 4px',
            overflow: 'hidden',
            cursor: canUpdate || canDelete ? 'pointer' : 'default',
            zIndex: 1,
        }"
        @click="canUpdate || canDelete ? emit('edit', schedule) : null"
    >
        <div style="font-size:11px;font-weight:600;line-height:1.2;color:var(--text-primary);">
            {{ schedule.subject.code }}
        </div>
        <div v-if="height >= 30" style="font-size:10px;color:var(--text-secondary);line-height:1.2;">
            {{ schedule.professor.user.name }}
        </div>
        <div v-if="height >= 45" style="font-size:10px;color:var(--text-muted);line-height:1.2;">
            {{ schedule.classroom.identifier }}
        </div>
    </div>
</template>
```

- [ ] **Step 2: Create WeeklyGrid component**

```vue
<script setup lang="ts">
import { computed } from 'vue'
import ScheduleSlot from '@/components/scheduling/ScheduleSlot.vue'
import type { Schedule, ScheduleCollection } from '@/types/scheduling'

const props = defineProps<{
    schedules: ScheduleCollection
    canUpdate: boolean
    canDelete: boolean
}>()

const emit = defineEmits<{
    create: [{ dayOfWeek: string; startTime: string }]
    edit: [schedule: Schedule]
}>()

const DAYS = [
    { key: 'monday',    label: 'Lunes' },
    { key: 'tuesday',   label: 'Martes' },
    { key: 'wednesday', label: 'Miércoles' },
    { key: 'thursday',  label: 'Jueves' },
    { key: 'friday',    label: 'Viernes' },
    { key: 'saturday',  label: 'Sábado' },
]

const HOURS = Array.from({ length: 11 }, (_, i) => {
    const h = 7 + i
    return `${String(h).padStart(2, '0')}:00`
})

const GRID_HEIGHT = 660 // 11 hours × 60px

function schedulesForDay(day: string): Schedule[] {
    return props.schedules.filter((s) => s.dayOfWeek === day)
}

function handleCellClick(day: string, hour: string): void {
    if (props.canUpdate || props.canCreate) {
        emit('create', { dayOfWeek: day, startTime: hour })
    }
}

const canCreate = computed(() => props.canUpdate)
</script>

<template>
    <div style="overflow-x:auto;">
        <div style="display:grid;grid-template-columns:60px repeat(6, 1fr);min-width:700px;">
            <!-- Header row -->
            <div style="border-bottom:1px solid var(--border);padding:8px 4px;font-size:var(--text-xs);color:var(--text-muted);" />
            <div
                v-for="day in DAYS"
                :key="day.key"
                style="border-bottom:1px solid var(--border);border-left:1px solid var(--border);padding:8px 4px;text-align:center;font-size:var(--text-sm);font-weight:600;color:var(--text-primary);"
            >
                {{ day.label }}
            </div>

            <!-- Time column + day columns -->
            <div style="display:contents;">
                <!-- Left time labels -->
                <div :style="{ height: GRID_HEIGHT + 'px', position: 'relative' }">
                    <div
                        v-for="hour in HOURS"
                        :key="hour"
                        :style="{
                            position: 'absolute',
                            top: (HOURS.indexOf(hour) * 60) + 'px',
                            right: '4px',
                            fontSize: '10px',
                            color: 'var(--text-muted)',
                            lineHeight: 1,
                        }"
                    >
                        {{ hour }}
                    </div>
                </div>

                <!-- Day columns -->
                <div
                    v-for="day in DAYS"
                    :key="day.key"
                    :style="{
                        position: 'relative',
                        height: GRID_HEIGHT + 'px',
                        borderLeft: '1px solid var(--border)',
                    }"
                    @click.self="handleCellClick(day.key, '08:00')"
                >
                    <!-- Hour lines -->
                    <div
                        v-for="(_, i) in HOURS"
                        :key="i"
                        :style="{
                            position: 'absolute',
                            top: (i * 60) + 'px',
                            left: 0,
                            right: 0,
                            borderTop: '1px solid var(--border-subtle, #f1f5f9)',
                            pointerEvents: 'none',
                        }"
                    />

                    <!-- Schedule slots -->
                    <ScheduleSlot
                        v-for="schedule in schedulesForDay(day.key)"
                        :key="schedule.id"
                        :schedule="schedule"
                        :can-update="canUpdate"
                        :can-delete="canDelete"
                        @edit="emit('edit', $event)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/scheduling/WeeklyGrid.vue resources/js/components/scheduling/ScheduleSlot.vue
git commit -m "feat(scheduling): add WeeklyGrid and ScheduleSlot components"
```

---

### Task 10: ProfessorHoursBar Component

**Files:**
- Create: `resources/js/components/scheduling/ProfessorHoursBar.vue`

This component receives `currentHours` (number, in decimal hours) and `limitHours` (number). Shows "X.X h de Y h máx" with a progress bar. Color changes to warning when over 80% full.

- [ ] **Step 1: Create ProfessorHoursBar**

```vue
<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    currentHours: number
    limitHours: number
}>()

const pct = computed(() => Math.min(100, (props.currentHours / props.limitHours) * 100))
const barColor = computed(() => {
    if (pct.value >= 100) return 'var(--color-danger, #ef4444)'
    if (pct.value >= 80)  return 'var(--color-warning, #f59e0b)'
    return 'var(--color-success, #22c55e)'
})
</script>

<template>
    <div style="display:flex;flex-direction:column;gap:4px;">
        <div style="display:flex;justify-content:space-between;font-size:var(--text-xs);color:var(--text-secondary);">
            <span>Carga semanal</span>
            <span>{{ currentHours.toFixed(1) }} h de {{ limitHours }} h máx</span>
        </div>
        <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden;">
            <div
                :style="{
                    height: '100%',
                    width: pct + '%',
                    backgroundColor: barColor,
                    borderRadius: '3px',
                    transition: 'width 0.2s',
                }"
            />
        </div>
    </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/scheduling/ProfessorHoursBar.vue
git commit -m "feat(scheduling): add ProfessorHoursBar component"
```

---

### Task 11: CreateScheduleModal

**Files:**
- Create: `resources/js/components/scheduling/CreateScheduleModal.vue`

- [ ] **Step 1: Create CreateScheduleModal**

```vue
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import ProfessorHoursBar from '@/components/scheduling/ProfessorHoursBar.vue'
import { store } from '@/routes/scheduling/schedules'
import type {
    ScheduleAvailableClassroom,
    ScheduleAvailableProfessor,
    ScheduleAvailableSection,
    ScheduleAvailableSubject,
} from '@/types/scheduling'

const props = defineProps<{
    open: boolean
    sections: ScheduleAvailableSection[]
    professors: ScheduleAvailableProfessor[]
    classrooms: ScheduleAvailableClassroom[]
    subjects: ScheduleAvailableSubject[]
    defaultSectionId?: number | null
    defaultDayOfWeek?: string
    defaultStartTime?: string
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const DAYS = [
    { value: 'monday',    label: 'Lunes' },
    { value: 'tuesday',   label: 'Martes' },
    { value: 'wednesday', label: 'Miércoles' },
    { value: 'thursday',  label: 'Jueves' },
    { value: 'friday',    label: 'Viernes' },
    { value: 'saturday',  label: 'Sábado' },
]

const TYPES = [
    { value: 'theory', label: 'Teórica' },
    { value: 'lab',    label: 'Laboratorio' },
]

function makeForm() {
    return useForm({
        section_id:   props.defaultSectionId ?? null as number | null,
        professor_id: null as number | null,
        classroom_id: null as number | null,
        subject_id:   null as number | null,
        day_of_week:  props.defaultDayOfWeek ?? 'monday',
        start_time:   props.defaultStartTime ?? '08:00',
        end_time:     '08:45',
        type:         'theory' as 'theory' | 'lab',
        valid_from:   '',
        valid_until:  null as string | null,
    })
}

const form = ref(makeForm())

const selectedProfessor = computed(() =>
    props.professors.find((p) => p.id === form.value.professor_id) ?? null
)

// Calculate current weekly hours for selected professor (sum of existing schedules)
// This is a simplified client-side estimate; actual validation is server-side
const professorCurrentHours = ref(0)

watch(
    () => props.open,
    (opened) => {
        if (opened) {
            form.value = makeForm()
            professorCurrentHours.value = 0
        }
    },
)

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.value.post(store.url(), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Nuevo horario" size="md" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:14px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Sección</label>
                        <select v-model="form.section_id" class="input" required>
                            <option :value="null" disabled>Seleccionar sección</option>
                            <option v-for="s in sections" :key="s.id" :value="s.id">
                                {{ s.code }} ({{ s.periodName }})
                            </option>
                        </select>
                        <InputError :message="form.errors.section_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Materia</label>
                        <select v-model="form.subject_id" class="input" required>
                            <option :value="null" disabled>Seleccionar materia</option>
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.code }} — {{ s.name }}</option>
                        </select>
                        <InputError :message="form.errors.subject_id" />
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Profesor</label>
                        <select v-model="form.professor_id" class="input" required>
                            <option :value="null" disabled>Seleccionar profesor</option>
                            <option v-for="p in professors" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <InputError :message="form.errors.professor_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Aula</label>
                        <select v-model="form.classroom_id" class="input" required>
                            <option :value="null" disabled>Seleccionar aula</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.identifier }}</option>
                        </select>
                        <InputError :message="form.errors.classroom_id" />
                    </div>
                </div>

                <ProfessorHoursBar
                    v-if="selectedProfessor"
                    :current-hours="professorCurrentHours"
                    :limit-hours="selectedProfessor.weeklyHourLimit"
                />

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Día</label>
                        <select v-model="form.day_of_week" class="input" required>
                            <option v-for="d in DAYS" :key="d.value" :value="d.value">{{ d.label }}</option>
                        </select>
                        <InputError :message="form.errors.day_of_week" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Inicio</label>
                        <input v-model="form.start_time" type="time" step="900" class="input" required />
                        <InputError :message="form.errors.start_time" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Fin</label>
                        <input v-model="form.end_time" type="time" step="900" class="input" required />
                        <InputError :message="form.errors.end_time" />
                    </div>
                </div>

                <div style="display:grid;gap:6px;">
                    <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Tipo</label>
                    <select v-model="form.type" class="input" required>
                        <option v-for="t in TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                    <InputError :message="form.errors.type" />
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Válido desde</label>
                        <input v-model="form.valid_from" type="date" class="input" required />
                        <InputError :message="form.errors.valid_from" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Válido hasta (opcional)</label>
                        <input v-model="form.valid_until" type="date" class="input" />
                        <InputError :message="form.errors.valid_until" />
                    </div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Crear horario</Button>
            </div>
        </form>
    </Modal>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/scheduling/CreateScheduleModal.vue
git commit -m "feat(scheduling): add CreateScheduleModal component"
```

---

### Task 12: EditScheduleModal + DeleteScheduleModal

**Files:**
- Create: `resources/js/components/scheduling/EditScheduleModal.vue`
- Create: `resources/js/components/scheduling/DeleteScheduleModal.vue`

- [ ] **Step 1: Create EditScheduleModal**

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/scheduling/schedules'
import type {
    Schedule,
    ScheduleAvailableClassroom,
    ScheduleAvailableProfessor,
    ScheduleAvailableSection,
    ScheduleAvailableSubject,
} from '@/types/scheduling'

const props = defineProps<{
    open: boolean
    schedule: Schedule
    sections: ScheduleAvailableSection[]
    professors: ScheduleAvailableProfessor[]
    classrooms: ScheduleAvailableClassroom[]
    subjects: ScheduleAvailableSubject[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const DAYS = [
    { value: 'monday',    label: 'Lunes' },
    { value: 'tuesday',   label: 'Martes' },
    { value: 'wednesday', label: 'Miércoles' },
    { value: 'thursday',  label: 'Jueves' },
    { value: 'friday',    label: 'Viernes' },
    { value: 'saturday',  label: 'Sábado' },
]

const TYPES = [
    { value: 'theory', label: 'Teórica' },
    { value: 'lab',    label: 'Laboratorio' },
]

function makeForm() {
    return useForm({
        section_id:   props.schedule.section.id,
        professor_id: props.schedule.professor.id,
        classroom_id: props.schedule.classroom.id,
        subject_id:   props.schedule.subject.id,
        day_of_week:  props.schedule.dayOfWeek,
        start_time:   props.schedule.startTime,
        end_time:     props.schedule.endTime,
        type:         props.schedule.type,
        valid_from:   props.schedule.validFrom,
        valid_until:  props.schedule.validUntil,
    })
}

const form = ref(makeForm())

watch(
    () => props.open,
    (opened) => { if (opened) form.value = makeForm() },
)

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.value.patch(update.url({ schedule: props.schedule }), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Editar horario" size="md" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:14px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Sección</label>
                        <select v-model="form.section_id" class="input" required>
                            <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.code }} ({{ s.periodName }})</option>
                        </select>
                        <InputError :message="form.errors.section_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Materia</label>
                        <select v-model="form.subject_id" class="input" required>
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.code }} — {{ s.name }}</option>
                        </select>
                        <InputError :message="form.errors.subject_id" />
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Profesor</label>
                        <select v-model="form.professor_id" class="input" required>
                            <option v-for="p in professors" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <InputError :message="form.errors.professor_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Aula</label>
                        <select v-model="form.classroom_id" class="input" required>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.identifier }}</option>
                        </select>
                        <InputError :message="form.errors.classroom_id" />
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Día</label>
                        <select v-model="form.day_of_week" class="input" required>
                            <option v-for="d in DAYS" :key="d.value" :value="d.value">{{ d.label }}</option>
                        </select>
                        <InputError :message="form.errors.day_of_week" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Inicio</label>
                        <input v-model="form.start_time" type="time" step="900" class="input" required />
                        <InputError :message="form.errors.start_time" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Fin</label>
                        <input v-model="form.end_time" type="time" step="900" class="input" required />
                        <InputError :message="form.errors.end_time" />
                    </div>
                </div>
                <div style="display:grid;gap:6px;">
                    <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Tipo</label>
                    <select v-model="form.type" class="input" required>
                        <option v-for="t in TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                    <InputError :message="form.errors.type" />
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Válido desde</label>
                        <input v-model="form.valid_from" type="date" class="input" required />
                        <InputError :message="form.errors.valid_from" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Válido hasta (opcional)</label>
                        <input v-model="form.valid_until" type="date" class="input" />
                        <InputError :message="form.errors.valid_until" />
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Guardar cambios</Button>
            </div>
        </form>
    </Modal>
</template>
```

- [ ] **Step 2: Create DeleteScheduleModal**

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/scheduling/schedules'
import type { Schedule } from '@/types/scheduling'

const props = defineProps<{
    open: boolean
    schedule: Schedule
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    useForm({}).delete(destroy.url({ schedule: props.schedule }), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Eliminar horario" size="sm" @update:open="close">
        <p style="font-size:var(--text-sm);color:var(--text-secondary);margin:0 0 20px;">
            ¿Confirmas que deseas eliminar el slot de <strong>{{ schedule.subject.code }}</strong>
            el {{ schedule.dayLabel }} de {{ schedule.startTime }}–{{ schedule.endTime }}?
        </p>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
            <Button type="button" variant="danger" @click="submit">Eliminar</Button>
        </div>
    </Modal>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/scheduling/EditScheduleModal.vue resources/js/components/scheduling/DeleteScheduleModal.vue
git commit -m "feat(scheduling): add EditScheduleModal and DeleteScheduleModal"
```

---

### Task 13: Schedules/Index.vue + Sidebar Item

**Files:**
- Create: `resources/js/pages/scheduling/Schedules/Index.vue`
- Modify: `resources/js/components/AppSidebar.vue`

- [ ] **Step 1: Create Schedules/Index.vue**

```vue
<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import CreateScheduleModal from '@/components/scheduling/CreateScheduleModal.vue'
import DeleteScheduleModal from '@/components/scheduling/DeleteScheduleModal.vue'
import EditScheduleModal from '@/components/scheduling/EditScheduleModal.vue'
import WeeklyGrid from '@/components/scheduling/WeeklyGrid.vue'
import { useScheduleFilters } from '@/composables/filters/useScheduleFilters'
import { useSchedulePermissions } from '@/composables/permissions/useSchedulePermissions'
import { index } from '@/routes/scheduling/schedules'
import type {
    Schedule,
    ScheduleAvailableClassroom,
    ScheduleAvailablePeriod,
    ScheduleAvailableProfessor,
    ScheduleAvailableSection,
    ScheduleAvailableSubject,
    ScheduleCollection,
} from '@/types/scheduling'

type Props = {
    schedules: ScheduleCollection
    periods: ScheduleAvailablePeriod[]
    sections: ScheduleAvailableSection[]
    professors: ScheduleAvailableProfessor[]
    classrooms: ScheduleAvailableClassroom[]
    subjects: ScheduleAvailableSubject[]
    filters: { period_id: number | null; section_id: number | null; professor_id: number | null }
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Horarios', href: '#' },
        { title: 'Horarios', href: index.url() },
    ],
})

const { canCreate, canUpdate, canDelete } = useSchedulePermissions()
const { periodId, sectionId, professorId, applyFilters } = useScheduleFilters(
    props.filters.period_id,
    props.filters.section_id,
    props.filters.professor_id,
)

const showCreate  = ref(false)
const createDefaults = ref<{ dayOfWeek?: string; startTime?: string }>({})
const editingSchedule   = ref<Schedule | null>(null)
const deletingSchedule  = ref<Schedule | null>(null)

const sectionsForPeriod = computed(() => {
    if (! periodId.value) return props.sections
    return props.sections.filter((s) => s.periodId === periodId.value)
})

function handleCreateFromGrid(defaults: { dayOfWeek: string; startTime: string }): void {
    createDefaults.value = defaults
    showCreate.value = true
}

function handleEditFromGrid(schedule: Schedule): void {
    editingSchedule.value = schedule
}
</script>

<script lang="ts">
import { computed } from 'vue'
</script>

<template>
    <Head title="Horarios" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Horarios
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Cuadrícula semanal de clases
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nuevo horario
            </Button>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <select v-model="periodId" class="input" style="max-width:180px;" aria-label="Filtrar por período" @change="applyFilters">
                <option :value="null">Todos los períodos</option>
                <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <select v-model="sectionId" class="input" style="max-width:180px;" aria-label="Filtrar por sección" @change="applyFilters">
                <option :value="null">Todas las secciones</option>
                <option v-for="s in sectionsForPeriod" :key="s.id" :value="s.id">{{ s.code }} ({{ s.periodName }})</option>
            </select>
            <select v-model="professorId" class="input" style="max-width:200px;" aria-label="Filtrar por profesor" @change="applyFilters">
                <option :value="null">Todos los profesores</option>
                <option v-for="p in professors" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
        </div>

        <div class="card" style="padding:0;overflow:hidden;">
            <WeeklyGrid
                :schedules="schedules"
                :can-update="canUpdate"
                :can-delete="canDelete"
                @create="handleCreateFromGrid"
                @edit="handleEditFromGrid"
            />
        </div>
    </div>

    <CreateScheduleModal
        :open="showCreate"
        :sections="sections"
        :professors="professors"
        :classrooms="classrooms"
        :subjects="subjects"
        :default-section-id="sectionId"
        :default-day-of-week="createDefaults.dayOfWeek"
        :default-start-time="createDefaults.startTime"
        @update:open="showCreate = $event"
    />

    <EditScheduleModal
        v-if="editingSchedule"
        :schedule="editingSchedule"
        :open="editingSchedule !== null"
        :sections="sections"
        :professors="professors"
        :classrooms="classrooms"
        :subjects="subjects"
        @update:open="editingSchedule = $event ? editingSchedule : null"
    />

    <DeleteScheduleModal
        v-if="deletingSchedule"
        :schedule="deletingSchedule"
        :open="deletingSchedule !== null"
        @update:open="deletingSchedule = $event ? deletingSchedule : null"
    />
</template>
```

- [ ] **Step 2: Add Horarios item to AppSidebar**

In `resources/js/components/AppSidebar.vue`, add the import after the school sections import (line ~18):

```typescript
import { index as schedulesIndex } from '@/routes/scheduling/schedules'
```

Then in the `navGroups` computed, inside the `Horarios` group items array (after the `schoolSectionsIndex` item), add:

```typescript
{ icon: 'clock', label: 'Horarios', href: schedulesIndex.url() },
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/scheduling/Schedules/Index.vue resources/js/components/AppSidebar.vue
git commit -m "feat(scheduling): add Schedules Index page and sidebar item"
```

---

### Task 14: Full Test Run + Smoke Check

**Files:** None created. Verification only.

- [ ] **Step 1: Run all backend tests**

```bash
sail php artisan test
```

Expected: All tests pass (previous 364+ tests plus new ScheduleControllerTest).

- [ ] **Step 2: Build frontend assets**

```bash
sail npm run build
```

Expected: 0 TypeScript errors, build succeeds.

- [ ] **Step 3: Verify Schedules page loads**

```bash
sail php artisan route:list | grep schedule
```

Expected output includes:
```
GET|HEAD  scheduling/schedules ............. scheduling.schedules.index
POST      scheduling/schedules ............. scheduling.schedules.store
PATCH     scheduling/schedules/{schedule} .. scheduling.schedules.update
DELETE    scheduling/schedules/{schedule} .. scheduling.schedules.destroy
```

- [ ] **Step 4: Commit**

```bash
git add -p
git commit -m "chore(scheduling): verify Spec 07 complete — schedule CRUD frontend + backend"
```

---

## Self-Review

**Spec coverage check:**
- ✅ Routes: GET/POST/PATCH/DELETE `/scheduling/schedules` — Task 5
- ✅ Permissions: `schedules.view/create/update/delete` — Task 1
- ✅ subject_id consistency (university + school) — Task 4 `validateSubjectConsistency()`
- ✅ professor_id consistency (school main teacher) — Task 4 `validateProfessorConsistency()`
- ✅ date range within period — Task 4 `validateDateRange()`
- ✅ 3 conflict checks (classroom, professor, weekly limit) — Task 4 `validateConflicts()`
- ✅ Spanish error messages for all 3 conflict types — Task 4
- ✅ Update excludes self from conflict checks — Task 4 `UpdateScheduleRequest`
- ✅ Weekly grid Mon–Sat × 07–18h — Task 9 `WeeklyGrid.vue`
- ✅ Slot cards with color by type (theory=blue, lab=green) — Task 9 `ScheduleSlot.vue`
- ✅ Create modal with pre-filled time from grid click — Task 11
- ✅ Edit/Delete modal from slot click — Task 12
- ✅ Errors shown inline in modal (Inertia useForm handles this via `form.errors`) — Tasks 11, 12
- ✅ ProfessorHoursBar — Task 10
- ✅ Sidebar Horarios item — Task 13
- ✅ Filters: period_id, section_id, professor_id — Task 5 (controller), Task 8 (composable)
- ✅ Feature tests (11 scenarios) — Task 6

**Type consistency:** `ScheduleWrapper` getters match `CreateScheduleAction` and `UpdateScheduleAction` usage. `ScheduleResource` `startTime`/`endTime` use `substr($time, 0, 5)` to trim seconds from DB "HH:MM:SS" format — consistent with `Schedule` TS type expecting "HH:MM" strings.

**Wayfinder dependency:** Task 5 runs `sail artisan wayfinder:generate` which must succeed before tasks 8–13 can import from `@/routes/scheduling/schedules`. Tasks are ordered correctly.
