# Lapsos — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Agregar el modelo `Lapse` como sub-recurso de `Period` (solo períodos de tipo `year`), con CRUD completo vía modales desde la página de Períodos. Los lapsos no tienen página propia: se gestionan y visualizan inline dentro de `Periods/Index.vue`.

**Architecture:** `FormRequest → Controller → Wrapper → Action → Resource` en backend; `FormComposable → Page → PermissionComposable → Type` en frontend. Las rutas están anidadas bajo `periods/{period}/lapses`. La validación de reglas de negocio (tipo year, período no closed, fechas dentro del rango) ocurre en `StoreLapseRequest` / `UpdateLapseRequest`.

**Tech Stack:** PHP 8.3, Laravel 13, Pest v4, Inertia.js v3, Vue 3, Tailwind CSS v4, Wayfinder v0, Spatie Permission.

**Worktree:** `/var/www/cacao/.worktrees/feat/scheduling-lapses` — branch `feat/scheduling-lapses`

**Docker prefix for all commands:**
```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 <command>
```

---

## File Map

### Nuevos archivos PHP
```
app/Models/Lapse.php
database/migrations/XXXX_create_lapses_table.php
database/factories/LapseFactory.php
app/Policies/LapsePolicy.php
app/Http/Requests/Scheduling/StoreLapseRequest.php
app/Http/Requests/Scheduling/UpdateLapseRequest.php
app/Http/Wrappers/Scheduling/LapseWrapper.php
app/Http/Resources/Scheduling/LapseResource.php
app/Actions/Scheduling/CreateLapseAction.php
app/Actions/Scheduling/UpdateLapseAction.php
app/Actions/Scheduling/DeleteLapseAction.php
app/Http/Controllers/Scheduling/LapseController.php
tests/Feature/Scheduling/LapseControllerTest.php
```

### Nuevos archivos frontend
```
resources/js/composables/forms/useLapseForm.ts
resources/js/components/scheduling/LapsesPanel.vue
resources/js/components/scheduling/CreateLapseModal.vue
resources/js/components/scheduling/EditLapseModal.vue
resources/js/components/scheduling/DeleteLapseModal.vue
```

### Archivos modificados
```
database/data/permissions.yaml          — 3 nuevos permisos lapses.*
database/data/roles.yaml                — permisos lapses.* al rol Admin
routes/web.php                          — 3 rutas nested bajo periods/{period}/lapses
app/Http/Resources/Scheduling/PeriodResource.php  — incluir lapses para year periods
app/Http/Controllers/Scheduling/PeriodController.php  — eager-load lapses en index
resources/js/types/scheduling.ts        — expandir tipo Lapse, actualizar Period.lapses
resources/js/pages/scheduling/Periods/Index.vue  — mostrar LapsesPanel para year periods
```

---

## Task 1: Migración, Modelo y Factory de Lapse

**Files:**
- Create: `database/migrations/XXXX_create_lapses_table.php`
- Create: `app/Models/Lapse.php`
- Create: `database/factories/LapseFactory.php`

- [ ] **Step 1: Crear migración de la tabla `lapses`**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 php artisan make:migration create_lapses_table --no-interaction
```

Editar el archivo generado (nombre real en `database/migrations/`):

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
        Schema::create('lapses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('number');
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->unique(['period_id', 'number']);
        });

        DB::statement('ALTER TABLE lapses ADD CONSTRAINT lapses_dates_check CHECK (end_date > start_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('lapses');
    }
};
```

- [ ] **Step 2: Ejecutar migración**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 php artisan migrate --no-interaction
```

Expected: migración ejecutada sin errores.

- [ ] **Step 3: Crear `app/Models/Lapse.php`**

```php
<?php

namespace App\Models;

use Database\Factories\LapseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['period_id', 'number', 'name', 'start_date', 'end_date'])]
class Lapse extends Model
{
    /** @use HasFactory<LapseFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
}
```

- [ ] **Step 4: Crear `database/factories/LapseFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\Lapse;
use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lapse> */
class LapseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'period_id'  => Period::factory()->year(),
            'number'     => 1,
            'name'       => 'Primer Lapso',
            'start_date' => '2025-09-01',
            'end_date'   => '2025-11-30',
        ];
    }

    public function forPeriod(Period $period, int $number = 1): static
    {
        $start = $period->start_date->copy()->addDays(($number - 1) * 60);
        $end   = $start->copy()->addDays(58);

        if ($end->gt($period->end_date)) {
            $end = $period->end_date->copy()->subDay();
        }

        return $this->state([
            'period_id'  => $period->id,
            'number'     => $number,
            'name'       => match ($number) {
                1        => 'Primer Lapso',
                2        => 'Segundo Lapso',
                3        => 'Tercer Lapso',
                default  => "Lapso {$number}",
            },
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
        ]);
    }
}
```

- [ ] **Step 5: Formatear con Pint**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git -C /var/www/cacao/.worktrees/feat/scheduling-lapses add \
    database/migrations/ \
    app/Models/Lapse.php \
    database/factories/LapseFactory.php
git -C /var/www/cacao/.worktrees/feat/scheduling-lapses commit -m "feat(scheduling): add lapses migration, model and factory"
```

---

## Task 2: Permisos

**Files:**
- Modify: `database/data/permissions.yaml`
- Modify: `database/data/roles.yaml`

- [ ] **Step 1: Agregar permisos en `database/data/permissions.yaml`**

Buscar el bloque `periods.delete` y añadir a continuación:

```yaml
  - name: lapses.create
    guard: web
  - name: lapses.update
    guard: web
  - name: lapses.delete
    guard: web
```

- [ ] **Step 2: Asignar permisos al rol Admin en `database/data/roles.yaml`**

Dentro del bloque `name: Admin`, en `permissions:`, añadir después de `periods.delete`:

```yaml
      - lapses.create
      - lapses.update
      - lapses.delete
```

- [ ] **Step 3: Commit**

```bash
git -C /var/www/cacao/.worktrees/feat/scheduling-lapses add \
    database/data/permissions.yaml \
    database/data/roles.yaml
git -C /var/www/cacao/.worktrees/feat/scheduling-lapses commit -m "feat(scheduling): add lapses.* permissions to Admin role"
```

---

## Task 3: Tests de Lapse (TDD — escribir primero, fallarán hasta Task 4)

**Files:**
- Create: `tests/Feature/Scheduling/LapseControllerTest.php`

- [ ] **Step 1: Crear archivo de test**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 php artisan make:test --pest Scheduling/LapseControllerTest --no-interaction
```

- [ ] **Step 2: Escribir los tests**

Reemplazar el contenido del archivo generado:

```php
<?php

use App\Enums\PeriodStatus;
use App\Enums\PeriodType;
use App\Models\Lapse;
use App\Models\Period;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['lapses.create', 'lapses.update', 'lapses.delete'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    // periods.view is needed for PeriodResource assertions
    Permission::firstOrCreate(['name' => 'periods.view', 'guard_name' => 'web']);
});

function userWithLapsePerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('admin can create a lapse in a year period', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);

    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Primer Lapso',
            'start_date' => '2025-09-01',
            'end_date'   => '2025-11-30',
        ])
        ->assertRedirect(route('scheduling.periods.index'));

    expect(Lapse::where('period_id', $period->id)->where('number', 1)->exists())->toBeTrue();
});

test('cannot create lapse in semester period', function () {
    $period = Period::factory()->semester()->create();

    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => $period->start_date->toDateString(),
            'end_date'   => $period->start_date->copy()->addMonth()->toDateString(),
        ])
        ->assertSessionHasErrors('period_id');
});

test('cannot create lapse in trimester period', function () {
    $period = Period::factory()->trimester()->create();

    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => $period->start_date->toDateString(),
            'end_date'   => $period->start_date->copy()->addMonth()->toDateString(),
        ])
        ->assertSessionHasErrors('period_id');
});

test('cannot create lapse in a closed period', function () {
    $period = Period::factory()->year()->closed()->create([
        'start_date' => '2024-09-01',
        'end_date'   => '2025-07-15',
    ]);

    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => '2024-09-01',
            'end_date'   => '2024-11-30',
        ])
        ->assertSessionHasErrors('period_id');
});

test('lapse dates must be within period range', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);

    // start_date before period start_date
    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => '2025-08-01',
            'end_date'   => '2025-11-30',
        ])
        ->assertSessionHasErrors('start_date');
});

test('lapse end date must be within period range', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);

    // end_date after period end_date
    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => '2025-09-01',
            'end_date'   => '2026-08-01',
        ])
        ->assertSessionHasErrors('end_date');
});

test('lapse end date must be after start date', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);

    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => '2025-11-30',
            'end_date'   => '2025-09-01',
        ])
        ->assertSessionHasErrors('end_date');
});

test('cannot duplicate lapse number in same period', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);

    Lapse::factory()->forPeriod($period, 1)->create();

    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Otro Lapso',
            'start_date' => '2025-12-01',
            'end_date'   => '2026-02-28',
        ])
        ->assertSessionHasErrors('number');
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('admin can update a lapse', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);
    $lapse = Lapse::factory()->forPeriod($period, 1)->create();

    $this->actingAs(userWithLapsePerm('lapses.update'))
        ->patch("/scheduling/periods/{$period->id}/lapses/{$lapse->id}", [
            'number'     => 1,
            'name'       => 'Primer Lapso Actualizado',
            'start_date' => '2025-09-01',
            'end_date'   => '2025-11-30',
        ])
        ->assertRedirect(route('scheduling.periods.index'));

    expect($lapse->fresh()->name)->toBe('Primer Lapso Actualizado');
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('admin can delete a lapse', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);
    $lapse = Lapse::factory()->forPeriod($period, 1)->create();

    $this->actingAs(userWithLapsePerm('lapses.delete'))
        ->delete("/scheduling/periods/{$period->id}/lapses/{$lapse->id}")
        ->assertRedirect(route('scheduling.periods.index'));

    expect(Lapse::find($lapse->id))->toBeNull();
});

// ---------------------------------------------------------------------------
// authorization
// ---------------------------------------------------------------------------

test('user without permission cannot create lapse', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);

    $this->actingAs(User::factory()->create())
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => '2025-09-01',
            'end_date'   => '2025-11-30',
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// PeriodResource includes lapses
// ---------------------------------------------------------------------------

test('period resource includes lapses for year periods', function () {
    Permission::firstOrCreate(['name' => 'periods.view', 'guard_name' => 'web']);

    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);
    Lapse::factory()->forPeriod($period, 1)->create();
    Lapse::factory()->forPeriod($period, 2)->create();

    $user = User::factory()->create();
    $user->givePermissionTo('periods.view');

    $this->actingAs($user)
        ->get('/scheduling/periods')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('periods', 1)
            ->has('periods.0.lapses', 2)
            ->where('periods.0.lapses.0.number', 1)
            ->where('periods.0.lapses.1.number', 2)
        );
});

test('period resource excludes lapses for semester periods', function () {
    Permission::firstOrCreate(['name' => 'periods.view', 'guard_name' => 'web']);

    $period = Period::factory()->semester()->create();

    $user = User::factory()->create();
    $user->givePermissionTo('periods.view');

    $this->actingAs($user)
        ->get('/scheduling/periods')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('periods', 1)
            ->where('periods.0.lapses', [])
        );
});
```

- [ ] **Step 3: Verificar que los tests fallan (rutas aún no existen)**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 php artisan test --compact --filter=LapseControllerTest 2>&1 | tail -10
```

Expected: todos los tests fallan con errores de ruta no definida.

- [ ] **Step 4: Commit**

```bash
git -C /var/www/cacao/.worktrees/feat/scheduling-lapses add tests/Feature/Scheduling/LapseControllerTest.php
git -C /var/www/cacao/.worktrees/feat/scheduling-lapses commit -m "test(scheduling): add LapseController feature tests (red)"
```

---

## Task 4: Backend completo de Lapse

**Files:**
- Create: `app/Policies/LapsePolicy.php`
- Create: `app/Http/Requests/Scheduling/StoreLapseRequest.php`
- Create: `app/Http/Requests/Scheduling/UpdateLapseRequest.php`
- Create: `app/Http/Wrappers/Scheduling/LapseWrapper.php`
- Create: `app/Http/Resources/Scheduling/LapseResource.php`
- Create: `app/Actions/Scheduling/CreateLapseAction.php`
- Create: `app/Actions/Scheduling/UpdateLapseAction.php`
- Create: `app/Actions/Scheduling/DeleteLapseAction.php`
- Create: `app/Http/Controllers/Scheduling/LapseController.php`
- Modify: `app/Http/Resources/Scheduling/PeriodResource.php`
- Modify: `app/Http/Controllers/Scheduling/PeriodController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Crear `app/Policies/LapsePolicy.php`**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 php artisan make:policy LapsePolicy --model=Lapse --no-interaction
```

Reemplazar el contenido:

```php
<?php

namespace App\Policies;

use App\Models\Lapse;
use App\Models\User;

class LapsePolicy
{
    public function create(User $user): bool
    {
        return $user->can('lapses.create');
    }

    public function update(User $user, Lapse $lapse): bool
    {
        return $user->can('lapses.update');
    }

    public function delete(User $user, Lapse $lapse): bool
    {
        return $user->can('lapses.delete');
    }
}
```

- [ ] **Step 2: Crear `app/Http/Requests/Scheduling/StoreLapseRequest.php`**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 php artisan make:request Scheduling/StoreLapseRequest --no-interaction
```

Reemplazar el contenido:

```php
<?php

namespace App\Http\Requests\Scheduling;

use App\Enums\PeriodStatus;
use App\Enums\PeriodType;
use App\Models\Lapse;
use App\Models\Period;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLapseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Lapse::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Period $period */
        $period = $this->route('period');

        return [
            'period_id'  => [
                'prohibited',
            ],
            'number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('lapses', 'number')->where('period_id', $period->id),
            ],
            'name'       => ['required', 'string', 'max:100'],
            'start_date' => [
                'required',
                'date',
                'after_or_equal:' . $period->start_date->toDateString(),
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
                'before_or_equal:' . $period->end_date->toDateString(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'number.unique'           => 'Ya existe un lapso con ese número en este período.',
            'start_date.after_or_equal' => 'La fecha de inicio debe estar dentro del período.',
            'end_date.after'           => 'La fecha de fin debe ser posterior a la de inicio.',
            'end_date.before_or_equal' => 'La fecha de fin debe estar dentro del período.',
        ];
    }

    protected function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        /** @var Period $period */
        $period = $this->route('period');

        $validator->after(function (\Illuminate\Contracts\Validation\Validator $v) use ($period) {
            if ($period->type !== PeriodType::Year) {
                $v->errors()->add('period_id', 'Solo se pueden crear lapsos en períodos de tipo Anual.');
            }

            if ($period->status === PeriodStatus::Closed) {
                $v->errors()->add('period_id', 'No se pueden crear lapsos en un período cerrado.');
            }
        });
    }
}
```

- [ ] **Step 3: Crear `app/Http/Requests/Scheduling/UpdateLapseRequest.php`**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 php artisan make:request Scheduling/UpdateLapseRequest --no-interaction
```

Reemplazar el contenido:

```php
<?php

namespace App\Http\Requests\Scheduling;

use App\Enums\PeriodStatus;
use App\Models\Lapse;
use App\Models\Period;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLapseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('lapse')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Period $period */
        $period = $this->route('period');

        /** @var Lapse $lapse */
        $lapse = $this->route('lapse');

        return [
            'number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('lapses', 'number')
                    ->where('period_id', $period->id)
                    ->ignore($lapse->id),
            ],
            'name'       => ['required', 'string', 'max:100'],
            'start_date' => [
                'required',
                'date',
                'after_or_equal:' . $period->start_date->toDateString(),
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
                'before_or_equal:' . $period->end_date->toDateString(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'number.unique'             => 'Ya existe un lapso con ese número en este período.',
            'start_date.after_or_equal' => 'La fecha de inicio debe estar dentro del período.',
            'end_date.after'            => 'La fecha de fin debe ser posterior a la de inicio.',
            'end_date.before_or_equal'  => 'La fecha de fin debe estar dentro del período.',
        ];
    }

    protected function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        /** @var Period $period */
        $period = $this->route('period');

        $validator->after(function (\Illuminate\Contracts\Validation\Validator $v) use ($period) {
            if ($period->status === PeriodStatus::Closed) {
                $v->errors()->add('period_id', 'No se pueden modificar lapsos en un período cerrado.');
            }
        });
    }
}
```

- [ ] **Step 4: Crear `app/Http/Wrappers/Scheduling/LapseWrapper.php`**

```php
<?php

namespace App\Http\Wrappers\Scheduling;

use Illuminate\Support\Collection;

class LapseWrapper extends Collection
{
    public function getNumber(): int
    {
        return (int) $this->get('number');
    }

    public function getName(): string
    {
        return (string) $this->get('name');
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

- [ ] **Step 5: Crear `app/Http/Resources/Scheduling/LapseResource.php`**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 php artisan make:resource Scheduling/LapseResource --no-interaction
```

Reemplazar el contenido:

```php
<?php

namespace App\Http\Resources\Scheduling;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LapseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'number'    => $this->number,
            'name'      => $this->name,
            'startDate' => $this->start_date->toDateString(),
            'endDate'   => $this->end_date->toDateString(),
        ];
    }
}
```

- [ ] **Step 6: Crear las tres Actions**

`app/Actions/Scheduling/CreateLapseAction.php`:

```php
<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\LapseWrapper;
use App\Models\Lapse;
use App\Models\Period;

class CreateLapseAction
{
    public function handle(Period $period, LapseWrapper $wrapper): Lapse
    {
        return Lapse::create([
            'period_id'  => $period->id,
            'number'     => $wrapper->getNumber(),
            'name'       => $wrapper->getName(),
            'start_date' => $wrapper->getStartDate(),
            'end_date'   => $wrapper->getEndDate(),
        ]);
    }
}
```

`app/Actions/Scheduling/UpdateLapseAction.php`:

```php
<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\LapseWrapper;
use App\Models\Lapse;

class UpdateLapseAction
{
    public function handle(Lapse $lapse, LapseWrapper $wrapper): Lapse
    {
        $lapse->update([
            'number'     => $wrapper->getNumber(),
            'name'       => $wrapper->getName(),
            'start_date' => $wrapper->getStartDate(),
            'end_date'   => $wrapper->getEndDate(),
        ]);

        return $lapse;
    }
}
```

`app/Actions/Scheduling/DeleteLapseAction.php`:

```php
<?php

namespace App\Actions\Scheduling;

use App\Models\Lapse;

class DeleteLapseAction
{
    public function handle(Lapse $lapse): bool
    {
        return (bool) $lapse->delete();
    }
}
```

- [ ] **Step 7: Crear `app/Http/Controllers/Scheduling/LapseController.php`**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 php artisan make:controller Scheduling/LapseController --no-interaction
```

Reemplazar el contenido:

```php
<?php

namespace App\Http\Controllers\Scheduling;

use App\Actions\Scheduling\CreateLapseAction;
use App\Actions\Scheduling\DeleteLapseAction;
use App\Actions\Scheduling\UpdateLapseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreLapseRequest;
use App\Http\Requests\Scheduling\UpdateLapseRequest;
use App\Http\Wrappers\Scheduling\LapseWrapper;
use App\Models\Lapse;
use App\Models\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class LapseController extends Controller
{
    public function store(StoreLapseRequest $request, Period $period, CreateLapseAction $action): RedirectResponse
    {
        $action->handle($period, new LapseWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lapso creado.']);

        return to_route('scheduling.periods.index');
    }

    public function update(UpdateLapseRequest $request, Period $period, Lapse $lapse, UpdateLapseAction $action): RedirectResponse
    {
        $action->handle($lapse, new LapseWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lapso actualizado.']);

        return to_route('scheduling.periods.index');
    }

    public function destroy(Period $period, Lapse $lapse, DeleteLapseAction $action): RedirectResponse
    {
        Gate::authorize('delete', $lapse);

        $action->handle($lapse);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lapso eliminado.']);

        return to_route('scheduling.periods.index');
    }
}
```

- [ ] **Step 8: Actualizar `app/Http/Resources/Scheduling/PeriodResource.php`**

Reemplazar el método `toArray` para incluir lapsos en períodos anuales:

```php
<?php

namespace App\Http\Resources\Scheduling;

use App\Enums\PeriodType;
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
            'lapses'      => $this->type === PeriodType::Year
                ? LapseResource::collection($this->lapses)
                : [],
        ];
    }
}
```

- [ ] **Step 9: Actualizar `app/Http/Controllers/Scheduling/PeriodController.php`**

Modificar el método `index` para eager-load lapses en períodos year. Reemplazar la línea del query en `index`:

```php
$periods = Period::when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
    ->with(['lapses' => fn ($q) => $q->orderBy('number')])
    ->orderByDesc('id')
    ->get();
```

El método completo queda:

```php
public function index(Request $request): Response
{
    Gate::authorize('viewAny', Period::class);

    $periods = Period::when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
        ->with(['lapses' => fn ($q) => $q->orderBy('number')])
        ->orderByDesc('id')
        ->get();

    return Inertia::render('scheduling/Periods/Index', [
        'periods' => PeriodResource::collection($periods)->resolve(),
        'filters' => ['type' => $request->input('type')],
        'can'     => ['create' => $request->user()->can('periods.create'), 'update' => $request->user()->can('periods.update'), 'delete' => $request->user()->can('periods.delete')],
    ]);
}
```

- [ ] **Step 10: Agregar rutas nested en `routes/web.php`**

Dentro del bloque `scheduling` existente, añadir después de las rutas de períodos:

```php
use App\Http\Controllers\Scheduling\LapseController;

// dentro del grupo Route::middleware(['auth', 'verified'])->prefix('scheduling')->name('scheduling.')->group(...)
Route::post('periods/{period}/lapses', [LapseController::class, 'store'])->name('lapses.store');
Route::patch('periods/{period}/lapses/{lapse}', [LapseController::class, 'update'])->name('lapses.update');
Route::delete('periods/{period}/lapses/{lapse}', [LapseController::class, 'destroy'])->name('lapses.destroy');
```

El bloque completo en `routes/web.php` queda:

```php
use App\Http\Controllers\Scheduling\LapseController;
use App\Http\Controllers\Scheduling\PeriodController;

Route::middleware(['auth', 'verified'])->prefix('scheduling')->name('scheduling.')->group(function () {
    Route::get('periods', [PeriodController::class, 'index'])->name('periods.index');
    Route::post('periods', [PeriodController::class, 'store'])->name('periods.store');
    Route::patch('periods/{period}', [PeriodController::class, 'update'])->name('periods.update');
    Route::delete('periods/{period}', [PeriodController::class, 'destroy'])->name('periods.destroy');
    Route::patch('periods/{period}/activate', [PeriodController::class, 'activate'])->name('periods.activate');
    Route::patch('periods/{period}/close', [PeriodController::class, 'close'])->name('periods.close');

    Route::post('periods/{period}/lapses', [LapseController::class, 'store'])->name('lapses.store');
    Route::patch('periods/{period}/lapses/{lapse}', [LapseController::class, 'update'])->name('lapses.update');
    Route::delete('periods/{period}/lapses/{lapse}', [LapseController::class, 'destroy'])->name('lapses.destroy');
});
```

- [ ] **Step 11: Ejecutar tests — deben pasar todos**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 php artisan test --compact --filter=LapseControllerTest 2>&1 | tail -10
```

Expected: todos los tests de Lapse en verde.

- [ ] **Step 12: Ejecutar suite completa para verificar que no hay regresiones**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 php artisan test --compact 2>&1 | tail -5
```

Expected: todos los tests en verde (297 + nuevos).

- [ ] **Step 13: Formatear con Pint**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 vendor/bin/pint --dirty --format agent
```

- [ ] **Step 14: Commit**

```bash
git -C /var/www/cacao/.worktrees/feat/scheduling-lapses add \
    app/Policies/LapsePolicy.php \
    app/Http/Requests/Scheduling/StoreLapseRequest.php \
    app/Http/Requests/Scheduling/UpdateLapseRequest.php \
    app/Http/Wrappers/Scheduling/LapseWrapper.php \
    app/Http/Resources/Scheduling/LapseResource.php \
    app/Actions/Scheduling/CreateLapseAction.php \
    app/Actions/Scheduling/UpdateLapseAction.php \
    app/Actions/Scheduling/DeleteLapseAction.php \
    app/Http/Controllers/Scheduling/LapseController.php \
    app/Http/Resources/Scheduling/PeriodResource.php \
    app/Http/Controllers/Scheduling/PeriodController.php \
    routes/web.php
git -C /var/www/cacao/.worktrees/feat/scheduling-lapses commit -m "feat(scheduling): implement Lapse CRUD (green)"
```

---

## Task 5: Frontend — tipos, composable y componentes

**Files:**
- Modify: `resources/js/types/scheduling.ts`
- Create: `resources/js/composables/forms/useLapseForm.ts`
- Create: `resources/js/components/scheduling/LapsesPanel.vue`
- Create: `resources/js/components/scheduling/CreateLapseModal.vue`
- Create: `resources/js/components/scheduling/EditLapseModal.vue`
- Create: `resources/js/components/scheduling/DeleteLapseModal.vue`
- Modify: `resources/js/pages/scheduling/Periods/Index.vue`

- [ ] **Step 1: Regenerar Wayfinder para incluir rutas de lapses**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 php artisan wayfinder:generate --no-interaction
```

Expected: se genera `resources/js/actions/App/Http/Controllers/Scheduling/LapseController.ts` con funciones `store`, `update`, `destroy`.

- [ ] **Step 2: Actualizar `resources/js/types/scheduling.ts`**

Reemplazar el contenido completo:

```typescript
export type Lapse = {
    id: number
    number: number
    name: string
    startDate: string
    endDate: string
}

export type Period = {
    id: number
    name: string
    type: 'semester' | 'year' | 'trimester'
    typeLabel: string
    startDate: string
    endDate: string
    status: 'upcoming' | 'active' | 'closed'
    statusLabel: string
    lapses: Lapse[]
}

export type PeriodCollection = Period[]
```

- [ ] **Step 3: Crear `resources/js/composables/forms/useLapseForm.ts`**

```typescript
import { useForm } from '@inertiajs/vue3'
import { destroy, store, update } from '@/actions/App/Http/Controllers/Scheduling/LapseController'
import type { Lapse, Period } from '@/types/scheduling'

export function useLapseForm() {
    const storeOps = {
        form({ period }: { period: Period }) {
            return useForm({
                number:     '' as unknown as number,
                name:       '',
                start_date: '',
                end_date:   '',
            })
        },
        url({ period }: { period: Period }) {
            return store.url({ period })
        },
    }

    const updateOps = {
        form({ lapse }: { lapse: Lapse }) {
            return useForm({
                number:     lapse.number,
                name:       lapse.name,
                start_date: lapse.startDate,
                end_date:   lapse.endDate,
            })
        },
        url({ period, lapse }: { period: Period; lapse: Lapse }) {
            return update.url({ period, lapse })
        },
    }

    const removeOps = {
        submit({ period, lapse }: { period: Period; lapse: Lapse }): void {
            useForm({}).delete(destroy.url({ period, lapse }))
        },
    }

    return {
        store:  storeOps,
        update: updateOps,
        remove: removeOps,
    }
}
```

- [ ] **Step 4: Crear `resources/js/components/scheduling/CreateLapseModal.vue`**

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/actions/App/Http/Controllers/Scheduling/LapseController'
import type { Period } from '@/types/scheduling'

const props = defineProps<{ open: boolean; period: Period }>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const formKey = ref(0)

function makeForm() {
    return useForm({
        number:     '' as unknown as number,
        name:       '',
        start_date: '',
        end_date:   '',
    })
}

let form = makeForm()

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) {
        form = makeForm()
        formKey.value++
    }
}

function submit(): void {
    form.post(store.url({ period: props.period }), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Nuevo lapso" size="sm" @update:open="close">
        <div :key="formKey" style="display:grid;gap:16px;">
            <div style="display:grid;gap:6px;">
                <label for="cl-number" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                    Número
                </label>
                <input
                    id="cl-number"
                    v-model="form.number"
                    type="number"
                    min="1"
                    class="input"
                    placeholder="Ej: 1"
                    required
                />
                <InputError :message="form.errors.number" />
            </div>
            <div style="display:grid;gap:6px;">
                <label for="cl-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                    Nombre
                </label>
                <input id="cl-name" v-model="form.name" class="input" placeholder="Ej: Primer Lapso" required />
                <InputError :message="form.errors.name" />
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="display:grid;gap:6px;">
                    <label for="cl-start" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Inicio
                    </label>
                    <input id="cl-start" v-model="form.start_date" type="date" class="input" required />
                    <InputError :message="form.errors.start_date" />
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="cl-end" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Fin
                    </label>
                    <input id="cl-end" v-model="form.end_date" type="date" class="input" required />
                    <InputError :message="form.errors.end_date" />
                </div>
            </div>
            <InputError :message="form.errors.period_id" />
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
            <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
            <Button type="button" variant="primary" :loading="form.processing" @click="submit">Crear lapso</Button>
        </div>
    </Modal>
</template>
```

- [ ] **Step 5: Crear `resources/js/components/scheduling/EditLapseModal.vue`**

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/actions/App/Http/Controllers/Scheduling/LapseController'
import type { Lapse, Period } from '@/types/scheduling'

const props = defineProps<{ open: boolean; period: Period; lapse: Lapse }>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const formKey = ref(0)

function makeForm() {
    return useForm({
        number:     props.lapse.number,
        name:       props.lapse.name,
        start_date: props.lapse.startDate,
        end_date:   props.lapse.endDate,
    })
}

let form = makeForm()

watch(() => props.lapse, () => {
    form = makeForm()
    formKey.value++
})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.patch(update.url({ period: props.period, lapse: props.lapse }), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Editar lapso" size="sm" @update:open="close">
        <div :key="formKey" style="display:grid;gap:16px;">
            <div style="display:grid;gap:6px;">
                <label for="el-number" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                    Número
                </label>
                <input
                    id="el-number"
                    v-model="form.number"
                    type="number"
                    min="1"
                    class="input"
                    required
                />
                <InputError :message="form.errors.number" />
            </div>
            <div style="display:grid;gap:6px;">
                <label for="el-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                    Nombre
                </label>
                <input id="el-name" v-model="form.name" class="input" required />
                <InputError :message="form.errors.name" />
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="display:grid;gap:6px;">
                    <label for="el-start" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Inicio
                    </label>
                    <input id="el-start" v-model="form.start_date" type="date" class="input" required />
                    <InputError :message="form.errors.start_date" />
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="el-end" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Fin
                    </label>
                    <input id="el-end" v-model="form.end_date" type="date" class="input" required />
                    <InputError :message="form.errors.end_date" />
                </div>
            </div>
            <InputError :message="form.errors.period_id" />
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
            <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
            <Button type="button" variant="primary" :loading="form.processing" @click="submit">Guardar cambios</Button>
        </div>
    </Modal>
</template>
```

- [ ] **Step 6: Crear `resources/js/components/scheduling/DeleteLapseModal.vue`**

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/actions/App/Http/Controllers/Scheduling/LapseController'
import type { Lapse, Period } from '@/types/scheduling'

const props = defineProps<{ open: boolean; period: Period; lapse: Lapse }>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.delete(destroy.url({ period: props.period, lapse: props.lapse }), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Eliminar lapso" size="sm" @update:open="close">
        <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
            ¿Eliminar el lapso <strong>{{ lapse.name }}</strong> del período
            <strong>{{ period.name }}</strong>? Esta acción no se puede deshacer.
        </p>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button variant="danger" :loading="form.processing" @click="submit">Eliminar</Button>
        </div>
    </Modal>
</template>
```

- [ ] **Step 7: Crear `resources/js/components/scheduling/LapsesPanel.vue`**

Panel colapsable que muestra los lapsos de un período year con acciones de crear, editar y eliminar.

```vue
<script setup lang="ts">
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import CreateLapseModal from '@/components/scheduling/CreateLapseModal.vue'
import DeleteLapseModal from '@/components/scheduling/DeleteLapseModal.vue'
import EditLapseModal from '@/components/scheduling/EditLapseModal.vue'
import type { Lapse, Period } from '@/types/scheduling'

const props = defineProps<{
    period: Period
    canCreate: boolean
    canUpdate: boolean
    canDelete: boolean
}>()

const expanded = ref(false)
const showCreate = ref(false)
const editingLapse = ref<Lapse | null>(null)
const deletingLapse = ref<Lapse | null>(null)
</script>

<template>
    <div style="margin-top:8px;">
        <button
            type="button"
            style="display:inline-flex;align-items:center;gap:6px;font-size:var(--text-xs);color:var(--text-muted);background:none;border:none;cursor:pointer;padding:0;"
            @click="expanded = !expanded"
        >
            <span style="transition:transform 0.15s;" :style="{ transform: expanded ? 'rotate(90deg)' : 'rotate(0deg)' }">▶</span>
            Lapsos ({{ period.lapses.length }})
        </button>

        <div v-if="expanded" style="margin-top:8px;padding:12px;background:var(--papel-dark);border-radius:6px;display:flex;flex-direction:column;gap:8px;">
            <div
                v-for="lapse in period.lapses"
                :key="lapse.id"
                style="display:flex;align-items:center;justify-content:space-between;gap:8px;"
            >
                <div>
                    <span style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        {{ lapse.name }}
                    </span>
                    <span style="font-size:var(--text-xs);color:var(--text-muted);margin-left:8px;">
                        {{ lapse.startDate }} — {{ lapse.endDate }}
                    </span>
                </div>
                <div style="display:flex;gap:4px;">
                    <Button
                        v-if="canUpdate"
                        variant="ghost"
                        size="sm"
                        icon-only
                        icon="edit"
                        :aria-label="`Editar ${lapse.name}`"
                        @click="editingLapse = lapse"
                    />
                    <Button
                        v-if="canDelete"
                        variant="ghost"
                        size="sm"
                        icon-only
                        icon="trash"
                        :aria-label="`Eliminar ${lapse.name}`"
                        @click="deletingLapse = lapse"
                    />
                </div>
            </div>

            <div v-if="period.lapses.length === 0" style="font-size:var(--text-xs);color:var(--text-muted);">
                Sin lapsos registrados.
            </div>

            <Button
                v-if="canCreate"
                variant="ghost"
                size="sm"
                icon="plus"
                style="align-self:flex-start;margin-top:4px;"
                @click="showCreate = true"
            >
                Agregar lapso
            </Button>
        </div>
    </div>

    <CreateLapseModal v-model:open="showCreate" :period="period" />

    <EditLapseModal
        v-if="editingLapse"
        :open="!!editingLapse"
        :period="period"
        :lapse="editingLapse"
        @update:open="v => { if (!v) editingLapse = null }"
    />

    <DeleteLapseModal
        v-if="deletingLapse"
        :open="!!deletingLapse"
        :period="period"
        :lapse="deletingLapse"
        @update:open="v => { if (!v) deletingLapse = null }"
    />
</template>
```

- [ ] **Step 8: Actualizar `resources/js/pages/scheduling/Periods/Index.vue`**

Agregar el import de `LapsesPanel` y de `useLapsePermissions` (o reutilizar `canUpdate`/`canDelete` del composable de períodos). Importar también desde `usePeriodPermissions` si exporta `canCreateLapse`.

En la práctica la página reutiliza `can` prop del servidor para permisos de lapses. Agregar al bloque `<script setup>`:

```typescript
import LapsesPanel from '@/components/scheduling/LapsesPanel.vue'
```

Y en el template, dentro de `<tbody>`, debajo de la fila `<tr v-for="period in periods">` pero antes del cierre `</tr>`, agregar una segunda fila para el panel de lapses (usando un `<tr>` condicional con `colspan`):

```vue
<template v-for="period in periods" :key="period.id">
    <tr>
        <td style="font-weight:500;">
            {{ period.name }}
            <LapsesPanel
                v-if="period.type === 'year'"
                :period="period"
                :can-create="can.create"
                :can-update="can.update"
                :can-delete="can.delete"
            />
        </td>
        <td style="color:var(--text-secondary);">{{ period.typeLabel }}</td>
        <td style="color:var(--text-secondary);">{{ period.startDate }} — {{ period.endDate }}</td>
        <td>
            <!-- status badge — misma lógica existente -->
        </td>
        <td>
            <!-- action buttons — misma lógica existente -->
        </td>
    </tr>
</template>
```

> **Nota de implementación:** La forma más sencilla es reemplazar el `v-for` actual en `<tbody>` de `<tr v-for="period in periods"` por `<template v-for="period in periods"` y mover el `LapsesPanel` dentro de la primera celda `<td>`. El resto del marcado de la fila no cambia.

El archivo completo de `Periods/Index.vue` tras la modificación (solo la sección `<tbody>` cambia):

```vue
<tbody>
    <template v-for="period in periods" :key="period.id">
        <tr>
            <td style="font-weight:500;">
                {{ period.name }}
                <LapsesPanel
                    v-if="period.type === 'year'"
                    :period="period"
                    :can-create="can.create"
                    :can-update="can.update"
                    :can-delete="can.delete"
                />
            </td>
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
                        v-if="canActivate && period.status === 'upcoming'"
                        variant="ghost"
                        size="sm"
                        @click="activate.submit({ period })"
                    >
                        Activar
                    </Button>
                    <Button
                        v-if="canClose && period.status === 'active'"
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
    </template>
    <tr v-if="!periods.length">
        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
            No hay períodos registrados.
        </td>
    </tr>
</tbody>
```

- [ ] **Step 9: Compilar frontend**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 npm run build 2>&1 | tail -15
```

Expected: compilación sin errores de TypeScript ni Vite.

- [ ] **Step 10: Ejecutar suite completa de tests**

```bash
docker exec -w /var/www/html/.worktrees/feat/scheduling-lapses cacao-laravel.test-1 php artisan test --compact 2>&1 | tail -5
```

Expected: todos los tests en verde.

- [ ] **Step 11: Commit final**

```bash
git -C /var/www/cacao/.worktrees/feat/scheduling-lapses add \
    resources/js/types/scheduling.ts \
    resources/js/composables/forms/useLapseForm.ts \
    resources/js/components/scheduling/LapsesPanel.vue \
    resources/js/components/scheduling/CreateLapseModal.vue \
    resources/js/components/scheduling/EditLapseModal.vue \
    resources/js/components/scheduling/DeleteLapseModal.vue \
    resources/js/pages/scheduling/Periods/Index.vue \
    resources/js/actions/
git -C /var/www/cacao/.worktrees/feat/scheduling-lapses commit -m "feat(scheduling): add Lapses frontend panel, modals and composable"
```
