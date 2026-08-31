# HLZ-44 + HLZ-46 — Restricción horaria en sesiones regulares + flujo de cancelación

## Problema

**HLZ-44:** ni `ClassSessionPolicy` (`create`, `takeAttendance`), ni `StoreClassSessionRequest`, ni ninguna Action de asistencia validan la fecha/hora de una sesión `Regular` contra el horario real (`Schedule`) de la sección. Confirmado en vivo: se creó una sesión con fecha ~3.5 meses en el futuro y el sistema la etiquetó de inmediato como "CLASE DE HOY", permitiendo pasar lista sin ningún bloqueo. El cálculo de "es la clase actual" ya existe (`Professor\DashboardController::index()`, campo `is_current`), pero solo a nivel de presentación, nunca reutilizado para autorización.

**HLZ-46:** el selector "Sesión vinculada" del tipo "Recuperación" pide elegir "la sesión cancelada que se está recuperando", pero **ningún flujo del sistema pone una sesión en `status: cancelled`** — el selector siempre está vacío. `CreateMakeupSessionAction` está correctamente implementado asumiendo que existirán sesiones `cancelled`, pero el paso previo que las genera nunca se construyó.

## Requisitos funcionales

- RF-01: `POST /professor/sections/{section}/attendance/sessions` con `type=regular` fuera de la ventana horaria real de la sección (día de la semana + hora dentro de algún `Schedule` de esa sección) responde 403.
- RF-02: `PUT /professor/sections/{section}/attendance/sessions/{classSession}` (pasar lista) sobre una sesión `type=regular` fuera de la ventana horaria real responde 403.
- RF-03: crear o pasar lista en sesiones `type=makeup` o `type=advance` no tiene restricción horaria — exentas por diseño (regresión, sin cambios de comportamiento).
- RF-04: lo mismo aplica en el flujo Admin (`/admin/sections/{section}/attendance*`).
- RF-05: existe una acción de cancelar sesión: transiciona `status` de `scheduled` o `held` a `cancelled`.
- RF-06: cancelar una sesión que ya está `cancelled`, `advanced` o `recovered` responde con error de validación (422), sin cambiar su estado.
- RF-07: cancelar una sesión `held` no borra sus `AttendanceRecord` existentes — quedan como historial asociado a la sesión (ahora `cancelled`).
- RF-08: tanto el profesor dueño de la sección como el Admin pueden cancelar una sesión (mismo patrón de autorización que el resto del módulo — `Gate::before` ya concede acceso total a Admin).
- RF-09: una vez cancelada, la sesión aparece como candidata en el selector "Sesión vinculada" de Recuperación, y se puede completar el flujo de Recuperación de punta a punta contra ella.

## Alcance

**Incluye:** guard horario en `ClassSessionPolicy` para sesiones `Regular` (crear y pasar lista); `CancelClassSessionAction` + ruta + botón "Cancelar" en Profesor y Admin.

**Excluye explícitamente:**
- Columna `schedule_id` en `class_sessions` — la ventana horaria se valida contra los `Schedule` de la `Section` directamente (día + hora), sin necesidad de vincular la sesión a un horario específico.
- Restricción horaria en Adelanto/Recuperación — exentos por decisión de negocio.
- Deshacer una cancelación (no hay "descancelar" en este alcance).
- HLZ-45 (doble adelanto) — cubierto en el feature `19-attendance-advance-guard-fix`, ya implementado por separado.
