# QA — Asistencia como profesor (sesiones regulares, adelanto, recuperación)
**Fecha de definición:** 2026-08-31
**Vista:** `/professor/attendance`, `/professor/sections/{section}/attendance`, `/professor/sections/{section}/attendance/sessions/{classSession}`
**Auditor:** QA Manager

---

## Contexto

El módulo de asistencia gira en torno a `ClassSession` (una "sesión de clase" con `type`: `regular`/`makeup`/`advance` y `status`: `scheduled`/`held`/`cancelled`/`recovered`/`advanced`) y `AttendanceRecord` (una marca `present`/`absent` por `enrollment_detail_id`, única por `(class_session_id, enrollment_detail_id)`). Un profesor gestiona la asistencia de sus propias secciones (`Section::main_teacher_id`); el mismo flujo existe en paralelo para Admin bajo `/admin/sections/{section}/attendance*` con las mismas 4 acciones (index/storeSession/sheet/upsertAttendance), interceptado por `Gate::before` para dar acceso total al Admin.

**Regla de negocio confirmada con el humano (2026-08-31):** un profesor solo debería poder marcar asistencia en la clase que tiene actualmente (según su horario). **El código actual no aplica esta restricción en ningún punto** (Policy, FormRequest, Action) — ver `HLZ-44` en `specs/qa/backlog.md`.

### Rutas (profesor)

| Método | URI | Controller@método | Middleware |
|---|---|---|---|
| GET | `/professor/attendance` | `Professor\AttendanceSectionsController@index` | `auth,verified,role:Profesor,Coordinador de Area` |
| GET | `/professor/sections/{section}/attendance` | `Professor\AttendanceController@index` | ídem |
| POST | `/professor/sections/{section}/attendance/sessions` | `Professor\AttendanceController@storeSession` | ídem |
| GET | `/professor/sections/{section}/attendance/sessions/{classSession}` | `Professor\AttendanceController@sheet` | ídem |
| PUT | `/professor/sections/{section}/attendance/sessions/{classSession}` | `Professor\AttendanceController@upsertAttendance` | ídem |

Equivalente admin bajo `/admin/*` — **sin `role:Admin` explícito en el middleware** (nota menor, ver debug.md ronda 2), depende de `ClassSessionPolicy` + `Gate::before`.

### Validaciones de negocio ya implementadas

1. Pertenencia de sección al profesor (`ClassSessionPolicy`, las 4 acciones)
2. `linked_session_id` requerido para `makeup`/`advance` (`StoreClassSessionRequest`)
3. Recuperación: rechaza vincular a una sesión ya `recovered` (`CreateMakeupSessionAction`)
4. Restricción única `(class_session_id, enrollment_detail_id)` en `attendance_records`
5. Adelanto: rechaza vincular a una sesión ya `advanced` (`CreateAdvanceSessionAction`) — resuelto en feature `19-attendance-advance-guard-fix`, ver **HLZ-45**

5. Sesiones Regular: crear/pasar lista exige que el momento actual caiga dentro de un `Schedule` real de la sección (`ClassSessionPolicy::isWithinScheduleWindow()`) — resuelto en feature `20-attendance-scheduling-and-recovery`, ver **HLZ-44**
6. Existe flujo real de cancelación de sesión (`CancelClassSessionAction` + `PATCH .../attendance/sessions/{classSession}/cancel`, Profesor y Admin) — resuelto en feature `20-attendance-scheduling-and-recovery`, ver **HLZ-46**

### Validaciones de negocio — histórico (ya resueltas, ver arriba)

Anteriormente ausentes, ahora cerradas por la ronda `20-attendance-scheduling-and-recovery`:
ninguna restricción horaria/de fecha (HLZ-44); ningún flujo real produce sesiones
`status: cancelled` (HLZ-46).

---

## UCs de sesión regular

- **UC-A01** — Profesor crea una sesión "Regular" con fecha dentro de su horario habitual: se crea en `status: scheduled`. **Funciona.**
- **UC-A02** — [ESTADO: RESUELTO — ver **HLZ-44**, feature `20-attendance-scheduling-and-recovery`] Profesor crea una sesión "Regular" con fecha muy alejada de la real (probado: ~3.5 meses en el futuro): antes se creaba sin ninguna advertencia. Ahora `ClassSessionPolicy::create()` exige que el momento actual (día de la semana + hora) caiga dentro de un `Schedule` real de la sección; fuera de esa ventana responde 403 y no se crea ningún `ClassSession`. Verificado en `tests/Feature/AttendanceSchedulingAndRecovery/Acceptance/ScheduleWindowGuardTest.php` (RF-01, incluyendo casos de borde inclusivos start_time/end_time) y Dusk `AttendanceSchedulingAndRecoveryTest.php::UC-QA-01`.
- **UC-A03** — [ESTADO: RESUELTO — ver **HLZ-44**, feature `20-attendance-scheduling-and-recovery`] Profesor pasa lista sobre una sesión con fecha fuera de su horario real: antes permitido sin bloqueo. Ahora `ClassSessionPolicy::takeAttendance()` aplica el mismo guard horario para sesiones `type=regular` (Makeup/Advance quedan exentos por diseño, RF-03). Verificado en `ScheduleWindowGuardTest.php` (RF-02).
- **UC-A04** — Pasar lista con marcas mixtas (presente/ausente) sobre una sesión regular: se crean/actualizan los `AttendanceRecord` correspondientes, la sesión pasa a `status: held`. **Funciona**, confirmado en vivo.

## UCs de "Adelanto" (advance)

- **UC-A05** — Crear un "Adelanto" vinculado a una sesión futura `scheduled` desde el selector de la UI: se crea correctamente, la sesión vinculada pasa a `status: advanced` **inmediatamente al crear** (no al pasar lista). Al pasar lista en el adelanto, la asistencia se copia automáticamente a la sesión vinculada (`TakeAttendanceAction::maybeCopyRecordsToLinkedSession`). **Funciona** en el caso simple (un solo adelanto por sesión objetivo), confirmado en vivo. Cubierto además por regresión Dusk `UC-QA-03` (`tests/Browser/Attendance/AttendanceQATest.php`), verificado en verde tras aplicar el guard de HLZ-45.
- **UC-A06** — [ESTADO: RESUELTO — ver **HLZ-45**, feature `19-attendance-advance-guard-fix`] Crear un segundo "Adelanto" vinculado a una sesión que ya está `advanced`: el selector de la UI ya no la ofrece como candidata (filtro solo de presentación), pero el backend la aceptaba igual si se enviaba `linked_session_id` directamente en la petición. `CreateAdvanceSessionAction::handle()` ahora valida `linked_session_id` contra `status === ClassSessionStatus::Advanced` y lanza `ValidationException` con el mensaje "Esta sesión ya fue adelantada." — mismo patrón que el guard ya existente en `CreateMakeupSessionAction` contra `status === Recovered`. Verificado en `tests/Feature/AttendanceAdvanceGuardFix/Acceptance/AdvanceGuardTest.php` (5 tests: guard a nivel Action, guard a nivel HTTP JSON con 422, guard a nivel HTTP form/Inertia con bypass del selector, y regresión de creación normal — todos en verde). Regresión del camino feliz confirmada además vía Dusk (`UC-QA-03` en `AttendanceQATest.php`).

## UCs de "Recuperación" (makeup)

- **UC-A07** — [ESTADO: RESUELTO — ver **HLZ-46**, feature `20-attendance-scheduling-and-recovery`] Abrir el formulario de "Recuperación": antes el selector "Sesión vinculada" aparecía siempre vacío porque ningún flujo del sistema ponía una sesión en `status: cancelled`. Ahora existe `CancelClassSessionAction` (botón "Cancelar" en Profesor y Admin, `PATCH .../attendance/sessions/{classSession}/cancel`, transiciona `scheduled`/`held` → `cancelled`), y la sesión recién cancelada aparece como candidata real en el selector. Verificado en `tests/Feature/AttendanceSchedulingAndRecovery/Acceptance/CancelClassSessionTest.php` (RF-05/RF-06/RF-07/RF-08) y Dusk `AttendanceSchedulingAndRecoveryTest.php::UC-QA-04`.
- **UC-A08** — [ESTADO: RESUELTO — ver **HLZ-46**, feature `20-attendance-scheduling-and-recovery`] Crear una "Recuperación" vinculada a una sesión `cancelled` real, de punta a punta (cancelar → crear makeup → pasar lista): funciona correctamente, la sesión objetivo pasa a `status: recovered` y conserva ese estado terminal tras la Recuperación. Verificado en `CancelClassSessionTest.php::RF-09` y Dusk `AttendanceSchedulingAndRecoveryTest.php::UC-QA-07`.

---

## Tests existentes (Feature/Unit/Dusk)

| UC(s) | Archivo | Cobertura |
|---|---|---|
| — | `specs/15-attendance-module/qa.md` (feature-gate, numeración local HLZ-38..41 propia de ese doc, no confundir con `specs/qa/backlog.md`) | 5 UCs Dusk documentados como "APROBADO" al 2026-08-30 — **pero UC-QA-02/05 (recuperación) arrancan de una sesión `cancelled` sembrada directamente en DB**, no alcanzable desde la app real (ver HLZ-46); vale la pena que `cacao_dev` revise esa distinción |
| **UC-A02, A03 (HLZ-44)** | `tests/Feature/AttendanceSchedulingAndRecovery/Acceptance/ScheduleWindowGuardTest.php` | **RESUELTO** — 12 tests Pest (RF-01 crear, RF-02 pasar lista, bordes inclusivos, RF-03 regresión Makeup/Advance exentos, RF-04 flujo Admin) en verde. Dusk `AttendanceSchedulingAndRecoveryTest.php::UC-QA-01` |
| **UC-A06 (HLZ-45)** | `tests/Feature/AttendanceAdvanceGuardFix/Acceptance/AdvanceGuardTest.php` | **RESUELTO** — 5 tests Pest (Action + HTTP JSON + HTTP form/Inertia + 2 regresiones) en verde. Regresión Dusk en `AttendanceQATest.php::UC-QA-03` |
| **UC-A07, A08 (HLZ-46)** | `tests/Feature/AttendanceSchedulingAndRecovery/Acceptance/CancelClassSessionTest.php` | **RESUELTO** — 12 tests Pest (RF-05 Action, RF-06 rechazo cancelled/advanced/recovered, RF-07 preserva asistencia, RF-08 autorización Profesor+Admin, RF-09 recuperación punta a punta) en verde. Dusk `AttendanceSchedulingAndRecoveryTest.php::UC-QA-04/UC-QA-07` |

### Regresión corregida en esta ronda (fixtures, no lógica de app)

`tests/Browser/Attendance/AttendanceQATest.php::UC-QA-01` (feature 15, preexistente) empezó a
fallar tras el guard horario nuevo — su fixture `attProfessorSection()` no crea ningún `Schedule`
para la sección. Confirmado con evidencia Dusk (console log: `403 (Forbidden)` en
`POST .../attendance/sessions`, ningún `ClassSession` creado). Corregido agregando
`attWithinScheduleWindow()`, que crea un `Schedule` real cubriendo el horario académico completo
(`07:00:00`–`18:00:00`, el máximo permitido por los check constraints de la tabla `schedules`) del
día real en que corre la suite — no se puede usar `Carbon::setTestNow()` en Dusk porque el browser
golpea un proceso de servidor separado del proceso de test. 5/5 UCs de ese archivo en verde tras
el fix.

---

## Notas de implementación pendiente

- `HLZ-44`, `HLZ-45` (crítica) y `HLZ-46` en `specs/qa/backlog.md` — los tres de la ronda anterior, ahora **resueltos** (`19-attendance-advance-guard-fix` y `20-attendance-scheduling-and-recovery`). Sin bugs pendientes conocidos en este flujo a la fecha.
- `specs/15-attendance-module/requirements.md` y `design.md` documentan comportamiento (validación de doble-adelanto, columna `schedule_id`, flujo de cancelación) que el código no implementa — recomendado que `cacao_dev` reconcilie esos documentos con el estado real antes de marcar el feature-gate como "APROBADO" sin matices.
- Notas menores fuera del pedido original (dashboard "Horas / Semana" negativo, doble-booking de horario, `/admin/attendance*` sin `role:Admin` explícito) quedan documentadas en `debug.md`, sección "Ronda 2".
