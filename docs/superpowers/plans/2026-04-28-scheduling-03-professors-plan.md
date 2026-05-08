# Profesores — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar el CRUD completo del modelo `Professor` — perfil académico mínimo de un usuario con rol Profesor — con página propia en `/scheduling/professors` y modales de creación, edición y eliminación.

**Architecture:** `Professor` tiene FK única a `users`. El controller pasa `availableUsers` (usuarios con rol "Profesor" sin perfil aún) al frontend para el modal de creación. Backend sigue `FormRequest → Controller → Wrapper → Action → Resource`. Frontend sigue `FormComposable → Page → PermissionComposable → Type`.

**Tech Stack:** PHP 8.3 · Laravel 13 · Spatie Permission · Pest 4 · Vue 3 · Inertia.js v3 · TypeScript · Tailwind CSS v4 · Wayfinder v0

---

## File Map

**Create:**
- `database/migrations/XXXX_create_professors_table.php`
- `app/Models/Professor.php`
- `database/factories/ProfessorFactory.php`
- `app/Policies/ProfessorPolicy.php`
- `app/Http/Requests/Scheduling/StoreProfessorRequest.php`
- `app/Http/Requests/Scheduling/UpdateProfessorRequest.php`
- `app/Http/Wrappers/Scheduling/ProfessorWrapper.php`
- `app/Http/Resources/Scheduling/ProfessorResource.php`
- `app/Actions/Scheduling/CreateProfessorAction.php`
- `app/Actions/Scheduling/UpdateProfessorAction.php`
- `app/Actions/Scheduling/DeleteProfessorAction.php`
- `app/Http/Controllers/Scheduling/ProfessorController.php`
- `tests/Feature/Scheduling/ProfessorControllerTest.php`
- `resources/js/composables/forms/useProfessorForm.ts`
- `resources/js/composables/permissions/useProfessorPermissions.ts`
- `resources/js/components/scheduling/CreateProfessorModal.vue`
- `resources/js/components/scheduling/EditProfessorModal.vue`
- `resources/js/components/scheduling/DeleteProfessorModal.vue`
- `resources/js/pages/scheduling/Professors/Index.vue`

**Modify:**
- `database/data/permissions.yaml` — agregar `professors.*`
- `database/data/roles.yaml` — asignar `professors.*` al rol Admin
- `routes/web.php` — agregar rutas `/scheduling/professors`
- `resources/js/types/scheduling.ts` — agregar `Professor`, `AvailableUser`, `ProfessorCollection`
- `resources/js/components/AppSidebar.vue` — agregar ítem Profesores

---

### Task 1: Migration, Model, Factory

**Files:**
- Create: `database/migrations/XXXX_create_professors_table.php`
- Create: `app/Models/Professor.php`
- Create: `database/factories/ProfessorFactory.php`

- [ ] **Step 1: Generate migration**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan make:migration create_professors_table --no-interaction"
```

- [ ] **Step 2: Fill migration**

Edit the generated file in `database/migrations/XXXX_create_professors_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('weekly_hour_limit')->default(20);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professors');
    }
};
```

- [ ] **Step 3: Run migration**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan migrate --no-interaction"
```

Expected: `professors` table created.

- [ ] **Step 4: Create model**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan make:model Professor --no-interaction"
```

Edit `app/Models/Professor.php`:

```php
<?php

namespace App\Models;

use Database\Factories\ProfessorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'weekly_hour_limit', 'active'])]
class Professor extends Model
{
    /** @use HasFactory<ProfessorFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 5: Create factory**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan make:factory ProfessorFactory --model=Professor --no-interaction"
```

Edit `database/factories/ProfessorFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Professor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<Professor>
 */
class ProfessorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'           => User::factory(),
            'weekly_hour_limit' => fake()->numberBetween(10, 40),
            'active'            => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Professor $professor) {
            $role = Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);
            $professor->user->assignRole($role);
        });
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
```

- [ ] **Step 6: Run pint**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && vendor/bin/pint --dirty --format agent"
```

- [ ] **Step 7: Commit**

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-professors && git add database/migrations app/Models/Professor.php database/factories/ProfessorFactory.php && git commit -m "feat: add Professor model, migration, and factory"
```

---

### Task 2: Policy + Permissions Data

**Files:**
- Create: `app/Policies/ProfessorPolicy.php`
- Modify: `database/data/permissions.yaml`
- Modify: `database/data/roles.yaml`

- [ ] **Step 1: Write failing test**

Create `tests/Feature/Scheduling/ProfessorControllerTest.php` with just authorization smoke tests:

```php
<?php

use App\Models\Professor;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);

    foreach (['professors.view', 'professors.create', 'professors.update', 'professors.delete'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWithProfessorPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

test('unauthenticated user cannot access professors', function () {
    $this->get('/scheduling/professors')->assertRedirect('/login');
});

test('user without permission cannot list professors', function () {
    $this->actingAs(User::factory()->create())
        ->get('/scheduling/professors')
        ->assertForbidden();
});
```

- [ ] **Step 2: Run failing test**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan test --compact --filter=ProfessorControllerTest 2>&1"
```

Expected: FAIL (route not found).

- [ ] **Step 3: Create policy**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan make:policy ProfessorPolicy --model=Professor --no-interaction"
```

Edit `app/Policies/ProfessorPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Professor;
use App\Models\User;

class ProfessorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('professors.view');
    }

    public function create(User $user): bool
    {
        return $user->can('professors.create');
    }

    public function update(User $user, Professor $professor): bool
    {
        return $user->can('professors.update');
    }

    public function delete(User $user, Professor $professor): bool
    {
        return $user->can('professors.delete');
    }
}
```

- [ ] **Step 4: Update permissions.yaml**

Add at the end of the `permissions:` list in `database/data/permissions.yaml`:

```yaml
  - name: professors.view
    guard: web
  - name: professors.create
    guard: web
  - name: professors.update
    guard: web
  - name: professors.delete
    guard: web
```

- [ ] **Step 5: Update roles.yaml**

Add to the Admin role's `permissions:` list in `database/data/roles.yaml`:

```yaml
      - professors.view
      - professors.create
      - professors.update
      - professors.delete
```

- [ ] **Step 6: Run pint**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && vendor/bin/pint --dirty --format agent"
```

- [ ] **Step 7: Commit**

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-professors && git add app/Policies/ProfessorPolicy.php database/data/permissions.yaml database/data/roles.yaml tests/Feature/Scheduling/ProfessorControllerTest.php && git commit -m "feat: add ProfessorPolicy and professors.* permissions"
```

---

### Task 3: FormRequests, Wrapper, Resource, Actions

**Files:**
- Create: `app/Http/Requests/Scheduling/StoreProfessorRequest.php`
- Create: `app/Http/Requests/Scheduling/UpdateProfessorRequest.php`
- Create: `app/Http/Wrappers/Scheduling/ProfessorWrapper.php`
- Create: `app/Http/Resources/Scheduling/ProfessorResource.php`
- Create: `app/Actions/Scheduling/CreateProfessorAction.php`
- Create: `app/Actions/Scheduling/UpdateProfessorAction.php`
- Create: `app/Actions/Scheduling/DeleteProfessorAction.php`

- [ ] **Step 1: Create StoreProfessorRequest**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan make:request Scheduling/StoreProfessorRequest --no-interaction"
```

Edit `app/Http/Requests/Scheduling/StoreProfessorRequest.php`:

```php
<?php

namespace App\Http\Requests\Scheduling;

use App\Models\Professor;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Professor::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id'           => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('professors', 'user_id'),
            ],
            'weekly_hour_limit' => ['required', 'integer', 'min:1', 'max:60'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v) {
            $userId = $this->input('user_id');

            if ($userId && ! User::find($userId)?->hasRole('Profesor')) {
                $v->errors()->add('user_id', 'El usuario seleccionado no tiene el rol Profesor.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'user_id.required'          => 'El usuario es obligatorio.',
            'user_id.exists'            => 'El usuario seleccionado no existe.',
            'user_id.unique'            => 'Este usuario ya tiene un perfil de profesor.',
            'weekly_hour_limit.required' => 'El límite de horas es obligatorio.',
            'weekly_hour_limit.min'      => 'El límite de horas debe ser al menos 1.',
            'weekly_hour_limit.max'      => 'El límite de horas no puede superar 60.',
        ];
    }
}
```

- [ ] **Step 2: Create UpdateProfessorRequest**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan make:request Scheduling/UpdateProfessorRequest --no-interaction"
```

Edit `app/Http/Requests/Scheduling/UpdateProfessorRequest.php`:

```php
<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('professor')) ?? false;
    }

    public function rules(): array
    {
        return [
            'weekly_hour_limit' => ['required', 'integer', 'min:1', 'max:60'],
            'active'            => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'weekly_hour_limit.required' => 'El límite de horas es obligatorio.',
            'weekly_hour_limit.min'      => 'El límite de horas debe ser al menos 1.',
            'weekly_hour_limit.max'      => 'El límite de horas no puede superar 60.',
            'active.required'            => 'El estado es obligatorio.',
            'active.boolean'             => 'El estado debe ser verdadero o falso.',
        ];
    }
}
```

- [ ] **Step 3: Create ProfessorWrapper**

Create `app/Http/Wrappers/Scheduling/ProfessorWrapper.php`:

```php
<?php

namespace App\Http\Wrappers\Scheduling;

use Illuminate\Support\Collection;

class ProfessorWrapper extends Collection
{
    public function getUserId(): int
    {
        return (int) $this->get('user_id');
    }

    public function getWeeklyHourLimit(): int
    {
        return (int) $this->get('weekly_hour_limit');
    }

    public function getActive(): bool
    {
        return (bool) $this->get('active');
    }
}
```

- [ ] **Step 4: Create ProfessorResource**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan make:resource Scheduling/ProfessorResource --no-interaction"
```

Edit `app/Http/Resources/Scheduling/ProfessorResource.php`:

```php
<?php

namespace App\Http\Resources\Scheduling;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfessorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'weeklyHourLimit'  => $this->weekly_hour_limit,
            'active'           => $this->active,
            'user'             => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ],
        ];
    }
}
```

- [ ] **Step 5: Create CreateProfessorAction**

Create `app/Actions/Scheduling/CreateProfessorAction.php`:

```php
<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\ProfessorWrapper;
use App\Models\Professor;

class CreateProfessorAction
{
    public function handle(ProfessorWrapper $wrapper): Professor
    {
        return Professor::create([
            'user_id'           => $wrapper->getUserId(),
            'weekly_hour_limit' => $wrapper->getWeeklyHourLimit(),
        ]);
    }
}
```

- [ ] **Step 6: Create UpdateProfessorAction**

Create `app/Actions/Scheduling/UpdateProfessorAction.php`:

```php
<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\ProfessorWrapper;
use App\Models\Professor;

class UpdateProfessorAction
{
    public function handle(Professor $professor, ProfessorWrapper $wrapper): Professor
    {
        $professor->update([
            'weekly_hour_limit' => $wrapper->getWeeklyHourLimit(),
            'active'            => $wrapper->getActive(),
        ]);

        return $professor;
    }
}
```

- [ ] **Step 7: Create DeleteProfessorAction**

Create `app/Actions/Scheduling/DeleteProfessorAction.php`:

```php
<?php

namespace App\Actions\Scheduling;

use App\Models\Professor;

class DeleteProfessorAction
{
    public function handle(Professor $professor): bool
    {
        return (bool) $professor->delete();
    }
}
```

- [ ] **Step 8: Run pint**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && vendor/bin/pint --dirty --format agent"
```

- [ ] **Step 9: Commit**

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-professors && git add app/Http/Requests/Scheduling/ app/Http/Wrappers/Scheduling/ProfessorWrapper.php app/Http/Resources/Scheduling/ProfessorResource.php app/Actions/Scheduling/ && git commit -m "feat: add Professor FormRequests, Wrapper, Resource, and Actions"
```

---

### Task 4: Controller, Routes, Tests

**Files:**
- Create: `app/Http/Controllers/Scheduling/ProfessorController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/Scheduling/ProfessorControllerTest.php`

- [ ] **Step 1: Add routes to routes/web.php**

Inside the existing `Route::middleware(['auth', 'verified'])->prefix('scheduling')->name('scheduling.')->group(...)` block, after the lapse routes, add:

```php
Route::get('professors', [ProfessorController::class, 'index'])->name('professors.index');
Route::post('professors', [ProfessorController::class, 'store'])->name('professors.store');
Route::patch('professors/{professor}', [ProfessorController::class, 'update'])->name('professors.update');
Route::delete('professors/{professor}', [ProfessorController::class, 'destroy'])->name('professors.destroy');
```

Also add the import at the top of the file:

```php
use App\Http\Controllers\Scheduling\ProfessorController;
```

- [ ] **Step 2: Create controller**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan make:controller Scheduling/ProfessorController --no-interaction"
```

Edit `app/Http/Controllers/Scheduling/ProfessorController.php`:

```php
<?php

namespace App\Http\Controllers\Scheduling;

use App\Actions\Scheduling\CreateProfessorAction;
use App\Actions\Scheduling\DeleteProfessorAction;
use App\Actions\Scheduling\UpdateProfessorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreProfessorRequest;
use App\Http\Requests\Scheduling\UpdateProfessorRequest;
use App\Http\Resources\Scheduling\ProfessorResource;
use App\Http\Wrappers\Scheduling\ProfessorWrapper;
use App\Models\Professor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class ProfessorController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Professor::class);

        $professors = Professor::with('user')->orderByDesc('id')->get();

        $existingUserIds = $professors->pluck('user_id');

        $profesorRole = Role::findByName('Profesor', 'web');
        $availableUsers = $profesorRole
            ? $profesorRole->users()->whereNotIn('users.id', $existingUserIds)->get(['users.id', 'users.name', 'users.email'])
            : collect();

        return Inertia::render('scheduling/Professors/Index', [
            'professors'     => ProfessorResource::collection($professors)->resolve(),
            'availableUsers' => $availableUsers->values(),
            'can'            => [
                'create' => $request->user()->can('professors.create'),
                'update' => $request->user()->can('professors.update'),
                'delete' => $request->user()->can('professors.delete'),
            ],
        ]);
    }

    public function store(StoreProfessorRequest $request, CreateProfessorAction $action): RedirectResponse
    {
        $action->handle(new ProfessorWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profesor creado.']);

        return to_route('scheduling.professors.index');
    }

    public function update(UpdateProfessorRequest $request, Professor $professor, UpdateProfessorAction $action): RedirectResponse
    {
        $action->handle($professor, new ProfessorWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profesor actualizado.']);

        return to_route('scheduling.professors.index');
    }

    public function destroy(Professor $professor, DeleteProfessorAction $action): RedirectResponse
    {
        Gate::authorize('delete', $professor);

        $action->handle($professor);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profesor eliminado.']);

        return to_route('scheduling.professors.index');
    }
}
```

- [ ] **Step 3: Write tests**

Replace `tests/Feature/Scheduling/ProfessorControllerTest.php` with full test coverage:

```php
<?php

use App\Models\Professor;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);

    foreach (['professors.view', 'professors.create', 'professors.update', 'professors.delete'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWithProfessorPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('admin can list professors', function () {
    Professor::factory()->count(2)->create();

    $this->actingAs(userWithProfessorPerm('professors.view'))
        ->get('/scheduling/professors')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('scheduling/Professors/Index', false)
            ->has('professors', 2)
            ->has('availableUsers')
        );
});

test('unauthenticated user cannot access professors', function () {
    $this->get('/scheduling/professors')->assertRedirect('/login');
});

test('user without permission cannot list professors', function () {
    $this->actingAs(User::factory()->create())
        ->get('/scheduling/professors')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('admin can create a professor', function () {
    $user = User::factory()->create();
    $user->assignRole('Profesor');

    $this->actingAs(userWithProfessorPerm('professors.create'))
        ->post('/scheduling/professors', [
            'user_id'           => $user->id,
            'weekly_hour_limit' => 20,
        ])
        ->assertRedirect(route('scheduling.professors.index'));

    expect(Professor::where('user_id', $user->id)->exists())->toBeTrue();
});

test('cannot create professor for user without Profesor role', function () {
    $user = User::factory()->create();

    $this->actingAs(userWithProfessorPerm('professors.create'))
        ->post('/scheduling/professors', [
            'user_id'           => $user->id,
            'weekly_hour_limit' => 20,
        ])
        ->assertSessionHasErrors('user_id');
});

test('cannot create duplicate professor for same user', function () {
    $professor = Professor::factory()->create();

    $this->actingAs(userWithProfessorPerm('professors.create'))
        ->post('/scheduling/professors', [
            'user_id'           => $professor->user_id,
            'weekly_hour_limit' => 20,
        ])
        ->assertSessionHasErrors('user_id');
});

test('weekly_hour_limit must be at least 1', function () {
    $user = User::factory()->create();
    $user->assignRole('Profesor');

    $this->actingAs(userWithProfessorPerm('professors.create'))
        ->post('/scheduling/professors', [
            'user_id'           => $user->id,
            'weekly_hour_limit' => 0,
        ])
        ->assertSessionHasErrors('weekly_hour_limit');
});

test('user without permission cannot create professor', function () {
    $user = User::factory()->create();
    $user->assignRole('Profesor');

    $this->actingAs(User::factory()->create())
        ->post('/scheduling/professors', [
            'user_id'           => $user->id,
            'weekly_hour_limit' => 20,
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('admin can update professor weekly hour limit and active status', function () {
    $professor = Professor::factory()->create(['weekly_hour_limit' => 20, 'active' => true]);

    $this->actingAs(userWithProfessorPerm('professors.update'))
        ->patch("/scheduling/professors/{$professor->id}", [
            'weekly_hour_limit' => 30,
            'active'            => false,
        ])
        ->assertRedirect(route('scheduling.professors.index'));

    expect($professor->fresh()->weekly_hour_limit)->toBe(30);
    expect($professor->fresh()->active)->toBeFalse();
});

test('user without permission cannot update professor', function () {
    $professor = Professor::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/scheduling/professors/{$professor->id}", [
            'weekly_hour_limit' => 30,
            'active'            => true,
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('admin can delete a professor', function () {
    $professor = Professor::factory()->create();

    $this->actingAs(userWithProfessorPerm('professors.delete'))
        ->delete("/scheduling/professors/{$professor->id}")
        ->assertRedirect(route('scheduling.professors.index'));

    expect(Professor::find($professor->id))->toBeNull();
});

test('user without permission cannot delete professor', function () {
    $professor = Professor::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete("/scheduling/professors/{$professor->id}")
        ->assertForbidden();
});
```

- [ ] **Step 4: Run tests**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan test --compact --filter=ProfessorControllerTest 2>&1"
```

Expected: all professor tests PASS.

- [ ] **Step 5: Run pint**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && vendor/bin/pint --dirty --format agent"
```

- [ ] **Step 6: Commit**

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-professors && git add app/Http/Controllers/Scheduling/ProfessorController.php routes/web.php tests/Feature/Scheduling/ProfessorControllerTest.php && git commit -m "feat: add ProfessorController, routes, and feature tests"
```

---

### Task 5: Frontend Types, Composables, Modals

**Files:**
- Modify: `resources/js/types/scheduling.ts`
- Create: `resources/js/composables/forms/useProfessorForm.ts`
- Create: `resources/js/composables/permissions/useProfessorPermissions.ts`
- Create: `resources/js/components/scheduling/CreateProfessorModal.vue`
- Create: `resources/js/components/scheduling/EditProfessorModal.vue`
- Create: `resources/js/components/scheduling/DeleteProfessorModal.vue`

- [ ] **Step 1: Generate Wayfinder routes**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan wayfinder:generate --no-interaction"
```

This creates `resources/js/routes/scheduling/professors.ts` with `index`, `store`, `update`, `destroy` exports.

- [ ] **Step 2: Update scheduling types**

Edit `resources/js/types/scheduling.ts` to add Professor types at the end:

```typescript
export type AvailableUser = {
    id: number
    name: string
    email: string
}

export type Professor = {
    id: number
    weeklyHourLimit: number
    active: boolean
    user: {
        id: number
        name: string
        email: string
    }
}

export type ProfessorCollection = Professor[]
```

- [ ] **Step 3: Create useProfessorForm.ts**

Create `resources/js/composables/forms/useProfessorForm.ts`:

```typescript
import { useForm } from '@inertiajs/vue3'
import { destroy, store, update } from '@/routes/scheduling/professors'
import type { Professor } from '@/types/scheduling'

export function useProfessorForm() {
    const storeOps = {
        form() {
            return {
                url:    store.url(),
                method: 'post' as const,
                data:   useForm({
                    user_id:           null as number | null,
                    weekly_hour_limit: 20,
                }),
            }
        },
    }

    const updateOps = {
        form({ professor }: { professor: Professor }) {
            return {
                url:    update.url({ professor }),
                method: 'patch' as const,
                data:   useForm({
                    weekly_hour_limit: professor.weeklyHourLimit,
                    active:            professor.active,
                }),
            }
        },
    }

    const removeOps = {
        submit({ professor }: { professor: Professor }): void {
            useForm({}).delete(destroy.url({ professor }))
        },
    }

    return { store: storeOps, update: updateOps, remove: removeOps }
}
```

- [ ] **Step 4: Create useProfessorPermissions.ts**

Create `resources/js/composables/permissions/useProfessorPermissions.ts`:

```typescript
import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useProfessorPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('professors.create'))
    const canUpdate = computed(() => can('professors.update'))
    const canDelete = computed(() => can('professors.delete'))

    return { canCreate, canUpdate, canDelete }
}
```

- [ ] **Step 5: Create CreateProfessorModal.vue**

Create `resources/js/components/scheduling/CreateProfessorModal.vue`:

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/scheduling/professors'
import type { AvailableUser } from '@/types/scheduling'

const props = defineProps<{ open: boolean; availableUsers: AvailableUser[] }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        user_id:           null as number | null,
        weekly_hour_limit: 20,
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
    <Modal :open="open" title="Nuevo profesor" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cp-user" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Usuario
                    </label>
                    <select id="cp-user" v-model="form.user_id" class="input" required>
                        <option :value="null" disabled>Selecciona un usuario</option>
                        <option v-for="u in availableUsers" :key="u.id" :value="u.id">
                            {{ u.name }} — {{ u.email }}
                        </option>
                    </select>
                    <InputError :message="form.errors.user_id" />
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="cp-hours" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Límite horas/semana
                    </label>
                    <input id="cp-hours" v-model.number="form.weekly_hour_limit" type="number" min="1" max="60" class="input" required />
                    <InputError :message="form.errors.weekly_hour_limit" />
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Crear profesor</Button>
            </div>
        </form>
    </Modal>
</template>
```

- [ ] **Step 6: Create EditProfessorModal.vue**

Create `resources/js/components/scheduling/EditProfessorModal.vue`:

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/scheduling/professors'
import type { Professor } from '@/types/scheduling'

const props = defineProps<{ professor: Professor; open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        weekly_hour_limit: props.professor.weeklyHourLimit,
        active:            props.professor.active,
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
    form.value.patch(update.url({ professor: props.professor }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Editar profesor" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-muted);">
                        Usuario
                    </label>
                    <p style="font-size:var(--text-sm);color:var(--text-primary);margin:0;">
                        {{ professor.user.name }}
                    </p>
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="ep-hours" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Límite horas/semana
                    </label>
                    <input id="ep-hours" v-model.number="form.weekly_hour_limit" type="number" min="1" max="60" class="input" required />
                    <InputError :message="form.errors.weekly_hour_limit" />
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <input id="ep-active" v-model="form.active" type="checkbox" class="checkbox" />
                    <label for="ep-active" style="font-size:var(--text-sm);color:var(--text-primary);">Activo</label>
                    <InputError :message="form.errors.active" />
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

- [ ] **Step 7: Create DeleteProfessorModal.vue**

Create `resources/js/components/scheduling/DeleteProfessorModal.vue`:

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/scheduling/professors'
import type { Professor } from '@/types/scheduling'

const props = defineProps<{ professor: Professor; open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.delete(destroy.url({ professor: props.professor }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Eliminar profesor" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 12px;">
                ¿Eliminar el perfil de profesor de <strong>{{ professor.user.name }}</strong>?
            </p>
            <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
                Esta acción no puede deshacerse. Si el profesor tiene secciones o horarios asignados, la eliminación fallará.
            </p>
            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="danger" :loading="form.processing">Eliminar</Button>
            </div>
        </form>
    </Modal>
</template>
```

- [ ] **Step 8: Commit**

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-professors && git add resources/js/types/scheduling.ts resources/js/composables/ resources/js/components/scheduling/ resources/js/routes/ && git commit -m "feat: add Professor frontend types, composables, and modals"
```

---

### Task 6: Index Page + Sidebar

**Files:**
- Create: `resources/js/pages/scheduling/Professors/Index.vue`
- Modify: `resources/js/components/AppSidebar.vue`

- [ ] **Step 1: Create Professors/Index.vue**

Create `resources/js/pages/scheduling/Professors/Index.vue`:

```vue
<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import AppBadge from '@/components/UI/AppBadge.vue'
import CreateProfessorModal from '@/components/scheduling/CreateProfessorModal.vue'
import DeleteProfessorModal from '@/components/scheduling/DeleteProfessorModal.vue'
import EditProfessorModal from '@/components/scheduling/EditProfessorModal.vue'
import { useProfessorForm } from '@/composables/forms/useProfessorForm'
import { useProfessorPermissions } from '@/composables/permissions/useProfessorPermissions'
import { index } from '@/routes/scheduling/professors'
import type { AvailableUser, Professor, ProfessorCollection } from '@/types/scheduling'

type Props = {
    professors: ProfessorCollection
    availableUsers: AvailableUser[]
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Horarios', href: '#' },
        { title: 'Profesores', href: index.url() },
    ],
})

const { canCreate, canUpdate, canDelete } = useProfessorPermissions()
const {} = useProfessorForm()

const showCreate = ref(false)
const editingProfessor = ref<Professor | null>(null)
const deletingProfessor = ref<Professor | null>(null)
</script>

<template>
    <Head title="Profesores" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Profesores
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Perfiles académicos de usuarios con rol Profesor
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nuevo profesor
            </Button>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Límite h/semana</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="professors.length === 0">
                        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px;">
                            No hay profesores registrados.
                        </td>
                    </tr>
                    <tr v-for="professor in professors" :key="professor.id">
                        <td style="font-weight:500;">{{ professor.user.name }}</td>
                        <td style="color:var(--text-secondary);">{{ professor.user.email }}</td>
                        <td>{{ professor.weeklyHourLimit }}h</td>
                        <td>
                            <AppBadge :variant="professor.active ? 'success' : 'neutral'">
                                {{ professor.active ? 'Activo' : 'Inactivo' }}
                            </AppBadge>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;justify-content:flex-end;gap:8px;">
                                <Button v-if="canUpdate" variant="ghost" size="sm" icon="pencil" @click="editingProfessor = professor" />
                                <Button v-if="canDelete" variant="ghost" size="sm" icon="trash" @click="deletingProfessor = professor" />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateProfessorModal
        :open="showCreate"
        :available-users="availableUsers"
        @update:open="showCreate = $event"
    />

    <EditProfessorModal
        v-if="editingProfessor"
        :professor="editingProfessor"
        :open="editingProfessor !== null"
        @update:open="editingProfessor = $event ? editingProfessor : null"
    />

    <DeleteProfessorModal
        v-if="deletingProfessor"
        :professor="deletingProfessor"
        :open="deletingProfessor !== null"
        @update:open="deletingProfessor = $event ? deletingProfessor : null"
    />
</template>
```

- [ ] **Step 2: Update AppSidebar.vue**

In `resources/js/components/AppSidebar.vue`, add:
1. Import at the top: `import { index as professorsIndex } from '@/routes/scheduling/professors'`
2. Add `{ icon: 'graduation-cap', label: 'Profesores', href: professorsIndex.url() }` to the Horarios group items, after the Períodos item.

The Horarios group currently looks like:
```typescript
{
    label: 'Horarios',
    items: [
        { icon: 'calendar', label: 'Períodos', href: periodsIndex.url() },
    ],
}
```

Change it to:
```typescript
{
    label: 'Horarios',
    items: [
        { icon: 'calendar', label: 'Períodos', href: periodsIndex.url() },
        { icon: 'graduation-cap', label: 'Profesores', href: professorsIndex.url() },
    ],
}
```

- [ ] **Step 3: Verify AppBadge exists or use inline badge**

Check if `AppBadge` component exists at `resources/js/components/UI/AppBadge.vue`. If it does not exist, replace `<AppBadge>` usage in Index.vue with an inline span:

```vue
<span
    :style="{
        display: 'inline-flex',
        alignItems: 'center',
        padding: '2px 8px',
        borderRadius: '9999px',
        fontSize: 'var(--text-xs)',
        fontWeight: '500',
        background: professor.active ? 'var(--color-success-light, #dcfce7)' : 'var(--color-neutral-light, #f3f4f6)',
        color: professor.active ? 'var(--color-success-text, #166534)' : 'var(--color-neutral-text, #374151)',
    }"
>
    {{ professor.active ? 'Activo' : 'Inactivo' }}
</span>
```

And remove the AppBadge import.

- [ ] **Step 4: Commit**

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-professors && git add resources/js/pages/scheduling/ resources/js/components/AppSidebar.vue && git commit -m "feat: add Professors index page and sidebar item"
```

---

### Task 7: Full Test Run + Final Verification

- [ ] **Step 1: Run all tests**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan test --compact 2>&1 | tail -10"
```

Expected: all tests pass (≥323 tests — 310 baseline + ~13 new professor tests).

- [ ] **Step 2: Run pint on all changed files**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && vendor/bin/pint --dirty --format agent"
```

- [ ] **Step 3: Commit pint fixes if any**

If pint made changes:

```bash
cd /var/www/cacao/.worktrees/feat/scheduling-professors && git add -p && git commit -m "style: pint formatting fixes"
```

- [ ] **Step 4: Final test run**

```bash
docker exec cacao-laravel.test-1 bash -c "cd /var/www/html/.worktrees/feat/scheduling-professors && php artisan test --compact 2>&1 | tail -5"
```

Expected: all tests passing, 0 failures.
