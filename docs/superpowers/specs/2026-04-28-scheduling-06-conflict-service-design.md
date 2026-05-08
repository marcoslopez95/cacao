# Spec 06 — Schedule: Modelo y Servicio de Conflictos

**Fecha:** 2026-04-28
**Estado:** Borrador
**Depende de:** Spec 01 (Períodos), Spec 03 (Profesores), Spec 04 (Secciones), módulo de infraestructura (Classrooms)
**Bloquea:** Spec 07 (Schedule CRUD)

---

## Contexto

Esta spec cubre solo la **capa de datos** del módulo de horarios: la tabla `schedules`, su modelo Eloquent, y el servicio de detección de conflictos (`ScheduleConflictService`). No incluye rutas, controladores ni frontend — esos van en Spec 07.

Separar el servicio del CRUD permite testear la lógica de conflictos de forma aislada, con tests unitarios, antes de conectarla al controlador.

---

## Modelo de datos

### `schedules`

| campo | tipo | notas |
|---|---|---|
| `id` | bigint PK | |
| `section_id` | FK → sections | RESTRICT on delete |
| `professor_id` | FK → professors | RESTRICT on delete |
| `classroom_id` | FK → classrooms | RESTRICT on delete |
| `subject_id` | FK → subjects | RESTRICT on delete |
| `day_of_week` | enum: `monday`…`saturday` | |
| `start_time` | time | |
| `end_time` | time | > `start_time` — CHECK constraint |
| `type` | enum: `theory` / `lab` | |
| `valid_from` | date | ≥ `section.period.start_date` |
| `valid_until` | date, nullable | null = activo hasta fin del período |
| `created_at` / `updated_at` | timestamps | |

**CHECK constraints en DB:**
- `end_time > start_time`
- `start_time >= '07:00:00'`
- `end_time <= '18:00:00'`

**Todas las FKs son RESTRICT.**

---

## Enums PHP nuevos

```
app/Enums/DayOfWeek.php            — monday…saturday  con label() en español y order() int
app/Enums/ScheduleSessionType.php  — theory | lab  con label()
```

---

## `ScheduleConflictService`

```
app/Services/Scheduling/ScheduleConflictService.php
```

### Interfaz pública

```php
// Retorna el schedule conflictivo o null si no hay conflicto
public function classroomConflict(Schedule $schedule): ?Schedule

// Retorna el schedule conflictivo o null
public function professorConflict(Schedule $schedule): ?Schedule

// Retorna true si el profesor superaría su límite semanal
public function professorWeeklyHoursExceeded(Schedule $schedule): bool

// Horas semanales actuales del profesor (excluye $excludeId si se pasa)
public function professorCurrentWeeklyHours(Professor $professor, ?int $excludeId = null): float
```

### Lógica de conflicto

Dos slots de horario **se solapan** si tienen el mismo `day_of_week` y sus tiempos se cruzan:
```
A.start_time < B.end_time  AND  B.start_time < A.end_time
```

Un slot existente **puede conflictuar** con el nuevo si:
1. Pertenece a un período con `status != 'closed'`.
2. Su rango de validez se solapa con el del nuevo slot:
   `existing.valid_from <= (new.valid_until ?? new_period.end_date)`
   `new.valid_from <= (existing.valid_until ?? existing_period.end_date)`
3. En creación: `id != null` no aplica. En edición: excluir el propio slot (`WHERE id != schedule.id`).

### Cálculo de horas semanales

Suma de `(end_time - start_time)` en minutos / 60 de todos los slots del profesor donde:
- La sección pertenece a un período `status != 'closed'`
- `valid_until IS NULL OR valid_until >= TODAY`

El slot nuevo suma su propia duración al total.

---

## Archivos

```
app/Enums/DayOfWeek.php
app/Enums/ScheduleSessionType.php
app/Models/Schedule.php                        — belongsTo Section, Professor, Classroom, Subject
database/migrations/XXXX_create_schedules_table.php
database/factories/ScheduleFactory.php         — slots de 45 min por defecto entre 07:00 y 17:45
app/Services/Scheduling/ScheduleConflictService.php
tests/Unit/Scheduling/ScheduleConflictServiceTest.php
```

---

## Testing (Unit)

Tests unitarios Pest — `tests/Unit/Scheduling/ScheduleConflictServiceTest.php`:

### `classroomConflict`
- Detecta solapamiento exacto (misma hora).
- Detecta solapamiento parcial (A empieza dentro de B).
- **No** detecta conflicto cuando los horarios son adyacentes (A termina cuando B empieza).
- **No** detecta conflicto con slots en períodos `closed`.
- **No** se conflictúa consigo mismo (en update, mismo `id`).

### `professorConflict`
- Detecta que el mismo profesor tiene dos clases en distintas secciones a la misma hora.
- **No** detecta conflicto si los horarios son en días distintos.
- **No** detecta conflicto con slots en períodos `closed`.

### `professorWeeklyHoursExceeded`
- Retorna `true` cuando sumar el nuevo slot supera el límite.
- Retorna `false` cuando queda dentro del límite.
- En update, no cuenta el slot que se está editando.

### `professorCurrentWeeklyHours`
- Suma correctamente múltiples slots del mismo profesor.
- Ignora slots de períodos `closed`.
- Ignora slots con `valid_until` en el pasado.
