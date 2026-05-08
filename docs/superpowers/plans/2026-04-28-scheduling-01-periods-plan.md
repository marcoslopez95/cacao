# Períodos — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear el CRUD completo de Períodos académicos, con transiciones de estado (upcoming → active → closed), filtro por tipo y página Vue.

**Architecture:** `FormRequest → Controller → Wrapper → Action → Resource` en backend; `FormComposable → Page → PermissionComposable → Type` en frontend. Las acciones de transición (activate/close) retornan `bool` y el controller muestra el toast correspondiente.

**Tech Stack:** PHP 8.3, Laravel 13, Pest v4, Inertia.js v3, Vue 3, Tailwind CSS v4, Wayfinder v0, Spatie Permission.

---

## File Map

### Nuevos archivos PHP
```
app/Enums/PeriodType.php
app/Enums/PeriodStatus.php
app/Models/Period.php
database/migrations/XXXX_add_trimester_to_pensums_period_type.php
database/migrations/XXXX_create_periods_table.php
database/factories/PeriodFactory.php
app/Policies/PeriodPolicy.php
app/Http/Requests/Scheduling/StorePeriodRequest.php
app/Http/Requests/Scheduling/UpdatePeriodRequest.php
app/Http/Wrappers/Scheduling/PeriodWrapper.php
app/Http/Resources/Scheduling/PeriodResource.php
app/Actions/Scheduling/CreatePeriodAction.php
app/Actions/Scheduling/UpdatePeriodAction.php
app/Actions/Scheduling/ActivatePeriodAction.php
app/Actions/Scheduling/ClosePeriodAction.php
app/Actions/Scheduling/DeletePeriodAction.php
app/Http/Controllers/Scheduling/PeriodController.php
tests/Feature/Scheduling/PeriodControllerTest.php
```

### Nuevos archivos frontend
```
resources/js/types/scheduling.ts
resources/js/composables/forms/usePeriodForm.ts
resources/js/composables/permissions/usePeriodPermissions.ts
resources/js/composables/filters/usePeriodFilters.ts
resources/js/components/scheduling/CreatePeriodModal.vue
resources/js/components/scheduling/EditPeriodModal.vue
resources/js/components/scheduling/DeletePeriodModal.vue
resources/js/pages/scheduling/Periods/Index.vue
```

### Archivos modificados
```
database/data/permissions.yaml          — 4 nuevos permisos periods.*
database/data/roles.yaml                — permisos al rol Admin
routes/web.php                          — grupo /scheduling/periods
resources/js/components/AppSidebar.vue  — grupo "Horarios" con ítem Períodos
```

---

## Task 1: Enums PeriodType y PeriodStatus

**Files:**
- Create: `app/Enums/PeriodType.php`
- Create: `app/Enums/PeriodStatus.php`

- [ ] **Step 1: Crear `app/Enums/PeriodType.php`**

```php
<?php

namespace App\Enums;

enum PeriodType: string
{
    case Semester  = 'semester';
    case Year      = 'year';
    case Trimester = 'trimester';

    public function label(): string
    {
        return match ($this) {
            self::Semester  => 'Semestral',
            self::Year      => 'Anual',
            self::Trimester => 'Trimestral',
        };
    }
}
```

- [ ] **Step 2: Crear `app/Enums/PeriodStatus.php`**

```php
<?php

namespace App\Enums;

enum PeriodStatus: string
{
    case Upcoming = 'upcoming';
    case Active   = 'active';
    case Closed   = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Próximo',
            self::Active   => 'Activo',
            self::Closed   => 'Cerrado',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Upcoming => $next === self::Active,
            self::Active   => $next === self::Closed,
            self::Closed   => false,
        };
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Enums/PeriodType.php app/Enums/PeriodStatus.php
git commit -m "feat(scheduling): add PeriodType and PeriodStatus enums"
```

---

## Task 2: Migración Pensum + Migración + Modelo + Factory de Period

**Files:**
- Create: `database/migrations/XXXX_add_trimester_to_pensums_period_type.php`
- Create: `database/migrations/XXXX_create_periods_table.php`
- Create: `app/Models/Period.php`
- Create: `database/factories/PeriodFactory.php`

- [ ] **Step 1: Crear migración para agregar `trimester` al CHECK de pensums**

```bash
vendor/bin/sail artisan make:migration add_trimester_to_pensums_period_type --no-interaction
```

Editar el archivo generado:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE pensums DROP CONSTRAINT IF EXISTS pensums_period_type_check');
        DB::statement("ALTER TABLE pensums ADD CONSTRAINT pensums_period_type_check CHECK (period_type IN ('semester', 'year', 'trimester'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pensums DROP CONSTRAINT IF EXISTS pensums_period_type_check');
        DB::statement("ALTER TABLE pensums ADD CONSTRAINT pensums_period_type_check CHECK (period_type IN ('semester', 'year'))");
    }
};
```

- [ ] **Step 2: Crear migración de la tabla `periods`**

```bash
vendor/bin/sail artisan make:migration create_periods_table --no-interaction
```

Editar el archivo generado:

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
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique();
            $table->enum('type', ['semester', 'year', 'trimester']);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['upcoming', 'active', 'closed'])->default('upcoming');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE periods ADD CONSTRAINT periods_dates_check CHECK (end_date > start_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
```

- [ ] **Step 3: Ejecutar migraciones**

```bash
vendor/bin/sail artisan migrate --no-interaction
```

Expected: ambas migraciones se ejecutan sin error.

- [ ] **Step 4: Crear `app/Models/Period.php`**

```php
<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use App\Enums\PeriodType;
use Database\Factories\PeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type', 'start_date', 'end_date', 'status'])]
class Period extends Model
{
    /** @use HasFactory<PeriodFactory> */
    use HasFactory;

    /** @var array<string, string> */
    protected $casts = [
        'type'       => PeriodType::class,
        'status'     => PeriodStatus::class,
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function lapses(): HasMany
    {
        return $this->hasMany(Lapse::class)->orderBy('number');
    }
}
```

> Nota: la clase `Lapse` aún no existe — se crea en Spec 02. PHP no rompe hasta que se llame al método.

- [ ] **Step 5: Crear `database/factories/PeriodFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Enums\PeriodStatus;
use App\Enums\PeriodType;
use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Period> */
class PeriodFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('2025-01-01', '2026-06-01');

        return [
            'name'       => fake()->unique()->numerify('20##-#'),
            'type'       => PeriodType::Semester,
            'start_date' => $start->format('Y-m-d'),
            'end_date'   => (clone $start)->modify('+6 months')->format('Y-m-d'),
            'status'     => PeriodStatus::Upcoming,
        ];
    }

    public function semester(): static
    {
        return $this->state(['type' => PeriodType::Semester]);
    }

    public function year(): static
    {
        $start = fake()->dateTimeBetween('2025-01-01', '2025-03-01');

        return $this->state([
            'type'       => PeriodType::Year,
            'start_date' => $start->format('Y-m-d'),
            'end_date'   => (clone $start)->modify('+11 months')->format('Y-m-d'),
        ]);
    }

    public function trimester(): static
    {
        $start = fake()->dateTimeBetween('2025-01-01', '2026-06-01');

        return $this->state([
            'type'       => PeriodType::Trimester,
            'start_date' => $start->format('Y-m-d'),
            'end_date'   => (clone $start)->modify('+3 months')->format('Y-m-d'),
        ]);
    }

    public function active(): static
    {
        return $this->state(['status' => PeriodStatus::Active]);
    }

    public function closed(): static
    {
        return $this->state(['status' => PeriodStatus::Closed]);
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add database/migrations/ app/Models/Period.php database/factories/PeriodFactory.php
git commit -m "feat(scheduling): add periods migration, model and factory"
```

---

## Task 3: Permisos

**Files:**
- Modify: `database/data/permissions.yaml`
- Modify: `database/data/roles.yaml`

- [ ] **Step 1: Agregar permisos en `database/data/permissions.yaml`**

Buscar el último bloque de permisos (ej. `classrooms.*`) y añadir a continuación:

```yaml
  - name: periods.view
    guard: web
  - name: periods.create
    guard: web
  - name: periods.update
    guard: web
  - name: periods.delete
    guard: web
```

- [ ] **Step 2: Asignar permisos al rol Admin en `database/data/roles.yaml`**

Dentro del bloque `name: Admin`, en `permissions:`, añadir al final:

```yaml
      - periods.view
      - periods.create
      - periods.update
      - periods.delete
```

- [ ] **Step 3: Commit**

```bash
git add database/data/permissions.yaml database/data/roles.yaml
git commit -m "feat(scheduling): add periods.* permissions to Admin role"
```

---

## Task 4: Tests de período (TDD — escribir primero, fallarán hasta Task 5)

**Files:**
- Create: `tests/Feature/Scheduling/PeriodControllerTest.php`

- [ ] **Step 1: Crear archivo de test**

```bash
vendor/bin/sail artisan make:test --pest Scheduling/PeriodControllerTest --no-interaction
```

- [ ] **Step 2: Escribir los tests**

Reemplazar el contenido del archivo generado:

```php
<?php

use App\Enums\PeriodStatus;
use App\Models\Period;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['periods.view', 'periods.create', 'periods.update', 'periods.delete'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWithPeriodPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('admin can list periods', function () {
    Period::factory()->count(3)->create();

    $this->actingAs(userWithPeriodPerm('periods.view'))
        ->get('/scheduling/periods')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('scheduling/Periods/Index', false)
            ->has('periods', 3)
        );
});

test('admin can filter periods by type', function () {
    Period::factory()->semester()->count(2)->create();
    Period::factory()->year()->count(1)->create();

    $this->actingAs(userWithPeriodPerm('periods.view'))
        ->get('/scheduling/periods?type=semester')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('periods', 2));
});

test('unauthenticated user cannot list periods', function () {
    $this->get('/scheduling/periods')->assertRedirect('/login');
});

test('user without permission cannot list periods', function () {
    $this->actingAs(User::factory()->create())
        ->get('/scheduling/periods')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('admin can create a semester period', function () {
    $this->actingAs(userWithPeriodPerm('periods.create'))
        ->post('/scheduling/periods', [
            'name'       => '2026-1',
            'type'       => 'semester',
            'start_date' => '2026-02-01',
            'end_date'   => '2026-07-31',
        ])
        ->assertRedirect(route('scheduling.periods.index'));

    expect(Period::where('name', '2026-1')->exists())->toBeTrue();
});

test('admin can create an annual period', function () {
    $this->actingAs(userWithPeriodPerm('periods.create'))
        ->post('/scheduling/periods', [
            'name'       => '2025-2026',
            'type'       => 'year',
            'start_date' => '2025-09-01',
            'end_date'   => '2026-07-15',
        ])
        ->assertRedirect(route('scheduling.periods.index'));

    expect(Period::where('name', '2025-2026')->where('type', 'year')->exists())->toBeTrue();
});

test('period name must be unique', function () {
    Period::factory()->create(['name' => '2026-1']);

    $this->actingAs(userWithPeriodPerm('periods.create'))
        ->post('/scheduling/periods', [
            'name'       => '2026-1',
            'type'       => 'semester',
            'start_date' => '2026-02-01',
            'end_date'   => '2026-07-31',
        ])
        ->assertSessionHasErrors('name');

    expect(Period::where('name', '2026-1')->count())->toBe(1);
});

test('end_date must be after start_date', function () {
    $this->actingAs(userWithPeriodPerm('periods.create'))
        ->post('/scheduling/periods', [
            'name'       => '2026-bad',
            'type'       => 'semester',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-01-01',
        ])
        ->assertSessionHasErrors('end_date');
});

test('invalid period type is rejected', function () {
    $this->actingAs(userWithPeriodPerm('periods.create'))
        ->post('/scheduling/periods', [
            'name'       => '2026-x',
            'type'       => 'quarterly',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-03-31',
        ])
        ->assertSessionHasErrors('type');
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('admin can update a period', function () {
    $period = Period::factory()->create(['name' => 'original']);

    $this->actingAs(userWithPeriodPerm('periods.update'))
        ->patch("/scheduling/periods/{$period->id}", [
            'name'       => 'updated',
            'type'       => $period->type->value,
            'start_date' => $period->start_date->toDateString(),
            'end_date'   => $period->end_date->toDateString(),
        ])
        ->assertRedirect(route('scheduling.periods.index'));

    expect($period->fresh()->name)->toBe('updated');
});

// ---------------------------------------------------------------------------
// activate
// ---------------------------------------------------------------------------

test('admin can activate an upcoming period', function () {
    $period = Period::factory()->create(['status' => 'upcoming']);

    $this->actingAs(userWithPeriodPerm('periods.update'))
        ->patch("/scheduling/periods/{$period->id}/activate")
        ->assertRedirect(route('scheduling.periods.index'));

    expect($period->fresh()->status)->toBe(PeriodStatus::Active);
});

test('cannot activate an already active period', function () {
    $period = Period::factory()->active()->create();

    $this->actingAs(userWithPeriodPerm('periods.update'))
        ->patch("/scheduling/periods/{$period->id}/activate")
        ->assertRedirect(route('scheduling.periods.index'));

    expect($period->fresh()->status)->toBe(PeriodStatus::Active);
});

test('cannot activate a closed period', function () {
    $period = Period::factory()->closed()->create();

    $this->actingAs(userWithPeriodPerm('periods.update'))
        ->patch("/scheduling/periods/{$period->id}/activate")
        ->assertRedirect(route('scheduling.periods.index'));

    expect($period->fresh()->status)->toBe(PeriodStatus::Closed);
});

// ---------------------------------------------------------------------------
// close
// ---------------------------------------------------------------------------

test('admin can close an active period', function () {
    $period = Period::factory()->active()->create();

    $this->actingAs(userWithPeriodPerm('periods.update'))
        ->patch("/scheduling/periods/{$period->id}/close")
        ->assertRedirect(route('scheduling.periods.index'));

    expect($period->fresh()->status)->toBe(PeriodStatus::Closed);
});

test('cannot close an upcoming period', function () {
    $period = Period::factory()->create(['status' => 'upcoming']);

    $this->actingAs(userWithPeriodPerm('periods.update'))
        ->patch("/scheduling/periods/{$period->id}/close")
        ->assertRedirect(route('scheduling.periods.index'));

    expect($period->fresh()->status)->toBe(PeriodStatus::Upcoming);
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('admin can delete an upcoming period', function () {
    $period = Period::factory()->create(['status' => 'upcoming']);

    $this->actingAs(userWithPeriodPerm('periods.delete'))
        ->delete("/scheduling/periods/{$period->id}")
        ->assertRedirect(route('scheduling.periods.index'));

    expect(Period::find($period->id))->toBeNull();
});

test('cannot delete an active period', function () {
    $period = Period::factory()->active()->create();

    $this->actingAs(userWithPeriodPerm('periods.delete'))
        ->delete("/scheduling/periods/{$period->id}")
        ->assertRedirect(route('scheduling.periods.index'));

    expect(Period::find($period->id))->not->toBeNull();
});

test('cannot delete a closed period', function () {
    $period = Period::factory()->closed()->create();

    $this->actingAs(userWithPeriodPerm('periods.delete'))
        ->delete("/scheduling/periods/{$period->id}")
        ->assertRedirect(route('scheduling.periods.index'));

    expect(Period::find($period->id))->not->toBeNull();
});
```

- [ ] **Step 3: Verificar que los tests fallan (rutas aún no existen)**

```bash
vendor/bin/sail artisan test --compact --filter=PeriodControllerTest
```

Expected: todos los tests fallan con `Route [scheduling.periods.index] not defined` o similar.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Scheduling/PeriodControllerTest.php
git commit -m "test(scheduling): add PeriodController feature tests (red)"
```

---

## Task 5: Backend completo de Período

**Files:**
- Create: `app/Policies/PeriodPolicy.php`
- Create: `app/Http/Requests/Scheduling/StorePeriodRequest.php`
- Create: `app/Http/Requests/Scheduling/UpdatePeriodRequest.php`
- Create: `app/Http/Wrappers/Scheduling/PeriodWrapper.php`
- Create: `app/Http/Resources/Scheduling/PeriodResource.php`
- Create: `app/Actions/Scheduling/CreatePeriodAction.php`
- Create: `app/Actions/Scheduling/UpdatePeriodAction.php`
- Create: `app/Actions/Scheduling/ActivatePeriodAction.php`
- Create: `app/Actions/Scheduling/ClosePeriodAction.php`
- Create: `app/Actions/Scheduling/DeletePeriodAction.php`
- Create: `app/Http/Controllers/Scheduling/PeriodController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Crear `app/Policies/PeriodPolicy.php`**

```bash
vendor/bin/sail artisan make:policy PeriodPolicy --model=Period --no-interaction
```

Reemplazar el contenido:

```php
<?php

namespace App\Policies;

use App\Models\Period;
use App\Models\User;

class PeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('periods.view');
    }

    public function create(User $user): bool
    {
        return $user->can('periods.create');
    }

    public function update(User $user, Period $period): bool
    {
        return $user->can('periods.update');
    }

    public function delete(User $user, Period $period): bool
    {
        return $user->can('periods.delete');
    }
}
```

- [ ] **Step 2: Crear `app/Http/Requests/Scheduling/StorePeriodRequest.php`**

```bash
vendor/bin/sail artisan make:request Scheduling/StorePeriodRequest --no-interaction
```

Reemplazar el contenido:

```php
<?php

namespace App\Http\Requests\Scheduling;

use App\Models\Period;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Period::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:20', 'unique:periods,name'],
            'type'       => ['required', Rule::in(['semester', 'year', 'trimester'])],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'       => 'El nombre del período es obligatorio.',
            'name.max'            => 'El nombre no puede superar 20 caracteres.',
            'name.unique'         => 'Ya existe un período con ese nombre.',
            'type.required'       => 'El tipo de período es obligatorio.',
            'type.in'             => 'El tipo seleccionado no es válido.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'end_date.required'   => 'La fecha de fin es obligatoria.',
            'end_date.after'      => 'La fecha de fin debe ser posterior a la de inicio.',
        ];
    }
}
```

- [ ] **Step 3: Crear `app/Http/Requests/Scheduling/UpdatePeriodRequest.php`**

```bash
vendor/bin/sail artisan make:request Scheduling/UpdatePeriodRequest --no-interaction
```

Reemplazar el contenido:

```php
<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('period')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:20', Rule::unique('periods', 'name')->ignore($this->route('period'))],
            'type'       => ['required', Rule::in(['semester', 'year', 'trimester'])],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique'    => 'Ya existe un período con ese nombre.',
            'end_date.after' => 'La fecha de fin debe ser posterior a la de inicio.',
        ];
    }
}
```

- [ ] **Step 4: Crear `app/Http/Wrappers/Scheduling/PeriodWrapper.php`**

```php
<?php

namespace App\Http\Wrappers\Scheduling;

use Illuminate\Support\Collection;

class PeriodWrapper extends Collection
{
    public function getName(): string
    {
        return (string) $this->get('name');
    }

    public function getType(): string
    {
        return (string) $this->get('type');
    }

    public function getStartDate(): string
    {
        return (string) $this->get('start_date');
    }

    public function getEndDate(): string
    {
        return (string) $this->get('end_date');
    }
}
```

- [ ] **Step 5: Crear `app/Http/Resources/Scheduling/PeriodResource.php`**

```bash
vendor/bin/sail artisan make:resource Scheduling/PeriodResource --no-interaction
```

Reemplazar el contenido:

```php
<?php

namespace App\Http\Resources\Scheduling;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'type'        => $this->type->value,
            'typeLabel'   => $this->type->label(),
            'startDate'   => $this->start_date->toDateString(),
            'endDate'     => $this->end_date->toDateString(),
            'status'      => $this->status->value,
            'statusLabel' => $this->status->label(),
            'lapses'      => [],
        ];
    }
}
```

> `lapses` se poblará en Spec 02 cuando exista el modelo Lapse.

- [ ] **Step 6: Crear las cinco Actions**

`app/Actions/Scheduling/CreatePeriodAction.php`:
```php
<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\PeriodWrapper;
use App\Models\Period;

class CreatePeriodAction
{
    public function handle(PeriodWrapper $wrapper): Period
    {
        return Period::create([
            'name'       => $wrapper->getName(),
            'type'       => $wrapper->getType(),
            'start_date' => $wrapper->getStartDate(),
            'end_date'   => $wrapper->getEndDate(),
        ]);
    }
}
```

`app/Actions/Scheduling/UpdatePeriodAction.php`:
```php
<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\PeriodWrapper;
use App\Models\Period;

class UpdatePeriodAction
{
    public function handle(Period $period, PeriodWrapper $wrapper): Period
    {
        $period->update([
            'name'       => $wrapper->getName(),
            'type'       => $wrapper->getType(),
            'start_date' => $wrapper->getStartDate(),
            'end_date'   => $wrapper->getEndDate(),
        ]);

        return $period;
    }
}
```

`app/Actions/Scheduling/ActivatePeriodAction.php`:
```php
<?php

namespace App\Actions\Scheduling;

use App\Enums\PeriodStatus;
use App\Models\Period;

class ActivatePeriodAction
{
    public function handle(Period $period): bool
    {
        if (! $period->status->canTransitionTo(PeriodStatus::Active)) {
            return false;
        }

        $period->update(['status' => PeriodStatus::Active]);

        return true;
    }
}
```

`app/Actions/Scheduling/ClosePeriodAction.php`:
```php
<?php

namespace App\Actions\Scheduling;

use App\Enums\PeriodStatus;
use App\Models\Period;

class ClosePeriodAction
{
    public function handle(Period $period): bool
    {
        if (! $period->status->canTransitionTo(PeriodStatus::Closed)) {
            return false;
        }

        $period->update(['status' => PeriodStatus::Closed]);

        return true;
    }
}
```

`app/Actions/Scheduling/DeletePeriodAction.php`:
```php
<?php

namespace App\Actions\Scheduling;

use App\Enums\PeriodStatus;
use App\Models\Period;

class DeletePeriodAction
{
    public function handle(Period $period): bool
    {
        if ($period->status !== PeriodStatus::Upcoming) {
            return false;
        }

        return (bool) $period->delete();
    }
}
```

- [ ] **Step 7: Crear `app/Http/Controllers/Scheduling/PeriodController.php`**

```bash
vendor/bin/sail artisan make:controller Scheduling/PeriodController --no-interaction
```

Reemplazar el contenido:

```php
<?php

namespace App\Http\Controllers\Scheduling;

use App\Actions\Scheduling\ActivatePeriodAction;
use App\Actions\Scheduling\ClosePeriodAction;
use App\Actions\Scheduling\CreatePeriodAction;
use App\Actions\Scheduling\DeletePeriodAction;
use App\Actions\Scheduling\UpdatePeriodAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StorePeriodRequest;
use App\Http\Requests\Scheduling\UpdatePeriodRequest;
use App\Http\Resources\Scheduling\PeriodResource;
use App\Http\Wrappers\Scheduling\PeriodWrapper;
use App\Models\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PeriodController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Period::class);

        $periods = Period::when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('scheduling/Periods/Index', [
            'periods' => PeriodResource::collection($periods)->resolve(),
            'filters' => ['type' => $request->input('type')],
            'can'     => [
                'create' => $request->user()->can('create', Period::class),
                'update' => $request->user()->can('update', new Period),
                'delete' => $request->user()->can('delete', new Period),
            ],
        ]);
    }

    public function store(StorePeriodRequest $request, CreatePeriodAction $action): RedirectResponse
    {
        $action->handle(new PeriodWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Período creado.']);

        return to_route('scheduling.periods.index');
    }

    public function update(UpdatePeriodRequest $request, Period $period, UpdatePeriodAction $action): RedirectResponse
    {
        $action->handle($period, new PeriodWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Período actualizado.']);

        return to_route('scheduling.periods.index');
    }

    public function activate(Period $period, ActivatePeriodAction $action): RedirectResponse
    {
        Gate::authorize('update', $period);

        if (! $action->handle($period)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede activar el período desde su estado actual.']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Período activado.']);
        }

        return to_route('scheduling.periods.index');
    }

    public function close(Period $period, ClosePeriodAction $action): RedirectResponse
    {
        Gate::authorize('update', $period);

        if (! $action->handle($period)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede cerrar el período desde su estado actual.']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Período cerrado.']);
        }

        return to_route('scheduling.periods.index');
    }

    public function destroy(Period $period, DeletePeriodAction $action): RedirectResponse
    {
        Gate::authorize('delete', $period);

        if (! $action->handle($period)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Solo se pueden eliminar períodos en estado Próximo.']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Período eliminado.']);
        }

        return to_route('scheduling.periods.index');
    }
}
```

- [ ] **Step 8: Agregar rutas en `routes/web.php`**

Añadir antes de `require __DIR__.'/settings.php';`:

```php
use App\Http\Controllers\Scheduling\PeriodController;

Route::middleware(['auth', 'verified'])->prefix('scheduling')->name('scheduling.')->group(function () {
    Route::get('periods', [PeriodController::class, 'index'])->name('periods.index');
    Route::post('periods', [PeriodController::class, 'store'])->name('periods.store');
    Route::patch('periods/{period}', [PeriodController::class, 'update'])->name('periods.update');
    Route::delete('periods/{period}', [PeriodController::class, 'destroy'])->name('periods.destroy');
    Route::patch('periods/{period}/activate', [PeriodController::class, 'activate'])->name('periods.activate');
    Route::patch('periods/{period}/close', [PeriodController::class, 'close'])->name('periods.close');
});
```

- [ ] **Step 9: Ejecutar tests — deben pasar todos**

```bash
vendor/bin/sail artisan test --compact --filter=PeriodControllerTest
```

Expected: todos los tests en verde ✓

- [ ] **Step 10: Formatear con Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 11: Commit**

```bash
git add app/Policies/PeriodPolicy.php \
        app/Http/Requests/Scheduling/ \
        app/Http/Wrappers/Scheduling/PeriodWrapper.php \
        app/Http/Resources/Scheduling/PeriodResource.php \
        app/Actions/Scheduling/ \
        app/Http/Controllers/Scheduling/PeriodController.php \
        routes/web.php
git commit -m "feat(scheduling): implement Period CRUD with status transitions (green)"
```

---

## Task 6: Frontend — tipos, composables y modales

**Files:**
- Create: `resources/js/types/scheduling.ts`
- Create: `resources/js/composables/forms/usePeriodForm.ts`
- Create: `resources/js/composables/permissions/usePeriodPermissions.ts`
- Create: `resources/js/composables/filters/usePeriodFilters.ts`
- Create: `resources/js/components/scheduling/CreatePeriodModal.vue`
- Create: `resources/js/components/scheduling/EditPeriodModal.vue`
- Create: `resources/js/components/scheduling/DeletePeriodModal.vue`

- [ ] **Step 1: Regenerar Wayfinder para que existan las rutas de scheduling**

```bash
vendor/bin/sail artisan wayfinder:generate --no-interaction
```

Expected: se genera `resources/js/routes/scheduling/periods.ts` con `index`, `store`, `update`, `destroy`, `activate`, `close`.

- [ ] **Step 2: Crear `resources/js/types/scheduling.ts`**

```typescript
export type Period = {
    id: number
    name: string
    type: 'semester' | 'year' | 'trimester'
    typeLabel: string
    startDate: string
    endDate: string
    status: 'upcoming' | 'active' | 'closed'
    statusLabel: string
    lapses: never[]
}

export type PeriodCollection = Period[]
```

- [ ] **Step 3: Crear `resources/js/composables/forms/usePeriodForm.ts`**

```typescript
import { useForm } from '@inertiajs/vue3'
import { activate, close, destroy, store, update } from '@/routes/scheduling/periods'
import type { Period } from '@/types/scheduling'

export function usePeriodForm() {
    const storeOps = {
        form() {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({
                    name:       '',
                    type:       'semester' as Period['type'],
                    start_date: '',
                    end_date:   '',
                }),
            }
        },
    }

    const updateOps = {
        form({ period }: { period: Period }) {
            return {
                url:    update.url({ period }),
                method: 'patch' as const,
                data:   useForm({
                    name:       period.name,
                    type:       period.type,
                    start_date: period.startDate,
                    end_date:   period.endDate,
                }),
            }
        },
    }

    const removeOps = {
        submit({ period }: { period: Period }): void {
            useForm({}).delete(destroy.url({ period }))
        },
    }

    const activateOps = {
        submit({ period }: { period: Period }): void {
            useForm({}).patch(activate.url({ period }))
        },
    }

    const closeOps = {
        submit({ period }: { period: Period }): void {
            useForm({}).patch(close.url({ period }))
        },
    }

    return {
        store:    storeOps,
        update:   updateOps,
        remove:   removeOps,
        activate: activateOps,
        close:    closeOps,
    }
}
```

- [ ] **Step 4: Crear `resources/js/composables/permissions/usePeriodPermissions.ts`**

```typescript
import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function usePeriodPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('periods.create'))
    const canUpdate = computed(() => can('periods.update'))
    const canDelete = computed(() => can('periods.delete'))

    return { canCreate, canUpdate, canDelete }
}
```

- [ ] **Step 5: Crear `resources/js/composables/filters/usePeriodFilters.ts`**

```typescript
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { index } from '@/routes/scheduling/periods'

export function usePeriodFilters(initialType: string | null = null) {
    const type = ref<string | null>(initialType)

    function applyFilter(): void {
        router.get(
            index.url(),
            type.value ? { type: type.value } : {},
            { preserveState: true, replace: true },
        )
    }

    return { type, applyFilter }
}
```

- [ ] **Step 6: Crear `resources/js/components/scheduling/CreatePeriodModal.vue`**

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { usePeriodForm } from '@/composables/forms/usePeriodForm'

defineProps<{ open: boolean }>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const { store } = usePeriodForm()
const formKey = ref(0)

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) {
        formKey.value++
    }
}
</script>

<template>
    <Modal :open="open" title="Nuevo período" size="sm" @update:open="close">
        <Form :key="formKey" v-bind="store.form()" v-slot="{ errors, processing }" @success="close(false)">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cp-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="cp-name" name="name" class="input" placeholder="Ej: 2026-1" required />
                    <InputError :message="errors.name" />
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="cp-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo
                    </label>
                    <select id="cp-type" name="type" class="input" required>
                        <option value="semester">Semestral</option>
                        <option value="year">Anual</option>
                        <option value="trimester">Trimestral</option>
                    </select>
                    <InputError :message="errors.type" />
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="cp-start" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Inicio
                        </label>
                        <input id="cp-start" name="start_date" type="date" class="input" required />
                        <InputError :message="errors.start_date" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="cp-end" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Fin
                        </label>
                        <input id="cp-end" name="end_date" type="date" class="input" required />
                        <InputError :message="errors.end_date" />
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Crear período</Button>
            </div>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 7: Crear `resources/js/components/scheduling/EditPeriodModal.vue`**

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { usePeriodForm } from '@/composables/forms/usePeriodForm'
import type { Period } from '@/types/scheduling'

const props = defineProps<{ open: boolean; period: Period }>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const { update } = usePeriodForm()
const formKey = ref(0)

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) {
        formKey.value++
    }
}
</script>

<template>
    <Modal :open="open" title="Editar período" size="sm" @update:open="close">
        <Form
            :key="formKey"
            v-bind="update.form({ period })"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="ep-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="ep-name" name="name" class="input" :value="props.period.name" required />
                    <InputError :message="errors.name" />
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="ep-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo
                    </label>
                    <select id="ep-type" name="type" class="input" required>
                        <option value="semester" :selected="props.period.type === 'semester'">Semestral</option>
                        <option value="year" :selected="props.period.type === 'year'">Anual</option>
                        <option value="trimester" :selected="props.period.type === 'trimester'">Trimestral</option>
                    </select>
                    <InputError :message="errors.type" />
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="ep-start" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Inicio
                        </label>
                        <input id="ep-start" name="start_date" type="date" class="input" :value="props.period.startDate" required />
                        <InputError :message="errors.start_date" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="ep-end" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Fin
                        </label>
                        <input id="ep-end" name="end_date" type="date" class="input" :value="props.period.endDate" required />
                        <InputError :message="errors.end_date" />
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Guardar cambios</Button>
            </div>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 8: Crear `resources/js/components/scheduling/DeletePeriodModal.vue`**

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/scheduling/periods'
import type { Period } from '@/types/scheduling'

const props = defineProps<{ open: boolean; period: Period }>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.delete(destroy.url(props.period), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Eliminar período" size="sm" @update:open="close">
        <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
            ¿Eliminar el período <strong>{{ period.name }}</strong>?
            Solo se pueden eliminar períodos en estado <strong>Próximo</strong>.
        </p>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button variant="danger" :loading="form.processing" @click="submit">Eliminar</Button>
        </div>
    </Modal>
</template>
```

- [ ] **Step 9: Commit**

```bash
git add resources/js/types/scheduling.ts \
        resources/js/composables/forms/usePeriodForm.ts \
        resources/js/composables/permissions/usePeriodPermissions.ts \
        resources/js/composables/filters/usePeriodFilters.ts \
        resources/js/components/scheduling/
git commit -m "feat(scheduling): add Period frontend types, composables and modals"
```

---

## Task 7: Página Vue y Sidebar

**Files:**
- Create: `resources/js/pages/scheduling/Periods/Index.vue`
- Modify: `resources/js/components/AppSidebar.vue`

- [ ] **Step 1: Crear `resources/js/pages/scheduling/Periods/Index.vue`**

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import CreatePeriodModal from '@/components/scheduling/CreatePeriodModal.vue'
import DeletePeriodModal from '@/components/scheduling/DeletePeriodModal.vue'
import EditPeriodModal from '@/components/scheduling/EditPeriodModal.vue'
import { usePeriodFilters } from '@/composables/filters/usePeriodFilters'
import { usePeriodForm } from '@/composables/forms/usePeriodForm'
import { usePeriodPermissions } from '@/composables/permissions/usePeriodPermissions'
import { index } from '@/routes/scheduling/periods'
import type { Period } from '@/types/scheduling'

type Props = {
    periods: Period[]
    filters: { type: string | null }
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Horarios', href: '#' },
            { title: 'Períodos', href: index.url() },
        ],
    },
})

const { canCreate, canUpdate, canDelete } = usePeriodPermissions()
const { activate, close } = usePeriodForm()
const { type, applyFilter } = usePeriodFilters(props.filters.type)

const showCreate = ref(false)
const editingPeriod = ref<Period | null>(null)
const deletingPeriod = ref<Period | null>(null)
</script>

<template>
    <Head title="Períodos" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Períodos
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Ciclos académicos con rango de fechas y estado
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nuevo período
            </Button>
        </div>

        <div>
            <select v-model="type" class="input" style="max-width:200px;" @change="applyFilter">
                <option :value="null">Todos los tipos</option>
                <option value="semester">Semestral</option>
                <option value="year">Anual</option>
                <option value="trimester">Trimestral</option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Fechas</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="period in periods" :key="period.id">
                        <td style="font-weight:500;">{{ period.name }}</td>
                        <td style="color:var(--text-secondary);">{{ period.typeLabel }}</td>
                        <td style="color:var(--text-secondary);">{{ period.startDate }} — {{ period.endDate }}</td>
                        <td>
                            <span
                                style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:4px;font-size:var(--text-xs);font-weight:500;"
                                :style="{
                                    background: period.status === 'active'   ? 'var(--color-success-light)' :
                                                period.status === 'upcoming' ? 'var(--color-info-light)'    :
                                                'var(--gris-borde)',
                                    color:      period.status === 'active'   ? 'var(--color-success)'       :
                                                period.status === 'upcoming' ? 'var(--color-info)'          :
                                                'var(--gris)',
                                }"
                            >
                                {{ period.statusLabel }}
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    v-if="canUpdate && period.status === 'upcoming'"
                                    variant="ghost"
                                    size="sm"
                                    @click="activate.submit({ period })"
                                >
                                    Activar
                                </Button>
                                <Button
                                    v-if="canUpdate && period.status === 'active'"
                                    variant="ghost"
                                    size="sm"
                                    @click="close.submit({ period })"
                                >
                                    Cerrar
                                </Button>
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar ${period.name}`"
                                    @click="editingPeriod = period"
                                />
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${period.name}`"
                                    @click="deletingPeriod = period"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!periods.length">
                        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay períodos registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreatePeriodModal v-model:open="showCreate" />

    <EditPeriodModal
        v-if="editingPeriod"
        :open="!!editingPeriod"
        :period="editingPeriod"
        @update:open="v => { if (!v) editingPeriod = null }"
    />

    <DeletePeriodModal
        v-if="deletingPeriod"
        :open="!!deletingPeriod"
        :period="deletingPeriod"
        @update:open="v => { if (!v) deletingPeriod = null }"
    />
</template>
```

- [ ] **Step 2: Actualizar `resources/js/components/AppSidebar.vue`**

Añadir el import al bloque de imports existente:

```typescript
import { index as periodsIndex } from '@/routes/scheduling/periods'
```

Dentro de `navGroups` computed, añadir después del bloque de Infraestructura:

```typescript
if (
    page.props.auth?.permissions?.includes('periods.view') ||
    page.props.auth?.roles?.includes('Admin')
) {
    groups.push({
        label: 'Horarios',
        items: [
            { icon: 'calendar', label: 'Períodos', href: periodsIndex.url() },
        ],
    })
}
```

- [ ] **Step 3: Compilar frontend**

```bash
vendor/bin/sail npm run build
```

Expected: compilación sin errores de TypeScript ni Vite.

- [ ] **Step 4: Ejecutar suite completa de tests para verificar que no hay regresiones**

```bash
vendor/bin/sail artisan test --compact
```

Expected: todos los tests en verde ✓

- [ ] **Step 5: Commit final**

```bash
git add resources/js/pages/scheduling/ resources/js/components/AppSidebar.vue
git commit -m "feat(scheduling): add Periods Vue page and sidebar navigation"
```
