# Career Code Auto-Generation — Plan de Implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** El código de carrera se auto-genera al crear usando las iniciales de las palabras significativas del nombre más el ID; el usuario puede editarlo después.

**Architecture:** Se hace nullable la columna `code`, `CreateCareerAction` la genera en dos pasos dentro de una transacción (INSERT sin código → UPDATE con `{iniciales}-{id}`). `StoreCareerRequest` deja de validar `code`. El campo desaparece del modal de creación pero sigue visible y editable en el de edición.

**Tech Stack:** Laravel 13 · PostgreSQL · Pest v4 · Vue 3 · Inertia v3

---

## Mapa de archivos

| Acción | Archivo |
|--------|---------|
| Crear | `database/migrations/YYYY_make_careers_code_nullable.php` |
| Modificar | `app/Actions/Academic/CreateCareerAction.php` |
| Modificar | `app/Http/Requests/Academic/StoreCareerRequest.php` |
| Modificar | `tests/Feature/Academic/CareerControllerTest.php` |
| Modificar | `resources/js/components/academic/CreateCareerModal.vue` |

---

## Task 1: Migración — hacer `code` nullable

La columna `code` actualmente es `NOT NULL UNIQUE`. El nuevo flujo de creación hace el INSERT sin código (el ID no existe todavía), por eso hay que permitir NULL. El constraint UNIQUE se mantiene.

**Files:**
- Create: `database/migrations/YYYY_make_careers_code_nullable.php`

- [ ] **Step 1: Crear la migración**

```bash
vendor/bin/sail artisan make:migration make_careers_code_nullable --table=careers
```

- [ ] **Step 2: Escribir el contenido de la migración**

Abrir el archivo recién creado en `database/migrations/` y reemplazar su contenido:

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
            $table->string('code', 10)->nullable()->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            $table->string('code', 10)->nullable(false)->change();
        });
    }
};
```

- [ ] **Step 3: Correr la migración**

```bash
vendor/bin/sail artisan migrate
```

Expected: `Migrating: YYYY_make_careers_code_nullable` → `Migrated`

- [ ] **Step 4: Verificar**

```bash
vendor/bin/sail artisan tinker --execute 'use Illuminate\Support\Facades\Schema; $col = collect(Schema::getColumns("careers"))->firstWhere("name", "code"); echo $col["nullable"] ? "nullable OK" : "ERROR: still not null";'
```

Expected: `nullable OK`

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add database/migrations/
git commit -m "feat: make careers.code nullable to support auto-generation"
```

---

## Task 2: Backend — auto-generación en `CreateCareerAction` + ajuste de `StoreCareerRequest` + tests

Aquí van los tres cambios backend juntos porque los tests validan los tres a la vez.

**Files:**
- Modify: `app/Actions/Academic/CreateCareerAction.php`
- Modify: `app/Http/Requests/Academic/StoreCareerRequest.php`
- Modify: `tests/Feature/Academic/CareerControllerTest.php`

- [ ] **Step 1: Actualizar los tests primero (TDD — deben fallar inicialmente)**

Reemplazar el contenido completo de `tests/Feature/Academic/CareerControllerTest.php` con:

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
            'name'               => 'Sistemas',
        ])
        ->assertForbidden();
});

test('store fails validation when name is blank', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name'               => '',
        ])
        ->assertSessionHasErrors('name');
});

test('store fails validation when category does not exist', function () {
    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => 9999,
            'name'               => 'Sistemas',
        ])
        ->assertSessionHasErrors('career_category_id');
});

test('user with careers.create can store a new career', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name'               => 'Informática',
        ])
        ->assertRedirect(route('academic.careers.index'));

    $career = Career::where('name', 'Informática')->first();
    expect($career)->not->toBeNull()
        ->and($career->code)->not->toBeNull()
        ->and($career->active)->toBeTrue();
});

test('store auto-generates code from name skipping stop words', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name'               => 'Ingeniería en Sistemas',
        ])
        ->assertRedirect(route('academic.careers.index'));

    $career = Career::where('name', 'Ingeniería en Sistemas')->first();
    expect($career)->not->toBeNull()
        ->and($career->code)->toMatch('/^IS-\d{2,}$/');
});

test('store auto-generates code using only significant words', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name'               => 'Diseño Gráfico y Comunicación Visual',
        ])
        ->assertRedirect(route('academic.careers.index'));

    $career = Career::where('name', 'Diseño Gráfico y Comunicación Visual')->first();
    expect($career)->not->toBeNull()
        ->and($career->code)->toMatch('/^DGCV-\d{2,}$/');
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

- [ ] **Step 2: Actualizar `StoreCareerRequest` antes de ejecutar los tests**

Reemplazar el contenido completo de `app/Http/Requests/Academic/StoreCareerRequest.php`:

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
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'career_category_id' => ['required', 'integer', Rule::exists('career_categories', 'id')],
            'name'               => ['required', 'string', 'min:2', 'max:255'],
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
        ];
    }
}
```

- [ ] **Step 3: Ejecutar tests para confirmar que solo los 2 nuevos fallan**

```bash
vendor/bin/sail artisan test --compact --filter=CareerControllerTest
```

Expected: fallan únicamente `store auto-generates code from name skipping stop words` y `store auto-generates code using only significant words`. Los demás siguen en verde.

- [ ] **Step 4: Actualizar `CreateCareerAction`**

Reemplazar el contenido completo de `app/Actions/Academic/CreateCareerAction.php`:

```php
<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\CareerWrapper;
use App\Models\Career;
use Illuminate\Support\Facades\DB;

class CreateCareerAction
{
    /** @var string[] */
    private const STOP_WORDS = [
        'a', 'al', 'con', 'de', 'del', 'e', 'el', 'en',
        'la', 'las', 'lo', 'los', 'o', 'para', 'por',
        'sin', 'su', 'un', 'una', 'y',
    ];

    public function handle(CareerWrapper $wrapper): Career
    {
        return DB::transaction(function () use ($wrapper): Career {
            $career = Career::create([
                'career_category_id' => $wrapper->getCategoryId(),
                'name'               => $wrapper->getName(),
                'active'             => $wrapper->isActive(),
            ]);

            $career->update(['code' => $this->generateCode($career->name, $career->id)]);

            return $career;
        });
    }

    private function generateCode(string $name, int $id): string
    {
        $words = preg_split('/\s+/', mb_strtolower(trim($name)));
        $significant = array_filter($words, fn ($w) => !in_array($w, self::STOP_WORDS, true));

        if (empty($significant)) {
            $significant = $words;
        }

        $initials = implode('', array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), $significant));

        $idPart = '-' . str_pad((string) $id, 2, '0', STR_PAD_LEFT);
        $initials = mb_substr($initials, 0, max(1, 10 - strlen($idPart)));

        return $initials . $idPart;
    }
}
```

- [ ] **Step 5: Ejecutar tests — deben pasar todos**

```bash
vendor/bin/sail artisan test --compact --filter=CareerControllerTest
```

Expected: todos los tests en verde. Si alguno falla, corregir la implementación (no los tests).

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Actions/Academic/CreateCareerAction.php \
        app/Http/Requests/Academic/StoreCareerRequest.php \
        tests/Feature/Academic/CareerControllerTest.php
git commit -m "feat: auto-generate career code from name initials and ID"
```

---

## Task 3: Frontend — eliminar campo código del modal de creación

**Files:**
- Modify: `resources/js/components/academic/CreateCareerModal.vue`

- [ ] **Step 1: Eliminar el bloque del campo `code`**

En `resources/js/components/academic/CreateCareerModal.vue`, eliminar el bloque completo del campo código (líneas 52–66 del archivo actual):

```html
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
```

El archivo resultante debe quedar exactamente así:

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
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Crear carrera</Button>
            </div>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 2: Ejecutar el suite completo para confirmar sin regresiones**

```bash
vendor/bin/sail artisan test --compact
```

Expected: todos los tests en verde (≥ 219 pasando).

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/academic/CreateCareerModal.vue
git commit -m "feat: remove manual code input from career create modal"
```
