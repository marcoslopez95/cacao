# QA Spec — Student Schedule View
**Feature:** 21-student-schedule-view
**Fecha:** 2026-09-13

---

## UCs a cubrir con Dusk

### UC-QA-01 — Estudiante con clases confirmadas y vigentes ve el grid semanal completo (desktop)

**Precondición:** estudiante con `Enrollment` confirmado en el período activo, con `EnrollmentDetail::status = confirmed` y secciones con `Schedule` vigente. Viewport desktop.

**Pasos:**
1. Login como el estudiante.
2. Ir a `/student/schedule`.

**Resultado esperado:** se ve el grid semanal (`WeeklyGrid`) con materia, profesor, aula y hora correctos para cada sección inscrita.

**Test Dusk:** `tests/Browser/Academic/StudentScheduleTest.php::UC-QA-01: estudiante con clase confirmada y vigente ve el grid semanal completo en desktop` — PASS (2026-09-13)

---

### UC-QA-02 — Mismo estudiante en viewport mobile ve la lista agrupada por día

**Precondición:** mismo estudiante de UC-QA-01. Viewport mobile.

**Pasos:**
1. Login como el estudiante.
2. Ir a `/student/schedule` en viewport mobile.

**Resultado esperado:** se ve `ScheduleListView` (lista agrupada por día), no el grid.

**Test Dusk:** `tests/Browser/Academic/StudentScheduleTest.php::UC-QA-02: mismo tipo de estudiante en viewport mobile ve la lista agrupada por día en vez del grid` — PASS (2026-09-13)

---

### UC-QA-03 — Estudiante sin período activo o sin enrollment confirmado: estado vacío

**Precondición:** estudiante sin `Enrollment` confirmado para el período activo (o sin período activo en el sistema).

**Pasos:**
1. Login como el estudiante.
2. Ir a `/student/schedule`.

**Resultado esperado:** estado vacío, sin error (HTTP 200, sin excepción).

**Test Dusk:** `tests/Browser/Academic/StudentScheduleTest.php::UC-QA-03: estudiante sin periodo activo ve estado vacio sin error en desktop` + `::UC-QA-03: estudiante sin enrollment confirmado ve estado vacio sin error en mobile` — PASS (2026-09-13, agregado en auditoría de Tasks 4/5)

---

### UC-QA-04 — Una sección con `EnrollmentDetail` en `draft` o `rejected` no aparece en el horario

**Precondición:** estudiante con al menos un `EnrollmentDetail` en `draft` y otro en `rejected`, además de secciones `confirmed`.

**Pasos:**
1. Login como el estudiante.
2. Ir a `/student/schedule`.

**Resultado esperado:** solo las secciones con `EnrollmentDetail::status = confirmed` aparecen; las de `draft`/`rejected` no se muestran.

**Test Dusk:** pendiente

---

### UC-QA-05 — Un `Schedule` con `valid_until` vencido no aparece; uno con `valid_from` futuro tampoco

**Precondición:** sección `confirmed` con un `Schedule` cuyo `valid_until` ya pasó, y otra sección `confirmed` con un `Schedule` cuyo `valid_from` es futuro.

**Pasos:**
1. Login como el estudiante.
2. Ir a `/student/schedule`.

**Resultado esperado:** ninguno de los dos horarios aparece en la vista.

**Test Dusk:** pendiente

---

### UC-QA-06 — El link "Ver horario completo →" del dashboard navega correctamente a `/student/schedule`

**Precondición:** estudiante logueado en `/student/dashboard`.

**Pasos:**
1. Click en "Ver horario completo →".

**Resultado esperado:** navega a `/student/schedule` y la vista carga correctamente.

**Test Dusk:** pendiente — bloqueado por Task 3 (link aún no agregado a `student/Dashboard.vue`). No evaluado en este `task-gate` (Task 2); se verifica en Task 5/Feature Gate.

---

### UC-QA-07 — Estudiante de nivel `primary` o `secondary` accede sin ningún bloqueo

**Precondición:** estudiante con `educational_level = primary` (y otro caso con `secondary`), con horario vigente.

**Pasos:**
1. Login como cada estudiante.
2. Ir a `/student/schedule`.

**Resultado esperado:** acceso permitido sin ninguna restricción de nivel (a diferencia de HLZ-38, que solo restringe auto-inscripción).

**Test Dusk:** `tests/Browser/Academic/StudentScheduleTest.php::UC-QA-07: estudiante de nivel primary accede sin bloqueo y ve el grid en desktop` + `::UC-QA-07: estudiante de nivel secondary accede sin bloqueo y ve el grid en desktop` — PASS (2026-09-13)

---

### UC-QA-08 — Click sobre un evento del horario no dispara navegación ni popover

**Precondición:** estudiante con al menos un evento visible en el grid (desktop) y en la lista (mobile).

**Pasos:**
1. Login como el estudiante.
2. Click sobre un evento del horario en desktop.
3. Repetir en mobile.

**Resultado esperado:** ningún efecto — sin navegación, sin popover, sin cambio de estado visual más allá del hover nativo del componente.

**Test Dusk:** `tests/Browser/Academic/StudentScheduleTest.php::UC-QA-08: click sobre un evento del grid en desktop no dispara navegación ni popover` + `::UC-QA-08: click sobre un evento de la lista en mobile no dispara navegación ni popover` — PASS (2026-09-13)

---

### UC-QA-09 — Usuario con rol Admin, Profesor o Representante accediendo a `/student/schedule`: 403

**Precondición:** usuarios con rol Admin, Profesor y Representante (uno de cada uno).

**Pasos:**
1. Login como cada rol.
2. Intentar acceder a `/student/schedule`.

**Resultado esperado:** 403 en los tres casos (fuera del grupo `role:Estudiante`).

**Test Dusk:** pendiente
