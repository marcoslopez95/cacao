# Secciones Universitarias — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar el CRUD completo de `Section` (tipo `university`) — unidad básica de inscripción y evaluación que vincula una materia con un período semestral/trimestral, con código de sección, cupo y aulas opcionales.

**Architecture:** `sections` table con polimorfismo por columna `type` (solo filas `university` por ahora; columnas escolares llegan en Spec 05). Backend sigue `FormRequest → Controller → Wrapper → Action → Resource`. Frontend sigue `FormComposable → Page → PermissionComposable → Type` con filtros de período y búsqueda de materia.

**Tech Stack:** PHP 8.3 · Laravel 13 · Pest 4 · Vue 3 · Inertia.js v3 · TypeScript · Tailwind CSS v4 · Wayfinder v0

---

## File Map

**Create:**
- `app/Enums/SectionType.php`
- `database/migrations/XXXX_create_sections_table.php`
- `app/Models/Section.php`
- `database/factories/SectionFactory.php`
- `app/Policies/SectionPolicy.php`
- `app/Http/Requests/Scheduling/StoreUniversitySectionRequest.php`
- `app/Http/Requests/Scheduling/UpdateUniversitySectionRequest.php`
- `app/Http/Wrappers/Scheduling/UniversitySectionWrapper.php`
- `app/Http/Resources/Scheduling/SectionResource.php`
- `app/Actions/Scheduling/CreateUniversitySectionAction.php`
- `app/Actions/Scheduling/UpdateUniversitySectionAction.php`
- `app/Actions/Scheduling/DeleteSectionAction.php`
- `app/Http/Controllers/Scheduling/UniversitySectionController.php`
- `tests/Feature/Scheduling/UniversitySectionControllerTest.php`
- `resources/js/composables/forms/useUniversitySectionForm.ts`
- `resources/js/composables/permissions/useSectionPermissions.ts`
- `resources/js/composables/filters/useUniversitySectionFilters.ts`
- `resources/js/components/scheduling/CreateUniversitySectionModal.vue`
- `resources/js/components/scheduling/EditUniversitySectionModal.vue`
- `resources/js/components/scheduling/DeleteSectionModal.vue`
- `resources/js/pages/scheduling/Sections/University.vue`

**Modify:**
- `database/data/permissions.yaml` — agregar `sections.*`
- `database/data/roles.yaml` — asignar `sections.*` al rol Admin
- `routes/web.php` — agregar rutas `/scheduling/sections/university`
- `resources/js/types/scheduling.ts` — agregar `UniversitySection`, `SectionSubject`, `SectionPeriod`, `SectionClassroom`, `UniversitySectionCollection`
- `resources/js/components/AppSidebar.vue` — agregar ítem Secciones Universitarias

---

### Task 1: SectionType Enum, Migration, Model, Factory

**Files:**
- Create: `app/Enums/SectionType.php`
- Create: `database/migrations/XXXX_create_sections_table.php`
- Create: `app/Models/Section.php`
- Create: `database/factories/SectionFactory.php`

- [ ] **Step 1: Create SectionType enum**

Create `app/Enums/SectionType.php`:

```php
<?php

namespace App\Enums;

enum SectionType: string
{
    case University = 'university';
    case School     = 'school';

    public function label(): string
    {
        return match ($this) {
            self::University => 'Universitaria',
            self::School     => 'Escolar',
        };
    }
}
```

- [ ] **Step 2: Generate migration**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan make:migration create_sections_table --no-interaction"
```

- [ ] **Step 3: Fill migration**

Edit the generated `database/migrations/XXXX_create_sections_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('university');
            $table->foreignId('period_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('code', 10);
            $table->foreignId('theory_classroom_id')->nullable()->constrained('classrooms')->restrictOnDelete();
            $table->foreignId('lab_classroom_id')->nullable()->constrained('classrooms')->restrictOnDelete();
            $table->unsignedSmallInteger('capacity');
            $table->timestamps();

            $table->unique(['period_id', 'subject_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
```

- [ ] **Step 4: Run migration**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan migrate --no-interaction"
```

Expected: `sections` table created.

- [ ] **Step 5: Create model**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan make:model Section --no-interaction"
```

Edit `app/Models/Section.php`:

```php
<?php

namespace App\Models;

use App\Enums\SectionType;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'period_id', 'subject_id', 'code', 'theory_classroom_id', 'lab_classroom_id', 'capacity'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
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
}
```

- [ ] **Step 6: Create factory**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan make:factory SectionFactory --model=Section --no-interaction"
```

Edit `database/factories/SectionFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\PeriodType;
use App\Enums\SectionType;
use App\Models\Period;
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

    public function forPeriodAndSubject(Period $period, Subject $subject): static
    {
        return $this->state([
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
        ]);
    }
}
```

- [ ] **Step 7: Run pint**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && /var/www/html/vendor/bin/pint --dirty --format agent"
```

- [ ] **Step 8: Commit**

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-sections && git add app/Enums/SectionType.php database/migrations app/Models/Section.php database/factories/SectionFactory.php && git commit -m "feat: add SectionType enum, Section model, migration, and factory"
```

---

### Task 2: Policy + Permissions Data

**Files:**
- Create: `app/Policies/SectionPolicy.php`
- Modify: `database/data/permissions.yaml`
- Modify: `database/data/roles.yaml`

- [ ] **Step 1: Create policy**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan make:policy SectionPolicy --model=Section --no-interaction"
```

Edit `app/Policies/SectionPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sections.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sections.create');
    }

    public function update(User $user, Section $section): bool
    {
        return $user->can('sections.update');
    }

    public function delete(User $user, Section $section): bool
    {
        return $user->can('sections.delete');
    }
}
```

- [ ] **Step 2: Update permissions.yaml**

Add at the end of the `permissions:` list in `database/data/permissions.yaml`:

```yaml
  - name: sections.view
    guard: web
  - name: sections.create
    guard: web
  - name: sections.update
    guard: web
  - name: sections.delete
    guard: web
```

- [ ] **Step 3: Update roles.yaml**

Add to the Admin role's `permissions:` list in `database/data/roles.yaml`:

```yaml
      - sections.view
      - sections.create
      - sections.update
      - sections.delete
```

- [ ] **Step 4: Run pint**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && /var/www/html/vendor/bin/pint --dirty --format agent"
```

- [ ] **Step 5: Commit**

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-sections && git add app/Policies/SectionPolicy.php database/data/permissions.yaml database/data/roles.yaml && git commit -m "feat: add SectionPolicy and sections.* permissions"
```

---

### Task 3: FormRequests, Wrapper, Resource, Actions

**Files:**
- Create: `app/Http/Requests/Scheduling/StoreUniversitySectionRequest.php`
- Create: `app/Http/Requests/Scheduling/UpdateUniversitySectionRequest.php`
- Create: `app/Http/Wrappers/Scheduling/UniversitySectionWrapper.php`
- Create: `app/Http/Resources/Scheduling/SectionResource.php`
- Create: `app/Actions/Scheduling/CreateUniversitySectionAction.php`
- Create: `app/Actions/Scheduling/UpdateUniversitySectionAction.php`
- Create: `app/Actions/Scheduling/DeleteSectionAction.php`

- [ ] **Step 1: Create StoreUniversitySectionRequest**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan make:request Scheduling/StoreUniversitySectionRequest --no-interaction"
```

Edit `app/Http/Requests/Scheduling/StoreUniversitySectionRequest.php`:

```php
<?php

namespace App\Http\Requests\Scheduling;

use App\Enums\PeriodType;
use App\Models\Period;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUniversitySectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Section::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => trim($this->input('code'))]);
        }
    }

    public function rules(): array
    {
        return [
            'period_id'           => ['required', 'integer', Rule::exists('periods', 'id')],
            'subject_id'          => ['required', 'integer', Rule::exists('subjects', 'id')],
            'code'                => [
                'required',
                'string',
                'max:10',
                Rule::unique('sections')->where(fn ($q) => $q
                    ->where('period_id', $this->input('period_id'))
                    ->where('subject_id', $this->input('subject_id'))
                ),
            ],
            'capacity'            => ['required', 'integer', 'min:1'],
            'theory_classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
            'lab_classroom_id'    => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v) {
            $period = Period::find($this->input('period_id'));

            if (! $period) {
                return;
            }

            if (! in_array($period->type, [PeriodType::Semester, PeriodType::Trimester])) {
                $v->errors()->add('period_id', 'Las secciones universitarias solo pueden pertenecer a períodos semestrales o trimestrales.');

                return;
            }

            $subject = Subject::with('pensum')->find($this->input('subject_id'));

            if ($subject?->pensum && $subject->pensum->period_type !== $period->type->value) {
                $v->errors()->add('subject_id', 'La materia no corresponde al tipo de período seleccionado.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'period_id.required'  => 'El período es obligatorio.',
            'period_id.exists'    => 'El período seleccionado no existe.',
            'subject_id.required' => 'La materia es obligatoria.',
            'subject_id.exists'   => 'La materia seleccionada no existe.',
            'code.required'       => 'El código de sección es obligatorio.',
            'code.max'            => 'El código no puede superar 10 caracteres.',
            'code.unique'         => 'Ya existe una sección con ese código para esta materia en este período.',
            'capacity.required'   => 'El cupo es obligatorio.',
            'capacity.min'        => 'El cupo debe ser al menos 1.',
        ];
    }
}
```

- [ ] **Step 2: Create UpdateUniversitySectionRequest**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan make:request Scheduling/UpdateUniversitySectionRequest --no-interaction"
```

Edit `app/Http/Requests/Scheduling/UpdateUniversitySectionRequest.php`:

```php
<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUniversitySectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('section')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => trim($this->input('code'))]);
        }
    }

    public function rules(): array
    {
        $section = $this->route('section');

        return [
            'code'                => [
                'required',
                'string',
                'max:10',
                Rule::unique('sections')->where(fn ($q) => $q
                    ->where('period_id', $section->period_id)
                    ->where('subject_id', $section->subject_id)
                )->ignore($section->id),
            ],
            'capacity'            => ['required', 'integer', 'min:1'],
            'theory_classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
            'lab_classroom_id'    => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'     => 'El código de sección es obligatorio.',
            'code.max'          => 'El código no puede superar 10 caracteres.',
            'code.unique'       => 'Ya existe una sección con ese código para esta materia en este período.',
            'capacity.required' => 'El cupo es obligatorio.',
            'capacity.min'      => 'El cupo debe ser al menos 1.',
        ];
    }
}
```

- [ ] **Step 3: Create UniversitySectionWrapper**

Create `app/Http/Wrappers/Scheduling/UniversitySectionWrapper.php`:

```php
<?php

namespace App\Http\Wrappers\Scheduling;

use Illuminate\Support\Collection;

class UniversitySectionWrapper extends Collection
{
    public function getPeriodId(): int
    {
        return (int) $this->get('period_id');
    }

    public function getSubjectId(): int
    {
        return (int) $this->get('subject_id');
    }

    public function getCode(): string
    {
        return (string) $this->get('code');
    }

    public function getCapacity(): int
    {
        return (int) $this->get('capacity');
    }

    public function getTheoryClassroomId(): ?int
    {
        return $this->get('theory_classroom_id') ? (int) $this->get('theory_classroom_id') : null;
    }

    public function getLabClassroomId(): ?int
    {
        return $this->get('lab_classroom_id') ? (int) $this->get('lab_classroom_id') : null;
    }
}
```

- [ ] **Step 4: Create SectionResource**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan make:resource Scheduling/SectionResource --no-interaction"
```

Edit `app/Http/Resources/Scheduling/SectionResource.php`:

```php
<?php

namespace App\Http\Resources\Scheduling;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
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
}
```

- [ ] **Step 5: Create CreateUniversitySectionAction**

Create `app/Actions/Scheduling/CreateUniversitySectionAction.php`:

```php
<?php

namespace App\Actions\Scheduling;

use App\Enums\SectionType;
use App\Http\Wrappers\Scheduling\UniversitySectionWrapper;
use App\Models\Section;

class CreateUniversitySectionAction
{
    public function handle(UniversitySectionWrapper $wrapper): Section
    {
        return Section::create([
            'type'                => SectionType::University,
            'period_id'           => $wrapper->getPeriodId(),
            'subject_id'          => $wrapper->getSubjectId(),
            'code'                => $wrapper->getCode(),
            'capacity'            => $wrapper->getCapacity(),
            'theory_classroom_id' => $wrapper->getTheoryClassroomId(),
            'lab_classroom_id'    => $wrapper->getLabClassroomId(),
        ]);
    }
}
```

- [ ] **Step 6: Create UpdateUniversitySectionAction**

Create `app/Actions/Scheduling/UpdateUniversitySectionAction.php`:

```php
<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\UniversitySectionWrapper;
use App\Models\Section;

class UpdateUniversitySectionAction
{
    public function handle(Section $section, UniversitySectionWrapper $wrapper): Section
    {
        $section->update([
            'code'                => $wrapper->getCode(),
            'capacity'            => $wrapper->getCapacity(),
            'theory_classroom_id' => $wrapper->getTheoryClassroomId(),
            'lab_classroom_id'    => $wrapper->getLabClassroomId(),
        ]);

        return $section;
    }
}
```

- [ ] **Step 7: Create DeleteSectionAction**

Create `app/Actions/Scheduling/DeleteSectionAction.php`:

```php
<?php

namespace App\Actions\Scheduling;

use App\Models\Section;

class DeleteSectionAction
{
    public function handle(Section $section): bool
    {
        return (bool) $section->delete();
    }
}
```

- [ ] **Step 8: Run pint**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && /var/www/html/vendor/bin/pint --dirty --format agent"
```

- [ ] **Step 9: Commit**

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-sections && git add app/Http/Requests/Scheduling/ app/Http/Wrappers/Scheduling/UniversitySectionWrapper.php app/Http/Resources/Scheduling/SectionResource.php app/Actions/Scheduling/ && git commit -m "feat: add Section FormRequests, Wrapper, Resource, and Actions"
```

---

### Task 4: Controller, Routes, Tests

**Files:**
- Create: `app/Http/Controllers/Scheduling/UniversitySectionController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Scheduling/UniversitySectionControllerTest.php`

- [ ] **Step 1: Write failing test first**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan make:test --pest Scheduling/UniversitySectionControllerTest --no-interaction"
```

Replace `tests/Feature/Scheduling/UniversitySectionControllerTest.php` with:

```php
<?php

use App\Enums\PeriodType;
use App\Enums\SectionType;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Pensum;
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

function userWithSectionPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

function semesterPeriodWithSubject(): array
{
    $period  = Period::factory()->semester()->create();
    $pensum  = Pensum::factory()->create(['period_type' => 'semester']);
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id]);

    return [$period, $subject];
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('admin can list university sections', function () {
    [$period, $subject] = semesterPeriodWithSubject();
    Section::factory()->forPeriodAndSubject($period, $subject)->count(3)->create();

    $this->actingAs(userWithSectionPerm('sections.view'))
        ->get('/scheduling/sections/university')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('scheduling/Sections/University', false)
            ->has('sections', 3)
        );
});

test('admin can filter sections by period', function () {
    [$period1, $subject1] = semesterPeriodWithSubject();
    [$period2, $subject2] = semesterPeriodWithSubject();

    Section::factory()->forPeriodAndSubject($period1, $subject1)->count(2)->create();
    Section::factory()->forPeriodAndSubject($period2, $subject2)->count(1)->create();

    $this->actingAs(userWithSectionPerm('sections.view'))
        ->get("/scheduling/sections/university?period_id={$period1->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('sections', 2));
});

test('unauthenticated user cannot access university sections', function () {
    $this->get('/scheduling/sections/university')->assertRedirect('/login');
});

test('user without permission cannot list sections', function () {
    $this->actingAs(User::factory()->create())
        ->get('/scheduling/sections/university')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('admin can create a university section', function () {
    [$period, $subject] = semesterPeriodWithSubject();

    $this->actingAs(userWithSectionPerm('sections.create'))
        ->post('/scheduling/sections/university', [
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
            'code'       => '01',
            'capacity'   => 30,
        ])
        ->assertRedirect(route('scheduling.sections.university.index'));

    expect(Section::where('period_id', $period->id)
        ->where('subject_id', $subject->id)
        ->where('code', '01')
        ->exists()
    )->toBeTrue();
});

test('cannot create section with year period', function () {
    $period  = Period::factory()->year()->create();
    $pensum  = Pensum::factory()->create(['period_type' => 'year']);
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id]);

    $this->actingAs(userWithSectionPerm('sections.create'))
        ->post('/scheduling/sections/university', [
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
            'code'       => '01',
            'capacity'   => 30,
        ])
        ->assertSessionHasErrors('period_id');
});

test('cannot create section with mismatched pensum period type', function () {
    $period  = Period::factory()->semester()->create();
    $pensum  = Pensum::factory()->create(['period_type' => 'trimester']);
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id]);

    $this->actingAs(userWithSectionPerm('sections.create'))
        ->post('/scheduling/sections/university', [
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
            'code'       => '01',
            'capacity'   => 30,
        ])
        ->assertSessionHasErrors('subject_id');
});

test('cannot create duplicate section code for same period and subject', function () {
    [$period, $subject] = semesterPeriodWithSubject();
    Section::factory()->forPeriodAndSubject($period, $subject)->create(['code' => '01']);

    $this->actingAs(userWithSectionPerm('sections.create'))
        ->post('/scheduling/sections/university', [
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
            'code'       => '01',
            'capacity'   => 25,
        ])
        ->assertSessionHasErrors('code');
});

test('capacity must be at least 1', function () {
    [$period, $subject] = semesterPeriodWithSubject();

    $this->actingAs(userWithSectionPerm('sections.create'))
        ->post('/scheduling/sections/university', [
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
            'code'       => '01',
            'capacity'   => 0,
        ])
        ->assertSessionHasErrors('capacity');
});

test('user without permission cannot create section', function () {
    [$period, $subject] = semesterPeriodWithSubject();

    $this->actingAs(User::factory()->create())
        ->post('/scheduling/sections/university', [
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
            'code'       => '01',
            'capacity'   => 30,
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('admin can update a section code and capacity', function () {
    [$period, $subject] = semesterPeriodWithSubject();
    $section = Section::factory()->forPeriodAndSubject($period, $subject)->create(['code' => '01', 'capacity' => 30]);

    $this->actingAs(userWithSectionPerm('sections.update'))
        ->patch("/scheduling/sections/university/{$section->id}", [
            'code'     => '02',
            'capacity' => 40,
        ])
        ->assertRedirect(route('scheduling.sections.university.index'));

    expect($section->fresh()->code)->toBe('02');
    expect($section->fresh()->capacity)->toBe(40);
});

test('user without permission cannot update section', function () {
    [$period, $subject] = semesterPeriodWithSubject();
    $section = Section::factory()->forPeriodAndSubject($period, $subject)->create();

    $this->actingAs(User::factory()->create())
        ->patch("/scheduling/sections/university/{$section->id}", [
            'code'     => '99',
            'capacity' => 10,
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('admin can delete a section', function () {
    [$period, $subject] = semesterPeriodWithSubject();
    $section = Section::factory()->forPeriodAndSubject($period, $subject)->create();

    $this->actingAs(userWithSectionPerm('sections.delete'))
        ->delete("/scheduling/sections/university/{$section->id}")
        ->assertRedirect(route('scheduling.sections.university.index'));

    expect(Section::find($section->id))->toBeNull();
});

test('user without permission cannot delete section', function () {
    [$period, $subject] = semesterPeriodWithSubject();
    $section = Section::factory()->forPeriodAndSubject($period, $subject)->create();

    $this->actingAs(User::factory()->create())
        ->delete("/scheduling/sections/university/{$section->id}")
        ->assertForbidden();
});
```

- [ ] **Step 2: Run failing test to confirm it fails**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan test --compact --filter=UniversitySectionControllerTest 2>&1 | tail -5"
```

Expected: FAIL (route not found).

- [ ] **Step 3: Add routes to routes/web.php**

Inside the existing `Route::middleware(['auth', 'verified'])->prefix('scheduling')->name('scheduling.')->group(...)` block, after the professors routes, add:

```php
Route::get('sections/university', [UniversitySectionController::class, 'index'])->name('sections.university.index');
Route::post('sections/university', [UniversitySectionController::class, 'store'])->name('sections.university.store');
Route::patch('sections/university/{section}', [UniversitySectionController::class, 'update'])->name('sections.university.update');
Route::delete('sections/university/{section}', [UniversitySectionController::class, 'destroy'])->name('sections.university.destroy');
```

Also add the import at the top:

```php
use App\Http\Controllers\Scheduling\UniversitySectionController;
```

- [ ] **Step 4: Create controller**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan make:controller Scheduling/UniversitySectionController --no-interaction"
```

Edit `app/Http/Controllers/Scheduling/UniversitySectionController.php`:

```php
<?php

namespace App\Http\Controllers\Scheduling;

use App\Actions\Scheduling\CreateUniversitySectionAction;
use App\Actions\Scheduling\DeleteSectionAction;
use App\Actions\Scheduling\UpdateUniversitySectionAction;
use App\Enums\ClassroomType;
use App\Enums\PeriodType;
use App\Enums\SectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreUniversitySectionRequest;
use App\Http\Requests\Scheduling\UpdateUniversitySectionRequest;
use App\Http\Resources\Scheduling\SectionResource;
use App\Http\Wrappers\Scheduling\UniversitySectionWrapper;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UniversitySectionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Section::class);

        $sections = Section::where('type', SectionType::University)
            ->when($request->input('period_id'), fn ($q, $id) => $q->where('period_id', $id))
            ->when($request->input('subject'), fn ($q, $s) => $q->whereHas('subject', fn ($q2) => $q2->where('name', 'ilike', "%{$s}%")->orWhere('code', 'ilike', "%{$s}%")))
            ->with(['period', 'subject', 'theoryClassroom', 'labClassroom'])
            ->orderByDesc('id')
            ->get();

        $periods = Period::whereIn('type', [PeriodType::Semester, PeriodType::Trimester])
            ->orderByDesc('id')
            ->get(['id', 'name', 'type']);

        $subjects = Subject::with('pensum:id,period_type')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'pensum_id']);

        $classrooms = Classroom::orderBy('identifier')->get(['id', 'identifier', 'type', 'capacity']);

        return Inertia::render('scheduling/Sections/University', [
            'sections'   => SectionResource::collection($sections)->resolve(),
            'periods'    => $periods->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'type' => $p->type->value]),
            'subjects'   => $subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code, 'pensumPeriodType' => $s->pensum?->period_type]),
            'classrooms' => $classrooms->map(fn ($c) => ['id' => $c->id, 'identifier' => $c->identifier, 'type' => $c->type->value, 'capacity' => $c->capacity]),
            'filters'    => ['period_id' => $request->input('period_id') ? (int) $request->input('period_id') : null, 'subject' => $request->input('subject')],
            'can'        => [
                'create' => $request->user()->can('sections.create'),
                'update' => $request->user()->can('sections.update'),
                'delete' => $request->user()->can('sections.delete'),
            ],
        ]);
    }

    public function store(StoreUniversitySectionRequest $request, CreateUniversitySectionAction $action): RedirectResponse
    {
        $action->handle(new UniversitySectionWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sección creada.']);

        return to_route('scheduling.sections.university.index');
    }

    public function update(UpdateUniversitySectionRequest $request, Section $section, UpdateUniversitySectionAction $action): RedirectResponse
    {
        $action->handle($section, new UniversitySectionWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sección actualizada.']);

        return to_route('scheduling.sections.university.index');
    }

    public function destroy(Section $section, DeleteSectionAction $action): RedirectResponse
    {
        Gate::authorize('delete', $section);

        $action->handle($section);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sección eliminada.']);

        return to_route('scheduling.sections.university.index');
    }
}
```

- [ ] **Step 5: Run tests**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan test --compact --filter=UniversitySectionControllerTest 2>&1"
```

Expected: all section tests PASS.

- [ ] **Step 6: Run pint**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && /var/www/html/vendor/bin/pint --dirty --format agent"
```

- [ ] **Step 7: Commit**

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-sections && git add app/Http/Controllers/Scheduling/UniversitySectionController.php routes/web.php tests/Feature/Scheduling/UniversitySectionControllerTest.php && git commit -m "feat: add UniversitySectionController, routes, and feature tests"
```

---

### Task 5: Frontend Types, Composables, Modals

**Files:**
- Modify: `resources/js/types/scheduling.ts`
- Create: `resources/js/composables/forms/useUniversitySectionForm.ts`
- Create: `resources/js/composables/permissions/useSectionPermissions.ts`
- Create: `resources/js/composables/filters/useUniversitySectionFilters.ts`
- Create: `resources/js/components/scheduling/CreateUniversitySectionModal.vue`
- Create: `resources/js/components/scheduling/EditUniversitySectionModal.vue`
- Create: `resources/js/components/scheduling/DeleteSectionModal.vue`

- [ ] **Step 1: Generate Wayfinder routes**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan wayfinder:generate --no-interaction"
```

This creates `resources/js/routes/scheduling/sections/university.ts` (or similar — check the actual path output).

- [ ] **Step 2: Update scheduling types**

Add to the end of `resources/js/types/scheduling.ts`:

```typescript
export type SectionPeriod = {
    id: number
    name: string
    type: 'semester' | 'trimester'
}

export type SectionSubject = {
    id: number
    name: string
    code: string
}

export type SectionClassroom = {
    id: number
    identifier: string
    capacity: number
}

export type UniversitySection = {
    id: number
    type: 'university'
    code: string
    capacity: number
    period: SectionPeriod
    subject: SectionSubject
    theoryClassroom: SectionClassroom | null
    labClassroom: SectionClassroom | null
}

export type UniversitySectionCollection = UniversitySection[]

export type AvailablePeriod = {
    id: number
    name: string
    type: 'semester' | 'trimester'
}

export type SubjectForSection = {
    id: number
    name: string
    code: string
    pensumPeriodType: string | null
}

export type ClassroomForSection = {
    id: number
    identifier: string
    type: 'theory' | 'laboratory'
    capacity: number
}
```

- [ ] **Step 3: Create useUniversitySectionForm.ts**

Check the Wayfinder-generated path for university sections routes. The route names are `scheduling.sections.university.*`, so Wayfinder generates based on the controller path. Check the actual file at `resources/js/routes/scheduling/sections/` and use the correct import path.

Create `resources/js/composables/forms/useUniversitySectionForm.ts`:

```typescript
import { useForm } from '@inertiajs/vue3'
import { destroy, store, update } from '@/routes/scheduling/sections/university'
import type { UniversitySection } from '@/types/scheduling'

export function useUniversitySectionForm() {
    const storeOps = {
        form() {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({
                    period_id:           null as number | null,
                    subject_id:          null as number | null,
                    code:                '',
                    capacity:            30,
                    theory_classroom_id: null as number | null,
                    lab_classroom_id:    null as number | null,
                }),
            }
        },
    }

    const updateOps = {
        form({ section }: { section: UniversitySection }) {
            return {
                url:    update.url({ section }),
                method: 'patch' as const,
                data:   useForm({
                    code:                section.code,
                    capacity:            section.capacity,
                    theory_classroom_id: section.theoryClassroom?.id ?? null,
                    lab_classroom_id:    section.labClassroom?.id ?? null,
                }),
            }
        },
    }

    const removeOps = {
        submit({ section }: { section: UniversitySection }): void {
            useForm({}).delete(destroy.url({ section }))
        },
    }

    return { store: storeOps, update: updateOps, remove: removeOps }
}
```

- [ ] **Step 4: Create useSectionPermissions.ts**

Create `resources/js/composables/permissions/useSectionPermissions.ts`:

```typescript
import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useSectionPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('sections.create'))
    const canUpdate = computed(() => can('sections.update'))
    const canDelete = computed(() => can('sections.delete'))

    return { canCreate, canUpdate, canDelete }
}
```

- [ ] **Step 5: Create useUniversitySectionFilters.ts**

Check how existing filter composables work (e.g. `resources/js/composables/filters/usePeriodFilters.ts`) and follow the same pattern.

Create `resources/js/composables/filters/useUniversitySectionFilters.ts`:

```typescript
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { index } from '@/routes/scheduling/sections/university'

export function useUniversitySectionFilters(
    initialPeriodId: number | null,
    initialSubject: string | null,
) {
    const periodId = ref<number | null>(initialPeriodId)
    const subject  = ref<string | null>(initialSubject)

    function applyFilters(): void {
        router.get(
            index.url(),
            { period_id: periodId.value ?? undefined, subject: subject.value || undefined },
            { preserveState: true, replace: true },
        )
    }

    return { periodId, subject, applyFilters }
}
```

- [ ] **Step 6: Create CreateUniversitySectionModal.vue**

Create `resources/js/components/scheduling/CreateUniversitySectionModal.vue`:

```vue
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/scheduling/sections/university'
import type { AvailablePeriod, ClassroomForSection, SubjectForSection } from '@/types/scheduling'

const props = defineProps<{
    open: boolean
    periods: AvailablePeriod[]
    subjects: SubjectForSection[]
    classrooms: ClassroomForSection[]
}>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        period_id:           null as number | null,
        subject_id:          null as number | null,
        code:                '',
        capacity:            30,
        theory_classroom_id: null as number | null,
        lab_classroom_id:    null as number | null,
    })
}

const form = ref(makeForm())

const selectedPeriodType = computed(() => {
    if (! form.value.period_id) { return null }
    return props.periods.find((p) => p.id === form.value.period_id)?.type ?? null
})

const filteredSubjects = computed(() => {
    if (! selectedPeriodType.value) { return props.subjects }
    return props.subjects.filter((s) => s.pensumPeriodType === selectedPeriodType.value)
})

const theoryClassrooms = computed(() => props.classrooms.filter((c) => c.type === 'theory'))
const labClassrooms    = computed(() => props.classrooms.filter((c) => c.type === 'laboratory'))

function close(v: boolean): void {
    emit('update:open', v)
}

watch(
    () => props.open,
    (opened) => {
        if (opened) {
            form.value = makeForm()
        }
    },
)

watch(
    () => form.value.period_id,
    () => {
        form.value.subject_id = null
    },
)

function submit(): void {
    form.value.post(store.url(), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Nueva sección universitaria" size="md" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="cs-period" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Período
                        </label>
                        <select id="cs-period" v-model="form.period_id" class="input" required>
                            <option :value="null" disabled>Seleccionar período</option>
                            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <InputError :message="form.errors.period_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="cs-code" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Código
                        </label>
                        <input id="cs-code" v-model="form.code" class="input" placeholder="Ej: 01" maxlength="10" required />
                        <InputError :message="form.errors.code" />
                    </div>
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="cs-subject" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Materia
                    </label>
                    <select id="cs-subject" v-model="form.subject_id" class="input" required :disabled="!form.period_id">
                        <option :value="null" disabled>{{ form.period_id ? 'Seleccionar materia' : 'Selecciona un período primero' }}</option>
                        <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.code }} — {{ s.name }}</option>
                    </select>
                    <InputError :message="form.errors.subject_id" />
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="cs-capacity" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Cupo
                    </label>
                    <input id="cs-capacity" v-model.number="form.capacity" type="number" min="1" class="input" required />
                    <InputError :message="form.errors.capacity" />
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="cs-theory" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Aula teórica (opcional)
                        </label>
                        <select id="cs-theory" v-model="form.theory_classroom_id" class="input">
                            <option :value="null">Sin aula teórica</option>
                            <option v-for="c in theoryClassrooms" :key="c.id" :value="c.id">{{ c.identifier }} ({{ c.capacity }})</option>
                        </select>
                        <InputError :message="form.errors.theory_classroom_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="cs-lab" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Aula laboratorio (opcional)
                        </label>
                        <select id="cs-lab" v-model="form.lab_classroom_id" class="input">
                            <option :value="null">Sin laboratorio</option>
                            <option v-for="c in labClassrooms" :key="c.id" :value="c.id">{{ c.identifier }} ({{ c.capacity }})</option>
                        </select>
                        <InputError :message="form.errors.lab_classroom_id" />
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Crear sección</Button>
            </div>
        </form>
    </Modal>
</template>
```

- [ ] **Step 7: Create EditUniversitySectionModal.vue**

Create `resources/js/components/scheduling/EditUniversitySectionModal.vue`:

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/scheduling/sections/university'
import type { ClassroomForSection, UniversitySection } from '@/types/scheduling'

const props = defineProps<{
    section: UniversitySection
    open: boolean
    classrooms: ClassroomForSection[]
}>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        code:                props.section.code,
        capacity:            props.section.capacity,
        theory_classroom_id: props.section.theoryClassroom?.id ?? null,
        lab_classroom_id:    props.section.labClassroom?.id ?? null,
    })
}

const form = ref(makeForm())
const theoryClassrooms = props.classrooms.filter((c) => c.type === 'theory')
const labClassrooms    = props.classrooms.filter((c) => c.type === 'laboratory')

function close(v: boolean): void {
    emit('update:open', v)
}

watch(
    () => props.open,
    (opened) => {
        if (opened) {
            form.value = makeForm()
        }
    },
)

function submit(): void {
    form.value.patch(update.url({ section: props.section }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Editar sección" size="md" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:4px;">
                    <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">Materia</p>
                    <p style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);margin:0;">
                        {{ section.subject.code }} — {{ section.subject.name }}
                    </p>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="es-code" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Código
                        </label>
                        <input id="es-code" v-model="form.code" class="input" maxlength="10" required />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="es-capacity" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Cupo
                        </label>
                        <input id="es-capacity" v-model.number="form.capacity" type="number" min="1" class="input" required />
                        <InputError :message="form.errors.capacity" />
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="es-theory" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Aula teórica
                        </label>
                        <select id="es-theory" v-model="form.theory_classroom_id" class="input">
                            <option :value="null">Sin aula teórica</option>
                            <option v-for="c in theoryClassrooms" :key="c.id" :value="c.id">{{ c.identifier }}</option>
                        </select>
                        <InputError :message="form.errors.theory_classroom_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="es-lab" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Laboratorio
                        </label>
                        <select id="es-lab" v-model="form.lab_classroom_id" class="input">
                            <option :value="null">Sin laboratorio</option>
                            <option v-for="c in labClassrooms" :key="c.id" :value="c.id">{{ c.identifier }}</option>
                        </select>
                        <InputError :message="form.errors.lab_classroom_id" />
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Guardar cambios</Button>
            </div>
        </form>
    </Modal>
</template>
```

- [ ] **Step 8: Create DeleteSectionModal.vue**

Create `resources/js/components/scheduling/DeleteSectionModal.vue`:

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/scheduling/sections/university'
import type { UniversitySection } from '@/types/scheduling'

const props = defineProps<{ section: UniversitySection; open: boolean }>()
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
    <Modal :open="open" title="Eliminar sección" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 12px;">
                ¿Eliminar la sección <strong>{{ section.subject.code }} — {{ section.code }}</strong>?
            </p>
            <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
                No se puede eliminar si tiene horarios o inscripciones asignadas.
            </p>
            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="danger" :loading="form.processing">Eliminar</Button>
            </div>
        </form>
    </Modal>
</template>
```

- [ ] **Step 9: Commit**

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-sections && git add resources/js/types/scheduling.ts resources/js/composables/ resources/js/components/scheduling/ resources/js/routes/ && git commit -m "feat: add university sections frontend types, composables, and modals"
```

---

### Task 6: Index Page + Sidebar

**Files:**
- Create: `resources/js/pages/scheduling/Sections/University.vue`
- Modify: `resources/js/components/AppSidebar.vue`

- [ ] **Step 1: Create University.vue page**

Create `resources/js/pages/scheduling/Sections/University.vue`:

```vue
<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import CreateUniversitySectionModal from '@/components/scheduling/CreateUniversitySectionModal.vue'
import DeleteSectionModal from '@/components/scheduling/DeleteSectionModal.vue'
import EditUniversitySectionModal from '@/components/scheduling/EditUniversitySectionModal.vue'
import { useUniversitySectionFilters } from '@/composables/filters/useUniversitySectionFilters'
import { useUniversitySectionForm } from '@/composables/forms/useUniversitySectionForm'
import { useSectionPermissions } from '@/composables/permissions/useSectionPermissions'
import { index } from '@/routes/scheduling/sections/university'
import type {
    AvailablePeriod,
    ClassroomForSection,
    SubjectForSection,
    UniversitySection,
    UniversitySectionCollection,
} from '@/types/scheduling'

type Props = {
    sections: UniversitySectionCollection
    periods: AvailablePeriod[]
    subjects: SubjectForSection[]
    classrooms: ClassroomForSection[]
    filters: { period_id: number | null; subject: string | null }
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Horarios', href: '#' },
        { title: 'Secciones Universitarias', href: index.url() },
    ],
})

const { canCreate, canUpdate, canDelete } = useSectionPermissions()
const {} = useUniversitySectionForm()
const { periodId, subject, applyFilters } = useUniversitySectionFilters(props.filters.period_id, props.filters.subject)

const showCreate = ref(false)
const editingSection = ref<UniversitySection | null>(null)
const deletingSection = ref<UniversitySection | null>(null)
</script>

<template>
    <Head title="Secciones Universitarias" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Secciones Universitarias
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Grupos de cursado por materia y período
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
            <input
                v-model="subject"
                class="input"
                style="max-width:220px;"
                placeholder="Buscar materia..."
                @input="applyFilters"
            />
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Cód.</th>
                        <th>Período</th>
                        <th>Cupo</th>
                        <th>Aulas</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="sections.length === 0">
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px;">
                            No hay secciones registradas.
                        </td>
                    </tr>
                    <tr v-for="section in sections" :key="section.id">
                        <td style="font-weight:500;">{{ section.subject.code }} — {{ section.subject.name }}</td>
                        <td>{{ section.code }}</td>
                        <td style="color:var(--text-secondary);">{{ section.period.name }}</td>
                        <td>{{ section.capacity }}</td>
                        <td style="color:var(--text-secondary);font-size:var(--text-xs);">
                            <span v-if="section.theoryClassroom">T: {{ section.theoryClassroom.identifier }}</span>
                            <span v-if="section.theoryClassroom && section.labClassroom"> · </span>
                            <span v-if="section.labClassroom">L: {{ section.labClassroom.identifier }}</span>
                            <span v-if="!section.theoryClassroom && !section.labClassroom">—</span>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;justify-content:flex-end;gap:8px;">
                                <Button v-if="canUpdate" variant="ghost" size="sm" icon="pencil" @click="editingSection = section" />
                                <Button v-if="canDelete" variant="ghost" size="sm" icon="trash" @click="deletingSection = section" />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateUniversitySectionModal
        :open="showCreate"
        :periods="periods"
        :subjects="subjects"
        :classrooms="classrooms"
        @update:open="showCreate = $event"
    />

    <EditUniversitySectionModal
        v-if="editingSection"
        :section="editingSection"
        :open="editingSection !== null"
        :classrooms="classrooms"
        @update:open="editingSection = $event ? editingSection : null"
    />

    <DeleteSectionModal
        v-if="deletingSection"
        :section="deletingSection"
        :open="deletingSection !== null"
        @update:open="deletingSection = $event ? deletingSection : null"
    />
</template>
```

- [ ] **Step 2: Update AppSidebar.vue**

In `resources/js/components/AppSidebar.vue`:

1. Add import: `import { index as universitySectionsIndex } from '@/routes/scheduling/sections/university'`
2. Add to the Horarios group after the Profesores item:
   `{ icon: 'layout-list', label: 'Secciones Univ.', href: universitySectionsIndex.url() }`

- [ ] **Step 3: Commit**

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-sections && git add resources/js/pages/scheduling/ resources/js/components/AppSidebar.vue && git commit -m "feat: add university sections page and sidebar item"
```

---

### Task 7: Full Test Run + Final Verification

- [ ] **Step 1: Run all tests**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan test --compact 2>&1 | tail -5"
```

Expected: all tests pass (≥336 — 322 baseline + ~14 new section tests).

- [ ] **Step 2: Run pint on all changed files**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && /var/www/html/vendor/bin/pint --dirty --format agent"
```

- [ ] **Step 3: Commit pint fixes if any**

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-sections && git add -p && git commit -m "style: pint formatting fixes"
```

- [ ] **Step 4: Final test run**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-sections && php artisan test --compact 2>&1 | tail -3"
```

Expected: 0 failures.
