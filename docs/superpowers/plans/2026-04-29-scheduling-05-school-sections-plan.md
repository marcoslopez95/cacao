# Scheduling — Spec 05: School Sections Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add CRUD for school sections (`type = 'school'`) — DB schema changes, full backend stack, and Vue frontend UI.

**Architecture:**
- Two migrations extend `sections` table (make `subject_id`/`code` nullable, replace the unique constraint with partial indexes, add school-specific columns) and create `section_subjects` pivot.
- `SectionResource` branches on `type` to return distinct JSON shapes for school vs university.
- `SchoolSectionController` follows `FormRequest → Controller → Wrapper → Action → Resource`.
- School sections are identified by `(period_id, pensum_id, grade, letter)`; `section_subjects` is auto-populated from the pensum subjects matching the grade on creation.
- `DeleteSectionAction` clears `section_subjects` rows first (FK is RESTRICT per project convention).

**Tech Stack:** PHP 8.3 · Laravel 13 · PostgreSQL · Vue 3 · Inertia.js v3 · Pest 4 · Tailwind CSS v4

**Working directory (container):** `/var/www/html/.worktrees/feat/scheduling-school-sections`

---

## File Map

| Status | Path | Purpose |
|--------|------|---------|
| Create | `database/migrations/{ts1}_add_school_columns_to_sections_table.php` | Alter sections: nullable cols, partial indexes, school cols |
| Create | `database/migrations/{ts2}_create_section_subjects_table.php` | Pivot: section ↔ subjects |
| Modify | `app/Models/Section.php` | Add fillable + new relationships |
| Modify | `database/factories/SectionFactory.php` | Add `school()` and `forPensumAndGrade()` states |
| Modify | `app/Http/Resources/Scheduling/SectionResource.php` | Type-aware: university vs school shape |
| Modify | `app/Actions/Scheduling/DeleteSectionAction.php` | Clear section_subjects before delete |
| Create | `app/Http/Requests/Scheduling/StoreSchoolSectionRequest.php` | Validate create |
| Create | `app/Http/Requests/Scheduling/UpdateSchoolSectionRequest.php` | Validate update |
| Create | `app/Http/Wrappers/Scheduling/SchoolSectionWrapper.php` | Typed getters |
| Create | `app/Actions/Scheduling/CreateSchoolSectionAction.php` | Create + sync section_subjects |
| Create | `app/Actions/Scheduling/UpdateSchoolSectionAction.php` | Update letter/capacity/teacher/classroom |
| Create | `app/Http/Controllers/Scheduling/SchoolSectionController.php` | index/store/update/destroy |
| Modify | `routes/web.php` | Add school section routes |
| Create | `tests/Feature/Scheduling/SchoolSectionControllerTest.php` | 13 feature tests |
| Modify | `resources/js/types/scheduling.ts` | Add school section types |
| Create | `resources/js/composables/forms/useSchoolSectionForm.ts` | Form composable |
| Create | `resources/js/composables/filters/useSchoolSectionFilters.ts` | Filter composable |
| Create | `resources/js/components/scheduling/CreateSchoolSectionModal.vue` | Create modal |
| Create | `resources/js/components/scheduling/EditSchoolSectionModal.vue` | Edit modal |
| Create | `resources/js/components/scheduling/DeleteSchoolSectionModal.vue` | Delete modal |
| Create | `resources/js/pages/scheduling/Sections/School.vue` | Index page |
| Modify | `resources/js/components/AppSidebar.vue` | Add sidebar entry |

---

### Task 1: DB Schema + Model + Factory

**Files:**
- Create: `database/migrations/{ts1}_add_school_columns_to_sections_table.php`
- Create: `database/migrations/{ts2}_create_section_subjects_table.php`
- Modify: `app/Models/Section.php`
- Modify: `database/factories/SectionFactory.php`

- [ ] **Step 1: Generate both migrations**

```bash
cd /var/www/html/.worktrees/feat/scheduling-school-sections
php artisan make:migration add_school_columns_to_sections_table --no-interaction
php artisan make:migration create_section_subjects_table --no-interaction
```

Expected: two new files in `database/migrations/`.

- [ ] **Step 2: Write alter-sections migration**

Replace the generated body of `{ts1}_add_school_columns_to_sections_table.php` with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropUnique(['period_id', 'subject_id', 'code']);
            $table->foreignId('subject_id')->nullable()->change();
            $table->string('code', 10)->nullable()->change();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "CREATE UNIQUE INDEX sections_university_unique ON sections (period_id, subject_id, code) WHERE type = 'university'"
            );
        }

        Schema::table('sections', function (Blueprint $table) {
            $table->foreignId('pensum_id')->nullable()->after('period_id')->constrained()->restrictOnDelete();
            $table->tinyInteger('grade')->unsigned()->nullable()->after('pensum_id');
            $table->string('letter', 5)->nullable()->after('grade');
            $table->foreignId('main_teacher_id')->nullable()->after('letter')->constrained('professors')->restrictOnDelete();
            $table->foreignId('classroom_id')->nullable()->after('main_teacher_id')->constrained('classrooms')->restrictOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "CREATE UNIQUE INDEX sections_school_unique ON sections (period_id, pensum_id, grade, letter) WHERE type = 'school'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS sections_university_unique');
            DB::statement('DROP INDEX IF EXISTS sections_school_unique');
        }

        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->dropForeign(['main_teacher_id']);
            $table->dropForeign(['pensum_id']);
            $table->dropColumn(['classroom_id', 'main_teacher_id', 'letter', 'grade', 'pensum_id']);
            $table->foreignId('subject_id')->nullable(false)->change();
            $table->string('code', 10)->nullable(false)->change();
            $table->unique(['period_id', 'subject_id', 'code']);
        });
    }
};
```

- [ ] **Step 3: Write section_subjects migration**

Replace the generated body of `{ts2}_create_section_subjects_table.php` with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->unique(['section_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_subjects');
    }
};
```

- [ ] **Step 4: Update Section model**

Replace `app/Models/Section.php` entirely with:

```php
<?php

namespace App\Models;

use App\Enums\SectionType;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['type', 'period_id', 'pensum_id', 'subject_id', 'code', 'grade', 'letter', 'theory_classroom_id', 'lab_classroom_id', 'main_teacher_id', 'classroom_id', 'capacity'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type'  => SectionType::class,
            'grade' => 'integer',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function pensum(): BelongsTo
    {
        return $this->belongsTo(Pensum::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function theoryClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'theory_classroom_id');
    }

    public function labClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'lab_classroom_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function mainTeacher(): BelongsTo
    {
        return $this->belongsTo(Professor::class, 'main_teacher_id');
    }

    public function sectionSubjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'section_subjects');
    }
}
```

- [ ] **Step 5: Update SectionFactory**

Replace `database/factories/SectionFactory.php` entirely with:

```php
<?php

namespace Database\Factories;

use App\Enums\SectionType;
use App\Models\Period;
use App\Models\Pensum;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type'                => SectionType::University,
            'period_id'           => Period::factory()->semester(),
            'subject_id'          => Subject::factory(),
            'code'                => fake()->numerify('##'),
            'theory_classroom_id' => null,
            'lab_classroom_id'    => null,
            'capacity'            => fake()->numberBetween(20, 50),
        ];
    }

    public function university(): static
    {
        return $this->state(['type' => SectionType::University]);
    }

    public function school(): static
    {
        $pensum = Pensum::factory()->create([
            'period_type'   => 'year',
            'total_periods' => 6,
        ]);
        $grade  = fake()->numberBetween(1, 6);
        $letter = fake()->randomElement(['A', 'B', 'C']);

        return $this->state([
            'type'            => SectionType::School,
            'period_id'       => Period::factory()->year(),
            'pensum_id'       => $pensum->id,
            'subject_id'      => null,
            'code'            => (string) $grade . $letter,
            'grade'           => $grade,
            'letter'          => $letter,
            'main_teacher_id' => null,
            'classroom_id'    => null,
        ]);
    }

    public function forPeriodAndSubject(Period $period, Subject $subject): static
    {
        return $this->state([
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
        ]);
    }

    public function forPensumAndGrade(Pensum $pensum, int $grade, string $letter = 'A'): static
    {
        return $this->state([
            'type'       => SectionType::School,
            'pensum_id'  => $pensum->id,
            'subject_id' => null,
            'code'       => (string) $grade . $letter,
            'grade'      => $grade,
            'letter'     => $letter,
        ]);
    }
}
```

- [ ] **Step 6: Run migrations**

```bash
php artisan migrate --no-interaction
```

Expected: all pending migrations applied with no errors.

- [ ] **Step 7: Run baseline tests — must still pass**

```bash
php artisan test --compact
```

Expected: 336 tests, all passing. If any fail, fix before continuing.

- [ ] **Step 8: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add database/migrations/ app/Models/Section.php database/factories/SectionFactory.php
git commit -m "feat: add school section DB schema, update Section model and factory"
```

---

### Task 2: SectionResource type-aware + DeleteSectionAction update

**Files:**
- Modify: `app/Http/Resources/Scheduling/SectionResource.php`
- Modify: `app/Actions/Scheduling/DeleteSectionAction.php`

- [ ] **Step 1: Replace SectionResource with type-aware version**

```php
<?php

namespace App\Http\Resources\Scheduling;

use App\Enums\SectionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->type === SectionType::School) {
            return $this->schoolShape();
        }

        return $this->universityShape();
    }

    /**
     * @return array<string, mixed>
     */
    private function universityShape(): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type->value,
            'code'            => $this->code,
            'capacity'        => $this->capacity,
            'period'          => [
                'id'   => $this->period->id,
                'name' => $this->period->name,
                'type' => $this->period->type->value,
            ],
            'subject'         => [
                'id'   => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ],
            'theoryClassroom' => $this->theory_classroom_id ? [
                'id'         => $this->theoryClassroom->id,
                'identifier' => $this->theoryClassroom->identifier,
                'capacity'   => $this->theoryClassroom->capacity,
            ] : null,
            'labClassroom'    => $this->lab_classroom_id ? [
                'id'         => $this->labClassroom->id,
                'identifier' => $this->labClassroom->identifier,
                'capacity'   => $this->labClassroom->capacity,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function schoolShape(): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->type->value,
            'grade'       => $this->grade,
            'letter'      => $this->letter,
            'code'        => $this->code,
            'capacity'    => $this->capacity,
            'period'      => [
                'id'   => $this->period->id,
                'name' => $this->period->name,
                'type' => $this->period->type->value,
            ],
            'pensum'      => [
                'id'     => $this->pensum->id,
                'name'   => $this->pensum->name,
                'career' => [
                    'id'   => $this->pensum->career->id,
                    'name' => $this->pensum->career->name,
                ],
            ],
            'mainTeacher' => $this->main_teacher_id ? [
                'id'   => $this->mainTeacher->id,
                'user' => [
                    'id'   => $this->mainTeacher->user->id,
                    'name' => $this->mainTeacher->user->name,
                ],
            ] : null,
            'classroom'   => $this->classroom_id ? [
                'id'         => $this->classroom->id,
                'identifier' => $this->classroom->identifier,
                'capacity'   => $this->classroom->capacity,
            ] : null,
            'subjects'    => $this->sectionSubjects->map(fn ($s) => [
                'id'   => $s->id,
                'name' => $s->name,
                'code' => $s->code,
            ])->values()->toArray(),
        ];
    }
}
```

- [ ] **Step 2: Replace DeleteSectionAction to clear section_subjects first**

```php
<?php

namespace App\Actions\Scheduling;

use App\Models\Section;
use Illuminate\Support\Facades\DB;

class DeleteSectionAction
{
    public function handle(Section $section): bool
    {
        DB::table('section_subjects')->where('section_id', $section->id)->delete();

        return (bool) $section->delete();
    }
}
```

- [ ] **Step 3: Run university section tests to confirm no regression**

```bash
php artisan test --compact --filter=UniversitySectionControllerTest
```

Expected: 14 tests, all passing.

- [ ] **Step 4: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Resources/Scheduling/SectionResource.php app/Actions/Scheduling/DeleteSectionAction.php
git commit -m "feat: make SectionResource type-aware, clear section_subjects before delete"
```

---

### Task 3: School Section Backend (Requests + Wrapper + Actions + Controller + Routes)

**Files:**
- Create: `app/Http/Requests/Scheduling/StoreSchoolSectionRequest.php`
- Create: `app/Http/Requests/Scheduling/UpdateSchoolSectionRequest.php`
- Create: `app/Http/Wrappers/Scheduling/SchoolSectionWrapper.php`
- Create: `app/Actions/Scheduling/CreateSchoolSectionAction.php`
- Create: `app/Actions/Scheduling/UpdateSchoolSectionAction.php`
- Create: `app/Http/Controllers/Scheduling/SchoolSectionController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create StoreSchoolSectionRequest**

```php
<?php

namespace App\Http\Requests\Scheduling;

use App\Enums\PeriodType;
use App\Enums\SectionType;
use App\Models\Period;
use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreSchoolSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Section::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period_id'       => ['required', 'integer', 'exists:periods,id'],
            'pensum_id'       => ['required', 'integer', 'exists:pensums,id'],
            'grade'           => ['required', 'integer', 'min:1', 'max:12'],
            'letter'          => ['required', 'string', 'max:1', 'regex:/^[A-Za-z]$/'],
            'capacity'        => ['required', 'integer', 'min:1'],
            'main_teacher_id' => ['nullable', 'integer', 'exists:professors,id'],
            'classroom_id'    => ['nullable', 'integer', 'exists:classrooms,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $period = Period::find($this->integer('period_id'));

            if ($period && $period->type !== PeriodType::Year) {
                $v->errors()->add('period_id', 'Las secciones escolares solo pueden pertenecer a períodos anuales.');
            }

            $duplicate = Section::where('type', SectionType::School)
                ->where('period_id', $this->integer('period_id'))
                ->where('pensum_id', $this->integer('pensum_id'))
                ->where('grade', $this->integer('grade'))
                ->where('letter', strtoupper($this->string('letter')))
                ->exists();

            if ($duplicate) {
                $v->errors()->add('letter', 'Ya existe una sección con este grado y letra para el período y pensum seleccionados.');
            }
        });
    }
}
```

- [ ] **Step 2: Create UpdateSchoolSectionRequest**

```php
<?php

namespace App\Http\Requests\Scheduling;

use App\Enums\SectionType;
use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateSchoolSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('section'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'letter'          => ['required', 'string', 'max:1', 'regex:/^[A-Za-z]$/'],
            'capacity'        => ['required', 'integer', 'min:1'],
            'main_teacher_id' => ['nullable', 'integer', 'exists:professors,id'],
            'classroom_id'    => ['nullable', 'integer', 'exists:classrooms,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            /** @var Section $section */
            $section = $this->route('section');

            $duplicate = Section::where('type', SectionType::School)
                ->where('period_id', $section->period_id)
                ->where('pensum_id', $section->pensum_id)
                ->where('grade', $section->grade)
                ->where('letter', strtoupper($this->string('letter')))
                ->where('id', '!=', $section->id)
                ->exists();

            if ($duplicate) {
                $v->errors()->add('letter', 'Ya existe una sección con este grado y letra para el período y pensum seleccionados.');
            }
        });
    }
}
```

- [ ] **Step 3: Create SchoolSectionWrapper**

```php
<?php

namespace App\Http\Wrappers\Scheduling;

use Illuminate\Support\Collection;

class SchoolSectionWrapper extends Collection
{
    public function getPeriodId(): int
    {
        return (int) $this->get('period_id');
    }

    public function getPensumId(): int
    {
        return (int) $this->get('pensum_id');
    }

    public function getGrade(): int
    {
        return (int) $this->get('grade');
    }

    public function getLetter(): string
    {
        return strtoupper((string) $this->get('letter'));
    }

    public function getCapacity(): int
    {
        return (int) $this->get('capacity');
    }

    public function getMainTeacherId(): ?int
    {
        return $this->get('main_teacher_id') ? (int) $this->get('main_teacher_id') : null;
    }

    public function getClassroomId(): ?int
    {
        return $this->get('classroom_id') ? (int) $this->get('classroom_id') : null;
    }
}
```

- [ ] **Step 4: Create CreateSchoolSectionAction**

```php
<?php

namespace App\Actions\Scheduling;

use App\Enums\SectionType;
use App\Http\Wrappers\Scheduling\SchoolSectionWrapper;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class CreateSchoolSectionAction
{
    public function handle(SchoolSectionWrapper $wrapper): Section
    {
        $grade  = $wrapper->getGrade();
        $letter = $wrapper->getLetter();

        $section = Section::create([
            'type'            => SectionType::School,
            'period_id'       => $wrapper->getPeriodId(),
            'pensum_id'       => $wrapper->getPensumId(),
            'grade'           => $grade,
            'letter'          => $letter,
            'code'            => $grade . $letter,
            'capacity'        => $wrapper->getCapacity(),
            'main_teacher_id' => $wrapper->getMainTeacherId(),
            'classroom_id'    => $wrapper->getClassroomId(),
        ]);

        $subjectIds = Subject::where('pensum_id', $section->pensum_id)
            ->where('period_number', $section->grade)
            ->pluck('id');

        if ($subjectIds->isNotEmpty()) {
            DB::table('section_subjects')->insert(
                $subjectIds->map(fn (int $id) => [
                    'section_id' => $section->id,
                    'subject_id' => $id,
                ])->all()
            );
        }

        return $section;
    }
}
```

- [ ] **Step 5: Create UpdateSchoolSectionAction**

```php
<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\SchoolSectionWrapper;
use App\Models\Section;

class UpdateSchoolSectionAction
{
    public function handle(Section $section, SchoolSectionWrapper $wrapper): Section
    {
        $section->update([
            'letter'          => $wrapper->getLetter(),
            'code'            => $section->grade . $wrapper->getLetter(),
            'capacity'        => $wrapper->getCapacity(),
            'main_teacher_id' => $wrapper->getMainTeacherId(),
            'classroom_id'    => $wrapper->getClassroomId(),
        ]);

        return $section;
    }
}
```

- [ ] **Step 6: Create SchoolSectionController**

```php
<?php

namespace App\Http\Controllers\Scheduling;

use App\Actions\Scheduling\CreateSchoolSectionAction;
use App\Actions\Scheduling\DeleteSectionAction;
use App\Actions\Scheduling\UpdateSchoolSectionAction;
use App\Enums\PeriodType;
use App\Enums\SectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreSchoolSectionRequest;
use App\Http\Requests\Scheduling\UpdateSchoolSectionRequest;
use App\Http\Resources\Scheduling\SectionResource;
use App\Http\Wrappers\Scheduling\SchoolSectionWrapper;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Pensum;
use App\Models\Professor;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchoolSectionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Section::class);

        $sections = Section::where('type', SectionType::School)
            ->when($request->input('period_id'), fn ($q, $id) => $q->where('period_id', $id))
            ->when($request->input('pensum_id'), fn ($q, $id) => $q->where('pensum_id', $id))
            ->with(['period', 'pensum.career', 'mainTeacher.user', 'classroom', 'sectionSubjects'])
            ->orderByDesc('id')
            ->get();

        $periods = Period::where('type', PeriodType::Year)
            ->orderByDesc('id')
            ->get(['id', 'name', 'type']);

        $pensums = Pensum::with('career:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'career_id', 'total_periods']);

        $professors = Professor::with('user:id,name')
            ->where('active', true)
            ->orderBy('id')
            ->get(['id', 'user_id']);

        $classrooms = Classroom::orderBy('identifier')
            ->get(['id', 'identifier', 'capacity']);

        return Inertia::render('scheduling/Sections/School', [
            'sections'   => SectionResource::collection($sections)->resolve(),
            'periods'    => $periods->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'type' => $p->type->value]),
            'pensums'    => $pensums->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'totalPeriods' => $p->total_periods, 'career' => ['id' => $p->career->id, 'name' => $p->career->name]]),
            'professors' => $professors->map(fn ($p) => ['id' => $p->id, 'user' => ['id' => $p->user->id, 'name' => $p->user->name]]),
            'classrooms' => $classrooms->map(fn ($c) => ['id' => $c->id, 'identifier' => $c->identifier, 'capacity' => $c->capacity]),
            'filters'    => [
                'period_id' => $request->input('period_id') ? (int) $request->input('period_id') : null,
                'pensum_id' => $request->input('pensum_id') ? (int) $request->input('pensum_id') : null,
            ],
            'can'        => [
                'create' => $request->user()->can('sections.create'),
                'update' => $request->user()->can('sections.update'),
                'delete' => $request->user()->can('sections.delete'),
            ],
        ]);
    }

    public function store(StoreSchoolSectionRequest $request, CreateSchoolSectionAction $action): RedirectResponse
    {
        $action->handle(new SchoolSectionWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sección escolar creada.']);

        return to_route('scheduling.sections.school.index');
    }

    public function update(UpdateSchoolSectionRequest $request, Section $section, UpdateSchoolSectionAction $action): RedirectResponse
    {
        $action->handle($section, new SchoolSectionWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sección escolar actualizada.']);

        return to_route('scheduling.sections.school.index');
    }

    public function destroy(Section $section, DeleteSectionAction $action): RedirectResponse
    {
        Gate::authorize('delete', $section);

        $action->handle($section);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sección escolar eliminada.']);

        return to_route('scheduling.sections.school.index');
    }
}
```

- [ ] **Step 7: Add routes to routes/web.php**

Add this import near the top of `routes/web.php` (alongside other Scheduling controller imports):

```php
use App\Http\Controllers\Scheduling\SchoolSectionController;
```

Inside the `scheduling` prefix group, after the university section routes:

```php
Route::get('sections/school', [SchoolSectionController::class, 'index'])->name('sections.school.index');
Route::post('sections/school', [SchoolSectionController::class, 'store'])->name('sections.school.store');
Route::patch('sections/school/{section}', [SchoolSectionController::class, 'update'])->name('sections.school.update');
Route::delete('sections/school/{section}', [SchoolSectionController::class, 'destroy'])->name('sections.school.destroy');
```

- [ ] **Step 8: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add app/Http/Requests/Scheduling/ app/Http/Wrappers/Scheduling/ app/Actions/Scheduling/ app/Http/Controllers/Scheduling/SchoolSectionController.php routes/web.php
git commit -m "feat: add SchoolSectionController, requests, wrapper, and actions"
```

---

### Task 4: Tests for SchoolSectionController

**Files:**
- Create: `tests/Feature/Scheduling/SchoolSectionControllerTest.php`

- [ ] **Step 1: Generate the test file**

```bash
php artisan make:test --pest Scheduling/SchoolSectionControllerTest --no-interaction
```

- [ ] **Step 2: Run it — expect 0 tests (file is empty)**

```bash
php artisan test --compact --filter=SchoolSectionControllerTest
```

- [ ] **Step 3: Write the test file**

Replace the generated `tests/Feature/Scheduling/SchoolSectionControllerTest.php` entirely with:

```php
<?php

use App\Enums\SectionType;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['sections.view', 'sections.create', 'sections.update', 'sections.delete'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWithSchoolPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

function yearPensumWithGrade(int $grade = 3): array
{
    $period  = Period::factory()->year()->create();
    $pensum  = Pensum::factory()->create(['period_type' => 'year', 'total_periods' => 6]);
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id, 'period_number' => $grade]);

    return [$period, $pensum, $subject];
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('admin can list school sections', function () {
    $period = Period::factory()->year()->create();
    $pensum = Pensum::factory()->create(['period_type' => 'year', 'total_periods' => 6]);

    Section::factory()->forPensumAndGrade($pensum, 3)->count(3)->create(['period_id' => $period->id]);

    $this->actingAs(userWithSchoolPerm('sections.view'))
        ->get('/scheduling/sections/school')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('scheduling/Sections/School', false)
            ->has('sections', 3)
        );
});

test('unauthenticated user cannot access school sections', function () {
    $this->get('/scheduling/sections/school')->assertRedirect('/login');
});

test('user without permission cannot list school sections', function () {
    $this->actingAs(User::factory()->create())
        ->get('/scheduling/sections/school')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('admin can create a school section', function () {
    [$period, $pensum] = yearPensumWithGrade(4);

    $this->actingAs(userWithSchoolPerm('sections.create'))
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 4,
            'letter'    => 'A',
            'capacity'  => 25,
        ])
        ->assertRedirect(route('scheduling.sections.school.index'));

    $section = Section::where('type', SectionType::School)
        ->where('period_id', $period->id)
        ->where('pensum_id', $pensum->id)
        ->where('grade', 4)
        ->where('letter', 'A')
        ->first();

    expect($section)->not->toBeNull();
    expect($section->code)->toBe('4A');
});

test('creating a school section auto-populates section_subjects for the grade', function () {
    [$period, $pensum, $subject] = yearPensumWithGrade(3);
    Subject::factory()->create(['pensum_id' => $pensum->id, 'period_number' => 5]);

    $this->actingAs(userWithSchoolPerm('sections.create'))
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 3,
            'letter'    => 'B',
            'capacity'  => 30,
        ])
        ->assertRedirect(route('scheduling.sections.school.index'));

    $section = Section::where('grade', 3)->where('letter', 'B')->first();

    expect($section->sectionSubjects)->toHaveCount(1);
    expect($section->sectionSubjects->first()->id)->toBe($subject->id);
});

test('cannot create school section with non-year period', function () {
    $period = Period::factory()->semester()->create();
    $pensum = Pensum::factory()->create(['period_type' => 'semester', 'total_periods' => 5]);

    $this->actingAs(userWithSchoolPerm('sections.create'))
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 3,
            'letter'    => 'A',
            'capacity'  => 25,
        ])
        ->assertSessionHasErrors('period_id');
});

test('cannot create duplicate school section for same period pensum grade letter', function () {
    [$period, $pensum] = yearPensumWithGrade(2);
    Section::factory()->forPensumAndGrade($pensum, 2, 'A')->create(['period_id' => $period->id]);

    $this->actingAs(userWithSchoolPerm('sections.create'))
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 2,
            'letter'    => 'A',
            'capacity'  => 25,
        ])
        ->assertSessionHasErrors('letter');
});

test('grade must be between 1 and 12', function () {
    [$period, $pensum] = yearPensumWithGrade();

    $this->actingAs(userWithSchoolPerm('sections.create'))
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 0,
            'letter'    => 'A',
            'capacity'  => 25,
        ])
        ->assertSessionHasErrors('grade');
});

test('capacity must be at least 1 for school section', function () {
    [$period, $pensum] = yearPensumWithGrade();

    $this->actingAs(userWithSchoolPerm('sections.create'))
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 3,
            'letter'    => 'A',
            'capacity'  => 0,
        ])
        ->assertSessionHasErrors('capacity');
});

test('user without permission cannot create school section', function () {
    [$period, $pensum] = yearPensumWithGrade();

    $this->actingAs(User::factory()->create())
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 3,
            'letter'    => 'A',
            'capacity'  => 25,
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('admin can update a school section letter and capacity', function () {
    [$period, $pensum] = yearPensumWithGrade();
    $section = Section::factory()->forPensumAndGrade($pensum, 3, 'A')->create(['period_id' => $period->id]);

    $this->actingAs(userWithSchoolPerm('sections.update'))
        ->patch("/scheduling/sections/school/{$section->id}", [
            'letter'   => 'B',
            'capacity' => 35,
        ])
        ->assertRedirect(route('scheduling.sections.school.index'));

    $section->refresh();
    expect($section->letter)->toBe('B');
    expect($section->capacity)->toBe(35);
    expect($section->code)->toBe('3B');
});

test('user without permission cannot update school section', function () {
    [$period, $pensum] = yearPensumWithGrade();
    $section = Section::factory()->forPensumAndGrade($pensum, 3)->create(['period_id' => $period->id]);

    $this->actingAs(User::factory()->create())
        ->patch("/scheduling/sections/school/{$section->id}", [
            'letter'   => 'C',
            'capacity' => 30,
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('admin can delete a school section', function () {
    [$period, $pensum] = yearPensumWithGrade(3);
    $section = Section::factory()->forPensumAndGrade($pensum, 3)->create(['period_id' => $period->id]);

    $this->actingAs(userWithSchoolPerm('sections.delete'))
        ->delete("/scheduling/sections/school/{$section->id}")
        ->assertRedirect(route('scheduling.sections.school.index'));

    expect(Section::find($section->id))->toBeNull();
});

test('user without permission cannot delete school section', function () {
    [$period, $pensum] = yearPensumWithGrade();
    $section = Section::factory()->forPensumAndGrade($pensum, 3)->create(['period_id' => $period->id]);

    $this->actingAs(User::factory()->create())
        ->delete("/scheduling/sections/school/{$section->id}")
        ->assertForbidden();
});
```

- [ ] **Step 4: Run tests — expect all 13 to pass**

```bash
php artisan test --compact --filter=SchoolSectionControllerTest
```

Expected: 13 tests, all passing.

- [ ] **Step 5: Run full suite**

```bash
php artisan test --compact
```

Expected: 349 tests (336 + 13), all passing.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Scheduling/SchoolSectionControllerTest.php
git commit -m "test: add SchoolSectionController feature tests"
```

---

### Task 5: Frontend

**Files:**
- Modify: `resources/js/types/scheduling.ts`
- Create: `resources/js/composables/forms/useSchoolSectionForm.ts`
- Create: `resources/js/composables/filters/useSchoolSectionFilters.ts`
- Create: `resources/js/components/scheduling/CreateSchoolSectionModal.vue`
- Create: `resources/js/components/scheduling/EditSchoolSectionModal.vue`
- Create: `resources/js/components/scheduling/DeleteSchoolSectionModal.vue`
- Create: `resources/js/pages/scheduling/Sections/School.vue`
- Modify: `resources/js/components/AppSidebar.vue`

- [ ] **Step 1: Generate Wayfinder routes**

```bash
php artisan wayfinder:generate --no-interaction
```

Expected: creates `resources/js/routes/scheduling/sections/school.ts` exporting `index`, `store`, `update`, `destroy`.

- [ ] **Step 2: Update resources/js/types/scheduling.ts**

Change the `AvailablePeriod` type to include `'year'` (school sections use year periods):

Find:
```typescript
export type AvailablePeriod = {
    id: number
    name: string
    type: 'semester' | 'trimester'
}
```

Replace with:
```typescript
export type AvailablePeriod = {
    id: number
    name: string
    type: 'semester' | 'trimester' | 'year'
}
```

Then append at the end of the file:

```typescript
export type SchoolSectionSubject = {
    id: number
    name: string
    code: string
}

export type SchoolSectionTeacher = {
    id: number
    user: {
        id: number
        name: string
    }
}

export type SchoolSectionClassroom = {
    id: number
    identifier: string
    capacity: number
}

export type SchoolSectionPensum = {
    id: number
    name: string
    career: {
        id: number
        name: string
    }
}

export type SchoolSection = {
    id: number
    type: 'school'
    grade: number
    letter: string
    code: string
    capacity: number
    period: SectionPeriod
    pensum: SchoolSectionPensum
    mainTeacher: SchoolSectionTeacher | null
    classroom: SchoolSectionClassroom | null
    subjects: SchoolSectionSubject[]
}

export type SchoolSectionCollection = SchoolSection[]

export type PensumForSection = {
    id: number
    name: string
    totalPeriods: number
    career: {
        id: number
        name: string
    }
}

export type ProfessorForSection = {
    id: number
    user: {
        id: number
        name: string
    }
}
```

- [ ] **Step 3: Create useSchoolSectionForm.ts**

```typescript
// resources/js/composables/forms/useSchoolSectionForm.ts
import { useForm } from '@inertiajs/vue3'
import { destroy, store, update } from '@/routes/scheduling/sections/school'
import type { SchoolSection } from '@/types/scheduling'

export function useSchoolSectionForm() {
    const storeOps = {
        form() {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({
                    period_id:       null as number | null,
                    pensum_id:       null as number | null,
                    grade:           null as number | null,
                    letter:          '',
                    capacity:        30,
                    main_teacher_id: null as number | null,
                    classroom_id:    null as number | null,
                }),
            }
        },
    }

    const updateOps = {
        form({ section }: { section: SchoolSection }) {
            return {
                url:    update.url({ section }),
                method: 'patch' as const,
                data:   useForm({
                    letter:          section.letter,
                    capacity:        section.capacity,
                    main_teacher_id: section.mainTeacher?.id ?? null,
                    classroom_id:    section.classroom?.id ?? null,
                }),
            }
        },
    }

    const removeOps = {
        submit({ section }: { section: SchoolSection }): void {
            useForm({}).delete(destroy.url({ section }))
        },
    }

    return { store: storeOps, update: updateOps, remove: removeOps }
}
```

- [ ] **Step 4: Create useSchoolSectionFilters.ts**

```typescript
// resources/js/composables/filters/useSchoolSectionFilters.ts
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { index } from '@/routes/scheduling/sections/school'

export function useSchoolSectionFilters(
    initialPeriodId: number | null,
    initialPensumId: number | null,
) {
    const periodId = ref<number | null>(initialPeriodId)
    const pensumId = ref<number | null>(initialPensumId)

    function applyFilters(): void {
        router.get(
            index.url(),
            { period_id: periodId.value ?? undefined, pensum_id: pensumId.value ?? undefined },
            { preserveState: true, replace: true },
        )
    }

    return { periodId, pensumId, applyFilters }
}
```

- [ ] **Step 5: Create CreateSchoolSectionModal.vue**

```vue
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/scheduling/sections/school'
import type { AvailablePeriod, PensumForSection, ProfessorForSection, SchoolSectionClassroom } from '@/types/scheduling'

const props = defineProps<{
    open: boolean
    periods: AvailablePeriod[]
    pensums: PensumForSection[]
    professors: ProfessorForSection[]
    classrooms: SchoolSectionClassroom[]
}>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        period_id:       null as number | null,
        pensum_id:       null as number | null,
        grade:           null as number | null,
        letter:          '',
        capacity:        30,
        main_teacher_id: null as number | null,
        classroom_id:    null as number | null,
    })
}

const form = ref(makeForm())

const selectedPensum = computed(() => props.pensums.find((p) => p.id === form.value.pensum_id) ?? null)

const gradeOptions = computed(() => {
    if (! selectedPensum.value) { return [] }
    return Array.from({ length: selectedPensum.value.totalPeriods }, (_, i) => i + 1)
})

function close(v: boolean): void {
    emit('update:open', v)
}

watch(
    () => props.open,
    (opened) => {
        if (opened) { form.value = makeForm() }
    },
)

watch(
    () => form.value.pensum_id,
    () => { form.value.grade = null },
)

function submit(): void {
    form.value.post(store.url(), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Nueva sección escolar" size="md" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="css-period" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Período</label>
                        <select id="css-period" v-model="form.period_id" class="input" required>
                            <option :value="null" disabled>Seleccionar período</option>
                            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <InputError :message="form.errors.period_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="css-pensum" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Pensum</label>
                        <select id="css-pensum" v-model="form.pensum_id" class="input" required>
                            <option :value="null" disabled>Seleccionar pensum</option>
                            <option v-for="p in pensums" :key="p.id" :value="p.id">{{ p.career.name }} — {{ p.name }}</option>
                        </select>
                        <InputError :message="form.errors.pensum_id" />
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="css-grade" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Grado</label>
                        <select id="css-grade" v-model="form.grade" class="input" required :disabled="! selectedPensum">
                            <option :value="null" disabled>Grado</option>
                            <option v-for="g in gradeOptions" :key="g" :value="g">{{ g }}°</option>
                        </select>
                        <InputError :message="form.errors.grade" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="css-letter" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Sección</label>
                        <input id="css-letter" v-model="form.letter" class="input" maxlength="1" placeholder="A" required style="text-transform:uppercase;" />
                        <InputError :message="form.errors.letter" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="css-capacity" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Cupo</label>
                        <input id="css-capacity" v-model.number="form.capacity" class="input" type="number" min="1" required />
                        <InputError :message="form.errors.capacity" />
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="css-teacher" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Docente principal</label>
                        <select id="css-teacher" v-model="form.main_teacher_id" class="input">
                            <option :value="null">Sin docente</option>
                            <option v-for="p in professors" :key="p.id" :value="p.id">{{ p.user.name }}</option>
                        </select>
                        <InputError :message="form.errors.main_teacher_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="css-classroom" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Aula</label>
                        <select id="css-classroom" v-model="form.classroom_id" class="input">
                            <option :value="null">Sin aula</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.identifier }} ({{ c.capacity }})</option>
                        </select>
                        <InputError :message="form.errors.classroom_id" />
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Crear sección</Button>
            </div>
        </form>
    </Modal>
</template>
```

- [ ] **Step 6: Create EditSchoolSectionModal.vue**

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/scheduling/sections/school'
import type { ProfessorForSection, SchoolSection, SchoolSectionClassroom } from '@/types/scheduling'

const props = defineProps<{
    open: boolean
    section: SchoolSection | null
    professors: ProfessorForSection[]
    classrooms: SchoolSectionClassroom[]
}>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        letter:          props.section?.letter ?? '',
        capacity:        props.section?.capacity ?? 30,
        main_teacher_id: props.section?.mainTeacher?.id ?? null as number | null,
        classroom_id:    props.section?.classroom?.id ?? null as number | null,
    })
}

const form = ref(makeForm())

function close(v: boolean): void {
    emit('update:open', v)
}

watch(
    () => props.open,
    (opened) => {
        if (opened) { form.value = makeForm() }
    },
)

function submit(): void {
    if (! props.section) { return }
    form.value.patch(update.url({ section: props.section }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" :title="section ? `Editar sección ${section.code}` : 'Editar sección'" size="md" @update:open="close">
        <form v-if="section" @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="es-letter" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Sección</label>
                        <input id="es-letter" v-model="form.letter" class="input" maxlength="1" placeholder="A" required style="text-transform:uppercase;" />
                        <InputError :message="form.errors.letter" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="es-capacity" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Cupo</label>
                        <input id="es-capacity" v-model.number="form.capacity" class="input" type="number" min="1" required />
                        <InputError :message="form.errors.capacity" />
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="es-teacher" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Docente principal</label>
                        <select id="es-teacher" v-model="form.main_teacher_id" class="input">
                            <option :value="null">Sin docente</option>
                            <option v-for="p in professors" :key="p.id" :value="p.id">{{ p.user.name }}</option>
                        </select>
                        <InputError :message="form.errors.main_teacher_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="es-classroom" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Aula</label>
                        <select id="es-classroom" v-model="form.classroom_id" class="input">
                            <option :value="null">Sin aula</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.identifier }} ({{ c.capacity }})</option>
                        </select>
                        <InputError :message="form.errors.classroom_id" />
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Guardar cambios</Button>
            </div>
        </form>
    </Modal>
</template>
```

- [ ] **Step 7: Create DeleteSchoolSectionModal.vue**

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/scheduling/sections/school'
import type { SchoolSection } from '@/types/scheduling'

const props = defineProps<{ section: SchoolSection; open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.delete(destroy.url({ section: props.section }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Eliminar sección escolar" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 12px;">
                ¿Eliminar la sección <strong>{{ section.pensum.career.name }} — {{ section.grade }}° {{ section.letter }}</strong>?
            </p>
            <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
                No se puede eliminar si tiene inscripciones asignadas.
            </p>
            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="danger" :loading="form.processing">Eliminar</Button>
            </div>
        </form>
    </Modal>
</template>
```

- [ ] **Step 8: Create School.vue page**

Create `resources/js/pages/scheduling/Sections/School.vue`:

```vue
<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import CreateSchoolSectionModal from '@/components/scheduling/CreateSchoolSectionModal.vue'
import DeleteSchoolSectionModal from '@/components/scheduling/DeleteSchoolSectionModal.vue'
import EditSchoolSectionModal from '@/components/scheduling/EditSchoolSectionModal.vue'
import { useSchoolSectionFilters } from '@/composables/filters/useSchoolSectionFilters'
import { useSchoolSectionForm } from '@/composables/forms/useSchoolSectionForm'
import { useSectionPermissions } from '@/composables/permissions/useSectionPermissions'
import { index } from '@/routes/scheduling/sections/school'
import type {
    AvailablePeriod,
    PensumForSection,
    ProfessorForSection,
    SchoolSection,
    SchoolSectionClassroom,
    SchoolSectionCollection,
} from '@/types/scheduling'

type Props = {
    sections: SchoolSectionCollection
    periods: AvailablePeriod[]
    pensums: PensumForSection[]
    professors: ProfessorForSection[]
    classrooms: SchoolSectionClassroom[]
    filters: { period_id: number | null; pensum_id: number | null }
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Horarios', href: '#' },
        { title: 'Secciones Escolares', href: index.url() },
    ],
})

const { canCreate, canUpdate, canDelete } = useSectionPermissions()
const {} = useSchoolSectionForm()
const { periodId, pensumId, applyFilters } = useSchoolSectionFilters(props.filters.period_id, props.filters.pensum_id)

const showCreate = ref(false)
const editingSection = ref<SchoolSection | null>(null)
const deletingSection = ref<SchoolSection | null>(null)
</script>

<template>
    <Head title="Secciones Escolares" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Secciones Escolares
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Grupos de cursado para educación primaria y secundaria
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nueva sección
            </Button>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <select v-model="periodId" class="input" style="max-width:200px;" aria-label="Filtrar por período" @change="applyFilters">
                <option :value="null">Todos los períodos</option>
                <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <select v-model="pensumId" class="input" style="max-width:240px;" aria-label="Filtrar por pensum" @change="applyFilters">
                <option :value="null">Todos los pensums</option>
                <option v-for="p in pensums" :key="p.id" :value="p.id">{{ p.career.name }} — {{ p.name }}</option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Sección</th>
                        <th>Período</th>
                        <th>Carrera / Pensum</th>
                        <th>Docente</th>
                        <th>Cupo</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="sections.length === 0">
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px;">
                            No hay secciones escolares registradas.
                        </td>
                    </tr>
                    <tr v-for="section in sections" :key="section.id">
                        <td style="font-weight:500;">{{ section.grade }}° {{ section.letter }}</td>
                        <td style="color:var(--text-secondary);">{{ section.period.name }}</td>
                        <td style="color:var(--text-secondary);">{{ section.pensum.career.name }}</td>
                        <td style="color:var(--text-secondary);">{{ section.mainTeacher?.user.name ?? '—' }}</td>
                        <td>{{ section.capacity }}</td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:8px;justify-content:flex-end;">
                                <Button v-if="canUpdate" variant="ghost" size="sm" icon="pencil" @click="editingSection = section">
                                    Editar
                                </Button>
                                <Button v-if="canDelete" variant="ghost" size="sm" icon="trash" @click="deletingSection = section">
                                    Eliminar
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <CreateSchoolSectionModal
            v-model:open="showCreate"
            :periods="periods"
            :pensums="pensums"
            :professors="professors"
            :classrooms="classrooms"
        />

        <EditSchoolSectionModal
            v-if="editingSection"
            :open="editingSection !== null"
            :section="editingSection"
            :professors="professors"
            :classrooms="classrooms"
            @update:open="(v) => { if (! v) editingSection = null }"
        />

        <DeleteSchoolSectionModal
            v-if="deletingSection"
            :open="deletingSection !== null"
            :section="deletingSection"
            @update:open="(v) => { if (! v) deletingSection = null }"
        />
    </div>
</template>
```

- [ ] **Step 9: Update AppSidebar.vue**

Add this import near the top of the script (after the existing Wayfinder route imports):

```typescript
import { index as schoolSectionsIndex } from '@/routes/scheduling/sections/school'
```

Add the sidebar item inside the `items` array of the Horarios group, after the university sections entry:

```typescript
{ icon: 'layout-list', label: 'Secciones Esc.', href: schoolSectionsIndex.url() },
```

- [ ] **Step 10: Verify TypeScript compiles**

```bash
npm run type-check
```

Expected: no errors. Fix any TS errors before committing.

- [ ] **Step 11: Commit**

```bash
git add resources/js/
git commit -m "feat: add School sections frontend — page, composables, modals, sidebar"
```
