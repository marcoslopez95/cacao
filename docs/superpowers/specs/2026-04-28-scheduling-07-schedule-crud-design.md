# Spec 07 — Schedule CRUD y Frontend

**Fecha:** 2026-04-28
**Estado:** Borrador
**Depende de:** Spec 06 (Schedule model + ScheduleConflictService)
**Bloquea:** Módulo de inscripciones

---

## Contexto

Esta spec cubre el CRUD completo de horarios: rutas, controlador, validación de conflictos integrada en los FormRequests, y la página Vue con la cuadrícula semanal.

La lógica de conflictos ya está encapsulada en `ScheduleConflictService` (Spec 06). Aquí solo se conecta al ciclo de request.

---

## Reglas de negocio (validación en FormRequest)

### Consistencia de `subject_id`
- Sección `university`: `subject_id` debe coincidir con `section.subject_id`.
- Sección `school`: debe existir un registro en `section_subjects` para `(section_id, subject_id)`.

### Consistencia de `professor_id`
- Sección `university`: cualquier profesor activo.
- Sección `school` con especialista asignado: el profesor debe ser el especialista o el docente de aula.
- Sección `school` sin especialista: el profesor debe ser el docente de aula.

### Consistencia de fechas
- `valid_from` ≥ `section.period.start_date`.
- `valid_until` (si no null) ≤ `section.period.end_date` y ≥ `valid_from`.

### Conflictos (hard stop — HTTP 422)
Los tres checks de `ScheduleConflictService` se ejecutan en `withValidator()`:
1. Aula ocupada → `"El aula {identifier} ya tiene clase el {día} de {start}–{end}."`
2. Profesor ocupado → `"El profesor {nombre} ya tiene clase el {día} de {start}–{end}."`
3. Límite semanal superado → `"El profesor {nombre} superaría su límite de {limit}h/semana ({actual}h actuales + {new}h nuevas)."`

---

## Backend

### Rutas

```
GET    /scheduling/schedules                  scheduling.schedules.index
POST   /scheduling/schedules                  scheduling.schedules.store
PATCH  /scheduling/schedules/{schedule}       scheduling.schedules.update
DELETE /scheduling/schedules/{schedule}       scheduling.schedules.destroy
```

Filtros soportados en `index` (query params): `section_id`, `professor_id`, `period_id`, `day_of_week`.

### Archivos PHP

```
app/Http/Requests/Scheduling/StoreScheduleRequest.php    — valida reglas + invoca ScheduleConflictService
app/Http/Requests/Scheduling/UpdateScheduleRequest.php   — idem, excluye el schedule actual
app/Http/Wrappers/Scheduling/ScheduleWrapper.php
app/Http/Resources/Scheduling/ScheduleResource.php
app/Actions/Scheduling/CreateScheduleAction.php
app/Actions/Scheduling/UpdateScheduleAction.php
app/Actions/Scheduling/DeleteScheduleAction.php
app/Http/Controllers/Scheduling/ScheduleController.php
app/Policies/SchedulePolicy.php
```

### Permisos (agregar a `database/data/permissions.yaml` y `roles.yaml`)

```
schedules.view    schedules.create    schedules.update    schedules.delete
```

### `ScheduleResource` shape

```json
{
  "id": 1,
  "section": { "id": 3, "code": "01", "type": "university" },
  "professor": { "id": 1, "user": { "name": "Carlos García" } },
  "classroom": { "id": 5, "identifier": "A-201" },
  "subject": { "id": 7, "name": "Cálculo I", "code": "MAT-101" },
  "dayOfWeek": "monday",
  "dayLabel": "Lunes",
  "startTime": "08:00",
  "endTime": "08:45",
  "type": "theory",
  "typeLabel": "Teórica",
  "validFrom": "2026-02-01",
  "validUntil": null
}
```

---

## Frontend

### Página

```
resources/js/pages/scheduling/Schedules/Index.vue
```

- **Filtros superiores:** período (select obligatorio), sección (select dependiente del período), y opcionalmente profesor.
- **Vista:** tabla semanal Lun–Sáb, filas por hora (07:00–18:00 en intervalos de 1h). Cada slot ocupa las celdas correspondientes a su duración.
- **Slot card:** muestra materia, profesor y aula. Color según tipo (teoría = azul claro, lab = verde claro).
- **Crear:** clic en celda vacía abre modal con hora pre-rellenada.
- **Editar/Eliminar:** clic en slot existente abre modal de edición.
- **Errores de conflicto:** mostrados inline dentro del modal (no como toast), con nombre del slot conflictivo.
- **Indicador de carga:** para el profesor seleccionado en el formulario, muestra `X h de Y h máx` (barra de progreso).

### Archivos frontend

```
resources/js/types/scheduling.ts                                — añadir Schedule, ScheduleCollection
resources/js/composables/forms/useScheduleForm.ts
resources/js/composables/permissions/useSchedulePermissions.ts
resources/js/composables/filters/useScheduleFilters.ts
resources/js/components/scheduling/WeeklyGrid.vue               — cuadrícula Lun–Sáb × 07–18h
resources/js/components/scheduling/ScheduleSlot.vue             — tarjeta de slot dentro de la cuadrícula
resources/js/components/scheduling/CreateScheduleModal.vue
resources/js/components/scheduling/EditScheduleModal.vue
resources/js/components/scheduling/DeleteScheduleModal.vue
resources/js/components/scheduling/ProfessorHoursBar.vue        — barra de progreso horas semanales
```

### Sidebar

Añadir ítem **Horarios** al grupo "Horarios".

---

## Testing

Feature tests Pest — `tests/Feature/Scheduling/ScheduleControllerTest.php`:

- Admin puede listar horarios con filtros.
- Admin puede crear slot sin conflictos.
- Admin **no puede** crear slot con aula ocupada → 422 con mensaje de aula.
- Admin **no puede** crear slot con profesor ocupado → 422 con mensaje de profesor.
- Admin **no puede** crear slot que supera límite semanal del profesor → 422.
- Al actualizar, un slot no conflictúa consigo mismo.
- Admin puede eliminar slot.
- `subject_id` inconsistente con la sección → 422.
- `valid_from` fuera del rango del período → 422.
- Sin autenticación → redirect `/login`.
- Sin permiso → 403.
