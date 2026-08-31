# QA Spec — Attendance Scheduling and Recovery
**Feature:** 20-attendance-scheduling-and-recovery
**Fecha:** 2026-08-31

---

## UCs a cubrir con Dusk

### UC-QA-01 — Crear sesión Regular fuera del horario real responde 403

**Precondición:** profesor con una sección cuyo `Schedule` real no cubre el momento actual (día/hora del sistema en la prueba).

**Pasos:**
1. Login como el profesor.
2. Intentar crear una sesión "Regular".

**Resultado esperado:** 403. Ningún `ClassSession` nuevo en DB.

**Test Dusk:** tests/Browser/Attendance/AttendanceSchedulingAndRecoveryTest.php::UC-QA-01
**Última verificación:** 2026-08-31

---

### UC-QA-02 — Pasar lista en sesión Regular fuera del horario real responde 403

**Precondición:** sesión `Regular` existente, momento actual fuera de la ventana horaria de la sección.

**Pasos:**
1. Login como el profesor.
2. Intentar pasar lista en esa sesión.

**Resultado esperado:** 403. `AttendanceRecord` no se crean/actualizan.

**Test:** tests/Feature/AttendanceSchedulingAndRecovery/Acceptance/ScheduleWindowGuardTest.php (RF-02)
**Última verificación:** 2026-08-31

---

### UC-QA-03 — Adelanto y Recuperación siguen funcionando fuera de horario (regresión)

**Precondición:** sesiones candidatas para Adelanto (`scheduled` futura) y Recuperación (`cancelled`), momento actual fuera de cualquier horario de la sección.

**Pasos:**
1. Login como el profesor.
2. Crear un Adelanto y una Recuperación, pasar lista en ambos.

**Resultado esperado:** ambos flujos completan sin ningún bloqueo — sin regresión respecto al comportamiento actual.

**Test Dusk:** tests/Browser/Attendance/AttendanceSchedulingAndRecoveryTest.php::UC-QA-03
**Última verificación:** 2026-08-31

---

### UC-QA-04 — Cancelar una sesión `scheduled` la deja disponible para Recuperación

**Precondición:** sesión `scheduled` sin asistencia tomada.

**Pasos:**
1. Login como el profesor.
2. Click "Cancelar" sobre la sesión.
3. Abrir "Nueva sesión" → Recuperación.

**Resultado esperado:** la sesión pasa a `status: cancelled`; aparece en el selector "Sesión vinculada" de Recuperación.

**Test Dusk:** tests/Browser/Attendance/AttendanceSchedulingAndRecoveryTest.php::UC-QA-04
**Última verificación:** 2026-08-31

---

### UC-QA-05 — Cancelar una sesión `held` preserva la asistencia ya tomada

**Precondición:** sesión `held` con al menos un `AttendanceRecord`.

**Pasos:**
1. Login como el profesor.
2. Click "Cancelar" sobre la sesión.

**Resultado esperado:** `status: cancelled`; los `AttendanceRecord` existentes de esa sesión siguen en DB sin cambios.

**Test:** tests/Feature/AttendanceSchedulingAndRecovery/Acceptance/CancelClassSessionTest.php (RF-05/RF-07)
**Última verificación:** 2026-08-31

---

### UC-QA-06 — Cancelar una sesión `advanced`/`recovered`/ya `cancelled` es rechazado

**Precondición:** sesiones en cada uno de esos 3 estados.

**Pasos:**
1. Intentar cancelar cada una.

**Resultado esperado:** 422 con mensaje "Esta sesión no se puede cancelar." en cada caso. Estado sin cambios.

**Test:** tests/Feature/AttendanceSchedulingAndRecovery/Acceptance/CancelClassSessionTest.php (RF-06)
**Última verificación:** 2026-08-31

---

### UC-QA-07 — Recuperación de punta a punta contra una sesión recién cancelada

**Precondición:** sesión cancelada vía UC-QA-04.

**Pasos:**
1. Crear Recuperación vinculada a esa sesión.
2. Pasar lista en la Recuperación.

**Resultado esperado:** la sesión objetivo pasa a `status: recovered`; la asistencia se refleja correctamente (mismo mecanismo de copia que ya existe para Adelanto/Recuperación).

**Test Dusk:** tests/Browser/Attendance/AttendanceSchedulingAndRecoveryTest.php::UC-QA-07
**Última verificación:** 2026-08-31
