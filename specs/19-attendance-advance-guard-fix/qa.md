# QA Spec — Attendance Advance Guard Fix
**Feature:** 19-attendance-advance-guard-fix
**Fecha:** 2026-08-31

---

## UCs a cubrir con Dusk

### UC-QA-01 — Crear un adelanto normal sigue funcionando (regresión)

**Precondición:** profesor con una sección y una sesión `scheduled` futura sin adelantar.

**Pasos:**
1. Login como el profesor.
2. Navegar a la sección → "Nueva sesión" → tipo Adelanto → seleccionar la sesión futura como "Sesión vinculada".
3. Confirmar.

**Resultado esperado:** el adelanto se crea, la sesión vinculada pasa a `status: advanced`. Sin errores.

**Test Dusk:** tests/Browser/Attendance/AttendanceQATest.php::UC-QA-03 (regresión — cubre este UC sin duplicar, decisión tomada en modo `pre`)

---

### UC-QA-02 — Segundo adelanto sobre una sesión ya adelantada es rechazado (vía request directo, bypass UI)

**Precondición:** una sesión ya `status: advanced` (con un primer adelanto ya creado).

**Pasos:**
1. Enviar `POST /professor/sections/{section}/attendance/sessions` con `type=advance` y `linked_session_id` de la sesión ya `advanced`, directamente (sin pasar por el selector de UI, que ya la excluye).

**Resultado esperado:** 422, con el mensaje "Esta sesión ya fue adelantada." en el campo `linked_session_id`. No se crea ningún `ClassSession` nuevo. La sesión objetivo no cambia su `linked_session_id`.

**Test:** Feature test (no requiere Dusk — es una validación de backend, sin flujo de UI que ejercitar más allá del ya cubierto en UC-QA-01) — tests/Feature/AttendanceAdvanceGuardFix/Acceptance/AdvanceGuardTest.php::"RF-01/RF-02: POST del endpoint responde 422 con el mensaje exacto cuando la sesión ya fue adelantada"
