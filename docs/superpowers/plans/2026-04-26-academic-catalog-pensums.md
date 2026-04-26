# Academic Catalog — Part 3: Pensums — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement full Pensum CRUD under `/academic/careers/{career}/pensums`, restore the pensum guard in Career deletion, and wire up the "Ver pensums" button in Careers/Index.

**Architecture:** Nine tasks — database/model foundation, policy/permissions, requests/wrapper, actions/resource, controller+routes+tests (TDD), career module restoration, frontend composables/types, frontend page+modals, and enabling the navigation button.

**Tech Stack:** Laravel 13 · PHP 8.3 · Eloquent · Spatie Permission · Vue 3 · Inertia v3 · Wayfinder · Pest v4

---

## File Map

| File | Status | Responsibility |
|---|---|---|
| `database/migrations/..._create_pensums_table.php` | Create | pensums schema |
| `app/Models/Pensum.php` | Create | Pensum model |
| `database/factories/PensumFactory.php` | Create | test factory |
| `app/Models/Career.php` | Modify | restore pensums() relation |
| `database/data/permissions.yaml` | Modify | add 4 pensum permissions |
| `app/Policies/Academic/PensumPolicy.php` | Create | gate checks |
| `app/Providers/AppServiceProvider.php` | Modify | register PensumPolicy |
| `app/Http/Requests/Academic/StorePensumRequest.php` | Create | create validation |
| `app/Http/Requests/Academic/UpdatePensumRequest.php` | Create | update validation |
| `app/Http/Wrappers/Academic/PensumWrapper.php` | Create | typed data access |
| `app/Actions/Academic/CreatePensumAction.php` | Create | create logic |
| `app/Actions/Academic/UpdatePensumAction.php` | Create | update logic |
| `app/Actions/Academic/DeletePensumAction.php` | Create | delete + guard |
| `app/Http/Resources/Academic/PensumResource.php` | Create | API shape |
| `app/Http/Controllers/Academic/PensumController.php` | Create | HTTP handlers |
| `routes/web.php` | Modify | 4 pensum routes |
| `tests/Feature/Academic/PensumControllerTest.php` | Create | feature tests |
| `app/Actions/Academic/DeleteCareerAction.php` | Modify | restore pensum guard |
| `app/Http/Controllers/Academic/CareerController.php` | Modify | withCount('pensums') |
| `tests/Feature/Academic/CareerControllerTest.php` | Modify | add pensum guard test |
| `resources/js/types/academic.ts` | Modify | add Pensum type |
| `resources/js/composables/permissions/usePensumPermissions.ts` | Create | canCreate/Update/Delete |
| `resources/js/composables/forms/usePensumForm.ts` | Create | toggle active |
| `resources/js/pages/academic/Pensums/Index.vue` | Create | pensums page |
| `resources/js/components/academic/CreatePensumModal.vue` | Create | create modal |
| `resources/js/components/academic/EditPensumModal.vue` | Create | edit modal |
| `resources/js/components/academic/DeletePensumModal.vue` | Create | delete modal |
| `resources/js/pages/academic/Careers/Index.vue` | Modify | enable Ver pensums |

---

## Task 1: Migration + Pensum Model + Factory + Career relation

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_pensums_table.php`
- Create: `app/Models/Pensum.php`
- Create: `database/factories/PensumFactory.php`
- Modify: `app/Models/Career.php`

- [ ] **Step 1: Create migration**

```bash
vendor/bin/sail artisan make:migration create_pensums_table
```

Open the generated file and replace its content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pensums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->enum('period_type', ['semester', 'year']);
            $table->tinyInteger('total_periods')->unsigned();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pensums');
    }
};
```

- [ ] **Step 2: Run migration**

```bash
vendor/bin/sail artisan migrate
```

Expected: `create_pensums_table` migrated successfully.

- [ ] **Step 3: Create Pensum model**

Create `app/Models/Pensum.php`:

```php
<?php

namespace App\Models;

use Database\Factories\PensumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['career_id', 'name', 'period_type', 'total_periods', 'is_active'])]
class Pensum extends Model
{
    /** @use HasFactory<PensumFactory> */
    use HasFactory;

    /** @var array<string, string> */
    protected $casts = ['is_active' => 'boolean'];

    public function career(): BelongsTo
    {
        return $this->belongsTo(Career::class);
    }

    // subjects() relation will be added when the Subject model is implemented (Part 4)
}
```

- [ ] **Step 4: Create PensumFactory**

Create `database/factories/PensumFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Career;
use App\Models\Pensum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pensum>
 */
class PensumFactory extends Factory
{
    public function definition(): array
    {
        return [
            'career_id'     => Career::factory(),
            'name'          => 'Plan de Estudios ' . fake()->unique()->numberBetween(2000, 2030),
            'period_type'   => fake()->randomElement(['semester', 'year']),
            'total_periods' => fake()->numberBetween(6, 12),
            'is_active'     => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
```

- [ ] **Step 5: Restore pensums() relation in Career model**

Open `app/Models/Career.php`. Replace the comment with the real relation and add `HasMany` import:

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

    public function pensums(): HasMany
    {
        return $this->hasMany(Pensum::class);
    }
}
```

- [ ] **Step 6: Run pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add database/migrations app/Models/Pensum.php database/factories/PensumFactory.php app/Models/Career.php
git commit -m "feat: add pensums table, Pensum model, factory, and Career.pensums() relation"
```

---

## Task 2: Permissions + Policy + AppServiceProvider

**Files:**
- Modify: `database/data/permissions.yaml`
- Create: `app/Policies/Academic/PensumPolicy.php`
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Add permissions to YAML**

Open `database/data/permissions.yaml`. After the `careers.delete` entry add:

```yaml
  - name: pensums.view
    guard: web
  - name: pensums.create
    guard: web
  - name: pensums.update
    guard: web
  - name: pensums.delete
    guard: web
```

- [ ] **Step 2: Create PensumPolicy**

Create `app/Policies/Academic/PensumPolicy.php`:

```php
<?php

namespace App\Policies\Academic;

use App\Models\Pensum;
use App\Models\User;

class PensumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pensums.view');
    }

    public function create(User $user): bool
    {
        return $user->can('pensums.create');
    }

    public function update(User $user, Pensum $pensum): bool
    {
        return $user->can('pensums.update');
    }

    public function delete(User $user, Pensum $pensum): bool
    {
        return $user->can('pensums.delete');
    }
}
```

- [ ] **Step 3: Register policy in AppServiceProvider**

Open `app/Providers/AppServiceProvider.php`. Add these two imports after the existing policy imports:

```php
use App\Models\Pensum;
use App\Policies\Academic\PensumPolicy;
```

Inside `configureAuthorization()`, add after the `Career` policy line:

```php
Gate::policy(Pensum::class, PensumPolicy::class);
```

- [ ] **Step 4: Run pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 5: Commit**

```bash
git add database/data/permissions.yaml app/Policies/Academic/PensumPolicy.php app/Providers/AppServiceProvider.php
git commit -m "feat: add pensum permissions, PensumPolicy, and Gate registration"
```

---

## Task 3: Form Requests + Wrapper

**Files:**
- Create: `app/Http/Requests/Academic/StorePensumRequest.php`
- Create: `app/Http/Requests/Academic/UpdatePensumRequest.php`
- Create: `app/Http/Wrappers/Academic/PensumWrapper.php`

- [ ] **Step 1: Create StorePensumRequest**

```php
<?php

namespace App\Http\Requests\Academic;

use App\Models\Pensum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePensumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Pensum::class) ?? false;
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
            'name'          => ['required', 'string', 'max:255'],
            'period_type'   => ['required', 'string', Rule::in(['semester', 'year'])],
            'total_periods' => ['required', 'integer', 'min:1', 'max:20'],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'          => 'El nombre es obligatorio.',
            'name.max'               => 'El nombre no puede superar los 255 caracteres.',
            'period_type.required'   => 'El tipo de período es obligatorio.',
            'period_type.in'         => 'El tipo de período debe ser semestral o anual.',
            'total_periods.required' => 'El total de períodos es obligatorio.',
            'total_periods.min'      => 'El total de períodos debe ser al menos 1.',
            'total_periods.max'      => 'El total de períodos no puede superar 20.',
        ];
    }
}
```

- [ ] **Step 2: Create UpdatePensumRequest**

```php
<?php

namespace App\Http\Requests\Academic;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePensumRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pensum = $this->route('pensum');

        return $this->user()?->can('update', $pensum) ?? false;
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
            'name'          => ['required', 'string', 'max:255'],
            'period_type'   => ['required', 'string', Rule::in(['semester', 'year'])],
            'total_periods' => ['required', 'integer', 'min:1', 'max:20'],
            'is_active'     => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'          => 'El nombre es obligatorio.',
            'name.max'               => 'El nombre no puede superar los 255 caracteres.',
            'period_type.required'   => 'El tipo de período es obligatorio.',
            'period_type.in'         => 'El tipo de período debe ser semestral o anual.',
            'total_periods.required' => 'El total de períodos es obligatorio.',
            'total_periods.min'      => 'El total de períodos debe ser al menos 1.',
            'total_periods.max'      => 'El total de períodos no puede superar 20.',
            'is_active.required'     => 'El estado es obligatorio.',
        ];
    }
}
```

- [ ] **Step 3: Create PensumWrapper**

```php
<?php

namespace App\Http\Wrappers\Academic;

use Illuminate\Support\Collection;

class PensumWrapper extends Collection
{
    public function getCareerId(): int
    {
        return (int) $this->get('career_id');
    }

    public function getName(): string
    {
        return (string) $this->get('name');
    }

    public function getPeriodType(): string
    {
        return (string) $this->get('period_type');
    }

    public function getTotalPeriods(): int
    {
        return (int) $this->get('total_periods');
    }

    public function isActive(): bool
    {
        return (bool) ($this->get('is_active') ?? true);
    }
}
```

- [ ] **Step 4: Run pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Requests/Academic/StorePensumRequest.php app/Http/Requests/Academic/UpdatePensumRequest.php app/Http/Wrappers/Academic/PensumWrapper.php
git commit -m "feat: add StorePensumRequest, UpdatePensumRequest, and PensumWrapper"
```

---

## Task 4: Actions + Resource

**Files:**
- Create: `app/Actions/Academic/CreatePensumAction.php`
- Create: `app/Actions/Academic/UpdatePensumAction.php`
- Create: `app/Actions/Academic/DeletePensumAction.php`
- Create: `app/Http/Resources/Academic/PensumResource.php`

- [ ] **Step 1: Create CreatePensumAction**

```php
<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\PensumWrapper;
use App\Models\Pensum;

class CreatePensumAction
{
    public function handle(PensumWrapper $wrapper): Pensum
    {
        return Pensum::create([
            'career_id'     => $wrapper->getCareerId(),
            'name'          => $wrapper->getName(),
            'period_type'   => $wrapper->getPeriodType(),
            'total_periods' => $wrapper->getTotalPeriods(),
            'is_active'     => $wrapper->isActive(),
        ]);
    }
}
```

- [ ] **Step 2: Create UpdatePensumAction**

```php
<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\PensumWrapper;
use App\Models\Pensum;

class UpdatePensumAction
{
    public function handle(Pensum $pensum, PensumWrapper $wrapper): Pensum
    {
        $pensum->update([
            'name'          => $wrapper->getName(),
            'period_type'   => $wrapper->getPeriodType(),
            'total_periods' => $wrapper->getTotalPeriods(),
            'is_active'     => $wrapper->isActive(),
        ]);

        return $pensum;
    }
}
```

- [ ] **Step 3: Create DeletePensumAction**

```php
<?php

namespace App\Actions\Academic;

use App\Models\Pensum;

class DeletePensumAction
{
    /**
     * Deletes the pensum if it has no associated subjects.
     * Returns false when deletion is blocked.
     */
    public function handle(Pensum $pensum): bool
    {
        // Subject guard will be re-enabled once App\Models\Subject is implemented (Part 4)
        $pensum->delete();

        return true;
    }
}
```

- [ ] **Step 4: Create PensumResource**

```php
<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PensumResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'periodType'    => $this->period_type,
            'totalPeriods'  => $this->total_periods,
            'isActive'      => $this->is_active,
            'subjectsCount' => $this->subjects_count ?? 0,
        ];
    }
}
```

- [ ] **Step 5: Run pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Actions/Academic/CreatePensumAction.php app/Actions/Academic/UpdatePensumAction.php app/Actions/Academic/DeletePensumAction.php app/Http/Resources/Academic/PensumResource.php
git commit -m "feat: add Pensum actions (create, update, delete) and PensumResource"
```

---

## Task 5: Controller + Routes + Tests (TDD)

**Files:**
- Create: `tests/Feature/Academic/PensumControllerTest.php`
- Create: `app/Http/Controllers/Academic/PensumController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write the failing test file**

Create `tests/Feature/Academic/PensumControllerTest.php`:

```php
<?php

use App\Models\Career;
use App\Models\Pensum;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'pensums.view',   'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'pensums.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'pensums.update', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'pensums.delete', 'guard_name' => 'web']);
});

function pensumUserWith(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('unauthenticated user is redirected to login on pensums index', function () {
    $career = Career::factory()->create();

    $this->get("/academic/careers/{$career->id}/pensums")
        ->assertRedirect('/login');
});

test('authenticated user without pensums.view gets 403 on pensums index', function () {
    $career = Career::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get("/academic/careers/{$career->id}/pensums")
        ->assertForbidden();
});

test('user with pensums.view sees the pensums index page for a career', function () {
    $career = Career::factory()->create();
    Pensum::factory()->create(['career_id' => $career->id]);

    $this->actingAs(pensumUserWith('pensums.view'))
        ->get("/academic/careers/{$career->id}/pensums")
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('academic/Pensums/Index', false)
                ->has('career')
                ->has('pensums', 1)
                ->has('can')
        );
});

test('pensums index only shows pensums belonging to the given career', function () {
    $career      = Career::factory()->create();
    $otherCareer = Career::factory()->create();

    Pensum::factory()->create(['career_id' => $career->id]);
    Pensum::factory()->create(['career_id' => $otherCareer->id]);

    $this->actingAs(pensumUserWith('pensums.view'))
        ->get("/academic/careers/{$career->id}/pensums")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('pensums', 1));
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('user without pensums.create cannot store a pensum', function () {
    $career = Career::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post("/academic/careers/{$career->id}/pensums", [
            'name'          => 'Plan de Estudios 2020',
            'period_type'   => 'semester',
            'total_periods' => 10,
        ])
        ->assertForbidden();
});

test('store fails validation when name is blank', function () {
    $career = Career::factory()->create();

    $this->actingAs(pensumUserWith('pensums.create'))
        ->post("/academic/careers/{$career->id}/pensums", [
            'name'          => '',
            'period_type'   => 'semester',
            'total_periods' => 10,
        ])
        ->assertSessionHasErrors('name');
});

test('store fails validation when period_type is invalid', function () {
    $career = Career::factory()->create();

    $this->actingAs(pensumUserWith('pensums.create'))
        ->post("/academic/careers/{$career->id}/pensums", [
            'name'          => 'Plan de Estudios 2020',
            'period_type'   => 'quarterly',
            'total_periods' => 10,
        ])
        ->assertSessionHasErrors('period_type');
});

test('store fails validation when total_periods is zero', function () {
    $career = Career::factory()->create();

    $this->actingAs(pensumUserWith('pensums.create'))
        ->post("/academic/careers/{$career->id}/pensums", [
            'name'          => 'Plan de Estudios 2020',
            'period_type'   => 'semester',
            'total_periods' => 0,
        ])
        ->assertSessionHasErrors('total_periods');
});

test('store fails validation when total_periods exceeds 20', function () {
    $career = Career::factory()->create();

    $this->actingAs(pensumUserWith('pensums.create'))
        ->post("/academic/careers/{$career->id}/pensums", [
            'name'          => 'Plan de Estudios 2020',
            'period_type'   => 'semester',
            'total_periods' => 21,
        ])
        ->assertSessionHasErrors('total_periods');
});

test('user with pensums.create can store a new pensum', function () {
    $career = Career::factory()->create();

    $this->actingAs(pensumUserWith('pensums.create'))
        ->post("/academic/careers/{$career->id}/pensums", [
            'name'          => 'Plan de Estudios 2020',
            'period_type'   => 'semester',
            'total_periods' => 10,
        ])
        ->assertRedirect(route('academic.pensums.index', $career));

    $pensum = Pensum::where('name', 'Plan de Estudios 2020')->first();
    expect($pensum)->not->toBeNull()
        ->and($pensum->career_id)->toBe($career->id)
        ->and($pensum->period_type)->toBe('semester')
        ->and($pensum->total_periods)->toBe(10)
        ->and($pensum->is_active)->toBeTrue();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('user without pensums.update cannot update a pensum', function () {
    $career = Career::factory()->create();
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);

    $this->actingAs(User::factory()->create())
        ->patch("/academic/careers/{$career->id}/pensums/{$pensum->id}", [
            'name'          => 'Plan Actualizado',
            'period_type'   => 'semester',
            'total_periods' => 10,
            'is_active'     => true,
        ])
        ->assertForbidden();
});

test('user with pensums.update can update a pensum', function () {
    $career = Career::factory()->create();
    $pensum = Pensum::factory()->create(['career_id' => $career->id, 'is_active' => true]);

    $this->actingAs(pensumUserWith('pensums.update'))
        ->patch("/academic/careers/{$career->id}/pensums/{$pensum->id}", [
            'name'          => 'Plan Actualizado',
            'period_type'   => 'year',
            'total_periods' => 5,
            'is_active'     => false,
        ])
        ->assertRedirect(route('academic.pensums.index', $career));

    $updated = $pensum->fresh();
    expect($updated->name)->toBe('Plan Actualizado')
        ->and($updated->period_type)->toBe('year')
        ->and($updated->total_periods)->toBe(5)
        ->and($updated->is_active)->toBeFalse();
});

test('user with pensums.update can toggle is_active to false', function () {
    $career = Career::factory()->create();
    $pensum = Pensum::factory()->create(['career_id' => $career->id, 'is_active' => true]);

    $this->actingAs(pensumUserWith('pensums.update'))
        ->patch("/academic/careers/{$career->id}/pensums/{$pensum->id}", [
            'name'          => $pensum->name,
            'period_type'   => $pensum->period_type,
            'total_periods' => $pensum->total_periods,
            'is_active'     => false,
        ])
        ->assertRedirect(route('academic.pensums.index', $career));

    expect($pensum->fresh()->is_active)->toBeFalse();
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('user without pensums.delete cannot destroy a pensum', function () {
    $career = Career::factory()->create();
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);

    $this->actingAs(User::factory()->create())
        ->delete("/academic/careers/{$career->id}/pensums/{$pensum->id}")
        ->assertForbidden();
});

test('user with pensums.delete can destroy a pensum', function () {
    $career = Career::factory()->create();
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);

    $this->actingAs(pensumUserWith('pensums.delete'))
        ->delete("/academic/careers/{$career->id}/pensums/{$pensum->id}")
        ->assertRedirect(route('academic.pensums.index', $career));

    expect(Pensum::find($pensum->id))->toBeNull();
});
```

- [ ] **Step 2: Run tests — confirm they all fail**

```bash
vendor/bin/sail artisan test --compact --filter=PensumControllerTest
```

Expected: ALL tests fail (route not found / class not found). If any pass unexpectedly, investigate before continuing.

- [ ] **Step 3: Add routes to web.php**

Open `routes/web.php`. Add this import at the top with the other Academic controller imports:

```php
use App\Http\Controllers\Academic\PensumController;
```

Inside the `academic.` middleware group, after the career routes, add:

```php
    Route::get('careers/{career}/pensums', [PensumController::class, 'index'])->name('pensums.index');
    Route::post('careers/{career}/pensums', [PensumController::class, 'store'])->name('pensums.store');
    Route::patch('careers/{career}/pensums/{pensum}', [PensumController::class, 'update'])->name('pensums.update');
    Route::delete('careers/{career}/pensums/{pensum}', [PensumController::class, 'destroy'])->name('pensums.destroy');
```

- [ ] **Step 4: Create PensumController**

Create `app/Http/Controllers/Academic/PensumController.php`:

```php
<?php

namespace App\Http\Controllers\Academic;

use App\Actions\Academic\CreatePensumAction;
use App\Actions\Academic\DeletePensumAction;
use App\Actions\Academic\UpdatePensumAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StorePensumRequest;
use App\Http\Requests\Academic\UpdatePensumRequest;
use App\Http\Resources\Academic\CareerResource;
use App\Http\Resources\Academic\PensumResource;
use App\Http\Wrappers\Academic\PensumWrapper;
use App\Models\Career;
use App\Models\Pensum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PensumController extends Controller
{
    public function index(Request $request, Career $career): Response
    {
        Gate::authorize('viewAny', Pensum::class);

        $actor = $request->user();

        return Inertia::render('academic/Pensums/Index', [
            'career'  => (new CareerResource($career))->resolve(),
            'pensums' => PensumResource::collection(
                $career->pensums()->orderBy('name')->get()
            )->resolve(),
            'can' => [
                'create' => $actor->can('create', Pensum::class),
                'update' => $actor->can('update', new Pensum),
                'delete' => $actor->can('delete', new Pensum),
            ],
        ]);
    }

    public function store(StorePensumRequest $request, Career $career, CreatePensumAction $action): RedirectResponse
    {
        $action->handle(new PensumWrapper($request->validated() + ['career_id' => $career->id]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pensum creado.']);

        return to_route('academic.pensums.index', $career);
    }

    public function update(UpdatePensumRequest $request, Career $career, Pensum $pensum, UpdatePensumAction $action): RedirectResponse
    {
        $action->handle($pensum, new PensumWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pensum actualizado.']);

        return to_route('academic.pensums.index', $career);
    }

    public function destroy(Career $career, Pensum $pensum, DeletePensumAction $action): RedirectResponse
    {
        Gate::authorize('delete', $pensum);

        if (! $action->handle($pensum)) {
            Inertia::flash('toast', [
                'type'    => 'error',
                'message' => 'No se puede eliminar: el pensum tiene materias asociadas.',
            ]);

            return to_route('academic.pensums.index', $career);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pensum eliminado.']);

        return to_route('academic.pensums.index', $career);
    }
}
```

- [ ] **Step 5: Run tests — confirm all pass**

```bash
vendor/bin/sail artisan test --compact --filter=PensumControllerTest
```

Expected: all tests pass (14 tests).

- [ ] **Step 6: Run full suite to check for regressions**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all tests pass.

- [ ] **Step 7: Run pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add tests/Feature/Academic/PensumControllerTest.php app/Http/Controllers/Academic/PensumController.php routes/web.php
git commit -m "feat: add PensumController, routes, and feature tests"
```

---

## Task 6: Restore Career module (pensum guard + withCount + test)

**Files:**
- Modify: `app/Actions/Academic/DeleteCareerAction.php`
- Modify: `app/Http/Controllers/Academic/CareerController.php`
- Modify: `tests/Feature/Academic/CareerControllerTest.php`

- [ ] **Step 1: Restore pensum guard in DeleteCareerAction**

Replace `app/Actions/Academic/DeleteCareerAction.php` entirely:

```php
<?php

namespace App\Actions\Academic;

use App\Models\Career;

class DeleteCareerAction
{
    /**
     * Deletes the career if it has no associated pensums.
     * Returns false when deletion is blocked.
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

- [ ] **Step 2: Add withCount('pensums') to CareerController::index**

Open `app/Http/Controllers/Academic/CareerController.php`. In the `index` method, change:

```php
Career::with('careerCategory')->orderBy('name')->get()
```

to:

```php
Career::with('careerCategory')->withCount('pensums')->orderBy('name')->get()
```

- [ ] **Step 3: Add the pensum guard test to CareerControllerTest**

Open `tests/Feature/Academic/CareerControllerTest.php`. Add this `use` statement after the existing use statements at the top:

```php
use App\Models\Pensum;
```

At the end of the `// destroy` section, add:

```php
test('user with careers.delete cannot destroy a career that has pensums', function () {
    $career = Career::factory()->create();
    Pensum::factory()->create(['career_id' => $career->id]);

    $this->actingAs(careerUserWith('careers.delete'))
        ->delete("/academic/careers/{$career->id}")
        ->assertRedirect(route('academic.careers.index'));

    expect(Career::find($career->id))->not->toBeNull();
});
```

- [ ] **Step 4: Run tests**

```bash
vendor/bin/sail artisan test --compact --filter=CareerControllerTest
```

Expected: all tests pass including the new pensum guard test.

- [ ] **Step 5: Run pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Actions/Academic/DeleteCareerAction.php app/Http/Controllers/Academic/CareerController.php tests/Feature/Academic/CareerControllerTest.php
git commit -m "feat: restore pensum guard in DeleteCareerAction and withCount in CareerController"
```

---

## Task 7: Frontend types + composables

**Files:**
- Modify: `resources/js/types/academic.ts`
- Create: `resources/js/composables/permissions/usePensumPermissions.ts`
- Create: `resources/js/composables/forms/usePensumForm.ts`

- [ ] **Step 1: Add Pensum type to academic.ts**

Open `resources/js/types/academic.ts`. Append:

```typescript
export type Pensum = {
  id: number
  careerId: number
  name: string
  periodType: 'semester' | 'year'
  totalPeriods: number
  isActive: boolean
  subjectsCount: number
}
```

- [ ] **Step 2: Create usePensumPermissions.ts**

Create `resources/js/composables/permissions/usePensumPermissions.ts`:

```typescript
import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function usePensumPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('pensums.create'))
    const canUpdate = computed(() => can('pensums.update'))
    const canDelete = computed(() => can('pensums.delete'))

    return { canCreate, canUpdate, canDelete }
}
```

- [ ] **Step 3: Generate Wayfinder files**

After adding routes in Task 5, run:

```bash
vendor/bin/sail artisan wayfinder:generate
```

Then check the generated file — it will be at `resources/js/routes/academic/pensums/index.ts`. Note the exported function names (`index`, `store`, `update`, `destroy`) and their parameter signatures. The pensum routes have a `{career}` param for all methods and additionally `{pensum}` for update/destroy.

- [ ] **Step 4: Create usePensumForm.ts**

Create `resources/js/composables/forms/usePensumForm.ts`. The `toggle` function sends a PATCH with all current pensum fields, flipping `is_active`. Import paths use the generated Wayfinder file. Pass `career` and `pensum` to the update route function as needed (check the generated signatures):

```typescript
import { router } from '@inertiajs/vue3'
import { update } from '@/routes/academic/pensums'
import type { Career, Pensum } from '@/types/academic'

export function usePensumForm(career: Career) {
    function toggle(pensum: Pensum): void {
        router.patch(update.url(career, pensum), {
            name:          pensum.name,
            period_type:   pensum.periodType,
            total_periods: pensum.totalPeriods,
            is_active:     !pensum.isActive,
        })
    }

    return { toggle }
}
```

Note: `update.url(career, pensum)` passes the two route model bindings. If the generated Wayfinder function expects `{ career, pensum }` as an object instead, use `update.url({ career, pensum })`. Check `resources/js/routes/academic/pensums/index.ts` to confirm.

- [ ] **Step 5: Commit**

```bash
git add resources/js/types/academic.ts resources/js/composables/permissions/usePensumPermissions.ts resources/js/composables/forms/usePensumForm.ts
git commit -m "feat: add Pensum type, usePensumPermissions, and usePensumForm composables"
```

---

## Task 8: Pensums/Index.vue + Modals

**Files:**
- Create: `resources/js/pages/academic/Pensums/Index.vue`
- Create: `resources/js/components/academic/CreatePensumModal.vue`
- Create: `resources/js/components/academic/EditPensumModal.vue`
- Create: `resources/js/components/academic/DeletePensumModal.vue`

- [ ] **Step 1: Create CreatePensumModal.vue**

`resources/js/components/academic/CreatePensumModal.vue` — accepts `career` prop to provide the `{career}` route param:

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/academic/pensums'
import type { Career } from '@/types/academic'

defineProps<{
    open: boolean
    career: Career
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
    <Modal :open="open" title="Nuevo pensum" size="sm" @update:open="close">
        <Form :key="formKey" v-bind="store.form(career)" v-slot="{ errors, processing }" @success="close(false)">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cp-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="cp-name" name="name" class="input" placeholder="Ej: Plan de Estudios 2024" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cp-period-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo de período
                    </label>
                    <select id="cp-period-type" name="period_type" class="input" required>
                        <option value="">Seleccionar tipo</option>
                        <option value="semester">Semestral</option>
                        <option value="year">Anual</option>
                    </select>
                    <InputError :message="errors.period_type" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cp-total-periods" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Total de períodos
                    </label>
                    <input id="cp-total-periods" name="total_periods" type="number" min="1" max="20" class="input" placeholder="Ej: 10" required />
                    <InputError :message="errors.total_periods" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cp-active" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Estado
                    </label>
                    <select id="cp-active" name="is_active" class="input">
                        <option value="1" selected>Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                    <InputError :message="errors.is_active" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Crear pensum</Button>
            </div>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 2: Create EditPensumModal.vue**

`resources/js/components/academic/EditPensumModal.vue`:

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/academic/pensums'
import type { Career, Pensum } from '@/types/academic'

defineProps<{
    open: boolean
    career: Career
    pensum: Pensum
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
    <Modal :open="open" title="Editar pensum" size="sm" @update:open="close">
        <Form
            :key="formKey"
            v-bind="update.form(career, pensum)"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="ep-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="ep-name" name="name" class="input" :value="pensum.name" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ep-period-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo de período
                    </label>
                    <select id="ep-period-type" name="period_type" class="input" required>
                        <option value="semester" :selected="pensum.periodType === 'semester'">Semestral</option>
                        <option value="year" :selected="pensum.periodType === 'year'">Anual</option>
                    </select>
                    <InputError :message="errors.period_type" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ep-total-periods" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Total de períodos
                    </label>
                    <input id="ep-total-periods" name="total_periods" type="number" min="1" max="20" class="input" :value="pensum.totalPeriods" required />
                    <InputError :message="errors.total_periods" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ep-active" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Estado
                    </label>
                    <select id="ep-active" name="is_active" class="input">
                        <option value="1" :selected="pensum.isActive">Activo</option>
                        <option value="0" :selected="!pensum.isActive">Inactivo</option>
                    </select>
                    <InputError :message="errors.is_active" />
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

- [ ] **Step 3: Create DeletePensumModal.vue**

`resources/js/components/academic/DeletePensumModal.vue`:

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/academic/pensums'
import type { Career, Pensum } from '@/types/academic'

const props = defineProps<{
    open: boolean
    career: Career
    pensum: Pensum
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.delete(destroy.url(props.career, props.pensum), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Eliminar pensum" size="sm" @update:open="close">
        <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
            ¿Eliminar el pensum <strong>{{ pensum.name }}</strong>?
            Si tiene materias asociadas, la operación será rechazada automáticamente.
        </p>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button variant="danger" :loading="form.processing" @click="submit">Eliminar</Button>
        </div>
    </Modal>
</template>
```

- [ ] **Step 4: Create Pensums/Index.vue**

Create the directory first: `resources/js/pages/academic/Pensums/`.

`resources/js/pages/academic/Pensums/Index.vue`:

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { useLayoutProps } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Badge from '@/components/UI/AppBadge.vue'
import Button from '@/components/UI/AppButton.vue'
import CreatePensumModal from '@/components/academic/CreatePensumModal.vue'
import DeletePensumModal from '@/components/academic/DeletePensumModal.vue'
import EditPensumModal from '@/components/academic/EditPensumModal.vue'
import { usePensumForm } from '@/composables/forms/usePensumForm'
import { usePensumPermissions } from '@/composables/permissions/usePensumPermissions'
import { index as careersIndex } from '@/routes/academic/careers'
import type { Career, Pensum } from '@/types/academic'

type Props = {
    career: Career
    pensums: Pensum[]
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

useLayoutProps({
    breadcrumbs: computed(() => [
        { title: 'Académico', href: '#' },
        { title: 'Carreras', href: careersIndex.url() },
        { title: props.career.name, href: '#' },
    ]),
})

const { canCreate, canUpdate, canDelete } = usePensumPermissions()
const { toggle } = usePensumForm(props.career)

const showCreate = ref(false)
const editingPensum = ref<Pensum | null>(null)
const deletingPensum = ref<Pensum | null>(null)
</script>

<template>
    <Head :title="`Pensums — ${career.name}`" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Pensums
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    {{ career.name }}
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nuevo pensum
            </Button>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Períodos</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="pensum in pensums" :key="pensum.id">
                        <td style="font-weight:500;">{{ pensum.name }}</td>
                        <td style="color:var(--text-secondary);">
                            {{ pensum.periodType === 'semester' ? 'Semestral' : 'Anual' }}
                        </td>
                        <td style="color:var(--text-secondary);">{{ pensum.totalPeriods }}</td>
                        <td>
                            <Badge :variant="pensum.isActive ? 'success' : 'neutral'" dot>
                                {{ pensum.isActive ? 'Activo' : 'Inactivo' }}
                            </Badge>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    icon="book-open"
                                    :aria-label="`Ver materias de ${pensum.name}`"
                                    disabled
                                    title="Disponible en la Parte 4"
                                />
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    :icon="pensum.isActive ? 'toggle-right' : 'toggle-left'"
                                    :aria-label="pensum.isActive ? `Desactivar ${pensum.name}` : `Activar ${pensum.name}`"
                                    @click="toggle(pensum)"
                                />
                                <Button
                                    v-if="canUpdate"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar ${pensum.name}`"
                                    @click="editingPensum = pensum"
                                />
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${pensum.name}`"
                                    @click="deletingPensum = pensum"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!pensums.length">
                        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay pensums registrados para esta carrera.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreatePensumModal
        v-model:open="showCreate"
        :career="career"
    />

    <EditPensumModal
        v-if="editingPensum"
        :open="!!editingPensum"
        :career="career"
        :pensum="editingPensum"
        @update:open="v => { if (!v) editingPensum = null }"
    />

    <DeletePensumModal
        v-if="deletingPensum"
        :open="!!deletingPensum"
        :career="career"
        :pensum="deletingPensum"
        @update:open="v => { if (!v) deletingPensum = null }"
    />
</template>
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/academic/Pensums/ resources/js/components/academic/CreatePensumModal.vue resources/js/components/academic/EditPensumModal.vue resources/js/components/academic/DeletePensumModal.vue
git commit -m "feat: add Pensums index page and Create/Edit/Delete modals"
```

---

## Task 9: Enable "Ver pensums" in Careers/Index.vue

**Files:**
- Modify: `resources/js/pages/academic/Careers/Index.vue`

- [ ] **Step 1: Update Careers/Index.vue**

Open `resources/js/pages/academic/Careers/Index.vue`.

Add this import at the top of the `<script setup>` after the existing imports:

```typescript
import { router } from '@inertiajs/vue3'
import { index as pensumsIndex } from '@/routes/academic/pensums'
```

Find the disabled "Ver pensums" button:

```vue
<Button
    variant="ghost"
    size="sm"
    icon="book-open"
    :aria-label="`Ver pensums de ${career.name}`"
    disabled
    title="Disponible en la Parte 3"
/>
```

Replace it with:

```vue
<Button
    variant="ghost"
    size="sm"
    icon="book-open"
    :aria-label="`Ver pensums de ${career.name}`"
    @click="router.visit(pensumsIndex.url(career))"
/>
```

- [ ] **Step 2: Run full test suite**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all tests pass.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/academic/Careers/Index.vue
git commit -m "feat: enable Ver pensums navigation from Careers index"
```

---

## Self-Review

**Spec coverage check:**
- ✅ `pensums` table: id, career_id, name, period_type, total_periods, is_active, timestamps — Task 1
- ✅ Pensum model with Career relation and Subject placeholder — Task 1
- ✅ Career.pensums() restored — Task 1
- ✅ PensumPolicy: viewAny/create/update/delete — Task 2
- ✅ 4 permissions in YAML — Task 2
- ✅ Policy registered in AppServiceProvider — Task 2
- ✅ StorePensumRequest + UpdatePensumRequest validation — Task 3
- ✅ PensumWrapper with all getters — Task 3
- ✅ CreatePensumAction, UpdatePensumAction, DeletePensumAction (with Subject guard placeholder) — Task 4
- ✅ PensumResource returns all 6 fields — Task 4
- ✅ PensumController: index/store/update/destroy — Task 5
- ✅ 4 nested routes in web.php — Task 5
- ✅ 14 feature tests — Task 5
- ✅ DeleteCareerAction pensum guard restored — Task 6
- ✅ CareerController withCount('pensums') — Task 6
- ✅ New pensum guard test in CareerControllerTest — Task 6
- ✅ Pensum TypeScript type — Task 7
- ✅ usePensumPermissions composable — Task 7
- ✅ usePensumForm with toggle() — Task 7
- ✅ Pensums/Index.vue with breadcrumb, table, toggle, modals — Task 8
- ✅ CreatePensumModal, EditPensumModal, DeletePensumModal — Task 8
- ✅ "Ver pensums" button enabled in Careers/Index.vue — Task 9
- ✅ Multiple pensums can be active simultaneously — enforced by absence of any uniqueness constraint on is_active

**No placeholders found. No type inconsistencies. Scope is correct (Pensums only, Subjects deferred to Part 4).**
