# QA Spec — Student Dashboard Schedule Fix
**Feature:** 17-student-dashboard-schedule-fix
**Fecha:** 2026-08-30

---

## UCs a cubrir con Dusk

### UC-QA-01 — Dashboard del estudiante un domingo no crashea

**Precondición:** estudiante con inscripción confirmada; fecha del sistema mockeada a un domingo.

**Pasos:**
1. Login como el estudiante.
2. Cargar `/student/dashboard`.

**Resultado esperado:** respuesta 200, sin página de error. `today_schedules` vacío. El resto del dashboard (período, UC del pensum, representantes) se muestra normalmente.

**Test Dusk:** pendiente

---

### UC-QA-02 — Widget "Hoy" excluye materias no confirmadas

**Precondición:** estudiante con dos `EnrollmentDetail` para el día actual: uno `confirmed`, otro `draft` (o `rejected`).

**Pasos:**
1. Login como el estudiante.
2. Cargar `/student/dashboard`.
3. Revisar el widget "Hoy".

**Resultado esperado:** solo aparece la materia con `EnrollmentDetail.status = confirmed`. La materia `draft`/`rejected` no aparece.

**Test Dusk:** pendiente

---

### UC-QA-03 — Regresión: día normal con todo confirmado

**Precondición:** estudiante con todos sus `EnrollmentDetail` en `confirmed`; fecha del sistema en un día de lunes a sábado con clases ese día.

**Pasos:**
1. Login como el estudiante.
2. Cargar `/student/dashboard`.

**Resultado esperado:** comportamiento idéntico al actual — todas las materias del día aparecen en "Hoy", sin regresiones.

**Test Dusk:** pendiente
