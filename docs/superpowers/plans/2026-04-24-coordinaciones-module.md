# Coordinaciones Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Coordinaciones module — CRUD for coordinations, coordinator assignment with history tracking, global Role enum (PHP + TS), and full permissions/CASL guards.

**Architecture:** Follows the existing `UserController` / `UserPolicy` / `permissions.yaml` pattern. Two new controllers (`CoordinationController`, `CoordinationAssignmentController`), two models (`Coordination`, `CoordinationAssignment`), one Policy, and a global `Role` enum. Vue index page with 5 modals, history loaded on-demand via `fetch()`.

**Tech Stack:** Laravel 13, Spatie Permission, Vue 3, Inertia v3, Tailwind v4 CACAO tokens, CASL, Wayfinder, Pest

**Spec:** `docs/superpowers/specs/2026-04-24-coordinaciones-design.md`

---

## File Map

**New files:**
- `app/Enums/Role.php`
- `resources/js/enums/Role.ts`
- `database/migrations/*_create_coordinations_table.php`
- `database/migrations/*_create_coordination_assignments_table.php`
- `app/Models/Coordination.php`
- `app/Models/CoordinationAssignment.php`
- `database/factories/CoordinationFactory.php`
- `database/factories/CoordinationAssignmentFactory.php`
- `app/Policies/CoordinationPolicy.php`
- `app/Http/Requests/Security/StoreCoordinationRequest.php`
- `app/Http/Requests/Security/UpdateCoordinationRequest.php`
- `app/Http/Requests/Security/StoreCoordinationAssignmentRequest.php`
- `app/Http/Controllers/Security/CoordinationController.php`
- `app/Http/Controllers/Security/CoordinationAssignmentController.php`
- `resources/js/pages/security/Coordinations/Index.vue`
- `resources/js/components/security/CreateCoordinationModal.vue`
- `resources/js/components/security/EditCoordinationModal.vue`
- `resources/js/components/security/AssignCoordinatorModal.vue`
- `resources/js/components/security/CoordinationHistoryModal.vue`
- `resources/js/components/security/DeleteCoordinationModal.vue`
- `tests/Feature/Security/CoordinationControllerTest.php`
- `tests/Feature/Security/CoordinationAssignmentControllerTest.php`
- `tests/Feature/Security/CoordinationPolicyTest.php`

**Modified files:**
- `database/data/permissions.yaml` — add 6 `coordinations.*` permissions
- `database/data/roles.yaml` — add `coordinations.*` to Admin
- `app/Providers/AppServiceProvider.php` — register `CoordinationPolicy`
- `routes/web.php` — add coordination routes
- `resources/js/types/security.ts` — add `CoordinationRow`, `CoordinationAssignment`, `CoordinationPaginator`
- `resources/js/casl/ability.ts` — add `'Coordination'` to `AppSubjects`
- `resources/js/components/AppSidebar.vue` — add Coordinaciones nav item

---

## Task 1: Global Role Enum (PHP + TypeScript)

**Files:**
- Create: `app/Enums/Role.php`
- Create: `resources/js/enums/Role.ts`

The enum values MUST match the Spatie role names in `database/data/roles.yaml` exactly (they are in Spanish). `Role::Coordinator` = `'Coordinador de Area'` — this is the name used by `$user->hasRole(Role::Coordinator->value)`.

- [ ] **Step 1: Create PHP Role enum**

```php
<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'Admin';
    case Professor = 'Profesor';
    case Student = 'Estudiante';
    case Coordinator = 'Coordinador de Area';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Professor => 'Profesor',
            self::Student => 'Estudiante',
            self::Coordinator => 'Coordinador de Área',
        };
    }

    /**
     * Get all role names as a flat array of strings.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

- [ ] **Step 2: Create TypeScript Role enum**

```ts
export const Role = {
    Admin: 'Admin',
    Professor: 'Profesor',
    Student: 'Estudiante',
    Coordinator: 'Coordinador de Area',
} as const;

export type RoleValue = typeof Role[keyof typeof Role];
```

Save to `resources/js/enums/Role.ts`.

- [ ] **Step 3: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 4: Commit**

```bash
git add app/Enums/Role.php resources/js/enums/Role.ts
git commit -m "feat: add global Role enum (PHP + TypeScript)"
```

---

## Task 2: Migrations + Models + Factories

**Files:**
- Create: migrations (2 files via Artisan)
- Create: `app/Models/Coordination.php`
- Create: `app/Models/CoordinationAssignment.php`
- Create: `database/factories/CoordinationFactory.php`
- Create: `database/factories/CoordinationAssignmentFactory.php`

- [ ] **Step 1: Create migrations via Artisan**

```bash
vendor/bin/sail artisan make:migration create_coordinations_table --no-interaction
vendor/bin/sail artisan make:migration create_coordination_assignments_table --no-interaction
```

- [ ] **Step 2: Write coordinations migration**

Replace the generated file content (find it in `database/migrations/` — it ends in `_create_coordinations_table.php`):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // career | grade | academic
            $table->string('education_level'); // university | secondary
            $table->string('secondary_type')->nullable(); // media_general | bachillerato (only when type=grade)
            // career_id references careers table (not yet created — FK added in Academic module migration)
            $table->unsignedBigInteger('career_id')->nullable()->index();
            $table->unsignedTinyInteger('grade_year')->nullable(); // 1-6, only when type=grade
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coordinations');
    }
};
```

- [ ] **Step 3: Write coordination_assignments migration**

Replace the generated file content (ends in `_create_coordination_assignments_table.php`):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordination_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coordination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['coordination_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coordination_assignments');
    }
};
```

- [ ] **Step 4: Run migrations**

```bash
vendor/bin/sail artisan migrate
```

Expected: two new tables created.

- [ ] **Step 5: Create Coordination model**

```bash
vendor/bin/sail artisan make:model Coordination --no-interaction
```

Replace generated content:

```php
<?php

namespace App\Models;

use Database\Factories\CoordinationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'type', 'education_level', 'secondary_type', 'career_id', 'grade_year', 'active'])]
class Coordination extends Model
{
    /** @use HasFactory<CoordinationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'grade_year' => 'integer',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CoordinationAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(CoordinationAssignment::class)->whereNull('ended_at');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    public function scopeByLevel(Builder $query, string $level): void
    {
        $query->where('education_level', $level);
    }
}
```

- [ ] **Step 6: Create CoordinationAssignment model**

```bash
vendor/bin/sail artisan make:model CoordinationAssignment --no-interaction
```

Replace generated content:

```php
<?php

namespace App\Models;

use Database\Factories\CoordinationAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['coordination_id', 'user_id', 'assigned_by', 'assigned_at', 'ended_at'])]
class CoordinationAssignment extends Model
{
    /** @use HasFactory<CoordinationAssignmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function coordination(): BelongsTo
    {
        return $this->belongsTo(Coordination::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
```

- [ ] **Step 7: Create CoordinationFactory**

```bash
vendor/bin/sail artisan make:factory CoordinationFactory --model=Coordination --no-interaction
```

Replace generated content:

```php
<?php

namespace Database\Factories;

use App\Models\Coordination;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coordination>
 */
class CoordinationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Coordinación de ' . fake()->words(2, true),
            'type' => 'career',
            'education_level' => 'university',
            'secondary_type' => null,
            'career_id' => null,
            'grade_year' => null,
            'active' => true,
        ];
    }

    public function career(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'career',
            'education_level' => 'university',
            'secondary_type' => null,
            'grade_year' => null,
        ]);
    }

    public function grade(string $secondaryType = 'media_general', int $year = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'grade',
            'education_level' => 'secondary',
            'secondary_type' => $secondaryType,
            'grade_year' => $year,
            'career_id' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
```

- [ ] **Step 8: Create CoordinationAssignmentFactory**

```bash
vendor/bin/sail artisan make:factory CoordinationAssignmentFactory --model=CoordinationAssignment --no-interaction
```

Replace generated content:

```php
<?php

namespace Database\Factories;

use App\Models\Coordination;
use App\Models\CoordinationAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoordinationAssignment>
 */
class CoordinationAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'coordination_id' => Coordination::factory(),
            'user_id' => User::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => now()->subDays(fake()->numberBetween(1, 365)),
            'ended_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
```

- [ ] **Step 9: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 10: Commit**

```bash
git add database/migrations app/Models/Coordination.php app/Models/CoordinationAssignment.php database/factories/CoordinationFactory.php database/factories/CoordinationAssignmentFactory.php
git commit -m "feat: add Coordination and CoordinationAssignment models with migrations and factories"
```

---

## Task 3: Permissions + Policy + AppServiceProvider

**Files:**
- Modify: `database/data/permissions.yaml`
- Modify: `database/data/roles.yaml`
- Create: `app/Policies/CoordinationPolicy.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `tests/Feature/Security/CoordinationPolicyTest.php`

- [ ] **Step 1: Add permissions to permissions.yaml**

Open `database/data/permissions.yaml`. After the last `users.*` permission, add:

```yaml
  - name: coordinations.view
    guard: web
  - name: coordinations.create
    guard: web
  - name: coordinations.edit
    guard: web
  - name: coordinations.delete
    guard: web
  - name: coordinations.assign
    guard: web
  - name: coordinations.view_history
    guard: web
```

- [ ] **Step 2: Add permissions to Admin role in roles.yaml**

Open `database/data/roles.yaml`. Find the `Admin` role's `permissions` array and append:

```yaml
      - coordinations.view
      - coordinations.create
      - coordinations.edit
      - coordinations.delete
      - coordinations.assign
      - coordinations.view_history
```

- [ ] **Step 3: Create CoordinationPolicy**

```bash
vendor/bin/sail artisan make:policy CoordinationPolicy --model=Coordination --no-interaction
```

Replace generated content:

```php
<?php

namespace App\Policies;

use App\Models\Coordination;
use App\Models\User;

class CoordinationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('coordinations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('coordinations.create');
    }

    public function update(User $user, Coordination $coordination): bool
    {
        return $user->can('coordinations.edit');
    }

    public function delete(User $user, Coordination $coordination): bool
    {
        return $user->can('coordinations.delete');
    }

    public function assign(User $user, Coordination $coordination): bool
    {
        return $user->can('coordinations.assign');
    }

    public function viewHistory(User $user, Coordination $coordination): bool
    {
        return $user->can('coordinations.view_history');
    }
}
```

- [ ] **Step 4: Register policy in AppServiceProvider**

Open `app/Providers/AppServiceProvider.php`. In `configureAuthorization()`, add after the last `Gate::policy` line:

```php
Gate::policy(Coordination::class, CoordinationPolicy::class);
```

Also add the import at the top:

```php
use App\Models\Coordination;
use App\Policies\CoordinationPolicy;
```

- [ ] **Step 5: Write the failing policy test**

```bash
vendor/bin/sail artisan make:test --pest Security/CoordinationPolicyTest --no-interaction
```

Replace generated content:

```php
<?php

use App\Models\Coordination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'coordinations.view', 'coordinations.create', 'coordinations.edit',
        'coordinations.delete', 'coordinations.assign', 'coordinations.view_history',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWith(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);
    return $user;
}

test('viewAny requires coordinations.view', function () {
    $noPermUser = User::factory()->create();
    $permUser = userWith('coordinations.view');
    $coordination = Coordination::factory()->create();

    expect($noPermUser->can('viewAny', Coordination::class))->toBeFalse();
    expect($permUser->can('viewAny', Coordination::class))->toBeTrue();
});

test('create requires coordinations.create', function () {
    $noPermUser = User::factory()->create();
    $permUser = userWith('coordinations.create');

    expect($noPermUser->can('create', Coordination::class))->toBeFalse();
    expect($permUser->can('create', Coordination::class))->toBeTrue();
});

test('update requires coordinations.edit', function () {
    $noPermUser = User::factory()->create();
    $permUser = userWith('coordinations.edit');
    $coordination = Coordination::factory()->create();

    expect($noPermUser->can('update', $coordination))->toBeFalse();
    expect($permUser->can('update', $coordination))->toBeTrue();
});

test('delete requires coordinations.delete', function () {
    $noPermUser = User::factory()->create();
    $permUser = userWith('coordinations.delete');
    $coordination = Coordination::factory()->create();

    expect($noPermUser->can('delete', $coordination))->toBeFalse();
    expect($permUser->can('delete', $coordination))->toBeTrue();
});

test('assign requires coordinations.assign', function () {
    $noPermUser = User::factory()->create();
    $permUser = userWith('coordinations.assign');
    $coordination = Coordination::factory()->create();

    expect($noPermUser->can('assign', $coordination))->toBeFalse();
    expect($permUser->can('assign', $coordination))->toBeTrue();
});

test('viewHistory requires coordinations.view_history', function () {
    $noPermUser = User::factory()->create();
    $permUser = userWith('coordinations.view_history');
    $coordination = Coordination::factory()->create();

    expect($noPermUser->can('viewHistory', $coordination))->toBeFalse();
    expect($permUser->can('viewHistory', $coordination))->toBeTrue();
});
```

- [ ] **Step 6: Run the failing test**

```bash
vendor/bin/sail artisan test --compact --filter=CoordinationPolicyTest
```

Expected: FAIL — policy class does not exist yet (or assertion fails if AppServiceProvider not updated yet).

- [ ] **Step 7: Run the test again after implementing policy**

If tests are already passing, that is the expected outcome — policy was created in Step 3 and registered in Step 4.

```bash
vendor/bin/sail artisan test --compact --filter=CoordinationPolicyTest
```

Expected: 6 tests pass.

- [ ] **Step 8: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add database/data/permissions.yaml database/data/roles.yaml app/Policies/CoordinationPolicy.php app/Providers/AppServiceProvider.php tests/Feature/Security/CoordinationPolicyTest.php
git commit -m "feat: add CoordinationPolicy and permissions for coordinations module"
```

---

## Task 4: CoordinationController + Form Requests + Routes

**Files:**
- Create: `app/Http/Requests/Security/StoreCoordinationRequest.php`
- Create: `app/Http/Requests/Security/UpdateCoordinationRequest.php`
- Create: `app/Http/Controllers/Security/CoordinationController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Security/CoordinationControllerTest.php`

- [ ] **Step 1: Create StoreCoordinationRequest**

```bash
vendor/bin/sail artisan make:request Security/StoreCoordinationRequest --no-interaction
```

Replace generated content:

```php
<?php

namespace App\Http\Requests\Security;

use App\Models\Coordination;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoordinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Coordination::class) ?? false;
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
        $type = $this->input('type');

        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['required', Rule::in(['career', 'grade', 'academic'])],
            'education_level' => ['required', Rule::in(['university', 'secondary'])],
            'secondary_type' => [
                Rule::requiredIf($type === 'grade'),
                'nullable',
                Rule::in(['media_general', 'bachillerato']),
            ],
            'career_id' => [
                Rule::requiredIf($type === 'career'),
                'nullable',
                'integer',
            ],
            'grade_year' => [
                Rule::requiredIf($type === 'grade'),
                'nullable',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) use ($educationLevel): void {
                    if ($value === null) {
                        return;
                    }
                    $secondaryType = $this->input('secondary_type');
                    $max = $secondaryType === 'bachillerato' ? 6 : 5;
                    if ($value > $max) {
                        $fail("El año escolar no puede ser mayor a {$max} para el tipo seleccionado.");
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => 'El tipo de coordinación no es válido.',
            'education_level.in' => 'El nivel educativo no es válido.',
            'secondary_type.in' => 'El tipo de educación media no es válido.',
            'grade_year.min' => 'El año escolar debe ser al menos 1.',
        ];
    }
}
```

- [ ] **Step 2: Create UpdateCoordinationRequest**

```bash
vendor/bin/sail artisan make:request Security/UpdateCoordinationRequest --no-interaction
```

Replace generated content:

```php
<?php

namespace App\Http\Requests\Security;

use App\Models\Coordination;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCoordinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $coordination = $this->route('coordination');

        return $this->user()?->can('update', $coordination) ?? false;
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
        $type = $this->input('type');

        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'type' => ['sometimes', 'required', Rule::in(['career', 'grade', 'academic'])],
            'education_level' => ['sometimes', 'required', Rule::in(['university', 'secondary'])],
            'secondary_type' => [
                Rule::requiredIf($type === 'grade'),
                'nullable',
                Rule::in(['media_general', 'bachillerato']),
            ],
            'career_id' => [
                Rule::requiredIf($type === 'career'),
                'nullable',
                'integer',
            ],
            'grade_year' => [
                Rule::requiredIf($type === 'grade'),
                'nullable',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null) {
                        return;
                    }
                    $secondaryType = $this->input('secondary_type');
                    $max = $secondaryType === 'bachillerato' ? 6 : 5;
                    if ($value > $max) {
                        $fail("El año escolar no puede ser mayor a {$max} para el tipo seleccionado.");
                    }
                },
            ],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
```

- [ ] **Step 3: Create CoordinationController**

```bash
vendor/bin/sail artisan make:controller Security/CoordinationController --no-interaction
```

Replace generated content:

```php
<?php

namespace App\Http\Controllers\Security;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreCoordinationRequest;
use App\Http\Requests\Security\UpdateCoordinationRequest;
use App\Models\Coordination;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CoordinationController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Coordination::class);

        $actor = $request->user();

        $query = Coordination::query()->with(['currentAssignment.user']);

        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($level = $request->input('education_level')) {
            $query->where('education_level', $level);
        }

        if ($status = $request->input('status')) {
            match ($status) {
                'active' => $query->where('active', true),
                'inactive' => $query->where('active', false),
                default => null,
            };
        }

        $perPage = min(100, max(10, (int) $request->input('per_page', 20)));

        $coordinations = $query
            ->orderBy('name')
            ->paginate($perPage)
            ->through(fn (Coordination $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->type,
                'education_level' => $c->education_level,
                'secondary_type' => $c->secondary_type,
                'career_id' => $c->career_id,
                'grade_year' => $c->grade_year,
                'active' => $c->active,
                'current_coordinator' => $c->currentAssignment?->user
                    ? ['id' => $c->currentAssignment->user->id, 'name' => $c->currentAssignment->user->name]
                    : null,
            ]);

        $coordinators = User::role(Role::Coordinator->value)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
            ->values();

        return Inertia::render('security/Coordinations/Index', [
            'coordinations' => $coordinations,
            'coordinators' => $coordinators,
            'careers' => [], // populated when Academic module is built
            'filters' => $request->only('search', 'type', 'education_level', 'status'),
            'can' => [
                'create' => $actor->can('create', Coordination::class),
                'update' => $actor->can('update', new Coordination),
                'delete' => $actor->can('delete', new Coordination),
                'assign' => $actor->can('assign', new Coordination),
                'viewHistory' => $actor->can('viewHistory', new Coordination),
            ],
        ]);
    }

    public function store(StoreCoordinationRequest $request): RedirectResponse
    {
        Coordination::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Coordinación creada.']);

        return to_route('security.coordinations.index');
    }

    public function update(UpdateCoordinationRequest $request, Coordination $coordination): RedirectResponse
    {
        $coordination->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Coordinación actualizada.']);

        return to_route('security.coordinations.index');
    }

    public function destroy(Request $request, Coordination $coordination): RedirectResponse
    {
        Gate::authorize('delete', $coordination);

        if ($coordination->currentAssignment()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: la coordinación tiene un coordinador activo.',
            ]);

            return to_route('security.coordinations.index');
        }

        $coordination->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Coordinación eliminada.']);

        return to_route('security.coordinations.index');
    }
}
```

- [ ] **Step 4: Add routes to web.php**

Open `routes/web.php`. Add imports at the top:

```php
use App\Http\Controllers\Security\CoordinationController;
use App\Http\Controllers\Security\CoordinationAssignmentController;
```

Inside the `security` route group, after the Invitations block, add:

```php
    // Coordinations
    Route::get('coordinations', [CoordinationController::class, 'index'])->name('coordinations.index');
    Route::post('coordinations', [CoordinationController::class, 'store'])->name('coordinations.store');
    Route::patch('coordinations/{coordination}', [CoordinationController::class, 'update'])->name('coordinations.update');
    Route::delete('coordinations/{coordination}', [CoordinationController::class, 'destroy'])->name('coordinations.destroy');
```

Note: The `CoordinationAssignmentController` routes will be added in Task 5.

- [ ] **Step 5: Generate Wayfinder types**

```bash
vendor/bin/sail artisan wayfinder:generate
```

Verify that `resources/js/routes/security/coordinations/index.ts` was created.

- [ ] **Step 6: Write the failing controller tests**

```bash
vendor/bin/sail artisan make:test --pest Security/CoordinationControllerTest --no-interaction
```

Replace generated content:

```php
<?php

use App\Models\Coordination;
use App\Models\CoordinationAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'coordinations.view', 'coordinations.create', 'coordinations.edit',
        'coordinations.delete', 'coordinations.assign', 'coordinations.view_history',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Coordinador de Area', 'guard_name' => 'web']);
});

function userWithCoordPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);
    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('unauthenticated redirects to login', function () {
    $this->get('/security/coordinations')->assertRedirect('/login');
});

test('user without coordinations.view gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get('/security/coordinations')
        ->assertForbidden();
});

test('user with coordinations.view sees index', function () {
    $this->actingAs(userWithCoordPerm('coordinations.view'))
        ->get('/security/coordinations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('security/Coordinations/Index', false)
            ->has('coordinations')
            ->has('coordinators')
        );
});

test('index includes current coordinator in row', function () {
    $actor = userWithCoordPerm('coordinations.view');
    $coordinator = User::factory()->create(['name' => 'Ana López']);
    $coordination = Coordination::factory()->create(['name' => 'Coord Test']);
    CoordinationAssignment::factory()->active()->create([
        'coordination_id' => $coordination->id,
        'user_id' => $coordinator->id,
        'assigned_by' => $actor->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($actor)
        ->get('/security/coordinations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('coordinations.data.0.current_coordinator.name', 'Ana López')
        );
});

test('index filters by search', function () {
    Coordination::factory()->create(['name' => 'Coordinación de Sistemas']);
    Coordination::factory()->create(['name' => 'Coordinación de Física']);

    $this->actingAs(userWithCoordPerm('coordinations.view'))
        ->get('/security/coordinations?search=Sistemas')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('coordinations.total', 1));
});

test('index filters by type', function () {
    Coordination::factory()->career()->create();
    Coordination::factory()->grade()->create();

    $this->actingAs(userWithCoordPerm('coordinations.view'))
        ->get('/security/coordinations?type=grade')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('coordinations.total', 1));
});

test('index filters by education level', function () {
    Coordination::factory()->career()->create(); // university
    Coordination::factory()->grade()->create(); // secondary

    $this->actingAs(userWithCoordPerm('coordinations.view'))
        ->get('/security/coordinations?education_level=secondary')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('coordinations.total', 1));
});

test('index filters by active status', function () {
    Coordination::factory()->create(['active' => true]);
    Coordination::factory()->inactive()->create();

    $this->actingAs(userWithCoordPerm('coordinations.view'))
        ->get('/security/coordinations?status=inactive')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('coordinations.total', 1));
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('user without coordinations.create gets 403 on store', function () {
    $this->actingAs(User::factory()->create())
        ->post('/security/coordinations', ['name' => 'X', 'type' => 'career', 'education_level' => 'university'])
        ->assertForbidden();
});

test('stores a career coordination', function () {
    $this->actingAs(userWithCoordPerm('coordinations.create'))
        ->post('/security/coordinations', [
            'name' => 'Coordinación de Ingeniería',
            'type' => 'career',
            'education_level' => 'university',
        ])
        ->assertRedirect('/security/coordinations');

    expect(Coordination::where('name', 'Coordinación de Ingeniería')->exists())->toBeTrue();
});

test('stores a grade coordination', function () {
    $this->actingAs(userWithCoordPerm('coordinations.create'))
        ->post('/security/coordinations', [
            'name' => '1er Año Media General',
            'type' => 'grade',
            'education_level' => 'secondary',
            'secondary_type' => 'media_general',
            'grade_year' => 1,
        ])
        ->assertRedirect('/security/coordinations');

    expect(Coordination::where('grade_year', 1)->exists())->toBeTrue();
});

test('rejects grade_year exceeding max for media_general', function () {
    $this->actingAs(userWithCoordPerm('coordinations.create'))
        ->post('/security/coordinations', [
            'name' => '6to Año',
            'type' => 'grade',
            'education_level' => 'secondary',
            'secondary_type' => 'media_general',
            'grade_year' => 6,
        ])
        ->assertSessionHasErrors('grade_year');
});

test('accepts grade_year 6 for bachillerato', function () {
    $this->actingAs(userWithCoordPerm('coordinations.create'))
        ->post('/security/coordinations', [
            'name' => '6to Año Bachillerato',
            'type' => 'grade',
            'education_level' => 'secondary',
            'secondary_type' => 'bachillerato',
            'grade_year' => 6,
        ])
        ->assertRedirect('/security/coordinations');
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('user without coordinations.edit gets 403 on update', function () {
    $coordination = Coordination::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/security/coordinations/{$coordination->id}", ['name' => 'X'])
        ->assertForbidden();
});

test('updates a coordination', function () {
    $coordination = Coordination::factory()->create(['name' => 'Original']);

    $this->actingAs(userWithCoordPerm('coordinations.edit'))
        ->patch("/security/coordinations/{$coordination->id}", [
            'name' => 'Actualizada',
            'type' => 'career',
            'education_level' => 'university',
        ])
        ->assertRedirect('/security/coordinations');

    expect($coordination->fresh()->name)->toBe('Actualizada');
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('user without coordinations.delete gets 403 on destroy', function () {
    $coordination = Coordination::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete("/security/coordinations/{$coordination->id}")
        ->assertForbidden();
});

test('deletes a coordination without active coordinator', function () {
    $coordination = Coordination::factory()->create();

    $this->actingAs(userWithCoordPerm('coordinations.delete'))
        ->delete("/security/coordinations/{$coordination->id}")
        ->assertRedirect('/security/coordinations');

    expect(Coordination::find($coordination->id))->toBeNull();
});

test('cannot delete coordination with active coordinator', function () {
    $actor = userWithCoordPerm('coordinations.delete');
    $coordination = Coordination::factory()->create();
    CoordinationAssignment::factory()->active()->create([
        'coordination_id' => $coordination->id,
        'assigned_by' => $actor->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($actor)
        ->delete("/security/coordinations/{$coordination->id}")
        ->assertRedirect('/security/coordinations');

    expect(Coordination::find($coordination->id))->not->toBeNull();
});
```

- [ ] **Step 7: Run the tests**

```bash
vendor/bin/sail artisan test --compact --filter=CoordinationControllerTest
```

Expected: All tests pass.

- [ ] **Step 8: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add app/Http/Requests/Security/StoreCoordinationRequest.php app/Http/Requests/Security/UpdateCoordinationRequest.php app/Http/Controllers/Security/CoordinationController.php routes/web.php resources/js/routes/security/coordinations/ resources/js/actions/App/Http/Controllers/Security/CoordinationController.ts tests/Feature/Security/CoordinationControllerTest.php
git commit -m "feat: add CoordinationController CRUD with routes, form requests, and tests"
```

---

## Task 5: CoordinationAssignmentController + Routes

**Files:**
- Create: `app/Http/Requests/Security/StoreCoordinationAssignmentRequest.php`
- Create: `app/Http/Controllers/Security/CoordinationAssignmentController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Security/CoordinationAssignmentControllerTest.php`

- [ ] **Step 1: Create StoreCoordinationAssignmentRequest**

```bash
vendor/bin/sail artisan make:request Security/StoreCoordinationAssignmentRequest --no-interaction
```

Replace generated content:

```php
<?php

namespace App\Http\Requests\Security;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCoordinationAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $coordination = $this->route('coordination');

        return $this->user()?->can('assign', $coordination) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $user = User::find($value);
                    if (! $user || ! $user->hasRole(Role::Coordinator->value)) {
                        $fail('El usuario seleccionado no tiene el rol de Coordinador de Área.');
                    }
                },
            ],
        ];
    }
}
```

- [ ] **Step 2: Create CoordinationAssignmentController**

```bash
vendor/bin/sail artisan make:controller Security/CoordinationAssignmentController --no-interaction
```

Replace generated content:

```php
<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreCoordinationAssignmentRequest;
use App\Models\Coordination;
use App\Models\CoordinationAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CoordinationAssignmentController extends Controller
{
    /**
     * Return the full assignment history for a coordination as JSON.
     * Used by the frontend history modal (fetched on demand with fetch()).
     */
    public function index(Coordination $coordination): JsonResponse
    {
        Gate::authorize('viewHistory', $coordination);

        $assignments = $coordination->assignments()
            ->with('user:id,name', 'assignedBy:id,name')
            ->orderByDesc('assigned_at')
            ->get()
            ->map(fn (CoordinationAssignment $a) => [
                'id' => $a->id,
                'user' => ['id' => $a->user->id, 'name' => $a->user->name],
                'assigned_by' => ['id' => $a->assignedBy->id, 'name' => $a->assignedBy->name],
                'assigned_at' => $a->assigned_at->toDateTimeString(),
                'ended_at' => $a->ended_at?->toDateTimeString(),
            ]);

        return response()->json($assignments);
    }

    /**
     * Assign a coordinator to the given coordination.
     * Closes any existing active assignment first.
     */
    public function store(StoreCoordinationAssignmentRequest $request, Coordination $coordination): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $coordination, $request): void {
            $coordination->assignments()
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);

            $coordination->assignments()->create([
                'user_id' => $data['user_id'],
                'assigned_by' => $request->user()->id,
                'assigned_at' => now(),
                'ended_at' => null,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Coordinador asignado.']);

        return to_route('security.coordinations.index');
    }
}
```

- [ ] **Step 3: Add assignment routes to web.php**

Inside the `security` route group, after the Coordinations block, add:

```php
    // Coordination Assignments
    Route::get('coordinations/{coordination}/assignments', [CoordinationAssignmentController::class, 'index'])->name('coordinations.assignments.index');
    Route::post('coordinations/{coordination}/assignments', [CoordinationAssignmentController::class, 'store'])->name('coordinations.assignments.store');
```

- [ ] **Step 4: Generate Wayfinder types**

```bash
vendor/bin/sail artisan wayfinder:generate
```

Verify that `resources/js/actions/App/Http/Controllers/Security/CoordinationAssignmentController.ts` was created (or updated).

- [ ] **Step 5: Write the failing assignment tests**

```bash
vendor/bin/sail artisan make:test --pest Security/CoordinationAssignmentControllerTest --no-interaction
```

Replace generated content:

```php
<?php

use App\Models\Coordination;
use App\Models\CoordinationAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'coordinations.view', 'coordinations.create', 'coordinations.edit',
        'coordinations.delete', 'coordinations.assign', 'coordinations.view_history',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Coordinador de Area', 'guard_name' => 'web']);
});

function actorWithAssignPerm(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('coordinations.assign');
    return $user;
}

function coordinatorUser(): User
{
    $user = User::factory()->create(['active' => true]);
    $user->assignRole('Coordinador de Area');
    return $user;
}

// ---------------------------------------------------------------------------
// store (assign coordinator)
// ---------------------------------------------------------------------------

test('unauthenticated cannot assign coordinator', function () {
    $coordination = Coordination::factory()->create();
    $coordinator = coordinatorUser();

    $this->post("/security/coordinations/{$coordination->id}/assignments", ['user_id' => $coordinator->id])
        ->assertRedirect('/login');
});

test('user without coordinations.assign gets 403', function () {
    $coordination = Coordination::factory()->create();
    $coordinator = coordinatorUser();

    $this->actingAs(User::factory()->create())
        ->post("/security/coordinations/{$coordination->id}/assignments", ['user_id' => $coordinator->id])
        ->assertForbidden();
});

test('assigns coordinator to coordination with no prior assignment', function () {
    $actor = actorWithAssignPerm();
    $coordination = Coordination::factory()->create();
    $coordinator = coordinatorUser();

    $this->actingAs($actor)
        ->post("/security/coordinations/{$coordination->id}/assignments", ['user_id' => $coordinator->id])
        ->assertRedirect('/security/coordinations');

    $assignment = CoordinationAssignment::where('coordination_id', $coordination->id)->first();
    expect($assignment)->not->toBeNull();
    expect($assignment->user_id)->toBe($coordinator->id);
    expect($assignment->ended_at)->toBeNull();
});

test('closes previous assignment when reassigning', function () {
    $actor = actorWithAssignPerm();
    $coordination = Coordination::factory()->create();
    $firstCoordinator = coordinatorUser();
    $secondCoordinator = coordinatorUser();

    // First assignment
    CoordinationAssignment::factory()->active()->create([
        'coordination_id' => $coordination->id,
        'user_id' => $firstCoordinator->id,
        'assigned_by' => $actor->id,
        'assigned_at' => now()->subDay(),
    ]);

    // Reassign
    $this->actingAs($actor)
        ->post("/security/coordinations/{$coordination->id}/assignments", ['user_id' => $secondCoordinator->id])
        ->assertRedirect('/security/coordinations');

    expect(
        CoordinationAssignment::where('coordination_id', $coordination->id)
            ->whereNull('ended_at')
            ->count()
    )->toBe(1);

    expect(
        CoordinationAssignment::where('coordination_id', $coordination->id)
            ->whereNotNull('ended_at')
            ->count()
    )->toBe(1);

    expect(
        CoordinationAssignment::where('coordination_id', $coordination->id)
            ->whereNull('ended_at')
            ->first()->user_id
    )->toBe($secondCoordinator->id);
});

test('rejects user without Coordinator role', function () {
    $actor = actorWithAssignPerm();
    $coordination = Coordination::factory()->create();
    $nonCoordinator = User::factory()->create(); // no coordinator role

    $this->actingAs($actor)
        ->post("/security/coordinations/{$coordination->id}/assignments", ['user_id' => $nonCoordinator->id])
        ->assertSessionHasErrors('user_id');
});

// ---------------------------------------------------------------------------
// index (history)
// ---------------------------------------------------------------------------

test('returns assignment history as JSON', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('coordinations.view_history');
    $coordination = Coordination::factory()->create();
    $coordinator = coordinatorUser();

    CoordinationAssignment::factory()->closed()->create([
        'coordination_id' => $coordination->id,
        'user_id' => $coordinator->id,
        'assigned_by' => $actor->id,
        'assigned_at' => now()->subMonth(),
    ]);
    CoordinationAssignment::factory()->active()->create([
        'coordination_id' => $coordination->id,
        'user_id' => $coordinator->id,
        'assigned_by' => $actor->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($actor)
        ->getJson("/security/coordinations/{$coordination->id}/assignments")
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonStructure([['id', 'user', 'assigned_by', 'assigned_at', 'ended_at']]);
});

test('user without coordinations.view_history gets 403 on history', function () {
    $coordination = Coordination::factory()->create();

    $this->actingAs(User::factory()->create())
        ->getJson("/security/coordinations/{$coordination->id}/assignments")
        ->assertForbidden();
});
```

- [ ] **Step 6: Run tests**

```bash
vendor/bin/sail artisan test --compact --filter=CoordinationAssignmentControllerTest
```

Expected: All tests pass.

- [ ] **Step 7: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add app/Http/Requests/Security/StoreCoordinationAssignmentRequest.php app/Http/Controllers/Security/CoordinationAssignmentController.php routes/web.php resources/js/actions/App/Http/Controllers/Security/CoordinationAssignmentController.ts tests/Feature/Security/CoordinationAssignmentControllerTest.php
git commit -m "feat: add CoordinationAssignmentController with assign and history endpoints"
```

---

## Task 6: Frontend Foundation — Types, CASL, Sidebar

**Files:**
- Modify: `resources/js/types/security.ts`
- Modify: `resources/js/casl/ability.ts`
- Modify: `resources/js/components/AppSidebar.vue`

- [ ] **Step 1: Add types to security.ts**

Open `resources/js/types/security.ts`. Add at the end:

```ts
export type CoordinationRow = {
    id: number;
    name: string;
    type: 'career' | 'grade' | 'academic';
    education_level: 'university' | 'secondary';
    secondary_type: 'media_general' | 'bachillerato' | null;
    career_id: number | null;
    grade_year: number | null;
    active: boolean;
    current_coordinator: { id: number; name: string } | null;
};

export type CoordinationAssignment = {
    id: number;
    user: { id: number; name: string };
    assigned_by: { id: number; name: string };
    assigned_at: string;
    ended_at: string | null;
};

export type CoordinationPaginator = {
    data: CoordinationRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};
```

- [ ] **Step 2: Add 'Coordination' to CASL AppSubjects**

Open `resources/js/casl/ability.ts`. Find `AppSubjects` and add `'Coordination'`:

```ts
export type AppSubjects =
    | 'all'
    | 'Academic'
    | 'Coordination'
    | 'People'
    | 'Enrollment'
    | 'Evaluation'
    | 'Infrastructure'
    | 'Resource'
    | 'User'
    | 'Settings'
    | string;
```

- [ ] **Step 3: Add Coordinaciones to AppSidebar**

Open `resources/js/components/AppSidebar.vue`.

Add the Wayfinder import at the top (after the existing security imports):

```ts
import { index as coordinationsIndex } from '@/routes/security/coordinations'
```

Inside the `navGroups` computed, in the security group block, find where `securityItems` is built. After the `users.view` block, add:

```ts
        if (
            page.props.auth?.permissions?.includes('coordinations.view') ||
            page.props.auth?.roles?.includes('Admin')
        ) {
            securityItems.push({ icon: 'building-2', label: 'Coordinaciones', href: coordinationsIndex.url() })
        }
```

Also update the outer guard to include `coordinations.view`:

```ts
    if (
        page.props.auth?.permissions?.includes('roles.view') ||
        page.props.auth?.permissions?.includes('users.view') ||
        page.props.auth?.permissions?.includes('coordinations.view') ||
        page.props.auth?.roles?.includes('Admin')
    ) {
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/types/security.ts resources/js/casl/ability.ts resources/js/components/AppSidebar.vue
git commit -m "feat: add Coordination types, CASL subject, and sidebar nav entry"
```

---

## Task 7: Frontend — Coordinations Index Page

**Files:**
- Create: `resources/js/pages/security/Coordinations/Index.vue`

- [ ] **Step 1: Create the directory and index page**

```bash
mkdir -p resources/js/pages/security/Coordinations
```

Create `resources/js/pages/security/Coordinations/Index.vue`:

```vue
<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Badge from '@/components/base/Badge.vue'
import Button from '@/components/base/Button.vue'
import Pagination from '@/components/base/Pagination.vue'
import AssignCoordinatorModal from '@/components/security/AssignCoordinatorModal.vue'
import CoordinationHistoryModal from '@/components/security/CoordinationHistoryModal.vue'
import CreateCoordinationModal from '@/components/security/CreateCoordinationModal.vue'
import DeleteCoordinationModal from '@/components/security/DeleteCoordinationModal.vue'
import EditCoordinationModal from '@/components/security/EditCoordinationModal.vue'
import { usePermission } from '@/composables/usePermission'
import { index } from '@/routes/security/coordinations'
import type { CoordinationPaginator, CoordinationRow } from '@/types/security'

type Props = {
    coordinations: CoordinationPaginator
    coordinators: { id: number; name: string }[]
    careers: { id: number; name: string }[]
    filters: { search?: string; type?: string; education_level?: string; status?: string }
    can: { create: boolean; update: boolean; delete: boolean; assign: boolean; viewHistory: boolean }
}

const props = defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Seguridad', href: '#' },
            { title: 'Coordinaciones', href: index.url() },
        ],
    },
})

const { can } = usePermission()

const search = ref(props.filters.search ?? '')
const typeFilter = ref(props.filters.type ?? '')
const levelFilter = ref(props.filters.education_level ?? '')
const statusFilter = ref(props.filters.status ?? '')

let debounceTimer: ReturnType<typeof setTimeout>

function applyFilters(): void {
    router.get(
        index.url(),
        {
            search: search.value || undefined,
            type: typeFilter.value || undefined,
            education_level: levelFilter.value || undefined,
            status: statusFilter.value || undefined,
            per_page: props.coordinations.per_page !== 20 ? props.coordinations.per_page : undefined,
        },
        { preserveState: true, replace: true },
    )
}

function onSearchInput(): void {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(applyFilters, 350)
}

const paginationFilters = computed(() => ({
    search: search.value || undefined,
    type: typeFilter.value || undefined,
    education_level: levelFilter.value || undefined,
    status: statusFilter.value || undefined,
}))

const showCreate = ref(false)
const editingCoordination = ref<CoordinationRow | null>(null)
const assigningCoordination = ref<CoordinationRow | null>(null)
const historyCoordination = ref<CoordinationRow | null>(null)
const deletingCoordination = ref<CoordinationRow | null>(null)

function typeLabel(type: string): string {
    const labels: Record<string, string> = { career: 'Carrera', grade: 'Año escolar', academic: 'Académica' }
    return labels[type] ?? type
}

function levelLabel(level: string): string {
    const labels: Record<string, string> = { university: 'Universitario', secondary: 'Media / Básica' }
    return labels[level] ?? level
}
</script>

<template>
    <Head title="Coordinaciones" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Coordinaciones
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Gestiona las coordinaciones académicas del sistema
                </p>
            </div>
            <Button v-if="props.can.create" variant="primary" icon="plus" @click="showCreate = true">
                Nueva coordinación
            </Button>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <input
                v-model="search"
                class="input"
                type="search"
                placeholder="Buscar coordinación..."
                style="flex:1;min-width:200px;"
                @input="onSearchInput"
            />
            <select v-model="typeFilter" class="select" @change="applyFilters">
                <option value="">Todos los tipos</option>
                <option value="career">Carrera</option>
                <option value="grade">Año escolar</option>
            </select>
            <select v-model="levelFilter" class="select" @change="applyFilters">
                <option value="">Todos los niveles</option>
                <option value="university">Universitario</option>
                <option value="secondary">Media / Básica</option>
            </select>
            <select v-model="statusFilter" class="select" @change="applyFilters">
                <option value="">Todos los estados</option>
                <option value="active">Activo</option>
                <option value="inactive">Inactivo</option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Nivel</th>
                        <th>Coordinador actual</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in props.coordinations.data" :key="c.id">
                        <td style="font-weight:500;">{{ c.name }}</td>
                        <td style="color:var(--text-secondary);">{{ typeLabel(c.type) }}</td>
                        <td style="color:var(--text-secondary);">{{ levelLabel(c.education_level) }}</td>
                        <td>
                            <span v-if="c.current_coordinator">{{ c.current_coordinator.name }}</span>
                            <Badge v-else variant="ghost">Sin asignar</Badge>
                        </td>
                        <td>
                            <Badge :variant="c.active ? 'success' : 'ghost'">
                                {{ c.active ? 'Activo' : 'Inactivo' }}
                            </Badge>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;justify-content:flex-end;gap:4px;">
                                <Button
                                    v-if="can('coordinations.edit')"
                                    variant="ghost"
                                    size="sm"
                                    icon="pencil"
                                    @click="editingCoordination = c"
                                />
                                <Button
                                    v-if="can('coordinations.assign')"
                                    variant="ghost"
                                    size="sm"
                                    icon="user-check"
                                    @click="assigningCoordination = c"
                                />
                                <Button
                                    v-if="can('coordinations.view_history')"
                                    variant="ghost"
                                    size="sm"
                                    icon="clock"
                                    @click="historyCoordination = c"
                                />
                                <Button
                                    v-if="can('coordinations.delete')"
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    @click="deletingCoordination = c"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!props.coordinations.data.length">
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px 0;">
                            No hay coordinaciones registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :paginator="props.coordinations" :filters="paginationFilters" />
    </div>

    <CreateCoordinationModal v-model:open="showCreate" :careers="props.careers" />

    <EditCoordinationModal
        v-if="editingCoordination"
        :open="!!editingCoordination"
        :coordination="editingCoordination"
        :careers="props.careers"
        @update:open="(v) => !v && (editingCoordination = null)"
    />

    <AssignCoordinatorModal
        v-if="assigningCoordination"
        :open="!!assigningCoordination"
        :coordination="assigningCoordination"
        :coordinators="props.coordinators"
        @update:open="(v) => !v && (assigningCoordination = null)"
    />

    <CoordinationHistoryModal
        v-if="historyCoordination"
        :open="!!historyCoordination"
        :coordination="historyCoordination"
        @update:open="(v) => !v && (historyCoordination = null)"
    />

    <DeleteCoordinationModal
        v-if="deletingCoordination"
        :open="!!deletingCoordination"
        :coordination="deletingCoordination"
        @update:open="(v) => !v && (deletingCoordination = null)"
    />
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/pages/security/Coordinations/Index.vue
git commit -m "feat: add Coordinations index page"
```

---

## Task 8: Frontend — Modals

**Files:**
- Create: `resources/js/components/security/CreateCoordinationModal.vue`
- Create: `resources/js/components/security/EditCoordinationModal.vue`
- Create: `resources/js/components/security/AssignCoordinatorModal.vue`
- Create: `resources/js/components/security/CoordinationHistoryModal.vue`
- Create: `resources/js/components/security/DeleteCoordinationModal.vue`

- [ ] **Step 1: Create CreateCoordinationModal.vue**

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { store } from '@/routes/security/coordinations'

defineProps<{
    open: boolean
    careers: { id: number; name: string }[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const formKey = ref(0)
const selectedType = ref('')
const selectedSecondaryType = ref('')

const gradeYearMax = computed(() => (selectedSecondaryType.value === 'bachillerato' ? 6 : 5))

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) {
        formKey.value++
        selectedType.value = ''
        selectedSecondaryType.value = ''
    }
}
</script>

<template>
    <Modal :open="open" title="Nueva coordinación" size="md" @update:open="close">
        <Form :key="formKey" v-bind="store.form()" v-slot="{ errors, processing }" @success="close(false)">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cc-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="cc-name" name="name" class="input" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cc-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo
                    </label>
                    <select
                        id="cc-type"
                        v-model="selectedType"
                        name="type"
                        class="select"
                        required
                    >
                        <option value="" disabled>Seleccionar tipo...</option>
                        <option value="career">Carrera</option>
                        <option value="grade">Año escolar</option>
                    </select>
                    <InputError :message="errors.type" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label
                        for="cc-level"
                        style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                    >
                        Nivel educativo
                    </label>
                    <select id="cc-level" name="education_level" class="select" required>
                        <option value="" disabled>Seleccionar nivel...</option>
                        <option value="university">Universitario</option>
                        <option value="secondary">Media / Básica</option>
                    </select>
                    <InputError :message="errors.education_level" />
                </div>

                <template v-if="selectedType === 'grade'">
                    <div style="display:grid;gap:6px;">
                        <label
                            for="cc-secondary-type"
                            style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                        >
                            Modalidad
                        </label>
                        <select
                            id="cc-secondary-type"
                            v-model="selectedSecondaryType"
                            name="secondary_type"
                            class="select"
                            required
                        >
                            <option value="" disabled>Seleccionar modalidad...</option>
                            <option value="media_general">Media General</option>
                            <option value="bachillerato">Bachillerato</option>
                        </select>
                        <InputError :message="errors.secondary_type" />
                    </div>

                    <div style="display:grid;gap:6px;">
                        <label
                            for="cc-grade-year"
                            style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                        >
                            Año (1–{{ gradeYearMax }})
                        </label>
                        <select id="cc-grade-year" name="grade_year" class="select" required>
                            <option value="" disabled>Seleccionar año...</option>
                            <option v-for="n in gradeYearMax" :key="n" :value="n">{{ n }}°</option>
                        </select>
                        <InputError :message="errors.grade_year" />
                    </div>
                </template>

                <template v-if="selectedType === 'career'">
                    <div style="display:grid;gap:6px;">
                        <label
                            for="cc-career"
                            style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                        >
                            Carrera
                        </label>
                        <select id="cc-career" name="career_id" class="select">
                            <option value="">Sin carrera asignada</option>
                            <option v-for="career in careers" :key="career.id" :value="career.id">
                                {{ career.name }}
                            </option>
                        </select>
                        <InputError :message="errors.career_id" />
                    </div>
                </template>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Crear coordinación</Button>
            </div>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 2: Create EditCoordinationModal.vue**

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { update } from '@/routes/security/coordinations'
import type { CoordinationRow } from '@/types/security'

const props = defineProps<{
    open: boolean
    coordination: CoordinationRow
    careers: { id: number; name: string }[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const formKey = ref(0)
const selectedType = ref(props.coordination.type)
const selectedSecondaryType = ref(props.coordination.secondary_type ?? '')

const gradeYearMax = computed(() => (selectedSecondaryType.value === 'bachillerato' ? 6 : 5))

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) formKey.value++
}
</script>

<template>
    <Modal :open="open" title="Editar coordinación" size="md" @update:open="close">
        <Form
            :key="formKey"
            v-bind="update.form(coordination)"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="ec-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="ec-name" name="name" class="input" :value="coordination.name" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ec-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo
                    </label>
                    <select
                        id="ec-type"
                        v-model="selectedType"
                        name="type"
                        class="select"
                        required
                    >
                        <option value="career">Carrera</option>
                        <option value="grade">Año escolar</option>
                    </select>
                    <InputError :message="errors.type" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label
                        for="ec-level"
                        style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                    >
                        Nivel educativo
                    </label>
                    <select id="ec-level" name="education_level" class="select" :value="coordination.education_level" required>
                        <option value="university">Universitario</option>
                        <option value="secondary">Media / Básica</option>
                    </select>
                    <InputError :message="errors.education_level" />
                </div>

                <template v-if="selectedType === 'grade'">
                    <div style="display:grid;gap:6px;">
                        <label
                            for="ec-secondary-type"
                            style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                        >
                            Modalidad
                        </label>
                        <select
                            id="ec-secondary-type"
                            v-model="selectedSecondaryType"
                            name="secondary_type"
                            class="select"
                        >
                            <option value="media_general">Media General</option>
                            <option value="bachillerato">Bachillerato</option>
                        </select>
                        <InputError :message="errors.secondary_type" />
                    </div>

                    <div style="display:grid;gap:6px;">
                        <label
                            for="ec-grade-year"
                            style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                        >
                            Año (1–{{ gradeYearMax }})
                        </label>
                        <select id="ec-grade-year" name="grade_year" class="select" :value="coordination.grade_year">
                            <option v-for="n in gradeYearMax" :key="n" :value="n">{{ n }}°</option>
                        </select>
                        <InputError :message="errors.grade_year" />
                    </div>
                </template>

                <template v-if="selectedType === 'career'">
                    <div style="display:grid;gap:6px;">
                        <label
                            for="ec-career"
                            style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                        >
                            Carrera
                        </label>
                        <select id="ec-career" name="career_id" class="select" :value="coordination.career_id">
                            <option :value="null">Sin carrera asignada</option>
                            <option v-for="career in careers" :key="career.id" :value="career.id">
                                {{ career.name }}
                            </option>
                        </select>
                        <InputError :message="errors.career_id" />
                    </div>
                </template>

                <div style="display:grid;gap:6px;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:var(--text-sm);cursor:pointer;">
                        <input
                            type="checkbox"
                            name="active"
                            :value="true"
                            :checked="coordination.active"
                            style="width:14px;height:14px;accent-color:var(--accent);"
                        />
                        <span style="font-weight:500;color:var(--text-primary);">Coordinación activa</span>
                    </label>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Guardar cambios</Button>
            </div>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 3: Create AssignCoordinatorModal.vue**

The action URL uses `CoordinationAssignmentController::store`. After Wayfinder generation, import from `@/actions/App/Http/Controllers/Security/CoordinationAssignmentController`.

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { store } from '@/actions/App/Http/Controllers/Security/CoordinationAssignmentController'
import type { CoordinationRow } from '@/types/security'

const props = defineProps<{
    open: boolean
    coordination: CoordinationRow
    coordinators: { id: number; name: string }[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const formKey = ref(0)

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) formKey.value++
}
</script>

<template>
    <Modal
        :open="open"
        title="Asignar coordinador"
        :description="`Coordinación: ${coordination.name}`"
        size="sm"
        @update:open="close"
    >
        <div
            v-if="coordination.current_coordinator"
            style="padding:12px;background:var(--surface-muted);border-radius:8px;margin-bottom:16px;font-size:var(--text-sm);"
        >
            <span style="color:var(--text-muted);">Coordinador actual:</span>
            <span style="font-weight:500;color:var(--text-primary);margin-left:6px;">
                {{ coordination.current_coordinator.name }}
            </span>
        </div>

        <Form
            :key="formKey"
            v-bind="store.form(coordination)"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label
                        for="ac-coordinator"
                        style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                    >
                        Nuevo coordinador
                    </label>
                    <select id="ac-coordinator" name="user_id" class="select" required>
                        <option value="" disabled>Seleccionar coordinador...</option>
                        <option
                            v-for="coordinator in coordinators"
                            :key="coordinator.id"
                            :value="coordinator.id"
                        >
                            {{ coordinator.name }}
                        </option>
                    </select>
                    <p
                        v-if="coordinators.length === 0"
                        style="font-size:var(--text-xs);color:var(--text-muted);"
                    >
                        No hay usuarios con rol de Coordinador de Área disponibles.
                    </p>
                    <InputError :message="errors.user_id" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing" :disabled="coordinators.length === 0">
                    Asignar coordinador
                </Button>
            </div>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 4: Create CoordinationHistoryModal.vue**

History is fetched on demand from the JSON endpoint when the modal opens.

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import Badge from '@/components/base/Badge.vue'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import type { CoordinationAssignment, CoordinationRow } from '@/types/security'

const props = defineProps<{
    open: boolean
    coordination: CoordinationRow
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const assignments = ref<CoordinationAssignment[]>([])
const loading = ref(false)

watch(
    () => props.open,
    async (isOpen) => {
        if (!isOpen) {
            assignments.value = []
            return
        }

        loading.value = true
        try {
            const res = await fetch(`/security/coordinations/${props.coordination.id}/assignments`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
            assignments.value = await res.json()
        } finally {
            loading.value = false
        }
    },
)

function close(v: boolean): void {
    emit('update:open', v)
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}
</script>

<template>
    <Modal
        :open="open"
        :title="`Historial — ${coordination.name}`"
        size="lg"
        @update:open="close"
    >
        <div v-if="loading" style="text-align:center;padding:32px;color:var(--text-muted);font-size:var(--text-sm);">
            Cargando historial...
        </div>

        <div v-else-if="assignments.length === 0" style="text-align:center;padding:32px;color:var(--text-muted);font-size:var(--text-sm);">
            No hay asignaciones registradas para esta coordinación.
        </div>

        <div v-else class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Coordinador</th>
                        <th>Asignado por</th>
                        <th>Desde</th>
                        <th>Hasta</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in assignments" :key="a.id">
                        <td style="font-weight:500;">{{ a.user.name }}</td>
                        <td style="color:var(--text-secondary);">{{ a.assigned_by.name }}</td>
                        <td style="color:var(--text-secondary);">{{ formatDate(a.assigned_at) }}</td>
                        <td>
                            <Badge v-if="!a.ended_at" variant="success">Activo</Badge>
                            <span v-else style="color:var(--text-secondary);">{{ formatDate(a.ended_at) }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:24px;">
            <Button variant="ghost" @click="close(false)">Cerrar</Button>
        </div>
    </Modal>
</template>
```

- [ ] **Step 5: Create DeleteCoordinationModal.vue**

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/security/coordinations'
import type { CoordinationRow } from '@/types/security'

const props = defineProps<{
    open: boolean
    coordination: CoordinationRow
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.delete(destroy.url(props.coordination), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal
        :open="open"
        title="Eliminar coordinación"
        size="sm"
        @update:open="close"
    >
        <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 12px;">
            ¿Eliminar la coordinación <strong>{{ coordination.name }}</strong>? Esta acción no se puede deshacer.
        </p>

        <p
            v-if="coordination.current_coordinator"
            style="color:var(--color-warning, #b45309);font-size:var(--text-sm);margin:0 0 24px;"
        >
            Advertencia: esta coordinación tiene un coordinador activo ({{ coordination.current_coordinator.name }}).
            Deberás reasignarlo antes de eliminarla.
        </p>
        <p v-else style="margin:0 0 24px;" />

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button variant="danger" :loading="form.processing" @click="submit">Eliminar</Button>
        </div>
    </Modal>
</template>
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/components/security/CreateCoordinationModal.vue resources/js/components/security/EditCoordinationModal.vue resources/js/components/security/AssignCoordinatorModal.vue resources/js/components/security/CoordinationHistoryModal.vue resources/js/components/security/DeleteCoordinationModal.vue
git commit -m "feat: add Coordination modals (create, edit, assign, history, delete)"
```

---

## Post-Implementation Checklist

- [ ] Run the full test suite: `vendor/bin/sail artisan test --compact`
- [ ] Seed the database: `vendor/bin/sail artisan db:seed`
- [ ] Log in as Admin and navigate to `/security/coordinations` to verify the page loads
- [ ] Create a coordination (career type and grade type)
- [ ] Assign a user with "Coordinador de Area" role as coordinator
- [ ] Verify the coordinator name appears in the table
- [ ] Open the history modal to verify it loads
- [ ] Try to delete a coordination with an active coordinator — verify the block message
- [ ] Verify "Coordinaciones" appears in the sidebar
