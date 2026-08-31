# QA Spec — Student Dashboard Schedule Fix
**Feature:** 17-student-dashboard-schedule-fix
**Fecha:** 2026-08-30

---

**Nota de cobertura:** los 3 UCs son de solo lectura (sin formulario/botón de guardar) —
decisión tomada en modo `pre`: no aplica Dusk para esta feature. El Feature test contra la
ruta real (`route('student.dashboard')`) ya prueba el ciclo completo
request → controller → JSON de Inertia, verificado en modo `feature-gate` el 2026-08-30.

## UCs a cubrir con Dusk

### UC-QA-01 — Dashboard del estudiante un domingo no crashea

**Precondición:** estudiante con inscripción confirmada; fecha del sistema mockeada a un domingo.

**Pasos:**
1. Login como el estudiante.
2. Cargar `/student/dashboard`.

**Resultado esperado:** respuesta 200, sin página de error. `today_schedules` vacío. El resto del dashboard (período, UC del pensum, representantes) se muestra normalmente.

**Test Dusk:** no aplica (UC de solo lectura). Cubierto por Feature test:
`tests/Feature/StudentDashboardScheduleFix/Acceptance/DashboardScheduleFixTest.php::RF-01: dashboard un domingo responde 200 con today_schedules vacío` — PASS (2026-08-30).

---

### UC-QA-02 — Widget "Hoy" excluye materias no confirmadas

**Precondición:** estudiante con dos `EnrollmentDetail` para el día actual: uno `confirmed`, otro `draft` (o `rejected`).

**Pasos:**
1. Login como el estudiante.
2. Cargar `/student/dashboard`.
3. Revisar el widget "Hoy".

**Resultado esperado:** solo aparece la materia con `EnrollmentDetail.status = confirmed`. La materia `draft`/`rejected` no aparece.

**Test Dusk:** no aplica (UC de solo lectura). Cubierto por Feature tests:
`tests/Feature/StudentDashboardScheduleFix/Acceptance/DashboardScheduleFixTest.php::RF-03: today_schedules solo incluye EnrollmentDetail confirmado, excluye draft` y
`::RF-03: today_schedules excluye EnrollmentDetail rechazado` — ambos PASS (2026-08-30).

---

### UC-QA-03 — Regresión: día normal con todo confirmado

**Precondición:** estudiante con todos sus `EnrollmentDetail` en `confirmed`; fecha del sistema en un día de lunes a sábado con clases ese día.

**Pasos:**
1. Login como el estudiante.
2. Cargar `/student/dashboard`.

**Resultado esperado:** comportamiento idéntico al actual — todas las materias del día aparecen en "Hoy", sin regresiones.

**Test Dusk:** no aplica (UC de solo lectura). Cubierto por Feature test:
`tests/Feature/StudentDashboardScheduleFix/Acceptance/DashboardScheduleFixTest.php::RF-02: dashboard un dia habil calcula periodo, inscripcion, uc y horario del dia sin cambios` — PASS (2026-08-30).
