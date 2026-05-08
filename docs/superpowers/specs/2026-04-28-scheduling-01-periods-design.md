# Spec 01 — Períodos

**Fecha:** 2026-04-28
**Estado:** Borrador
**Depende de:** ninguna (entidad base)
**Bloquea:** Lapsos (Spec 02), Secciones (Specs 04-05), Horarios (Specs 06-07)

---

## Contexto

Los períodos son los ciclos académicos con fecha de inicio y fin. Agrupan secciones y horarios. Son la base temporal de todo el módulo de horarios.

Un período tiene un tipo que debe ser compatible con el tipo del pensum asociado (`pensums.period_type`). La migración existente solo tiene `semester` y `year`; se agrega `trimester` aquí.

---

## Modelo de datos

### `periods`

| campo | tipo | notas |
|---|---|---|
| `id` | bigint PK | |
| `name` | string(20) | único — ej. `"2026-1"`, `"2025-2026"` |
| `type` | enum: `semester` / `year` / `trimester` | alineado con `pensums.period_type` |
| `start_date` | date | |
| `end_date` | date | > `start_date` — CHECK constraint en DB |
| `status` | enum: `upcoming` / `active` / `closed` | default `upcoming` |
| `created_at` / `updated_at` | timestamps | |

### Migración previa: `pensums.period_type`

Antes de crear `periods`, agregar `trimester` al CHECK constraint de PostgreSQL:

```sql
ALTER TABLE pensums DROP CONSTRAINT IF EXISTS pensums_period_type_check;
ALTER TABLE pensums ADD CONSTRAINT pensums_period_type_check
  CHECK (period_type IN ('semester', 'year', 'trimester'));
```

---

## Reglas de negocio

- `status` avanza en una sola dirección: `upcoming → active → closed`. No se puede retroceder.
- Solo se puede eliminar un período con `status = 'upcoming'`.
- Pueden coexistir múltiples períodos activos de distinto tipo.
- No se puede eliminar un período que tenga secciones (FK RESTRICT en `sections.period_id`, se aplica en Spec 04).

---

## Backend

Patrón: `FormRequest → Controller → Wrapper → Action → Resource`.

### Enums PHP nuevos

```
app/Enums/PeriodType.php     — semester | year | trimester  con label()
app/Enums/PeriodStatus.php   — upcoming | active | closed   con label() y canTransitionTo()
```

### Rutas

```
GET    /scheduling/periods                   scheduling.periods.index
POST   /scheduling/periods                   scheduling.periods.store
PATCH  /scheduling/periods/{period}          scheduling.periods.update
DELETE /scheduling/periods/{period}          scheduling.periods.destroy
PATCH  /scheduling/periods/{period}/activate scheduling.periods.activate
PATCH  /scheduling/periods/{period}/close    scheduling.periods.close
```

### Archivos PHP

```
app/Models/Period.php
database/migrations/XXXX_add_trimester_to_pensums_period_type.php
database/migrations/XXXX_create_periods_table.php
database/factories/PeriodFactory.php          — estados: semester(), year(), trimester(), active(), closed()
app/Policies/PeriodPolicy.php
app/Http/Requests/Scheduling/StorePeriodRequest.php
app/Http/Requests/Scheduling/UpdatePeriodRequest.php
app/Http/Wrappers/Scheduling/PeriodWrapper.php
app/Http/Resources/Scheduling/PeriodResource.php
app/Actions/Scheduling/CreatePeriodAction.php
app/Actions/Scheduling/UpdatePeriodAction.php
app/Actions/Scheduling/ActivatePeriodAction.php   — valida canTransitionTo(Active)
app/Actions/Scheduling/ClosePeriodAction.php       — valida canTransitionTo(Closed)
app/Actions/Scheduling/DeletePeriodAction.php      — valida status=upcoming, retorna bool
app/Http/Controllers/Scheduling/PeriodController.php
```

### Permisos (agregar a `database/data/permissions.yaml` y `roles.yaml`)

```
periods.view    periods.create    periods.update    periods.delete
```

### `PeriodResource` shape

```json
{
  "id": 1,
  "name": "2026-1",
  "type": "semester",
  "typeLabel": "Semestral",
  "startDate": "2026-02-01",
  "endDate": "2026-07-31",
  "status": "upcoming",
  "statusLabel": "Próximo"
}
```

---

## Frontend

### Página

```
resources/js/pages/scheduling/Periods/Index.vue
```

- Tabla: Nombre · Tipo · Rango de fechas · Estado · Acciones.
- Filtro por tipo (select: Todos / Semestral / Anual / Trimestral).
- Botones de transición de estado en cada fila: **Activar** (solo si `upcoming`) y **Cerrar** (solo si `active`).
- Modales: Crear período, Editar período, Eliminar período.

### Archivos frontend

```
resources/js/types/scheduling.ts                              — exporta Period, PeriodCollection
resources/js/composables/forms/usePeriodForm.ts               — store / update / remove / activate / close
resources/js/composables/permissions/usePeriodPermissions.ts
resources/js/composables/filters/usePeriodFilters.ts          — filtro por type con router.get
resources/js/components/scheduling/CreatePeriodModal.vue
resources/js/components/scheduling/EditPeriodModal.vue
resources/js/components/scheduling/DeletePeriodModal.vue
```

### Sidebar

Nuevo grupo **"Horarios"** con ítem: Períodos.

---

## Testing

Feature tests Pest — `tests/Feature/Scheduling/PeriodControllerTest.php`:

- Admin puede listar períodos (con y sin filtro de tipo).
- Admin puede crear período.
- Admin puede actualizar período.
- Admin puede activar período `upcoming` → responde redirect.
- Admin **no puede** activar período `active` o `closed` → 422.
- Admin puede cerrar período `active`.
- Admin **no puede** cerrar período `upcoming` o `closed` → 422.
- Admin puede eliminar período `upcoming`.
- Admin **no puede** eliminar período `active` o `closed` → redirect con mensaje de error.
- Sin autenticación → redirect `/login`.
- Sin permiso → 403.
- Nombre duplicado falla validación.
- `end_date ≤ start_date` falla validación.
