# Módulo de Infraestructura: Edificios y Aulas — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear el módulo de Infraestructura con CRUD completo de Edificios y Aulas, incluyendo backend (Laravel) y frontend (Vue + Inertia).

**Architecture:** Dos entidades independientes (`Building`, `Classroom`) bajo el prefijo `/infrastructure/`, siguiendo el patrón `FormRequest → Controller → Wrapper → Action → Resource` en backend y `FormComposable → Page → PermissionComposable → Type` en frontend. `Classroom` pertenece a `Building` con FK RESTRICT.

**Tech Stack:** PHP 8.3, Laravel 13, Inertia.js v3, Vue 3, Pest v4, Tailwind CSS v4, Wayfinder v0, Spatie Permission.

---

## File Map

### Nuevos archivos PHP
```
app/Enums/ClassroomType.php
app/Models/Building.php
app/Models/Classroom.php
database/factories/BuildingFactory.php
database/factories/ClassroomFactory.php
database/migrations/XXXX_create_buildings_table.php
database/migrations/XXXX_create_classrooms_table.php
app/Policies/BuildingPolicy.php
app/Policies/ClassroomPolicy.php
app/Http/Requests/Infrastructure/StoreBuildingRequest.php
app/Http/Requests/Infrastructure/UpdateBuildingRequest.php
app/Http/Requests/Infrastructure/StoreClassroomRequest.php
app/Http/Requests/Infrastructure/UpdateClassroomRequest.php
app/Http/Wrappers/Infrastructure/BuildingWrapper.php
app/Http/Wrappers/Infrastructure/ClassroomWrapper.php
app/Http/Resources/Infrastructure/BuildingResource.php
app/Http/Resources/Infrastructure/ClassroomResource.php
app/Actions/Infrastructure/CreateBuildingAction.php
app/Actions/Infrastructure/UpdateBuildingAction.php
app/Actions/Infrastructure/DeleteBuildingAction.php
app/Actions/Infrastructure/CreateClassroomAction.php
app/Actions/Infrastructure/UpdateClassroomAction.php
app/Actions/Infrastructure/DeleteClassroomAction.php
app/Http/Controllers/Infrastructure/BuildingController.php
app/Http/Controllers/Infrastructure/ClassroomController.php
tests/Feature/Infrastructure/BuildingControllerTest.php
tests/Feature/Infrastructure/ClassroomControllerTest.php
```

### Nuevos archivos frontend
```
resources/js/types/infrastructure.ts
resources/js/composables/permissions/useBuildingPermissions.ts
resources/js/composables/permissions/useClassroomPermissions.ts
resources/js/composables/forms/useBuildingForm.ts
resources/js/composables/forms/useClassroomForm.ts
resources/js/composables/filters/useClassroomFilters.ts
resources/js/components/infrastructure/CreateBuildingModal.vue
resources/js/components/infrastructure/EditBuildingModal.vue
resources/js/components/infrastructure/DeleteBuildingModal.vue
resources/js/components/infrastructure/CreateClassroomModal.vue
resources/js/components/infrastructure/EditClassroomModal.vue
resources/js/components/infrastructure/DeleteClassroomModal.vue
resources/js/pages/infrastructure/Buildings/Index.vue
resources/js/pages/infrastructure/Classrooms/Index.vue
```

### Archivos modificados
```
database/data/permissions.yaml       — 8 nuevos permisos
database/data/roles.yaml             — permisos al rol Admin
routes/web.php                       — grupo /infrastructure
resources/js/components/AppSidebar.vue — grupo "Infraestructura"
```

---

## Task 1: Enum + Migraciones + Modelos + Factories

**Files:**
- Create: `app/Enums/ClassroomType.php`
- Create: `app/Models/Building.php`
- Create: `app/Models/Classroom.php`
- Create: `database/factories/BuildingFactory.php`
- Create: `database/factories/ClassroomFactory.php`

- [ ] **Step 1: Crear el enum ClassroomType**

```bash
php artisan make:class Enums/ClassroomType --invokable=false
```

Reemplazar el contenido con:

```php
<?php

namespace App\Enums;

enum ClassroomType: string
{
    case Theory     = 'theory';
    case Laboratory = 'laboratory';

    public function label(): string
    {
        return match ($this) {
            self::Theory     => 'Teórica',
            self::Laboratory => 'Laboratorio',
        };
    }
}
```

- [ ] **Step 2: Crear migración de buildings**

```bash
vendor/bin/sail artisan make:migration create_buildings_table
```

Contenido de la migración:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
```

- [ ] **Step 3: Crear migración de classrooms**

```bash
vendor/bin/sail artisan make:migration create_classrooms_table
```

Contenido:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->restrictOnDelete();
            $table->string('identifier', 50);
            $table->enum('type', ['theory', 'laboratory']);
            $table->unsignedSmallInteger('capacity');
            $table->timestamps();

            $table->unique(['building_id', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
```

- [ ] **Step 4: Ejecutar migraciones**

```bash
vendor/bin/sail artisan migrate
```

Expected: `migrations` table updated, tables `buildings` y `classrooms` creadas.

- [ ] **Step 5: Crear modelo Building con factory**

```bash
vendor/bin/sail artisan make:model Building --factory
```

Reemplazar `app/Models/Building.php`:

```php
<?php

namespace App\Models;

use Database\Factories\BuildingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use HasFactory;

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
```

Reemplazar `database/factories/BuildingFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Edificio ' . fake()->unique()->word(),
        ];
    }
}
```

- [ ] **Step 6: Crear modelo Classroom con factory**

```bash
vendor/bin/sail artisan make:model Classroom --factory
```

Reemplazar `app/Models/Classroom.php`:

```php
<?php

namespace App\Models;

use App\Enums\ClassroomType;
use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['building_id', 'identifier', 'type', 'capacity'])]
class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'type' => ClassroomType::class,
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
```

Reemplazar `database/factories/ClassroomFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\ClassroomType;
use App\Models\Building;
use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'identifier'  => fake()->unique()->bothify('???##'),
            'type'        => fake()->randomElement(ClassroomType::cases())->value,
            'capacity'    => fake()->numberBetween(20, 60),
        ];
    }

    public function theory(): static
    {
        return $this->state(['type' => ClassroomType::Theory->value]);
    }

    public function laboratory(): static
    {
        return $this->state(['type' => ClassroomType::Laboratory->value]);
    }
}
```

- [ ] **Step 7: Commit**

```bash
git add app/Enums/ClassroomType.php app/Models/Building.php app/Models/Classroom.php database/factories/BuildingFactory.php database/factories/ClassroomFactory.php database/migrations/
git commit -m "feat: add Building and Classroom models, migrations, factories, and ClassroomType enum"
```

---

## Task 2: Permisos

**Files:**
- Modify: `database/data/permissions.yaml`
- Modify: `database/data/roles.yaml`

- [ ] **Step 1: Agregar permisos al YAML**

Añadir al final de `database/data/permissions.yaml` (antes del EOF):

```yaml
  - name: buildings.view
    guard: web
  - name: buildings.create
    guard: web
  - name: buildings.update
    guard: web
  - name: buildings.delete
    guard: web
  - name: classrooms.view
    guard: web
  - name: classrooms.create
    guard: web
  - name: classrooms.update
    guard: web
  - name: classrooms.delete
    guard: web
```

- [ ] **Step 2: Asignar permisos al rol Admin en roles.yaml**

Añadir al bloque `permissions` del rol Admin en `database/data/roles.yaml`:

```yaml
      - buildings.view
      - buildings.create
      - buildings.update
      - buildings.delete
      - classrooms.view
      - classrooms.create
      - classrooms.update
      - classrooms.delete
```

- [ ] **Step 3: Re-seedear permisos**

```bash
vendor/bin/sail artisan db:seed --class=PermissionSeeder
vendor/bin/sail artisan db:seed --class=RoleSeeder
```

Expected: sin errores, permisos y roles actualizados.

- [ ] **Step 4: Commit**

```bash
git add database/data/permissions.yaml database/data/roles.yaml
git commit -m "feat: add buildings and classrooms permissions"
```

---

## Task 3: Backend de Edificios

**Files:**
- Create: `app/Policies/BuildingPolicy.php`
- Create: `app/Http/Requests/Infrastructure/StoreBuildingRequest.php`
- Create: `app/Http/Requests/Infrastructure/UpdateBuildingRequest.php`
- Create: `app/Http/Wrappers/Infrastructure/BuildingWrapper.php`
- Create: `app/Http/Resources/Infrastructure/BuildingResource.php`
- Create: `app/Actions/Infrastructure/CreateBuildingAction.php`
- Create: `app/Actions/Infrastructure/UpdateBuildingAction.php`
- Create: `app/Actions/Infrastructure/DeleteBuildingAction.php`
- Create: `app/Http/Controllers/Infrastructure/BuildingController.php`

- [ ] **Step 1: Crear BuildingPolicy**

```bash
vendor/bin/sail artisan make:policy BuildingPolicy --model=Building
```

Reemplazar `app/Policies/BuildingPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;

class BuildingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('buildings.view');
    }

    public function create(User $user): bool
    {
        return $user->can('buildings.create');
    }

    public function update(User $user, Building $building): bool
    {
        return $user->can('buildings.update');
    }

    public function delete(User $user, Building $building): bool
    {
        return $user->can('buildings.delete');
    }
}
```

- [ ] **Step 2: Crear StoreBuildingRequest**

```bash
vendor/bin/sail artisan make:request Infrastructure/StoreBuildingRequest
```

Contenido de `app/Http/Requests/Infrastructure/StoreBuildingRequest.php`:

```php
<?php

namespace App\Http\Requests\Infrastructure;

use App\Models\Building;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Building::class) ?? false;
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
            'name' => ['required', 'string', 'max:100', 'unique:buildings,name'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max'      => 'El nombre no puede superar los 100 caracteres.',
            'name.unique'   => 'Ya existe un edificio con este nombre.',
        ];
    }
}
```

- [ ] **Step 3: Crear UpdateBuildingRequest**

```bash
vendor/bin/sail artisan make:request Infrastructure/UpdateBuildingRequest
```

Contenido de `app/Http/Requests/Infrastructure/UpdateBuildingRequest.php`:

```php
<?php

namespace App\Http\Requests\Infrastructure;

use App\Models\Building;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Building $building */
        $building = $this->route('building');

        return $this->user()?->can('update', $building) ?? false;
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
        /** @var Building $building */
        $building = $this->route('building');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('buildings', 'name')->ignore($building->id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max'      => 'El nombre no puede superar los 100 caracteres.',
            'name.unique'   => 'Ya existe un edificio con este nombre.',
        ];
    }
}
```

- [ ] **Step 4: Crear BuildingWrapper**

```bash
mkdir -p app/Http/Wrappers/Infrastructure
```

Crear `app/Http/Wrappers/Infrastructure/BuildingWrapper.php`:

```php
<?php

namespace App\Http\Wrappers\Infrastructure;

use Illuminate\Support\Collection;

class BuildingWrapper extends Collection
{
    public function getName(): string
    {
        return (string) $this->get('name');
    }
}
```

- [ ] **Step 5: Crear BuildingResource**

```bash
vendor/bin/sail artisan make:resource Infrastructure/BuildingResource
```

Contenido de `app/Http/Resources/Infrastructure/BuildingResource.php`:

```php
<?php

namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'classroomsCount' => $this->classrooms_count ?? 0,
        ];
    }
}
```

- [ ] **Step 6: Crear Actions de Building**

```bash
mkdir -p app/Actions/Infrastructure
```

Crear `app/Actions/Infrastructure/CreateBuildingAction.php`:

```php
<?php

namespace App\Actions\Infrastructure;

use App\Http\Wrappers\Infrastructure\BuildingWrapper;
use App\Models\Building;

class CreateBuildingAction
{
    public function handle(BuildingWrapper $wrapper): Building
    {
        return Building::create(['name' => $wrapper->getName()]);
    }
}
```

Crear `app/Actions/Infrastructure/UpdateBuildingAction.php`:

```php
<?php

namespace App\Actions\Infrastructure;

use App\Http\Wrappers\Infrastructure\BuildingWrapper;
use App\Models\Building;

class UpdateBuildingAction
{
    public function handle(Building $building, BuildingWrapper $wrapper): Building
    {
        $building->update(['name' => $wrapper->getName()]);

        return $building;
    }
}
```

Crear `app/Actions/Infrastructure/DeleteBuildingAction.php`:

```php
<?php

namespace App\Actions\Infrastructure;

use App\Models\Building;

class DeleteBuildingAction
{
    public function handle(Building $building): bool
    {
        if ($building->classrooms()->exists()) {
            return false;
        }

        $building->delete();

        return true;
    }
}
```

- [ ] **Step 7: Crear BuildingController**

```bash
vendor/bin/sail artisan make:controller Infrastructure/BuildingController
```

Contenido de `app/Http/Controllers/Infrastructure/BuildingController.php`:

```php
<?php

namespace App\Http\Controllers\Infrastructure;

use App\Actions\Infrastructure\CreateBuildingAction;
use App\Actions\Infrastructure\DeleteBuildingAction;
use App\Actions\Infrastructure\UpdateBuildingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Infrastructure\StoreBuildingRequest;
use App\Http\Requests\Infrastructure\UpdateBuildingRequest;
use App\Http\Resources\Infrastructure\BuildingResource;
use App\Http\Wrappers\Infrastructure\BuildingWrapper;
use App\Models\Building;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BuildingController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Building::class);

        $buildings = Building::withCount('classrooms')->orderBy('name')->get();

        return Inertia::render('infrastructure/Buildings/Index', [
            'buildings' => BuildingResource::collection($buildings)->resolve(),
            'can' => [
                'create' => $request->user()->can('create', Building::class),
                'update' => $request->user()->can('update', new Building),
                'delete' => $request->user()->can('delete', new Building),
            ],
        ]);
    }

    public function store(StoreBuildingRequest $request, CreateBuildingAction $action): RedirectResponse
    {
        $action->handle(new BuildingWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Edificio creado.']);

        return to_route('infrastructure.buildings.index');
    }

    public function update(UpdateBuildingRequest $request, Building $building, UpdateBuildingAction $action): RedirectResponse
    {
        $action->handle($building, new BuildingWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Edificio actualizado.']);

        return to_route('infrastructure.buildings.index');
    }

    public function destroy(Building $building, DeleteBuildingAction $action): RedirectResponse
    {
        Gate::authorize('delete', $building);

        if (! $action->handle($building)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar: el edificio tiene aulas asignadas.']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Edificio eliminado.']);
        }

        return to_route('infrastructure.buildings.index');
    }
}
```

- [ ] **Step 8: Formatear PHP**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add app/Policies/BuildingPolicy.php app/Http/Requests/Infrastructure/ app/Http/Wrappers/Infrastructure/ app/Http/Resources/Infrastructure/BuildingResource.php app/Actions/Infrastructure/CreateBuildingAction.php app/Actions/Infrastructure/UpdateBuildingAction.php app/Actions/Infrastructure/DeleteBuildingAction.php app/Http/Controllers/Infrastructure/BuildingController.php
git commit -m "feat: add Building backend (Policy, Requests, Wrapper, Resource, Actions, Controller)"
```

---

## Task 4: Backend de Aulas

**Files:**
- Create: `app/Policies/ClassroomPolicy.php`
- Create: `app/Http/Requests/Infrastructure/StoreClassroomRequest.php`
- Create: `app/Http/Requests/Infrastructure/UpdateClassroomRequest.php`
- Create: `app/Http/Wrappers/Infrastructure/ClassroomWrapper.php`
- Create: `app/Http/Resources/Infrastructure/ClassroomResource.php`
- Create: `app/Actions/Infrastructure/CreateClassroomAction.php`
- Create: `app/Actions/Infrastructure/UpdateClassroomAction.php`
- Create: `app/Actions/Infrastructure/DeleteClassroomAction.php`
- Create: `app/Http/Controllers/Infrastructure/ClassroomController.php`

- [ ] **Step 1: Crear ClassroomPolicy**

```bash
vendor/bin/sail artisan make:policy ClassroomPolicy --model=Classroom
```

Reemplazar `app/Policies/ClassroomPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('classrooms.view');
    }

    public function create(User $user): bool
    {
        return $user->can('classrooms.create');
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return $user->can('classrooms.update');
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return $user->can('classrooms.delete');
    }
}
```

- [ ] **Step 2: Crear StoreClassroomRequest**

```bash
vendor/bin/sail artisan make:request Infrastructure/StoreClassroomRequest
```

Contenido de `app/Http/Requests/Infrastructure/StoreClassroomRequest.php`:

```php
<?php

namespace App\Http\Requests\Infrastructure;

use App\Models\Classroom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Classroom::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('identifier'))) {
            $this->merge(['identifier' => trim($this->input('identifier'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'building_id' => ['required', 'integer', 'exists:buildings,id'],
            'identifier'  => [
                'required',
                'string',
                'max:50',
                Rule::unique('classrooms')->where('building_id', $this->input('building_id')),
            ],
            'type'        => ['required', Rule::in(['theory', 'laboratory'])],
            'capacity'    => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'building_id.required' => 'El edificio es obligatorio.',
            'building_id.exists'   => 'El edificio seleccionado no existe.',
            'identifier.required'  => 'El identificador es obligatorio.',
            'identifier.max'       => 'El identificador no puede superar los 50 caracteres.',
            'identifier.unique'    => 'Ya existe un aula con este identificador en el edificio seleccionado.',
            'type.required'        => 'El tipo de aula es obligatorio.',
            'type.in'              => 'El tipo debe ser "Teórica" o "Laboratorio".',
            'capacity.required'    => 'La capacidad es obligatoria.',
            'capacity.min'         => 'La capacidad debe ser al menos 1.',
            'capacity.max'         => 'La capacidad no puede superar 500.',
        ];
    }
}
```

- [ ] **Step 3: Crear UpdateClassroomRequest**

```bash
vendor/bin/sail artisan make:request Infrastructure/UpdateClassroomRequest
```

Contenido de `app/Http/Requests/Infrastructure/UpdateClassroomRequest.php`:

```php
<?php

namespace App\Http\Requests\Infrastructure;

use App\Models\Classroom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Classroom $classroom */
        $classroom = $this->route('classroom');

        return $this->user()?->can('update', $classroom) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('identifier'))) {
            $this->merge(['identifier' => trim($this->input('identifier'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Classroom $classroom */
        $classroom = $this->route('classroom');

        return [
            'building_id' => ['required', 'integer', 'exists:buildings,id'],
            'identifier'  => [
                'required',
                'string',
                'max:50',
                Rule::unique('classrooms')->where('building_id', $this->input('building_id'))->ignore($classroom->id),
            ],
            'type'        => ['required', Rule::in(['theory', 'laboratory'])],
            'capacity'    => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'building_id.required' => 'El edificio es obligatorio.',
            'building_id.exists'   => 'El edificio seleccionado no existe.',
            'identifier.required'  => 'El identificador es obligatorio.',
            'identifier.max'       => 'El identificador no puede superar los 50 caracteres.',
            'identifier.unique'    => 'Ya existe un aula con este identificador en el edificio seleccionado.',
            'type.required'        => 'El tipo de aula es obligatorio.',
            'type.in'              => 'El tipo debe ser "Teórica" o "Laboratorio".',
            'capacity.required'    => 'La capacidad es obligatoria.',
            'capacity.min'         => 'La capacidad debe ser al menos 1.',
            'capacity.max'         => 'La capacidad no puede superar 500.',
        ];
    }
}
```

- [ ] **Step 4: Crear ClassroomWrapper**

Crear `app/Http/Wrappers/Infrastructure/ClassroomWrapper.php`:

```php
<?php

namespace App\Http\Wrappers\Infrastructure;

use Illuminate\Support\Collection;

class ClassroomWrapper extends Collection
{
    public function getBuildingId(): int
    {
        return (int) $this->get('building_id');
    }

    public function getIdentifier(): string
    {
        return (string) $this->get('identifier');
    }

    public function getType(): string
    {
        return (string) $this->get('type');
    }

    public function getCapacity(): int
    {
        return (int) $this->get('capacity');
    }
}
```

- [ ] **Step 5: Crear ClassroomResource**

```bash
vendor/bin/sail artisan make:resource Infrastructure/ClassroomResource
```

Contenido de `app/Http/Resources/Infrastructure/ClassroomResource.php`:

```php
<?php

namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassroomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'identifier' => $this->identifier,
            'type'       => $this->type->value,
            'capacity'   => $this->capacity,
            'building'   => [
                'id'   => $this->building->id,
                'name' => $this->building->name,
            ],
        ];
    }
}
```

- [ ] **Step 6: Crear Actions de Classroom**

Crear `app/Actions/Infrastructure/CreateClassroomAction.php`:

```php
<?php

namespace App\Actions\Infrastructure;

use App\Http\Wrappers\Infrastructure\ClassroomWrapper;
use App\Models\Classroom;

class CreateClassroomAction
{
    public function handle(ClassroomWrapper $wrapper): Classroom
    {
        return Classroom::create([
            'building_id' => $wrapper->getBuildingId(),
            'identifier'  => $wrapper->getIdentifier(),
            'type'        => $wrapper->getType(),
            'capacity'    => $wrapper->getCapacity(),
        ]);
    }
}
```

Crear `app/Actions/Infrastructure/UpdateClassroomAction.php`:

```php
<?php

namespace App\Actions\Infrastructure;

use App\Http\Wrappers\Infrastructure\ClassroomWrapper;
use App\Models\Classroom;

class UpdateClassroomAction
{
    public function handle(Classroom $classroom, ClassroomWrapper $wrapper): Classroom
    {
        $classroom->update([
            'building_id' => $wrapper->getBuildingId(),
            'identifier'  => $wrapper->getIdentifier(),
            'type'        => $wrapper->getType(),
            'capacity'    => $wrapper->getCapacity(),
        ]);

        return $classroom;
    }
}
```

Crear `app/Actions/Infrastructure/DeleteClassroomAction.php`:

```php
<?php

namespace App\Actions\Infrastructure;

use App\Models\Classroom;

class DeleteClassroomAction
{
    public function handle(Classroom $classroom): void
    {
        $classroom->delete();
    }
}
```

- [ ] **Step 7: Crear ClassroomController**

```bash
vendor/bin/sail artisan make:controller Infrastructure/ClassroomController
```

Contenido de `app/Http/Controllers/Infrastructure/ClassroomController.php`:

```php
<?php

namespace App\Http\Controllers\Infrastructure;

use App\Actions\Infrastructure\CreateClassroomAction;
use App\Actions\Infrastructure\DeleteClassroomAction;
use App\Actions\Infrastructure\UpdateClassroomAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Infrastructure\StoreClassroomRequest;
use App\Http\Requests\Infrastructure\UpdateClassroomRequest;
use App\Http\Resources\Infrastructure\BuildingResource;
use App\Http\Resources\Infrastructure\ClassroomResource;
use App\Http\Wrappers\Infrastructure\ClassroomWrapper;
use App\Models\Building;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Classroom::class);

        $classrooms = Classroom::with('building')
            ->when($request->integer('building_id') ?: null, fn ($q, $id) => $q->where('building_id', $id))
            ->orderBy('building_id')
            ->orderBy('identifier')
            ->get();

        $buildings = Building::orderBy('name')->get();

        return Inertia::render('infrastructure/Classrooms/Index', [
            'classrooms' => ClassroomResource::collection($classrooms)->resolve(),
            'buildings'  => BuildingResource::collection($buildings)->resolve(),
            'filters'    => [
                'buildingId' => $request->integer('building_id') ?: null,
            ],
            'can' => [
                'create' => $request->user()->can('create', Classroom::class),
                'update' => $request->user()->can('update', new Classroom),
                'delete' => $request->user()->can('delete', new Classroom),
            ],
        ]);
    }

    public function store(StoreClassroomRequest $request, CreateClassroomAction $action): RedirectResponse
    {
        $action->handle(new ClassroomWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Aula creada.']);

        return to_route('infrastructure.classrooms.index');
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom, UpdateClassroomAction $action): RedirectResponse
    {
        $action->handle($classroom, new ClassroomWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Aula actualizada.']);

        return to_route('infrastructure.classrooms.index');
    }

    public function destroy(Classroom $classroom, DeleteClassroomAction $action): RedirectResponse
    {
        Gate::authorize('delete', $classroom);

        $action->handle($classroom);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Aula eliminada.']);

        return to_route('infrastructure.classrooms.index');
    }
}
```

- [ ] **Step 8: Formatear PHP**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add app/Policies/ClassroomPolicy.php app/Http/Requests/Infrastructure/StoreClassroomRequest.php app/Http/Requests/Infrastructure/UpdateClassroomRequest.php app/Http/Wrappers/Infrastructure/ClassroomWrapper.php app/Http/Resources/Infrastructure/ClassroomResource.php app/Actions/Infrastructure/CreateClassroomAction.php app/Actions/Infrastructure/UpdateClassroomAction.php app/Actions/Infrastructure/DeleteClassroomAction.php app/Http/Controllers/Infrastructure/ClassroomController.php
git commit -m "feat: add Classroom backend (Policy, Requests, Wrapper, Resource, Actions, Controller)"
```

---

## Task 5: Rutas + Wayfinder

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Agregar rutas de infraestructura**

Añadir antes de `require __DIR__.'/settings.php';` en `routes/web.php`:

```php
use App\Http\Controllers\Infrastructure\BuildingController;
use App\Http\Controllers\Infrastructure\ClassroomController;
```

Y el grupo de rutas:

```php
Route::middleware(['auth', 'verified'])->prefix('infrastructure')->name('infrastructure.')->group(function () {
    Route::get('buildings', [BuildingController::class, 'index'])->name('buildings.index');
    Route::post('buildings', [BuildingController::class, 'store'])->name('buildings.store');
    Route::patch('buildings/{building}', [BuildingController::class, 'update'])->name('buildings.update');
    Route::delete('buildings/{building}', [BuildingController::class, 'destroy'])->name('buildings.destroy');

    Route::get('classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
    Route::post('classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
    Route::patch('classrooms/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');
    Route::delete('classrooms/{classroom}', [ClassroomController::class, 'destroy'])->name('classrooms.destroy');
});
```

- [ ] **Step 2: Verificar rutas registradas**

```bash
vendor/bin/sail artisan route:list --path=infrastructure --except-vendor
```

Expected: 8 rutas de infraestructura listadas.

- [ ] **Step 3: Regenerar Wayfinder**

```bash
vendor/bin/sail artisan wayfinder:generate
```

Expected: archivos generados en `resources/js/actions/` y/o `resources/js/routes/infrastructure/`.

- [ ] **Step 4: Commit**

```bash
git add routes/web.php resources/js/routes/ resources/js/actions/
git commit -m "feat: add infrastructure routes and regenerate Wayfinder"
```

---

## Task 6: Tipos TypeScript

**Files:**
- Create: `resources/js/types/infrastructure.ts`

- [ ] **Step 1: Crear types/infrastructure.ts**

Crear `resources/js/types/infrastructure.ts`:

```typescript
export type Building = {
    id: number
    name: string
    classroomsCount: number
}

export type BuildingCollection = Building[]

export type Classroom = {
    id: number
    identifier: string
    type: 'theory' | 'laboratory'
    capacity: number
    building: {
        id: number
        name: string
    }
}

export type ClassroomCollection = Classroom[]
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/types/infrastructure.ts
git commit -m "feat: add infrastructure TypeScript types"
```

---

## Task 7: Frontend de Edificios

**Files:**
- Create: `resources/js/composables/permissions/useBuildingPermissions.ts`
- Create: `resources/js/composables/forms/useBuildingForm.ts`
- Create: `resources/js/components/infrastructure/CreateBuildingModal.vue`
- Create: `resources/js/components/infrastructure/EditBuildingModal.vue`
- Create: `resources/js/components/infrastructure/DeleteBuildingModal.vue`
- Create: `resources/js/pages/infrastructure/Buildings/Index.vue`

- [ ] **Step 1: Crear useBuildingPermissions.ts**

Crear `resources/js/composables/permissions/useBuildingPermissions.ts`:

```typescript
import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useBuildingPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('buildings.create'))
    const canUpdate = computed(() => can('buildings.update'))
    const canDelete = computed(() => can('buildings.delete'))

    return { canCreate, canUpdate, canDelete }
}
```

- [ ] **Step 2: Crear useBuildingForm.ts**

Crear `resources/js/composables/forms/useBuildingForm.ts`:

```typescript
import { useForm } from '@inertiajs/vue3'
import { store, update, destroy } from '@/routes/infrastructure/buildings'
import type { Building } from '@/types/infrastructure'

export function useBuildingForm() {
    const storeOps = {
        form() {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({ name: '' }),
            }
        },
    }

    const updateOps = {
        form({ building }: { building: Building }) {
            return {
                url:    update.url({ building }),
                method: 'patch' as const,
                data:   useForm({ name: building.name }),
            }
        },
    }

    const removeOps = {
        submit({ building }: { building: Building }): void {
            useForm({}).delete(destroy.url({ building }))
        },
    }

    return { store: storeOps, update: updateOps, remove: removeOps }
}
```

- [ ] **Step 3: Crear CreateBuildingModal.vue**

```bash
mkdir -p resources/js/components/infrastructure
```

Crear `resources/js/components/infrastructure/CreateBuildingModal.vue`:

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/infrastructure/buildings'

const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({ name: '' })
}

const form = ref(makeForm())

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
    form.value.post(store.url(), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Nuevo edificio" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cb-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input
                        id="cb-name"
                        v-model="form.name"
                        class="input"
                        placeholder="Ej: Edificio A"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Crear edificio</Button>
            </div>
        </form>
    </Modal>
</template>
```

- [ ] **Step 4: Crear EditBuildingModal.vue**

Crear `resources/js/components/infrastructure/EditBuildingModal.vue`:

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/infrastructure/buildings'
import type { Building } from '@/types/infrastructure'

const props = defineProps<{ building: Building; open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({ name: props.building.name })
}

const form = ref(makeForm())

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
    form.value.patch(update.url({ building: props.building }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Editar edificio" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="eb-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input
                        id="eb-name"
                        v-model="form.name"
                        class="input"
                        required
                    />
                    <InputError :message="form.errors.name" />
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

- [ ] **Step 5: Crear DeleteBuildingModal.vue**

Crear `resources/js/components/infrastructure/DeleteBuildingModal.vue`:

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/infrastructure/buildings'
import type { Building } from '@/types/infrastructure'

const props = defineProps<{ building: Building; open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.delete(destroy.url({ building: props.building }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Eliminar edificio" size="sm" @update:open="close">
        <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
            ¿Eliminar el edificio <strong>{{ building.name }}</strong>?
            No podrás eliminarlo si tiene aulas asignadas.
        </p>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button variant="danger" :loading="form.processing" @click="submit">Eliminar</Button>
        </div>
    </Modal>
</template>
```

- [ ] **Step 6: Crear Buildings/Index.vue**

```bash
mkdir -p resources/js/pages/infrastructure/Buildings
```

Crear `resources/js/pages/infrastructure/Buildings/Index.vue`:

```vue
<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import CreateBuildingModal from '@/components/infrastructure/CreateBuildingModal.vue'
import DeleteBuildingModal from '@/components/infrastructure/DeleteBuildingModal.vue'
import EditBuildingModal from '@/components/infrastructure/EditBuildingModal.vue'
import { useBuildingPermissions } from '@/composables/permissions/useBuildingPermissions'
import type { Building } from '@/types/infrastructure'

type Props = {
    buildings: Building[]
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Infraestructura', href: '#' },
        { title: 'Edificios', href: '#' },
    ],
})

const { canCreate, canUpdate, canDelete } = useBuildingPermissions()

const selectedBuilding = ref<Building | null>(null)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

function openEdit(building: Building): void {
    selectedBuilding.value = building
    showEditModal.value = true
}

function openDelete(building: Building): void {
    selectedBuilding.value = building
    showDeleteModal.value = true
}
</script>

<template>
    <Head title="Edificios" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Edificios
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Gestión de edificios de la institución
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreateModal = true">
                Nuevo edificio
            </Button>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Aulas</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="building in buildings" :key="building.id">
                        <td style="font-weight:500;">{{ building.name }}</td>
                        <td style="color:var(--text-secondary);">{{ building.classroomsCount }}</td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar ${building.name}`"
                                    @click="openEdit(building)"
                                />
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${building.name}`"
                                    @click="openDelete(building)"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!buildings.length">
                        <td colspan="3" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay edificios registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateBuildingModal v-model:open="showCreateModal" />

    <EditBuildingModal
        v-if="selectedBuilding && showEditModal"
        :open="showEditModal"
        :building="selectedBuilding"
        @update:open="v => { if (!v) { showEditModal = false; selectedBuilding = null } }"
    />

    <DeleteBuildingModal
        v-if="selectedBuilding && showDeleteModal"
        :open="showDeleteModal"
        :building="selectedBuilding"
        @update:open="v => { if (!v) { showDeleteModal = false; selectedBuilding = null } }"
    />
</template>
```

- [ ] **Step 7: Commit**

```bash
git add resources/js/composables/permissions/useBuildingPermissions.ts resources/js/composables/forms/useBuildingForm.ts resources/js/components/infrastructure/ resources/js/pages/infrastructure/Buildings/
git commit -m "feat: add Buildings frontend (page, modals, composables)"
```

---

## Task 8: Frontend de Aulas

**Files:**
- Create: `resources/js/composables/permissions/useClassroomPermissions.ts`
- Create: `resources/js/composables/forms/useClassroomForm.ts`
- Create: `resources/js/composables/filters/useClassroomFilters.ts`
- Create: `resources/js/components/infrastructure/CreateClassroomModal.vue`
- Create: `resources/js/components/infrastructure/EditClassroomModal.vue`
- Create: `resources/js/components/infrastructure/DeleteClassroomModal.vue`
- Create: `resources/js/pages/infrastructure/Classrooms/Index.vue`

- [ ] **Step 1: Crear useClassroomPermissions.ts**

Crear `resources/js/composables/permissions/useClassroomPermissions.ts`:

```typescript
import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useClassroomPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('classrooms.create'))
    const canUpdate = computed(() => can('classrooms.update'))
    const canDelete = computed(() => can('classrooms.delete'))

    return { canCreate, canUpdate, canDelete }
}
```

- [ ] **Step 2: Crear useClassroomForm.ts**

Crear `resources/js/composables/forms/useClassroomForm.ts`:

```typescript
import { useForm } from '@inertiajs/vue3'
import { store, update, destroy } from '@/routes/infrastructure/classrooms'
import type { Classroom } from '@/types/infrastructure'

export function useClassroomForm() {
    const storeOps = {
        form() {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({
                    building_id: null as number | null,
                    identifier:  '',
                    type:        'theory' as 'theory' | 'laboratory',
                    capacity:    30,
                }),
            }
        },
    }

    const updateOps = {
        form({ classroom }: { classroom: Classroom }) {
            return {
                url:    update.url({ classroom }),
                method: 'patch' as const,
                data:   useForm({
                    building_id: classroom.building.id,
                    identifier:  classroom.identifier,
                    type:        classroom.type,
                    capacity:    classroom.capacity,
                }),
            }
        },
    }

    const removeOps = {
        submit({ classroom }: { classroom: Classroom }): void {
            useForm({}).delete(destroy.url({ classroom }))
        },
    }

    return { store: storeOps, update: updateOps, remove: removeOps }
}
```

- [ ] **Step 3: Crear useClassroomFilters.ts**

Crear `resources/js/composables/filters/useClassroomFilters.ts`:

```typescript
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { index } from '@/routes/infrastructure/classrooms'

export function useClassroomFilters(initialBuildingId: number | null = null) {
    const buildingId = ref<number | null>(initialBuildingId)

    function applyFilter(): void {
        router.get(
            index.url(),
            buildingId.value ? { building_id: buildingId.value } : {},
            { preserveState: true, replace: true },
        )
    }

    return { buildingId, applyFilter }
}
```

- [ ] **Step 4: Crear CreateClassroomModal.vue**

Crear `resources/js/components/infrastructure/CreateClassroomModal.vue`:

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/infrastructure/classrooms'
import type { Building } from '@/types/infrastructure'

const props = defineProps<{ buildings: Building[]; open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        building_id: null as number | null,
        identifier:  '',
        type:        'theory' as 'theory' | 'laboratory',
        capacity:    30,
    })
}

const form = ref(makeForm())

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
    form.value.post(store.url(), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Nueva aula" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cc-building" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Edificio
                    </label>
                    <select id="cc-building" v-model.number="form.building_id" class="input" required>
                        <option :value="null" disabled>Selecciona un edificio</option>
                        <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <InputError :message="form.errors.building_id" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cc-identifier" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Identificador
                    </label>
                    <input
                        id="cc-identifier"
                        v-model="form.identifier"
                        class="input"
                        placeholder="Ej: 301, Lab A"
                        required
                    />
                    <InputError :message="form.errors.identifier" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cc-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo
                    </label>
                    <select id="cc-type" v-model="form.type" class="input" required>
                        <option value="theory">Teórica</option>
                        <option value="laboratory">Laboratorio</option>
                    </select>
                    <InputError :message="form.errors.type" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cc-capacity" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Capacidad
                    </label>
                    <input
                        id="cc-capacity"
                        v-model.number="form.capacity"
                        type="number"
                        min="1"
                        max="500"
                        class="input"
                        required
                    />
                    <InputError :message="form.errors.capacity" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Crear aula</Button>
            </div>
        </form>
    </Modal>
</template>
```

- [ ] **Step 5: Crear EditClassroomModal.vue**

Crear `resources/js/components/infrastructure/EditClassroomModal.vue`:

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/infrastructure/classrooms'
import type { Building, Classroom } from '@/types/infrastructure'

const props = defineProps<{ classroom: Classroom; buildings: Building[]; open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        building_id: props.classroom.building.id,
        identifier:  props.classroom.identifier,
        type:        props.classroom.type,
        capacity:    props.classroom.capacity,
    })
}

const form = ref(makeForm())

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
    form.value.patch(update.url({ classroom: props.classroom }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Editar aula" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="ec-building" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Edificio
                    </label>
                    <select id="ec-building" v-model.number="form.building_id" class="input" required>
                        <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <InputError :message="form.errors.building_id" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ec-identifier" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Identificador
                    </label>
                    <input
                        id="ec-identifier"
                        v-model="form.identifier"
                        class="input"
                        required
                    />
                    <InputError :message="form.errors.identifier" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ec-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo
                    </label>
                    <select id="ec-type" v-model="form.type" class="input" required>
                        <option value="theory">Teórica</option>
                        <option value="laboratory">Laboratorio</option>
                    </select>
                    <InputError :message="form.errors.type" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ec-capacity" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Capacidad
                    </label>
                    <input
                        id="ec-capacity"
                        v-model.number="form.capacity"
                        type="number"
                        min="1"
                        max="500"
                        class="input"
                        required
                    />
                    <InputError :message="form.errors.capacity" />
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

- [ ] **Step 6: Crear DeleteClassroomModal.vue**

Crear `resources/js/components/infrastructure/DeleteClassroomModal.vue`:

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/infrastructure/classrooms'
import type { Classroom } from '@/types/infrastructure'

const props = defineProps<{ classroom: Classroom; open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.delete(destroy.url({ classroom: props.classroom }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Eliminar aula" size="sm" @update:open="close">
        <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
            ¿Eliminar el aula <strong>{{ classroom.identifier }}</strong> del edificio
            <strong>{{ classroom.building.name }}</strong>? Esta acción no se puede deshacer.
        </p>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button variant="danger" :loading="form.processing" @click="submit">Eliminar</Button>
        </div>
    </Modal>
</template>
```

- [ ] **Step 7: Crear Classrooms/Index.vue**

```bash
mkdir -p resources/js/pages/infrastructure/Classrooms
```

Crear `resources/js/pages/infrastructure/Classrooms/Index.vue`:

```vue
<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { ref } from 'vue'
import Badge from '@/components/UI/AppBadge.vue'
import Button from '@/components/UI/AppButton.vue'
import CreateClassroomModal from '@/components/infrastructure/CreateClassroomModal.vue'
import DeleteClassroomModal from '@/components/infrastructure/DeleteClassroomModal.vue'
import EditClassroomModal from '@/components/infrastructure/EditClassroomModal.vue'
import { useClassroomFilters } from '@/composables/filters/useClassroomFilters'
import { useClassroomPermissions } from '@/composables/permissions/useClassroomPermissions'
import type { Building, Classroom } from '@/types/infrastructure'

type Props = {
    classrooms: Classroom[]
    buildings: Building[]
    filters: { buildingId: number | null }
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Infraestructura', href: '#' },
        { title: 'Aulas', href: '#' },
    ],
})

const { canCreate, canUpdate, canDelete } = useClassroomPermissions()
const { buildingId, applyFilter } = useClassroomFilters(props.filters.buildingId)

const selectedClassroom = ref<Classroom | null>(null)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

function openEdit(classroom: Classroom): void {
    selectedClassroom.value = classroom
    showEditModal.value = true
}

function openDelete(classroom: Classroom): void {
    selectedClassroom.value = classroom
    showDeleteModal.value = true
}

const typeLabel: Record<string, string> = {
    theory:     'Teórica',
    laboratory: 'Laboratorio',
}
</script>

<template>
    <Head title="Aulas" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Aulas
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Gestión de aulas de la institución
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreateModal = true">
                Nueva aula
            </Button>
        </div>

        <div style="display:flex;align-items:center;gap:12px;">
            <select
                v-model.number="buildingId"
                class="input"
                style="max-width:220px;"
                @change="applyFilter"
            >
                <option :value="null">Todos los edificios</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Identificador</th>
                        <th>Edificio</th>
                        <th>Tipo</th>
                        <th>Capacidad</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="classroom in classrooms" :key="classroom.id">
                        <td style="font-weight:500;">{{ classroom.identifier }}</td>
                        <td style="color:var(--text-secondary);">{{ classroom.building.name }}</td>
                        <td>
                            <Badge :variant="classroom.type === 'theory' ? 'neutral' : 'info'">
                                {{ typeLabel[classroom.type] }}
                            </Badge>
                        </td>
                        <td style="color:var(--text-secondary);">{{ classroom.capacity }}</td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar ${classroom.identifier}`"
                                    @click="openEdit(classroom)"
                                />
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${classroom.identifier}`"
                                    @click="openDelete(classroom)"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!classrooms.length">
                        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay aulas registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateClassroomModal
        v-model:open="showCreateModal"
        :buildings="buildings"
    />

    <EditClassroomModal
        v-if="selectedClassroom && showEditModal"
        :open="showEditModal"
        :classroom="selectedClassroom"
        :buildings="buildings"
        @update:open="v => { if (!v) { showEditModal = false; selectedClassroom = null } }"
    />

    <DeleteClassroomModal
        v-if="selectedClassroom && showDeleteModal"
        :open="showDeleteModal"
        :classroom="selectedClassroom"
        @update:open="v => { if (!v) { showDeleteModal = false; selectedClassroom = null } }"
    />
</template>
```

- [ ] **Step 8: Commit**

```bash
git add resources/js/composables/permissions/useClassroomPermissions.ts resources/js/composables/forms/useClassroomForm.ts resources/js/composables/filters/useClassroomFilters.ts resources/js/components/infrastructure/CreateClassroomModal.vue resources/js/components/infrastructure/EditClassroomModal.vue resources/js/components/infrastructure/DeleteClassroomModal.vue resources/js/pages/infrastructure/Classrooms/
git commit -m "feat: add Classrooms frontend (page, modals, composables, filters)"
```

---

## Task 9: Navegación (Sidebar)

**Files:**
- Modify: `resources/js/components/AppSidebar.vue`

- [ ] **Step 1: Agregar imports de Wayfinder en AppSidebar.vue**

Añadir junto a los otros imports de rutas:

```typescript
import { index as buildingsIndex } from '@/routes/infrastructure/buildings'
import { index as classroomsIndex } from '@/routes/infrastructure/classrooms'
```

- [ ] **Step 2: Agregar grupo Infraestructura en navGroups**

Añadir el bloque después del grupo "Académico" y antes del grupo "Mi cuenta":

```typescript
if (
    page.props.auth?.permissions?.includes('buildings.view') ||
    page.props.auth?.permissions?.includes('classrooms.view') ||
    page.props.auth?.roles?.includes('Admin')
) {
    const infraItems: { icon: string; label: string; href: string }[] = []

    if (
        page.props.auth?.permissions?.includes('buildings.view') ||
        page.props.auth?.roles?.includes('Admin')
    ) {
        infraItems.push({ icon: 'building', label: 'Edificios', href: buildingsIndex.url() })
    }

    if (
        page.props.auth?.permissions?.includes('classrooms.view') ||
        page.props.auth?.roles?.includes('Admin')
    ) {
        infraItems.push({ icon: 'grid', label: 'Aulas', href: classroomsIndex.url() })
    }

    if (infraItems.length) {
        groups.push({ label: 'Infraestructura', items: infraItems })
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/AppSidebar.vue
git commit -m "feat: add Infraestructura group to sidebar navigation"
```

---

## Task 10: Feature Tests

**Files:**
- Create: `tests/Feature/Infrastructure/BuildingControllerTest.php`
- Create: `tests/Feature/Infrastructure/ClassroomControllerTest.php`

- [ ] **Step 1: Crear BuildingControllerTest**

```bash
vendor/bin/sail artisan make:test --pest Infrastructure/BuildingControllerTest
```

Reemplazar `tests/Feature/Infrastructure/BuildingControllerTest.php`:

```php
<?php

use App\Models\Building;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'buildings.view',   'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'buildings.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'buildings.update', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'buildings.delete', 'guard_name' => 'web']);
});

function buildingUserWith(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// index

test('unauthenticated user is redirected to login on buildings index', function () {
    $this->get(route('infrastructure.buildings.index'))
        ->assertRedirect('/login');
});

test('user without buildings.view gets 403 on buildings index', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('infrastructure.buildings.index'))
        ->assertForbidden();
});

test('user with buildings.view sees buildings index with all buildings', function () {
    Building::factory()->count(3)->create();

    $this->actingAs(buildingUserWith('buildings.view'))
        ->get(route('infrastructure.buildings.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('infrastructure/Buildings/Index', false)
                ->has('buildings', 3)
                ->has('can')
        );
});

// store

test('user without buildings.create gets 403 on store', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('infrastructure.buildings.store'), ['name' => 'Edificio A'])
        ->assertForbidden();
});

test('user with buildings.create can create a building', function () {
    $this->actingAs(buildingUserWith('buildings.create'))
        ->post(route('infrastructure.buildings.store'), ['name' => 'Edificio A'])
        ->assertRedirect(route('infrastructure.buildings.index'));

    $this->assertDatabaseHas('buildings', ['name' => 'Edificio A']);
});

test('building name must be unique on store', function () {
    Building::factory()->create(['name' => 'Edificio A']);

    $this->actingAs(buildingUserWith('buildings.create'))
        ->post(route('infrastructure.buildings.store'), ['name' => 'Edificio A'])
        ->assertSessionHasErrors('name');
});

test('building name is required on store', function () {
    $this->actingAs(buildingUserWith('buildings.create'))
        ->post(route('infrastructure.buildings.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

// update

test('user without buildings.update gets 403 on update', function () {
    $building = Building::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('infrastructure.buildings.update', $building), ['name' => 'Nuevo'])
        ->assertForbidden();
});

test('user with buildings.update can rename a building', function () {
    $building = Building::factory()->create(['name' => 'Antiguo']);

    $this->actingAs(buildingUserWith('buildings.update'))
        ->patch(route('infrastructure.buildings.update', $building), ['name' => 'Nuevo'])
        ->assertRedirect(route('infrastructure.buildings.index'));

    $this->assertDatabaseHas('buildings', ['id' => $building->id, 'name' => 'Nuevo']);
});

test('update allows keeping the same name', function () {
    $building = Building::factory()->create(['name' => 'Edificio A']);

    $this->actingAs(buildingUserWith('buildings.update'))
        ->patch(route('infrastructure.buildings.update', $building), ['name' => 'Edificio A'])
        ->assertRedirect(route('infrastructure.buildings.index'));
});

// destroy

test('user without buildings.delete gets 403 on destroy', function () {
    $building = Building::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('infrastructure.buildings.destroy', $building))
        ->assertForbidden();
});

test('user with buildings.delete can delete a building that has no classrooms', function () {
    $building = Building::factory()->create();

    $this->actingAs(buildingUserWith('buildings.delete'))
        ->delete(route('infrastructure.buildings.destroy', $building))
        ->assertRedirect(route('infrastructure.buildings.index'));

    $this->assertDatabaseMissing('buildings', ['id' => $building->id]);
});

test('building with classrooms cannot be deleted', function () {
    $building = Building::factory()->create();
    Classroom::factory()->create(['building_id' => $building->id]);

    $this->actingAs(buildingUserWith('buildings.delete'))
        ->delete(route('infrastructure.buildings.destroy', $building))
        ->assertRedirect(route('infrastructure.buildings.index'));

    $this->assertDatabaseHas('buildings', ['id' => $building->id]);
});
```

- [ ] **Step 2: Ejecutar BuildingControllerTest**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Infrastructure/BuildingControllerTest.php
```

Expected: todos los tests en verde.

- [ ] **Step 3: Crear ClassroomControllerTest**

```bash
vendor/bin/sail artisan make:test --pest Infrastructure/ClassroomControllerTest
```

Reemplazar `tests/Feature/Infrastructure/ClassroomControllerTest.php`:

```php
<?php

use App\Models\Building;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'classrooms.view',   'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'classrooms.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'classrooms.update', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'classrooms.delete', 'guard_name' => 'web']);

    $this->building = Building::factory()->create();
});

function classroomUserWith(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// index

test('unauthenticated user is redirected to login on classrooms index', function () {
    $this->get(route('infrastructure.classrooms.index'))
        ->assertRedirect('/login');
});

test('user without classrooms.view gets 403 on classrooms index', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('infrastructure.classrooms.index'))
        ->assertForbidden();
});

test('user with classrooms.view sees classrooms index', function () {
    Classroom::factory()->count(2)->create(['building_id' => $this->building->id]);

    $this->actingAs(classroomUserWith('classrooms.view'))
        ->get(route('infrastructure.classrooms.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('infrastructure/Classrooms/Index', false)
                ->has('classrooms', 2)
                ->has('buildings')
                ->has('filters')
                ->has('can')
        );
});

test('classrooms index filters by building_id', function () {
    $other = Building::factory()->create();
    Classroom::factory()->count(2)->create(['building_id' => $this->building->id]);
    Classroom::factory()->create(['building_id' => $other->id]);

    $this->actingAs(classroomUserWith('classrooms.view'))
        ->get(route('infrastructure.classrooms.index', ['building_id' => $this->building->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('classrooms', 2));
});

// store

test('user without classrooms.create gets 403 on store', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('infrastructure.classrooms.store'), [
            'building_id' => $this->building->id,
            'identifier'  => '301',
            'type'        => 'theory',
            'capacity'    => 40,
        ])
        ->assertForbidden();
});

test('user with classrooms.create can create a classroom', function () {
    $this->actingAs(classroomUserWith('classrooms.create'))
        ->post(route('infrastructure.classrooms.store'), [
            'building_id' => $this->building->id,
            'identifier'  => '301',
            'type'        => 'theory',
            'capacity'    => 40,
        ])
        ->assertRedirect(route('infrastructure.classrooms.index'));

    $this->assertDatabaseHas('classrooms', [
        'building_id' => $this->building->id,
        'identifier'  => '301',
        'type'        => 'theory',
        'capacity'    => 40,
    ]);
});

test('identifier must be unique within the same building on store', function () {
    Classroom::factory()->create(['building_id' => $this->building->id, 'identifier' => '301']);

    $this->actingAs(classroomUserWith('classrooms.create'))
        ->post(route('infrastructure.classrooms.store'), [
            'building_id' => $this->building->id,
            'identifier'  => '301',
            'type'        => 'theory',
            'capacity'    => 40,
        ])
        ->assertSessionHasErrors('identifier');
});

test('same identifier in different buildings is allowed', function () {
    $other = Building::factory()->create();
    Classroom::factory()->create(['building_id' => $other->id, 'identifier' => '301']);

    $this->actingAs(classroomUserWith('classrooms.create'))
        ->post(route('infrastructure.classrooms.store'), [
            'building_id' => $this->building->id,
            'identifier'  => '301',
            'type'        => 'theory',
            'capacity'    => 40,
        ])
        ->assertRedirect(route('infrastructure.classrooms.index'));

    $this->assertDatabaseCount('classrooms', 2);
});

test('type must be theory or laboratory', function () {
    $this->actingAs(classroomUserWith('classrooms.create'))
        ->post(route('infrastructure.classrooms.store'), [
            'building_id' => $this->building->id,
            'identifier'  => '301',
            'type'        => 'invalid',
            'capacity'    => 40,
        ])
        ->assertSessionHasErrors('type');
});

// update

test('user without classrooms.update gets 403 on update', function () {
    $classroom = Classroom::factory()->create(['building_id' => $this->building->id]);

    $this->actingAs(User::factory()->create())
        ->patch(route('infrastructure.classrooms.update', $classroom), [
            'building_id' => $this->building->id,
            'identifier'  => 'nuevo',
            'type'        => 'laboratory',
            'capacity'    => 20,
        ])
        ->assertForbidden();
});

test('user with classrooms.update can update a classroom', function () {
    $classroom = Classroom::factory()->create([
        'building_id' => $this->building->id,
        'identifier'  => 'viejo',
        'type'        => 'theory',
    ]);

    $this->actingAs(classroomUserWith('classrooms.update'))
        ->patch(route('infrastructure.classrooms.update', $classroom), [
            'building_id' => $this->building->id,
            'identifier'  => 'nuevo',
            'type'        => 'laboratory',
            'capacity'    => 20,
        ])
        ->assertRedirect(route('infrastructure.classrooms.index'));

    $this->assertDatabaseHas('classrooms', [
        'id'         => $classroom->id,
        'identifier' => 'nuevo',
        'type'       => 'laboratory',
    ]);
});

test('update allows keeping the same identifier in the same building', function () {
    $classroom = Classroom::factory()->create([
        'building_id' => $this->building->id,
        'identifier'  => '301',
    ]);

    $this->actingAs(classroomUserWith('classrooms.update'))
        ->patch(route('infrastructure.classrooms.update', $classroom), [
            'building_id' => $this->building->id,
            'identifier'  => '301',
            'type'        => $classroom->type->value,
            'capacity'    => $classroom->capacity,
        ])
        ->assertRedirect(route('infrastructure.classrooms.index'));
});

// destroy

test('user without classrooms.delete gets 403 on destroy', function () {
    $classroom = Classroom::factory()->create(['building_id' => $this->building->id]);

    $this->actingAs(User::factory()->create())
        ->delete(route('infrastructure.classrooms.destroy', $classroom))
        ->assertForbidden();
});

test('user with classrooms.delete can delete a classroom', function () {
    $classroom = Classroom::factory()->create(['building_id' => $this->building->id]);

    $this->actingAs(classroomUserWith('classrooms.delete'))
        ->delete(route('infrastructure.classrooms.destroy', $classroom))
        ->assertRedirect(route('infrastructure.classrooms.index'));

    $this->assertDatabaseMissing('classrooms', ['id' => $classroom->id]);
});
```

- [ ] **Step 4: Ejecutar ClassroomControllerTest**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Infrastructure/ClassroomControllerTest.php
```

Expected: todos los tests en verde.

- [ ] **Step 5: Ejecutar toda la suite de tests**

```bash
vendor/bin/sail artisan test --compact
```

Expected: sin regresiones.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Infrastructure/
git commit -m "test: add BuildingControllerTest and ClassroomControllerTest"
```
