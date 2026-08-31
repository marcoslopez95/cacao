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

### Validaciones de negocio **ausentes** (confirmadas esta ronda)

1. Ninguna restricción horaria/de fecha — ver **HLZ-44**
2. Adelanto: **no** rechaza vincular a una sesión ya `advanced` — ver **HLZ-45**
3. Ningún flujo real produce sesiones `status: cancelled` — ver **HLZ-46**

---

## UCs de sesión regular

- **UC-A01** — Profesor crea una sesión "Regular" con fecha dentro de su horario habitual: se crea en `status: scheduled`. **Funciona.**
- **UC-A02** — [ESTADO: BUG — ver **HLZ-44**] Profesor crea una sesión "Regular" con fecha muy alejada de la real (probado: ~3.5 meses en el futuro): se crea sin ninguna advertencia y aparece de inmediato etiquetada "CLASE DE HOY", con "Pasar lista" habilitado. Confirmado en vivo.
- **UC-A03** — [ESTADO: BUG — ver **HLZ-44**] Profesor pasa lista sobre una sesión con fecha fuera de su horario real: permitido sin bloqueo. Efecto secundario: `held_at` se sobrescribe silenciosamente con la fecha real del servidor al guardar, descartando la fecha originalmente elegida al crear la sesión. Confirmado en vivo (fecha elegida `15/12/2026` → sobrescrita a la fecha real tras guardar asistencia).
- **UC-A04** — Pasar lista con marcas mixtas (presente/ausente) sobre una sesión regular: se crean/actualizan los `AttendanceRecord` correspondientes, la sesión pasa a `status: held`. **Funciona**, confirmado en vivo.

## UCs de "Adelanto" (advance)

- **UC-A05** — Crear un "Adelanto" vinculado a una sesión futura `scheduled` desde el selector de la UI: se crea correctamente, la sesión vinculada pasa a `status: advanced` **inmediatamente al crear** (no al pasar lista). Al pasar lista en el adelanto, la asistencia se copia automáticamente a la sesión vinculada (`TakeAttendanceAction::maybeCopyRecordsToLinkedSession`). **Funciona** en el caso simple (un solo adelanto por sesión objetivo), confirmado en vivo.
- **UC-A06** — [ESTADO: BUG CRÍTICO — ver **HLZ-45**] Crear un segundo "Adelanto" vinculado a una sesión que ya está `advanced`: el selector de la UI ya no la ofrece como candidata (filtro solo de presentación), pero el backend la acepta igual si se envía `linked_session_id` directamente en la petición — sin ninguna validación. `requirements.md` documenta que esto debería rechazarse con "Esta sesión ya fue adelantada"; no ocurre. Consecuencia reproducida en vivo: pasar lista en el segundo adelanto pisa silenciosamente los registros de asistencia que había copiado el primero en la sesión objetivo, sin ningún conflicto ni aviso.

## UCs de "Recuperación" (makeup)

- **UC-A07** — [ESTADO: BUG — ver **HLZ-46**] Abrir el formulario de "Recuperación": el selector "Sesión vinculada" (que pide elegir "la sesión cancelada que se está recuperando") aparece siempre vacío, para cualquier sección con cualquier cantidad/tipo de sesiones existentes, porque ningún flujo del sistema pone una sesión en `status: cancelled`. En la práctica, **este UC nunca se puede completar** desde la UI normal — pendiente de que se implemente el paso previo de "cancelar sesión".
- **UC-A08** — [no ejercitable hoy, bloqueado por UC-A07] Crear una "Recuperación" vinculada a una sesión `cancelled` real: según el código (`CreateMakeupSessionAction`), debería funcionar correctamente y sí tiene el guard contra re-recuperar (`status === Recovered` → rechaza). No se pudo probar en vivo por falta de datos alcanzables — la lógica en sí parece más sólida que la de Adelanto.

---

## Tests existentes (Feature/Unit/Dusk)

| UC(s) | Archivo | Cobertura |
|---|---|---|
| — | `specs/15-attendance-module/qa.md` (feature-gate, numeración local HLZ-38..41 propia de ese doc, no confundir con `specs/qa/backlog.md`) | 5 UCs Dusk documentados como "APROBADO" al 2026-08-30 — **pero UC-QA-02/05 (recuperación) arrancan de una sesión `cancelled` sembrada directamente en DB**, no alcanzable desde la app real (ver HLZ-46); vale la pena que `cacao_dev` revise esa distinción |
| **UC-A02, A03 (HLZ-44)** | — | pendiente — requiere decidir con el humano si la restricción es dura o blanda antes de escribir el test |
| **UC-A06 (HLZ-45)** | — | pendiente — test de regresión vía request directo (no solo UI) contra `CreateAdvanceSessionAction` |
| **UC-A07 (HLZ-46)** | — | pendiente — depende de que exista primero un flujo real de cancelación |

---

## Notas de implementación pendiente

- Ver `HLZ-44`, `HLZ-45` (crítica) y `HLZ-46` en `specs/qa/backlog.md` — los tres nuevos de esta ronda.
- `specs/15-attendance-module/requirements.md` y `design.md` documentan comportamiento (validación de doble-adelanto, columna `schedule_id`, flujo de cancelación) que el código no implementa — recomendado que `cacao_dev` reconcilie esos documentos con el estado real antes de marcar el feature-gate como "APROBADO" sin matices.
- Notas menores fuera del pedido original (dashboard "Horas / Semana" negativo, doble-booking de horario, `/admin/attendance*` sin `role:Admin` explícito) quedan documentadas en `debug.md`, sección "Ronda 2".
