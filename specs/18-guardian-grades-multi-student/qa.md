# QA Spec — Guardian Grades Multi-Student
**Feature:** 18-guardian-grades-multi-student
**Fecha:** 2026-08-30

---

## UCs a cubrir con Dusk

### UC-QA-01 — Representante con 2 estudiantes consulta notas del segundo

**Precondición:** representante vinculado a 2 estudiantes `secondary`/`primary`, ambos con inscripción y notas cargadas.

**Pasos:**
1. Login como el representante.
2. Navegar a `/guardian/grades`.
3. Usar el selector de estudiante para elegir el segundo.

**Resultado esperado:** la vista muestra las notas del segundo estudiante, no del primero. La URL/petición incluye `student_id` del segundo.

**Test Dusk:** pendiente

---

### UC-QA-02 — Representante con 1 solo estudiante no ve selector

**Precondición:** representante vinculado a exactamente 1 estudiante.

**Pasos:**
1. Login como el representante.
2. Navegar a `/guardian/grades`.

**Resultado esperado:** notas del único estudiante se muestran directamente, sin selector visible — comportamiento idéntico al actual.

**Test Dusk:** pendiente

---

### UC-QA-03 — `student_id` ajeno al representante es rechazado

**Precondición:** representante vinculado a 1+ estudiantes; existe otro estudiante NO vinculado a este representante.

**Pasos:**
1. Login como el representante.
2. Solicitar `GET /guardian/grades?student_id={ajeno}`.

**Resultado esperado:** 403.

**Test:** Feature test (no requiere Dusk)

---

### UC-QA-04 — Estudiante universitario nunca aparece en vistas de representante (defensivo)

**Precondición:** vínculo guardian↔estudiante forzado vía factory con un estudiante `university` (escenario que no ocurre hoy con datos reales, pero sin guard que lo impida).

**Pasos:**
1. Login como el representante.
2. Cargar `/guardian/dashboard`.
3. Intentar `GET /guardian/grades?student_id={id_universitario}`.

**Resultado esperado:** el estudiante universitario no aparece en la lista de `/guardian/dashboard`; el intento directo en `/guardian/grades` responde 403 (o 404 si no queda ningún estudiante elegible).

**Test:** Feature test (escenario forzado por factory, no requiere Dusk)
