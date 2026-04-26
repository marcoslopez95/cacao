# Academic Catalog — Part 3: Pensums — Design Spec

**Goal:** Implement the Pensum module — CRUD completo bajo `/academic/careers/{career}/pensums`, siguiendo los patrones establecidos en las partes 1 y 2 (CareerCategories y Careers).

**Architecture:** Pensum es una entidad anidada bajo Career. Cuatro rutas nested, un Inertia page con drill-down desde Careers/Index. Múltiples pensums de la misma carrera pueden estar activos simultáneamente (estudiantes actuales permanecen en su pensum vigente).

**Tech Stack:** Laravel 13 · Eloquent · Spatie Permission · Vue 3 · Inertia v3 · Wayfinder · Pest v4

---

## Data Model

### `pensums`

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| career_id | FK → careers | cascade delete |
| name | string(255) | ej. "Plan de Estudios 2020" |
| period_type | enum(`semester`, `year`) | |
| total_periods | tinyint | 1–20 |
| is_active | boolean | default true |
| timestamps | | |

**Regla de negocio:** múltiples pensums de la misma carrera pueden estar activos simultáneamente. No hay constraint de "solo uno activo". El toggle activo es reversible y no requiere confirmación modal.

---

## Routes

Nested bajo `/academic/careers/{career}/`, dentro del grupo `auth + verified`, prefix `academic.`.

```
GET    /academic/careers/{career}/pensums                    academic.pensums.index
POST   /academic/careers/{career}/pensums                    academic.pensums.store
PATCH  /academic/careers/{career}/pensums/{pensum}           academic.pensums.update
DELETE /academic/careers/{career}/pensums/{pensum}           academic.pensums.destroy
```

---

## Backend

### Model

```
app/Models/Pensum.php
```

- Fillable: `career_id`, `name`, `period_type`, `total_periods`, `is_active`
- Casts: `is_active → boolean`
- Relations:
  - `belongsTo(Career::class)`
  - `hasMany(Subject::class)` — placeholder hasta Part 4

### Policy

```
app/Policies/Academic/PensumPolicy.php
```

- `viewAny` → `pensums.view`
- `create` → `pensums.create`
- `update` → `pensums.update`
- `delete` → `pensums.delete`

Registrar en `AppServiceProvider`: `Gate::policy(Pensum::class, PensumPolicy::class)`.

### Permissions

Agregar a `database/data/permissions.yaml`:

```yaml
- pensums.view
- pensums.create
- pensums.update
- pensums.delete
```

Ejecutar `PermissionSeeder` (o `firstOrCreate` en tests).

### Form Requests

```
app/Http/Requests/Academic/StorePensumRequest.php
app/Http/Requests/Academic/UpdatePensumRequest.php
```

**`StorePensumRequest::rules()`**
```php
'name'          => ['required', 'string', 'max:255'],
'period_type'   => ['required', 'string', Rule::in(['semester', 'year'])],
'total_periods' => ['required', 'integer', 'min:1', 'max:20'],
'is_active'     => ['sometimes', 'boolean'],
```

**`UpdatePensumRequest::rules()`** — igual, pero `is_active` es `required`.

Ambos autorizan vía `PensumPolicy` en `authorize()`.

### Wrapper

```
app/Http/Wrappers/Academic/PensumWrapper.php
```

Extiende `Illuminate\Support\Collection`. Getters:
- `getName(): string`
- `getPeriodType(): string`
- `getTotalPeriods(): int`
- `isActive(): bool` — default `true`

El `career_id` se inyecta desde el controller vía route model binding: `new PensumWrapper($request->validated() + ['career_id' => $career->id])`.

### Actions

```
app/Actions/Academic/CreatePensumAction.php   handle(PensumWrapper): Pensum
app/Actions/Academic/UpdatePensumAction.php   handle(Pensum, PensumWrapper): Pensum
app/Actions/Academic/DeletePensumAction.php   handle(Pensum): bool
```

- **Create**: `Pensum::create([...])` — sin lógica especial
- **Update**: `$pensum->update([...])` — actualiza todos los campos
- **Delete**: guard — si `$pensum->subjects()->exists()` → lanzar excepción / retornar false. Si no → `$pensum->delete()`

### Resource

```
app/Http/Resources/Academic/PensumResource.php
```

```php
[
    'id'           => $this->id,
    'name'         => $this->name,
    'periodType'   => $this->period_type,
    'totalPeriods' => $this->total_periods,
    'isActive'     => $this->is_active,
    'subjectsCount' => $this->subjects_count ?? 0,
]
```

### Controller

```
app/Http/Controllers/Academic/PensumController.php
```

- `index(Career $career)` — retorna Inertia con `career` (CareerResource), `pensums[]` (PensumResource collection con `withCount('subjects')`), `can{}`
- `store(StorePensumRequest $request, Career $career, CreatePensumAction $action)` — redirige a `academic.pensums.index`
- `update(UpdatePensumRequest $request, Career $career, Pensum $pensum, UpdatePensumAction $action)` — redirige a `academic.pensums.index`
- `destroy(Career $career, Pensum $pensum, DeletePensumAction $action)` — redirige a `academic.pensums.index`

Flash toast en cada operación exitosa y en el guard de eliminación.

---

## Cambios en módulo Career

### `Career` model

Restaurar la relación (actualmente comentada):

```php
public function pensums(): HasMany
{
    return $this->hasMany(Pensum::class);
}
```

### `DeleteCareerAction`

Restaurar el guard (actualmente comentado):

```php
if ($career->pensums()->exists()) {
    // flash error + return false
}
```

### `CareerResource`

Retornar `pensumsCount` real desde `$this->pensums_count` (cargar con `withCount('pensums')` en `CareerController::index`).

---

## Frontend

### Tipos

Agregar a `resources/js/types/academic.ts`:

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

### Composables

```
resources/js/composables/permissions/usePensumPermissions.ts
resources/js/composables/forms/usePensumForm.ts
```

- `usePensumPermissions` — `canCreate`, `canUpdate`, `canDelete` (CASL / page props `can`)
- `usePensumForm` — `create()`, `update(pensum)`, `remove(pensum)` usando Wayfinder routes

### Página

```
resources/js/pages/academic/Pensums/Index.vue
```

Props: `career: Career`, `pensums: Pensum[]`, `can: { createPensum, updatePensum, deletePensum }`

- **Breadcrumb:** Carreras → `{career.name}`
- **Botón "Nuevo pensum"** — visible si `canCreate`
- **Tabla:**
  | Nombre | Tipo | Períodos | Estado | Acciones |
  |---|---|---|---|---|
  | name | Semestral / Anual | total_periods | badge activo/inactivo | botones |
- **Acciones por fila:**
  - Editar → abre `EditPensumModal`
  - Toggle activo → PATCH directo (sin modal, sin confirmación)
  - Ver materias → deshabilitado (placeholder, Part 4)
  - Eliminar → abre `DeletePensumModal`
- **Estado vacío:** mensaje si no hay pensums

### Modales

```
resources/js/components/academic/CreatePensumModal.vue
resources/js/components/academic/EditPensumModal.vue
resources/js/components/academic/DeletePensumModal.vue
```

**CreatePensumModal** — campos:
- Nombre (text, required)
- Tipo de período (select: Semestral / Anual, required)
- Total de períodos (number, 1–20, required)
- Activo (select: Sí / No, default Sí)

**EditPensumModal** — mismos campos, pre-poblados con `:value` / `:selected`.

**DeletePensumModal** — confirmación mostrando `pensum.name`. Si el servidor devuelve error (tiene materias), mostrar el mensaje del toast.

### Actualización Careers/Index.vue

El botón "Ver pensums" (actualmente deshabilitado con comentario "Part 3") pasa a ser un `<Link>` real a `academic.pensums.index({ career: career.id })`.

---

## Error Handling

| Situación | Comportamiento |
|---|---|
| Eliminar carrera con pensums | Flash error toast, redirect back |
| Eliminar pensum con materias | Flash error toast, redirect back (guard en `DeletePensumAction`) |
| `total_periods` fuera de rango | Error de validación en campo |
| `period_type` inválido | Error de validación en campo |

---

## Testing

```
tests/Feature/Academic/PensumControllerTest.php
```

Casos requeridos:

- Usuario no autenticado → redirect a login (index)
- Usuario sin `pensums.view` → 403 (index)
- Usuario con `pensums.view` ve la página con los pensums de la carrera
- Usuario sin `pensums.create` → 403 (store)
- Store falla validación: nombre vacío
- Store falla validación: `period_type` inválido
- Store falla validación: `total_periods` fuera de rango (0, 21)
- Store exitoso → redirect a `academic.pensums.index`, pensum creado en DB
- Update exitoso → nombre/tipo/períodos/activo actualizados
- Delete exitoso (sin materias) → redirect, pensum eliminado
- Delete bloqueado (con materias) → flash error, pensum persiste
- Delete carrera con pensums → bloqueado (guard restaurado en `DeleteCareerAction`)
- Los pensums de una carrera no se muestran al consultar otra carrera
