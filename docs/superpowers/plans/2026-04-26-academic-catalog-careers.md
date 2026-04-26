# Módulo Carreras — Plan de Implementación (Parte 2)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar el CRUD completo del módulo Carreras (segunda entidad del catálogo académico), incluyendo backend completo, tests Pest y UI con modals, siguiendo los mismos patrones de la Parte 1 (CareerCategory).

**Architecture:** `StoreCareerRequest → CareerController → CareerWrapper → CreateCareerAction → CareerResource`. La tabla `careers` ya existe en la BD pero le faltan columnas. La página Vue recibe `careers[]`, `categories[]` y `can{}`, con filtro por categoría en cliente. "Ver pensums" queda como enlace placeholder hasta la Parte 3.

**Tech Stack:** Laravel 13 · Eloquent · Spatie Permission · Vue 3 · Inertia v3 · Wayfinder v0 · Pest v4 · TailwindCSS v4

---

## Mapa de archivos

| Acción | Archivo |
|--------|---------|
| Crear | `database/migrations/YYYY_add_fields_to_careers_table.php` |
| Modificar | `app/Models/Career.php` |
| Crear | `database/factories/CareerFactory.php` |
| Modificar | `database/data/permissions.yaml` |
| Crear | `app/Policies/Academic/CareerPolicy.php` |
| Crear | `app/Http/Requests/Academic/StoreCareerRequest.php` |
| Crear | `app/Http/Requests/Academic/UpdateCareerRequest.php` |
| Crear | `app/Http/Wrappers/Academic/CareerWrapper.php` |
| Crear | `app/Actions/Academic/CreateCareerAction.php` |
| Crear | `app/Actions/Academic/UpdateCareerAction.php` |
| Crear | `app/Actions/Academic/DeleteCareerAction.php` |
| Crear | `app/Http/Resources/Academic/CareerResource.php` |
| Crear | `app/Http/Controllers/Academic/CareerController.php` |
| Modificar | `routes/web.php` |
| Modificar | `app/Providers/AppServiceProvider.php` |
| Crear | `tests/Feature/Academic/CareerControllerTest.php` |
| Modificar | `resources/js/types/academic.ts` |
| Crear | `resources/js/composables/permissions/useCareerPermissions.ts` |
| Crear | `resources/js/components/academic/CreateCareerModal.vue` |
| Crear | `resources/js/components/academic/EditCareerModal.vue` |
| Crear | `resources/js/components/academic/DeleteCareerModal.vue` |
| Crear | `resources/js/pages/academic/Careers/Index.vue` |
| Modificar | `resources/js/components/AppSidebar.vue` |

---

## Task 1: Migración — agregar columnas a `careers`

La tabla `careers` existe con solo `id`, `career_category_id` y `timestamps`. Hay que agregar `name`, `code` y `active`.

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_add_fields_to_careers_table.php`

- [ ] **Step 1: Crear la migración**

```bash
vendor/bin/sail artisan make:migration add_fields_to_careers_table --table=careers
```

- [ ] **Step 2: Escribir el contenido de la migración**

Abrir el archivo recién creado en `database/migrations/` y reemplazar su contenido con:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            $table->string('name', 255)->after('career_category_id');
            $table->string('code', 10)->unique()->after('name');
            $table->boolean('active')->default(true)->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            $table->dropColumn(['name', 'code', 'active']);
        });
    }
};
```

- [ ] **Step 3: Correr la migración**

```bash
vendor/bin/sail artisan migrate
```

Expected: `Migrating: YYYY_MM_DD_HHMMSS_add_fields_to_careers_table` → `Migrated`

- [ ] **Step 4: Completar el modelo `Career`**

Reemplazar `app/Models/Career.php` con:

```php
<?php

namespace App\Models;

use Database\Factories\CareerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['career_category_id', 'name', 'code', 'active'])]
class Career extends Model
{
    /** @use HasFactory<CareerFactory> */
    use HasFactory;

    /** @var array<string, string> */
    protected $casts = ['active' => 'boolean'];

    public function careerCategory(): BelongsTo
    {
        return $this->belongsTo(CareerCategory::class);
    }

    /** Relación con pensums — será completada en Parte 3. */
    public function pensums(): HasMany
    {
        return $this->hasMany('App\Models\Pensum');
    }
}
```

- [ ] **Step 5: Crear la factory `CareerFactory`**

```bash
vendor/bin/sail artisan make:factory CareerFactory --model=Career
```

Reemplazar el contenido generado con:

```php
<?php

namespace Database\Factories;

use App\Models\Career;
use App\Models\CareerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Career>
 */
class CareerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'career_category_id' => CareerCategory::factory(),
            'name' => fake()->unique()->sentence(3, false),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
```

- [ ] **Step 6: Verificar que el modelo y la factory funcionan**

```bash
vendor/bin/sail artisan tinker --execute 'use App\Models\Career; $c = Career::factory()->make(); echo $c->name . " - " . $c->code;'
```

Expected: imprime algo como `"adipisci nam at - XYZ"`

- [ ] **Step 7: Commit**

```bash
git add database/migrations/ app/Models/Career.php database/factories/CareerFactory.php
git commit -m "feat: add fields to careers table, complete Career model and factory"
```

---

## Task 2: Permisos Spatie

**Files:**
- Modify: `database/data/permissions.yaml`

- [ ] **Step 1: Agregar permisos de carreras al YAML**

En `database/data/permissions.yaml`, añadir al final de la lista `permissions:`:

```yaml
  - name: careers.view
    guard: web
  - name: careers.create
    guard: web
  - name: careers.update
    guard: web
  - name: careers.delete
    guard: web
```

- [ ] **Step 2: Sembrar los permisos en la BD**

```bash
vendor/bin/sail artisan db:seed --class=PermissionSeeder
```

Expected: termina sin errores.

- [ ] **Step 3: Commit**

```bash
git add database/data/permissions.yaml
git commit -m "feat: add careers permissions to YAML seeder"
```

---

## Task 3: Policy + FormRequests + Wrapper

**Files:**
- Create: `app/Policies/Academic/CareerPolicy.php`
- Create: `app/Http/Requests/Academic/StoreCareerRequest.php`
- Create: `app/Http/Requests/Academic/UpdateCareerRequest.php`
- Create: `app/Http/Wrappers/Academic/CareerWrapper.php`

- [ ] **Step 1: Crear `CareerPolicy`**

```bash
vendor/bin/sail artisan make:policy Academic/CareerPolicy --model=Career
```

Reemplazar el contenido con:

```php
<?php

namespace App\Policies\Academic;

use App\Models\Career;
use App\Models\User;

class CareerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('careers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('careers.create');
    }

    public function update(User $user, Career $career): bool
    {
        return $user->can('careers.update');
    }

    public function delete(User $user, Career $career): bool
    {
        return $user->can('careers.delete');
    }
}
```

- [ ] **Step 2: Registrar la policy en `AppServiceProvider`**

En `app/Providers/AppServiceProvider.php`, en el bloque de `Gate::policy(...)`, añadir después de la línea de `CareerCategoryPolicy`:

```php
Gate::policy(Career::class, CareerPolicy::class);
```

Y añadir el import al inicio del archivo (junto a los otros imports de modelos/policies):

```php
use App\Models\Career;
use App\Policies\Academic\CareerPolicy;
```

- [ ] **Step 3: Crear `StoreCareerRequest`**

```bash
vendor/bin/sail artisan make:request Academic/StoreCareerRequest
```

Reemplazar el contenido con:

```php
<?php

namespace App\Http\Requests\Academic;

use App\Models\Career;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCareerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Career::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }

        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'career_category_id' => ['required', 'integer', Rule::exists('career_categories', 'id')],
            'name'               => ['required', 'string', 'min:2', 'max:255'],
            'code'               => ['required', 'string', 'max:10', Rule::unique('careers', 'code')],
            'active'             => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'career_category_id.required' => 'Debes seleccionar una categoría.',
            'career_category_id.exists'   => 'La categoría seleccionada no existe.',
            'name.min'                    => 'El nombre debe tener al menos 2 caracteres.',
            'name.max'                    => 'El nombre no puede superar los 255 caracteres.',
            'code.unique'                 => 'Ya existe una carrera con ese código.',
            'code.max'                    => 'El código no puede superar los 10 caracteres.',
        ];
    }
}
```

- [ ] **Step 4: Crear `UpdateCareerRequest`**

```bash
vendor/bin/sail artisan make:request Academic/UpdateCareerRequest
```

Reemplazar el contenido con:

```php
<?php

namespace App\Http\Requests\Academic;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCareerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $career = $this->route('career');

        return $this->user()?->can('update', $career) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }

        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $career = $this->route('career');

        return [
            'career_category_id' => ['required', 'integer', Rule::exists('career_categories', 'id')],
            'name'               => ['required', 'string', 'min:2', 'max:255'],
            'code'               => ['required', 'string', 'max:10', Rule::unique('careers', 'code')->ignore($career)],
            'active'             => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'career_category_id.required' => 'Debes seleccionar una categoría.',
            'career_category_id.exists'   => 'La categoría seleccionada no existe.',
            'name.min'                    => 'El nombre debe tener al menos 2 caracteres.',
            'name.max'                    => 'El nombre no puede superar los 255 caracteres.',
            'code.unique'                 => 'Ya existe una carrera con ese código.',
            'code.max'                    => 'El código no puede superar los 10 caracteres.',
        ];
    }
}
```

- [ ] **Step 5: Crear `CareerWrapper`**

Crear el archivo `app/Http/Wrappers/Academic/CareerWrapper.php`:

```php
<?php

namespace App\Http\Wrappers\Academic;

use Illuminate\Support\Collection;

class CareerWrapper extends Collection
{
    public function getCategoryId(): int
    {
        return (int) $this->get('career_category_id');
    }

    public function getName(): string
    {
        return $this->get('name');
    }

    public function getCode(): string
    {
        return $this->get('code');
    }

    public function isActive(): bool
    {
        return (bool) ($this->get('active') ?? true);
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Policies/Academic/CareerPolicy.php \
        app/Providers/AppServiceProvider.php \
        app/Http/Requests/Academic/StoreCareerRequest.php \
        app/Http/Requests/Academic/UpdateCareerRequest.php \
        app/Http/Wrappers/Academic/CareerWrapper.php
git commit -m "feat: add CareerPolicy, form requests, and wrapper"
```

---

## Task 4: Actions + Resource

**Files:**
- Create: `app/Actions/Academic/CreateCareerAction.php`
- Create: `app/Actions/Academic/UpdateCareerAction.php`
- Create: `app/Actions/Academic/DeleteCareerAction.php`
- Create: `app/Http/Resources/Academic/CareerResource.php`

- [ ] **Step 1: Crear `CreateCareerAction`**

```bash
vendor/bin/sail artisan make:class Actions/Academic/CreateCareerAction
```

Reemplazar el contenido con:

```php
<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\CareerWrapper;
use App\Models\Career;

class CreateCareerAction
{
    public function handle(CareerWrapper $wrapper): Career
    {
        return Career::create([
            'career_category_id' => $wrapper->getCategoryId(),
            'name'               => $wrapper->getName(),
            'code'               => $wrapper->getCode(),
            'active'             => $wrapper->isActive(),
        ]);
    }
}
```

- [ ] **Step 2: Crear `UpdateCareerAction`**

```bash
vendor/bin/sail artisan make:class Actions/Academic/UpdateCareerAction
```

Reemplazar el contenido con:

```php
<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\CareerWrapper;
use App\Models\Career;

class UpdateCareerAction
{
    public function handle(Career $career, CareerWrapper $wrapper): Career
    {
        $career->update([
            'career_category_id' => $wrapper->getCategoryId(),
            'name'               => $wrapper->getName(),
            'code'               => $wrapper->getCode(),
            'active'             => $wrapper->isActive(),
        ]);

        return $career;
    }
}
```

- [ ] **Step 3: Crear `DeleteCareerAction`**

```bash
vendor/bin/sail artisan make:class Actions/Academic/DeleteCareerAction
```

Reemplazar el contenido con:

```php
<?php

namespace App\Actions\Academic;

use App\Models\Career;

class DeleteCareerAction
{
    /**
     * Elimina la carrera si no tiene pensums asociados.
     * Retorna false si la eliminación fue bloqueada.
     */
    public function handle(Career $career): bool
    {
        if ($career->pensums()->exists()) {
            return false;
        }

        $career->delete();

        return true;
    }
}
```

- [ ] **Step 4: Crear `CareerResource`**

```bash
vendor/bin/sail artisan make:resource Academic/CareerResource
```

Reemplazar el contenido con:

```php
<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CareerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'code'         => $this->code,
            'active'       => $this->active,
            'category'     => [
                'id'   => $this->careerCategory->id,
                'name' => $this->careerCategory->name,
            ],
            'pensumsCount' => $this->pensums_count ?? 0,
        ];
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Actions/Academic/CreateCareerAction.php \
        app/Actions/Academic/UpdateCareerAction.php \
        app/Actions/Academic/DeleteCareerAction.php \
        app/Http/Resources/Academic/CareerResource.php
git commit -m "feat: add Career actions and resource"
```

---

## Task 5: Controller + Routes

**Files:**
- Create: `app/Http/Controllers/Academic/CareerController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Crear `CareerController`**

```bash
vendor/bin/sail artisan make:controller Academic/CareerController
```

Reemplazar el contenido con:

```php
<?php

namespace App\Http\Controllers\Academic;

use App\Actions\Academic\CreateCareerAction;
use App\Actions\Academic\DeleteCareerAction;
use App\Actions\Academic\UpdateCareerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreCareerRequest;
use App\Http\Requests\Academic\UpdateCareerRequest;
use App\Http\Resources\Academic\CareerCategoryResource;
use App\Http\Resources\Academic\CareerResource;
use App\Http\Wrappers\Academic\CareerWrapper;
use App\Models\Career;
use App\Models\CareerCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CareerController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Career::class);

        $actor = $request->user();

        return Inertia::render('academic/Careers/Index', [
            'careers'    => CareerResource::collection(
                Career::with('careerCategory')->orderBy('name')->get()
            )->resolve(),
            'categories' => CareerCategoryResource::collection(
                CareerCategory::orderBy('name')->get()
            )->resolve(),
            'can' => [
                'create' => $actor->can('create', Career::class),
                'update' => $actor->can('update', new Career),
                'delete' => $actor->can('delete', new Career),
            ],
        ]);
    }

    public function store(StoreCareerRequest $request, CreateCareerAction $action): RedirectResponse
    {
        $action->handle(new CareerWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Carrera creada.']);

        return to_route('academic.careers.index');
    }

    public function update(UpdateCareerRequest $request, Career $career, UpdateCareerAction $action): RedirectResponse
    {
        $action->handle($career, new CareerWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Carrera actualizada.']);

        return to_route('academic.careers.index');
    }

    public function destroy(Career $career, DeleteCareerAction $action): RedirectResponse
    {
        Gate::authorize('delete', $career);

        if (! $action->handle($career)) {
            Inertia::flash('toast', [
                'type'    => 'error',
                'message' => 'No se puede eliminar: la carrera tiene pensums asociados.',
            ]);

            return to_route('academic.careers.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Carrera eliminada.']);

        return to_route('academic.careers.index');
    }
}
```

- [ ] **Step 2: Agregar rutas al grupo `academic` en `routes/web.php`**

Dentro del bloque `Route::middleware(['auth', 'verified'])->prefix('academic')->name('academic.')`, añadir DESPUÉS de las rutas de `career-categories`:

```php
Route::get('careers', [CareerController::class, 'index'])->name('careers.index');
Route::post('careers', [CareerController::class, 'store'])->name('careers.store');
Route::patch('careers/{career}', [CareerController::class, 'update'])->name('careers.update');
Route::delete('careers/{career}', [CareerController::class, 'destroy'])->name('careers.destroy');
```

Y añadir el import del controller al inicio del archivo web.php (junto a los demás imports de controllers Academic):

```php
use App\Http\Controllers\Academic\CareerController;
```

- [ ] **Step 3: Verificar que las rutas fueron registradas**

```bash
vendor/bin/sail artisan route:list --name=academic.careers
```

Expected:
```
GET|HEAD  academic/careers              academic.careers.index
POST      academic/careers              academic.careers.store
PATCH     academic/careers/{career}     academic.careers.update
DELETE    academic/careers/{career}     academic.careers.destroy
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Academic/CareerController.php routes/web.php
git commit -m "feat: add CareerController and careers routes"
```

---

## Task 6: Regenerar Wayfinder + Pint

- [ ] **Step 1: Regenerar archivos Wayfinder**

```bash
vendor/bin/sail artisan wayfinder:generate
```

Expected: genera `resources/js/routes/academic/careers/index.ts` con funciones tipadas para `index`, `store`, `update`, `destroy`.

- [ ] **Step 2: Verificar el archivo generado**

```bash
grep -E "^export const (index|store|update|destroy)" resources/js/routes/academic/careers/index.ts
```

Expected:
```
export const index = ...
export const store = ...
export const update = ...
export const destroy = ...
```

- [ ] **Step 3: Formatear PHP con Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

Expected: no errores; aplica formato a los archivos PHP modificados.

- [ ] **Step 4: Commit**

```bash
git add resources/js/routes/academic/careers/
git commit -m "chore: regenerate Wayfinder for careers routes"
```

---

## Task 7: Feature Tests

**Files:**
- Create: `tests/Feature/Academic/CareerControllerTest.php`

- [ ] **Step 1: Crear el archivo de test**

```bash
vendor/bin/sail artisan make:test --pest Academic/CareerControllerTest
```

- [ ] **Step 2: Escribir los tests**

Reemplazar el contenido de `tests/Feature/Academic/CareerControllerTest.php` con:

```php
<?php

use App\Models\Career;
use App\Models\CareerCategory;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'careers.view',   'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'careers.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'careers.update', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'careers.delete', 'guard_name' => 'web']);

    Permission::firstOrCreate(['name' => 'career-categories.view', 'guard_name' => 'web']);
});

function careerUserWith(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('unauthenticated user is redirected to login on careers index', function () {
    $this->get('/academic/careers')
        ->assertRedirect('/login');
});

test('authenticated user without careers.view gets 403 on careers index', function () {
    $this->actingAs(User::factory()->create())
        ->get('/academic/careers')
        ->assertForbidden();
});

test('user with careers.view sees the careers index page', function () {
    $category = CareerCategory::factory()->create();
    Career::factory()->create(['career_category_id' => $category->id, 'name' => 'Informática', 'code' => 'INF']);

    $this->actingAs(careerUserWith('careers.view'))
        ->get('/academic/careers')
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('academic/Careers/Index', false)
                ->has('careers', 1)
                ->has('categories')
                ->has('can')
        );
});

test('careers index includes category name in each career', function () {
    $category = CareerCategory::factory()->create(['name' => 'Ingeniería']);
    Career::factory()->create(['career_category_id' => $category->id, 'code' => 'INF']);

    $this->actingAs(careerUserWith('careers.view'))
        ->get('/academic/careers')
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('careers.0.category.name')
                ->where('careers.0.category.name', 'Ingeniería')
        );
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('user without careers.create cannot store a career', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name' => 'Sistemas',
            'code' => 'SIS',
        ])
        ->assertForbidden();
});

test('store fails validation when name is blank', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name' => '',
            'code' => 'SIS',
        ])
        ->assertSessionHasErrors('name');
});

test('store fails validation when code is duplicate', function () {
    $category = CareerCategory::factory()->create();
    Career::factory()->create(['career_category_id' => $category->id, 'code' => 'INF']);

    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name'               => 'Informática 2',
            'code'               => 'INF',
        ])
        ->assertSessionHasErrors('code');
});

test('store normalizes code to uppercase', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name'               => 'Sistemas',
            'code'               => 'sis',
        ])
        ->assertRedirect(route('academic.careers.index'));

    expect(Career::where('code', 'SIS')->exists())->toBeTrue();
});

test('store fails validation when category does not exist', function () {
    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => 9999,
            'name'               => 'Sistemas',
            'code'               => 'SIS',
        ])
        ->assertSessionHasErrors('career_category_id');
});

test('user with careers.create can store a new career', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name'               => 'Informática',
            'code'               => 'INF',
        ])
        ->assertRedirect(route('academic.careers.index'));

    $career = Career::where('code', 'INF')->first();
    expect($career)->not->toBeNull()
        ->and($career->name)->toBe('Informática')
        ->and($career->active)->toBeTrue();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('user without careers.update cannot update a career', function () {
    $career = Career::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/academic/careers/{$career->id}", [
            'career_category_id' => $career->career_category_id,
            'name'               => 'Nuevo nombre',
            'code'               => $career->code,
            'active'             => true,
        ])
        ->assertForbidden();
});

test('update fails validation when code is duplicate of another career', function () {
    $category = CareerCategory::factory()->create();
    Career::factory()->create(['career_category_id' => $category->id, 'code' => 'INF']);
    $career = Career::factory()->create(['career_category_id' => $category->id, 'code' => 'SIS']);

    $this->actingAs(careerUserWith('careers.update'))
        ->patch("/academic/careers/{$career->id}", [
            'career_category_id' => $category->id,
            'name'               => $career->name,
            'code'               => 'INF',
            'active'             => true,
        ])
        ->assertSessionHasErrors('code');
});

test('update allows same code for the same career', function () {
    $career = Career::factory()->create(['code' => 'INF']);

    $this->actingAs(careerUserWith('careers.update'))
        ->patch("/academic/careers/{$career->id}", [
            'career_category_id' => $career->career_category_id,
            'name'               => $career->name,
            'code'               => 'INF',
            'active'             => true,
        ])
        ->assertRedirect(route('academic.careers.index'));
});

test('user with careers.update can rename a career', function () {
    $career = Career::factory()->create(['name' => 'Informática', 'code' => 'INF']);

    $this->actingAs(careerUserWith('careers.update'))
        ->patch("/academic/careers/{$career->id}", [
            'career_category_id' => $career->career_category_id,
            'name'               => 'Ingeniería en Sistemas',
            'code'               => 'INF',
            'active'             => true,
        ])
        ->assertRedirect(route('academic.careers.index'));

    expect($career->fresh()->name)->toBe('Ingeniería en Sistemas');
});

test('user with careers.update can deactivate a career', function () {
    $career = Career::factory()->create(['active' => true]);

    $this->actingAs(careerUserWith('careers.update'))
        ->patch("/academic/careers/{$career->id}", [
            'career_category_id' => $career->career_category_id,
            'name'               => $career->name,
            'code'               => $career->code,
            'active'             => false,
        ])
        ->assertRedirect(route('academic.careers.index'));

    expect($career->fresh()->active)->toBeFalse();
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('user without careers.delete cannot destroy a career', function () {
    $career = Career::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete("/academic/careers/{$career->id}")
        ->assertForbidden();
});

test('user with careers.delete can destroy a career with no pensums', function () {
    $career = Career::factory()->create();

    $this->actingAs(careerUserWith('careers.delete'))
        ->delete("/academic/careers/{$career->id}")
        ->assertRedirect(route('academic.careers.index'));

    expect(Career::find($career->id))->toBeNull();
});
```

- [ ] **Step 3: Ejecutar los tests para verificar que todos pasan**

```bash
vendor/bin/sail artisan test --compact --filter=CareerControllerTest
```

Expected: todos los tests en verde (`PASS`). Si alguno falla, corregir antes de continuar.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Academic/CareerControllerTest.php
git commit -m "test: add feature tests for CareerController"
```

---

## Task 8: Frontend — Types + Permissions Composable

**Files:**
- Modify: `resources/js/types/academic.ts`
- Create: `resources/js/composables/permissions/useCareerPermissions.ts`

- [ ] **Step 1: Actualizar `types/academic.ts`**

Reemplazar el contenido actual del archivo con:

```typescript
export type CareerCategory = {
  id: number
  name: string
}

export type Career = {
  id: number
  name: string
  code: string
  active: boolean
  category: CareerCategory
  pensumsCount: number
}
```

- [ ] **Step 2: Crear `useCareerPermissions.ts`**

Crear `resources/js/composables/permissions/useCareerPermissions.ts`:

```typescript
import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useCareerPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('careers.create'))
    const canUpdate = computed(() => can('careers.update'))
    const canDelete = computed(() => can('careers.delete'))

    return { canCreate, canUpdate, canDelete }
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/types/academic.ts \
        resources/js/composables/permissions/useCareerPermissions.ts
git commit -m "feat: add Career TS type and career permissions composable"
```

---

## Task 9: Frontend — Modals

**Files:**
- Create: `resources/js/components/academic/CreateCareerModal.vue`
- Create: `resources/js/components/academic/EditCareerModal.vue`
- Create: `resources/js/components/academic/DeleteCareerModal.vue`

- [ ] **Step 1: Crear `CreateCareerModal.vue`**

Crear `resources/js/components/academic/CreateCareerModal.vue`:

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/academic/careers'
import type { CareerCategory } from '@/types/academic'

defineProps<{
    open: boolean
    categories: CareerCategory[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const formKey = ref(0)

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) {
        formKey.value++
    }
}
</script>

<template>
    <Modal :open="open" title="Nueva carrera" size="sm" @update:open="close">
        <Form :key="formKey" v-bind="store.form()" v-slot="{ errors, processing }" @success="close(false)">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cc-category" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Categoría
                    </label>
                    <select id="cc-category" name="career_category_id" class="input" required>
                        <option value="">Seleccionar categoría</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                    <InputError :message="errors.career_category_id" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cc-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="cc-name" name="name" class="input" placeholder="Ej: Ingeniería en Sistemas" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cc-code" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Código
                    </label>
                    <input
                        id="cc-code"
                        name="code"
                        class="input"
                        placeholder="Ej: INF"
                        maxlength="10"
                        style="text-transform:uppercase;"
                        required
                    />
                    <InputError :message="errors.code" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Crear carrera</Button>
            </div>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 2: Crear `EditCareerModal.vue`**

Crear `resources/js/components/academic/EditCareerModal.vue`:

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/academic/careers'
import type { Career, CareerCategory } from '@/types/academic'

const props = defineProps<{
    open: boolean
    career: Career
    categories: CareerCategory[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const formKey = ref(0)

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) {
        formKey.value++
    }
}
</script>

<template>
    <Modal :open="open" title="Editar carrera" size="sm" @update:open="close">
        <Form
            :key="formKey"
            v-bind="update.form(career)"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="ec-category" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Categoría
                    </label>
                    <select id="ec-category" name="career_category_id" class="input" required>
                        <option
                            v-for="cat in categories"
                            :key="cat.id"
                            :value="cat.id"
                            :selected="cat.id === career.category.id"
                        >
                            {{ cat.name }}
                        </option>
                    </select>
                    <InputError :message="errors.career_category_id" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ec-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="ec-name" name="name" class="input" :value="career.name" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ec-code" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Código
                    </label>
                    <input
                        id="ec-code"
                        name="code"
                        class="input"
                        :value="career.code"
                        maxlength="10"
                        style="text-transform:uppercase;"
                        required
                    />
                    <InputError :message="errors.code" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ec-active" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Estado
                    </label>
                    <select id="ec-active" name="active" class="input">
                        <option value="1" :selected="career.active">Activa</option>
                        <option value="0" :selected="!career.active">Inactiva</option>
                    </select>
                    <InputError :message="errors.active" />
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

- [ ] **Step 3: Crear `DeleteCareerModal.vue`**

Crear `resources/js/components/academic/DeleteCareerModal.vue`:

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/academic/careers'
import type { Career } from '@/types/academic'

const props = defineProps<{
    open: boolean
    career: Career
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.delete(destroy.url(props.career), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Eliminar carrera" size="sm" @update:open="close">
        <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
            ¿Eliminar la carrera <strong>{{ career.name }} ({{ career.code }})</strong>?
            Si tiene pensums asociados, la operación será rechazada automáticamente.
        </p>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button variant="danger" :loading="form.processing" @click="submit">Eliminar</Button>
        </div>
    </Modal>
</template>
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/academic/CreateCareerModal.vue \
        resources/js/components/academic/EditCareerModal.vue \
        resources/js/components/academic/DeleteCareerModal.vue
git commit -m "feat: add Career modals (create, edit, delete)"
```

---

## Task 10: Frontend — Página + Sidebar

**Files:**
- Create: `resources/js/pages/academic/Careers/Index.vue`
- Modify: `resources/js/components/AppSidebar.vue`

- [ ] **Step 1: Crear `resources/js/pages/academic/Careers/Index.vue`**

```vue
<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Badge from '@/components/UI/AppBadge.vue'
import Button from '@/components/UI/AppButton.vue'
import CreateCareerModal from '@/components/academic/CreateCareerModal.vue'
import DeleteCareerModal from '@/components/academic/DeleteCareerModal.vue'
import EditCareerModal from '@/components/academic/EditCareerModal.vue'
import { useCareerPermissions } from '@/composables/permissions/useCareerPermissions'
import { index } from '@/routes/academic/careers'
import { index as categoriesIndex } from '@/routes/academic/career-categories'
import type { Career, CareerCategory } from '@/types/academic'

type Props = {
    careers: Career[]
    categories: CareerCategory[]
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Académico', href: '#' },
            { title: 'Carreras', href: index.url() },
        ],
    },
})

const { canCreate, canUpdate, canDelete } = useCareerPermissions()

const showCreate = ref(false)
const editingCareer = ref<Career | null>(null)
const deletingCareer = ref<Career | null>(null)

const selectedCategoryId = ref<number | ''>('')

const filteredCareers = computed(() => {
    if (selectedCategoryId.value === '') {
        return props.careers
    }

    return props.careers.filter((c) => c.category.id === selectedCategoryId.value)
})
</script>

<template>
    <Head title="Carreras" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Carreras
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Gestión de carreras agrupadas por categoría
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nueva carrera
            </Button>
        </div>

        <div style="display:flex;align-items:center;gap:12px;">
            <select
                v-model="selectedCategoryId"
                class="input"
                style="max-width:240px;"
            >
                <option value="">Todas las categorías</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Código</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Pensums</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="career in filteredCareers" :key="career.id">
                        <td style="font-weight:500;">{{ career.name }}</td>
                        <td>
                            <Badge variant="neutral">{{ career.code }}</Badge>
                        </td>
                        <td style="color:var(--text-secondary);">{{ career.category.name }}</td>
                        <td>
                            <Badge :variant="career.active ? 'success' : 'neutral'" dot>
                                {{ career.active ? 'Activa' : 'Inactiva' }}
                            </Badge>
                        </td>
                        <td style="color:var(--text-secondary);">{{ career.pensumsCount }}</td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    icon="book-open"
                                    :aria-label="`Ver pensums de ${career.name}`"
                                    disabled
                                    title="Disponible en la Parte 3"
                                />
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar ${career.name}`"
                                    @click="editingCareer = career"
                                />
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${career.name}`"
                                    @click="deletingCareer = career"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!filteredCareers.length">
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay carreras registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateCareerModal
        v-model:open="showCreate"
        :categories="categories"
    />

    <EditCareerModal
        v-if="editingCareer"
        :open="!!editingCareer"
        :career="editingCareer"
        :categories="categories"
        @update:open="v => { if (!v) editingCareer = null }"
    />

    <DeleteCareerModal
        v-if="deletingCareer"
        :open="!!deletingCareer"
        :career="deletingCareer"
        @update:open="v => { if (!v) deletingCareer = null }"
    />
</template>
```

- [ ] **Step 2: Actualizar `AppSidebar.vue` — agregar "Carreras" al grupo Académico**

En `resources/js/components/AppSidebar.vue`, importar el índice de carreras junto a los otros imports de rutas:

```typescript
import { index as careersIndex } from '@/routes/academic/careers'
```

Luego, dentro del bloque del grupo "Académico" (el `if` que comprueba `career-categories.view`), añadir la condición para mostrar "Carreras":

Reemplazar el bloque actual:
```typescript
if (
    page.props.auth?.permissions?.includes('career-categories.view') ||
    page.props.auth?.roles?.includes('Admin')
) {
    groups.push({
        label: 'Académico',
        items: [
            { icon: 'folder', label: 'Categorías', href: careerCategoriesIndex.url() },
        ],
    })
}
```

Con:
```typescript
if (
    page.props.auth?.permissions?.includes('career-categories.view') ||
    page.props.auth?.permissions?.includes('careers.view') ||
    page.props.auth?.roles?.includes('Admin')
) {
    const academicItems: { icon: string; label: string; href: string }[] = []

    if (
        page.props.auth?.permissions?.includes('career-categories.view') ||
        page.props.auth?.roles?.includes('Admin')
    ) {
        academicItems.push({ icon: 'folder', label: 'Categorías', href: careerCategoriesIndex.url() })
    }

    if (
        page.props.auth?.permissions?.includes('careers.view') ||
        page.props.auth?.roles?.includes('Admin')
    ) {
        academicItems.push({ icon: 'book', label: 'Carreras', href: careersIndex.url() })
    }

    if (academicItems.length) {
        groups.push({ label: 'Académico', items: academicItems })
    }
}
```

- [ ] **Step 3: Ejecutar el servidor de desarrollo para verificar visualmente**

```bash
vendor/bin/sail npm run dev
```

Abrir el navegador en la URL del proyecto (usar `vendor/bin/sail artisan laravel-boost:get-absolute-url` si no se conoce). Navegar a `/academic/careers` como usuario Admin y verificar:
- La tabla se muestra con columnas: Nombre, Código, Categoría, Estado, Pensums, Acciones
- El botón "Nueva carrera" abre el modal con el formulario completo
- El filtro por categoría funciona
- El sidebar muestra "Carreras" bajo el grupo Académico

- [ ] **Step 4: Formatear PHP con Pint (verificación final)**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 5: Commit final**

```bash
git add resources/js/pages/academic/Careers/Index.vue \
        resources/js/components/AppSidebar.vue
git commit -m "feat: add Careers index page and sidebar entry"
```

---

## Notas para la Parte 3 (Pensums)

- El botón "Ver pensums" en la tabla de Careers está deshabilitado. En la Parte 3 se reemplazará con `router.visit(pensums.index.url(career))` usando Wayfinder.
- El campo `pensumsCount` en `CareerResource` retorna `0` porque el controller aún no carga `withCount('pensums')`. En la Parte 3 se añade `withCount('pensums')` al query.
- `DeleteCareerAction` ya verifica `$career->pensums()->exists()` — cuando la tabla `pensums` exista, este guard funcionará automáticamente.
- El test "destroy blocked by pensums" debe agregarse en la Parte 3 una vez que `Career::factory()` pueda asociar pensums.
