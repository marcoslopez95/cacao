# HLZ-45 — Guard contra doble "Adelanto" sobre la misma sesión

## Problema

`specs/15-attendance-module/requirements.md` documenta explícitamente el comportamiento esperado: "Crear advance vinculado a una sesión que ya está marcada `advanced`" → "Validación: 'Esta sesión ya fue adelantada'". Esa validación **no existe** en `CreateAdvanceSessionAction::handle()` — a diferencia de `CreateMakeupSessionAction::handle()`, que sí valida contra sesiones ya `Recovered` y lanza `ValidationException`.

El único freno existente es un filtro de presentación: una vez que una sesión pasa a `status: advanced`, el selector "Sesión vinculada" de la UI deja de ofrecerla como candidata. Pero el backend acepta igual una petición directa con ese `linked_session_id` (confirmado en vivo, `specs/qa/attendance/professor-attendance.md`, UC-A06).

**Consecuencia reproducida en vivo:** crear un segundo adelanto vinculado a la misma sesión objetivo la re-marca `advanced` (pisando el `linked_session_id` del primero), y al pasar lista en el segundo adelanto, `TakeAttendanceAction::maybeCopyRecordsToLinkedSession()` pisa silenciosamente — sin ningún conflicto ni aviso — los registros de asistencia que había copiado el primer adelanto en la sesión objetivo.

## Requisitos funcionales

- RF-01: `POST /professor/sections/{section}/attendance/sessions` con `type=advance` y `linked_session_id` de una sesión que ya tiene `status = advanced` responde con error de validación (422), no 200/201.
- RF-02: el mensaje de error es "Esta sesión ya fue adelantada." (mismo patrón que el mensaje ya existente para recuperación duplicada).
- RF-03: crear un adelanto vinculado a una sesión `scheduled` (nunca adelantada) sigue funcionando exactamente igual que hoy (regresión).
- RF-04: el guard aplica tanto si la petición viene del selector normal de la UI como si se envía el `linked_session_id` directamente al endpoint (bypass de UI) — la validación vive en el backend, no depende del filtro de presentación.

## Alcance

**Incluye:** guard de estado en `CreateAdvanceSessionAction::handle()`.

**Excluye explícitamente:**
- Cambios al filtro de presentación de la UI (`guardian/Grades/Index.vue` no aplica aquí — es el selector "Sesión vinculada" del formulario de asistencia, que ya excluye correctamente sesiones `advanced`; no requiere cambios).
- Cambios a `TakeAttendanceAction::maybeCopyRecordsToLinkedSession()` — deja de ser alcanzable una vez que el guard exista, no necesita lógica adicional.
- HLZ-44 (restricción horaria) y HLZ-46 (flujo de cancelación) — cubiertos en el feature `20-attendance-scheduling-and-recovery`.
