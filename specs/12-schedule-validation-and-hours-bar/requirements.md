# HLZ-33 + HLZ-34 — Schedule: validación de horario 07:00–18:00 + ProfessorHoursBar en edición

## Contexto

Dos bugs descubiertos durante la auditoría QA del módulo de horarios (2026-05-30),
correspondientes a UC-H16/H17/H18 y UC-H19 del documento `specs/qa/academic/schedules.md`.

---

## Bug 1 — HLZ-33: Horarios fuera de rango 07:00–18:00 explotan en DB sin mensaje amigable

### Síntoma
Si el usuario envía `start_time < 07:00` o `end_time > 18:00`, el backend no valida
en el FormRequest y la restricción CHECK de PostgreSQL lanza una excepción sin
mensaje amigable al usuario.

### Principio del sistema
Ningún error debe llegar desde la DB. Toda restricción de negocio se valida en el
FormRequest con reglas Laravel. Nunca try/catch, nunca excepciones de DB.

### Fix acordado
Agregar las reglas `after_or_equal:07:00` y `before_or_equal:18:00` a `start_time`
y `end_time` en `StoreScheduleRequest` y `UpdateScheduleRequest`.

### Archivos afectados
- `app/Http/Requests/Scheduling/StoreScheduleRequest.php`
- `app/Http/Requests/Scheduling/UpdateScheduleRequest.php`

### Requisitos funcionales
- RF-01: `start_time = 06:59` → error visible en campo `start_time`.
- RF-02: `end_time = 18:01` → error visible en campo `end_time`.
- RF-03: `start_time = 07:00` y `end_time = 18:00` → formulario acepta sin error (límites inclusivos).
- RF-04: Tests Dusk UC-H16, UC-H17, UC-H18 pasan en verde.

---

## Bug 2 — HLZ-34: ProfessorHoursBar ausente en EditScheduleModal

### Síntoma
El modal de edición no muestra la barra de horas semanales del profesor. Al cambiar
el profesor en el select de edición, el admin no tiene visibilidad de cuántas horas
lleva ese profesor, lo que puede llevar a crear conflictos de horas sin advertencia visual.

### Fix acordado
Replicar en `EditScheduleModal.vue` el mismo patrón que usa `CreateScheduleModal.vue`:
- Importar `ProfessorHoursBar`
- Agregar `computed selectedProfessor` que busca en `props.professors` por `form.professor_id`
- Renderizar `<ProfessorHoursBar v-if="selectedProfessor" ...>` debajo del select de profesor

### Archivos afectados
- `resources/js/components/scheduling/EditScheduleModal.vue`

### Requisitos funcionales
- RF-05: Al abrir EditScheduleModal con un horario existente, la ProfessorHoursBar
  aparece mostrando las horas actuales del profesor pre-seleccionado.
- RF-06: Al cambiar el profesor en el select, la barra se actualiza reactivamente.
- RF-07: Test Dusk UC-H19 pasa en verde.
