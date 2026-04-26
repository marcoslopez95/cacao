# Academic Catalog — Part 4: Subjects — Design Spec

**Goal:** Implement the Subject module — CRUD completo bajo `/academic/careers/{career}/pensums/{pensum}/subjects`, con gestión de prerrequisitos, siguiendo los patrones establecidos en las partes 1–3.

**Architecture:** Subject es una entidad anidada bajo Pensum. Cinco rutas nested (4 CRUD + 1 sync prerrequisitos), una página Inertia con drill-down desde Pensums/Index. El código de materia se auto-genera server-side en el create (no visible en el formulario de creación) y es editable en el update. Los prerrequisitos se gestionan desde un modal separado con sync completo.

**Tech Stack:** Laravel 13 · Eloquent · Spatie Permission · Vue 3 · Inertia v3 · Wayfinder · Pest v4

---

## Data Model

### `subjects`

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| pensum_id | FK → pensums | cascade delete |
| name | string(255) | |
| code | string(20) | auto-generado en create, editable en update, unique within pensum |
| credits_uc | tinyint | ≥ 1, ≤ 20 |
| period_number | tinyint | 1 .. pensum.total_periods |
| description | text, nullable | |
| timestamps | | |

Sin `coordination_id` — la coordinación se deriva por `materia → pensum → carrera → coordinación`.

### `subject_prerequisites` (pivot auto-referencial)

| Columna | Tipo |
|---|---|
| subject_id | FK → subjects, PK |
| prerequisite_id | FK → subjects, PK |

Constraint de negocio (capa aplicación): el `prerequisite_id` debe pertenecer al **mismo pensum** y tener `period_number < subject.period_number`. No hay constraint de DB para esto.

### Regla de generación de código

```
{career.code}-{period_number}{secuencia_padded_2}
```

- `career.code` viene de `pensum → career → code`
- `secuencia` = número de materias existentes en ese período + 1, padded a 2 dígitos
- Si hay colisión (por ediciones manuales previas), incrementa hasta encontrar código libre
- Ejemplos: `INF-101`, `INF-102`, `INF-201`, `INF-1001` (período 10)
- Cálculo en `CreateSubjectAction` dentro de `DB::transaction`

---

## Routes

Nested bajo el grupo `academic.` existente, dentro del subgrupo `scopeBindings()`:

```
GET    /academic/careers/{career}/pensums/{pensum}/subjects                                academic.subjects.index
POST   /academic/careers/{career}/pensums/{pensum}/subjects                                academic.subjects.store
PATCH  /academic/careers/{career}/pensums/{pensum}/subjects/{subject}                      academic.subjects.update
DELETE /academic/careers/{career}/pensums/{pensum}/subjects/{subject}                      academic.subjects.destroy
POST   /academic/careers/{career}/pensums/{pensum}/subjects/{subject}/prerequisites/sync   academic.subjects.prerequisites.sync
```

---

## Backend

### Model — `app/Models/Subject.php`

- Fillable: `pensum_id`, `name`, `code`, `credits_uc`, `period_number`, `description`
- Relations:
  - `belongsTo(Pensum::class)`
  - `belongsToMany(Subject::class, 'subject_prerequisites', 'subject_id', 'prerequisite_id')->as('prerequisites')` — materias que esta materia requiere
  - `belongsToMany(Subject::class, 'subject_prerequisites', 'prerequisite_id', 'subject_id')->as('dependents')` — materias que requieren a esta

### Pensum model — restauraciones

```php
public function subjects(): HasMany
{
    return $this->hasMany(Subject::class);
}
```

### Policy — `app/Policies/Academic/SubjectPolicy.php`

- `viewAny` → `subjects.view`
- `create` → `subjects.create`
- `update` → `subjects.update`
- `delete` → `subjects.delete`
- `managePrerequisites` → `subjects.manage-prerequisites`

Registrar en `AppServiceProvider`: `Gate::policy(Subject::class, SubjectPolicy::class)`.

### Permissions

Agregar a `database/data/permissions.yaml`:

```yaml
- subjects.view
- subjects.create
- subjects.update
- subjects.delete
- subjects.manage-prerequisites
```

### Form Requests

**`StoreSubjectRequest::rules()`**
```php
'name'          => ['required', 'string', 'max:255'],
'credits_uc'    => ['required', 'integer', 'min:1', 'max:20'],
'period_number' => ['required', 'integer', 'min:1', 'max:' . $this->route('pensum')->total_periods],
'description'   => ['nullable', 'string'],
```
Autoriza vía `$this->user()->can('create', Subject::class)`.

**`UpdateSubjectRequest::rules()`**
```php
'name'          => ['required', 'string', 'max:255'],
'code'          => ['required', 'string', 'max:20', Rule::unique('subjects')->where('pensum_id', $subject->pensum_id)->ignore($subject->id)],
'credits_uc'    => ['required', 'integer', 'min:1', 'max:20'],
'period_number' => ['required', 'integer', 'min:1', 'max:' . $pensum->total_periods],
'description'   => ['nullable', 'string'],
```
Validación adicional en `withValidator`: si `period_number` cambia y algún prerequisito existente tiene `period_number >= nuevo_period_number`, añade error en `period_number`: `"Elimina los prerrequisitos incompatibles antes de cambiar el período."`.

Autoriza vía `$this->user()->can('update', $subject)`.

**`SyncPrerequisitesRequest::rules()`**
```php
'prerequisites'   => ['present', 'array'],
'prerequisites.*' => ['integer', 'exists:subjects,id'],
```
Validación adicional en `withValidator`: cada ID en `prerequisites` debe pertenecer al mismo `pensum_id` que el subject de la ruta, y tener `period_number < $subject->period_number`.

Autoriza vía `$this->user()->can('managePrerequisites', $subject)`.

### Wrapper — `app/Http/Wrappers/Academic/SubjectWrapper.php`

Extiende `Collection`. Getters:
- `getPensumId(): int`
- `getName(): string`
- `getCode(): string` — usado solo en update
- `getCreditsUc(): int`
- `getPeriodNumber(): int`
- `getDescription(): ?string`

### Actions

**`CreateSubjectAction`** — `handle(SubjectWrapper $wrapper, Career $career): Subject`
```php
DB::transaction(function () use ($wrapper, $career): Subject {
    $subject = Subject::create([
        'pensum_id'     => $wrapper->getPensumId(),
        'name'          => $wrapper->getName(),
        'credits_uc'    => $wrapper->getCreditsUc(),
        'period_number' => $wrapper->getPeriodNumber(),
        'description'   => $wrapper->getDescription(),
        'code'          => '', // temporal
    ]);
    $subject->update(['code' => $this->generateCode($career->code, $wrapper->getPeriodNumber(), $wrapper->getPensumId(), $subject->id)]);
    return $subject;
});
```
Método privado `generateCode(string $careerCode, int $period, int $pensumId, int $subjectId): string`:
- `sequence` = `Subject::where('pensum_id', $pensumId)->where('period_number', $period)->where('id', '!=', $subjectId)->count() + 1` (excluye el stub recién insertado para obtener el count real de subjects previos)
- Base: `{careerCode}-{period}{sequence:02d}` — ejemplos: `INF-101`, `INF-202`, `INF-1001` (período 10)
- Si el código generado ya existe en ese pensum (colisión por edición manual previa), incrementa `sequence` hasta encontrar uno libre

**`UpdateSubjectAction`** — `handle(Subject $subject, SubjectWrapper $wrapper): Subject`
Actualiza todos los campos: `name`, `code`, `credits_uc`, `period_number`, `description`.

**`DeleteSubjectAction`** — `handle(Subject $subject): bool`
Guard: si `$subject->dependents()->exists()` → return false. Si no → `$subject->delete()`, return true.

**`SyncPrerequisitesAction`** — `handle(Subject $subject, array $prerequisiteIds): Subject`
`$subject->prerequisites()->sync($prerequisiteIds)`. Retorna el subject con prerequisites recargas.

### Resource — `app/Http/Resources/Academic/SubjectResource.php`

```php
[
    'id'           => $this->id,
    'name'         => $this->name,
    'code'         => $this->code,
    'creditsUc'    => $this->credits_uc,
    'periodNumber' => $this->period_number,
    'description'  => $this->description,
    'prerequisites' => $this->prerequisites->map(fn ($p) => [
        'id'   => $p->id,
        'name' => $p->name,
        'code' => $p->code,
    ])->values()->all(),
]
```

### Controller — `app/Http/Controllers/Academic/SubjectController.php`

- `index(Request $request, Career $career, Pensum $pensum)` — Gate::authorize viewAny; retorna Inertia con `career` (CareerResource), `pensum` (PensumResource), `subjects[]` (SubjectResource collection con prerequisites eager-loaded), `can{}`
- `store(StoreSubjectRequest, Career, Pensum, CreateSubjectAction)` — `new SubjectWrapper($request->validated() + ['pensum_id' => $pensum->id])`; redirect a `academic.subjects.index`
- `update(UpdateSubjectRequest, Career, Pensum, Subject, UpdateSubjectAction)` — redirect
- `destroy(Career, Pensum, Subject, DeleteSubjectAction)` — Gate::authorize delete; flash error si false, flash success si true; redirect
- `syncPrerequisites(SyncPrerequisitesRequest, Career, Pensum, Subject, SyncPrerequisitesAction)` — flash success; redirect a `academic.subjects.index`

---

## Cambios en módulo Pensum

### `DeletePensumAction`

Restaurar el guard (actualmente stub):

```php
if ($pensum->subjects()->exists()) {
    return false;
}
```

### `PensumResource`

Ya retorna `subjectsCount` via `$this->subjects_count ?? 0`. Solo asegurarse de que `PensumController::index` use `withCount('subjects')`.

---

## Frontend

### Tipos — `resources/js/types/academic.ts`

```typescript
export type Subject = {
  id: number
  name: string
  code: string
  creditsUc: number
  periodNumber: number
  description: string | null
  prerequisites: { id: number; name: string; code: string }[]
}
```

### Composables

**`useSubjectPermissions`** — `canCreate`, `canUpdate`, `canDelete`, `canManagePrerequisites`

**`useSubjectForm`** — `remove(subject, pensum, career)` usando `useForm({}).delete(destroy.url({ career, pensum, subject }))`

### Página — `resources/js/pages/academic/Subjects/Index.vue`

Props: `career: Career`, `pensum: Pensum`, `subjects: Subject[]`, `can: { create, update, delete, managePrerequisites }`

- **Breadcrumb:** Académico → Carreras → `{career.name}` → `{pensum.name}`
- **Botón "Nueva materia"** — visible si `canCreate`
- **Filtro de período** — selector `Todos` + 1..pensum.totalPeriods
- **Tabla:**

| Nombre | Código | Período | UC | Prerrequisitos | Acciones |
|---|---|---|---|---|---|
| Cálculo I | INF-101 | 1 | 4 | — | botones |

- **Prerrequisitos**: badges con `code` de cada prereq. Si no tiene, guión.
- **Acciones por fila**: Editar (`canUpdate`), Gestionar prereqs (`canManagePrerequisites`), Eliminar (`canDelete`)
- **Estado vacío**: mensaje si no hay materias

### Modales

**`CreateSubjectModal`** — campos: Nombre, UC (number, 1–20), Período (select 1..pensum.totalPeriods), Descripción (textarea, opcional). Sin campo código. Usa `<Form v-bind="store.form({ career, pensum })"`.

**`EditSubjectModal`** — mismos campos + Código (editable, pre-poblado). Usa `<Form v-bind="update.form({ career, pensum, subject })"`.

**`DeleteSubjectModal`** — confirmación mostrando `subject.name (subject.code)`. `useForm({}).delete()`.

**`PrerequisitesModal`** — recibe `subject: Subject` y `subjects: Subject[]` (todas las materias del pensum).
- Filtra client-side: `subjects.filter(s => s.periodNumber < subject.periodNumber)`
- Agrupa por `periodNumber`
- Multi-select checkboxes, inicializados con `subject.prerequisites.map(p => p.id)`
- Botón "Guardar" → `useForm({ prerequisites: selectedIds }).post(sync.url({ career, pensum, subject }))`
- Si no hay materias elegibles (período 1 o no existe ninguna en períodos anteriores): mensaje "Esta materia no puede tener prerrequisitos."

### Actualización `Pensums/Index.vue`

El botón "Ver materias" (`disabled`, `title="Disponible en la Parte 4"`) pasa a ser:

```vue
<Button
    variant="ghost"
    size="sm"
    icon="book"
    icon-only
    :aria-label="`Ver materias de ${pensum.name}`"
    @click="router.visit(subjectsIndex.url({ career, pensum }))"
/>
```

---

## Error Handling

| Situación | Comportamiento |
|---|---|
| Eliminar pensum con materias | Flash error toast, redirect (guard restaurado) |
| Eliminar materia que es prereq de otra | Flash error toast, redirect |
| Cambiar period_number con prereqs incompatibles | Error de validación en campo `period_number` |
| Prereq de distinto pensum o período >= actual | Error de validación en `prerequisites.*` |
| Código duplicado en el pensum (update) | Error de validación en campo `code` |
| `credits_uc` fuera de rango | Error de validación |
| `period_number` > `pensum.total_periods` | Error de validación |
| Subject de otro pensum | 404 (scopeBindings) |

---

## Testing

### `PensumControllerTest` (adición)

- Eliminar pensum con materias → bloqueado, pensum persiste

### `SubjectControllerTest` — `tests/Feature/Academic/SubjectControllerTest.php`

**Index:**
- Sin autenticar → redirect login
- Sin `subjects.view` → 403
- Con `subjects.view` → ve la página con materias del pensum
- Materias de otro pensum no aparecen
- Subject de otro pensum devuelve 404 en update (`scopeBindings`)

**Store:**
- Sin `subjects.create` → 403
- Falla validación: nombre vacío, UC = 0, UC = 21, period_number = 0, period_number > pensum.total_periods
- Store exitoso → redirect, materia creada, código tiene formato `{career.code}-{period}{seq}`

**Update:**
- Sin `subjects.update` → 403
- Update exitoso → campos actualizados
- Falla: código duplicado dentro del pensum
- Falla: period_number cambia y deja prereqs incompatibles

**Destroy:**
- Sin `subjects.delete` → 403
- Destroy exitoso (sin dependents) → redirect, materia eliminada
- Destroy bloqueado (es prereq de otra) → flash error, materia persiste
- Subject de otro pensum → 404 (`scopeBindings`)

**Sync prerequisites:**
- Sin `subjects.manage-prerequisites` → 403
- Sync exitoso → prereqs reemplazados
- Falla: prereq de otro pensum
- Falla: prereq con period_number >= subject.period_number
- Subject de otro pensum → 404 (`scopeBindings`)
