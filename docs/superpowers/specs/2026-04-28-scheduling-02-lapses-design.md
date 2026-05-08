# Spec 02 — Lapsos

**Fecha:** 2026-04-28
**Estado:** Borrador
**Depende de:** Spec 01 (Períodos)
**Bloquea:** nada directamente

---

## Contexto

Los lapsos son subdivisiones de los períodos de tipo `year` (educación básica/media). Un período anual tiene típicamente 3 lapsos, pero el número es variable. Son gestionados desde la misma página de Períodos, no tienen página propia.

---

## Modelo de datos

### `lapses`

| campo | tipo | notas |
|---|---|---|
| `id` | bigint PK | |
| `period_id` | FK → periods | RESTRICT on delete |
| `number` | tinyint unsigned | 1, 2, 3… |
| `name` | string(100) | ej. `"Primer Lapso"` |
| `start_date` | date | ≥ `period.start_date` |
| `end_date` | date | ≤ `period.end_date`, > `start_date` — CHECK constraint |
| `created_at` / `updated_at` | timestamps | |
| unique | (`period_id`, `number`) | |

---

## Reglas de negocio

- Solo se pueden crear lapsos en períodos de tipo `year`.
- Las fechas del lapso deben estar contenidas dentro del rango del período padre.
- El número no puede repetirse dentro del mismo período.
- No se puede crear ni modificar lapsos en un período `closed`.

---

## Backend

Patrón: `FormRequest → Controller → Wrapper → Action → Resource`.

### Rutas (nested bajo período)

```
POST   /scheduling/periods/{period}/lapses              scheduling.lapses.store
PATCH  /scheduling/periods/{period}/lapses/{lapse}      scheduling.lapses.update
DELETE /scheduling/periods/{period}/lapses/{lapse}      scheduling.lapses.destroy
```

No hay `index` propio: los lapsos se retornan dentro de `PeriodResource` cuando `type = 'year'`.

### Archivos PHP

```
app/Models/Lapse.php
database/migrations/XXXX_create_lapses_table.php
database/factories/LapseFactory.php
app/Policies/LapsePolicy.php                               — reutiliza permisos lapses.*
app/Http/Requests/Scheduling/StoreLapseRequest.php         — valida type=year y fechas en rango
app/Http/Requests/Scheduling/UpdateLapseRequest.php
app/Http/Wrappers/Scheduling/LapseWrapper.php
app/Http/Resources/Scheduling/LapseResource.php
app/Actions/Scheduling/CreateLapseAction.php
app/Actions/Scheduling/UpdateLapseAction.php
app/Actions/Scheduling/DeleteLapseAction.php
app/Http/Controllers/Scheduling/LapseController.php
```

### Permisos (agregar a `database/data/permissions.yaml` y `roles.yaml`)

```
lapses.create    lapses.update    lapses.delete
```

No hay `lapses.view` — los lapsos se ven dentro de `PeriodResource`.

### `PeriodResource` actualizado

`PeriodResource` ya existe tras Spec 01. Se actualiza para incluir lapsos cuando `type = 'year'`:

```json
{
  "id": 5,
  "name": "2025-2026",
  "type": "year",
  "lapses": [
    { "id": 1, "number": 1, "name": "Primer Lapso", "startDate": "2025-09-01", "endDate": "2025-11-30" },
    { "id": 2, "number": 2, "name": "Segundo Lapso", "startDate": "2025-12-01", "endDate": "2026-02-28" }
  ]
}
```

---

## Frontend

No se añade página nueva — los lapsos se gestionan desde `Periods/Index.vue` (Spec 01).

Se añade a esa página:
- Fila expandible para períodos de tipo `year`: muestra la lista de lapsos con nombre, rango de fechas y acciones.
- Modales: Crear lapso, Editar lapso, Eliminar lapso.

### Archivos frontend nuevos

```
resources/js/composables/forms/useLapseForm.ts
resources/js/components/scheduling/LapsesPanel.vue           — panel colapsable con lista de lapsos
resources/js/components/scheduling/CreateLapseModal.vue
resources/js/components/scheduling/EditLapseModal.vue
resources/js/components/scheduling/DeleteLapseModal.vue
```

### Archivos frontend modificados

```
resources/js/types/scheduling.ts            — añadir Lapse, actualizar Period para incluir lapses[]
resources/js/pages/scheduling/Periods/Index.vue  — añadir LapsesPanel por cada período year
```

---

## Testing

Feature tests Pest — `tests/Feature/Scheduling/LapseControllerTest.php`:

- Admin puede crear lapso en período `year`.
- Admin **no puede** crear lapso en período `semester` o `trimester` → 422.
- Admin **no puede** crear lapso en período `closed` → 422.
- Fechas fuera del rango del período padre fallan validación.
- Número duplicado en el mismo período falla validación.
- Admin puede actualizar lapso.
- Admin puede eliminar lapso.
- Sin permiso → 403.
