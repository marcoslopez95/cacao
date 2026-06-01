# Requirements — Attendance Module
**Feature:** `15-attendance-module`
**Fecha:** 2026-06-01

---

## Resumen

El profesor pasa asistencia por sesión de clase (presente/ausente por estudiante inscrito). Las sesiones pueden ser regulares, recuperaciones o adelantos. Los totales de inasistencia son una sumatoria de registros individuales. El módulo contempla ausencias del profesor con `professor_present = false`.

---

## Actores y permisos

| Actor | Acción permitida |
|---|---|
| Profesor | Crear sesión en su propia sección, pasar lista, editar asistencia de sus sesiones, ver historial |
| Admin | Subir asistencia en cualquier sesión (incluye sesiones donde el profesor faltó), crear sesiones de recuperación |
| Coordinador | Igual que admin — vía panel admin (sin portal coordinador en v1) |
| Estudiante | Sin acceso en v1 |
| Representante | Sin acceso en v1 |

---

## Alcance

### Incluye
- Tabla `class_sessions`: sesiones regulares, recuperaciones y adelantos
- Tabla `attendance_records`: registro presente/ausente por estudiante × sesión
- Profesor: crear sesión, pasar lista, ver historial de su sección
- Admin/Coordinador: subir asistencia manual con `professor_present = false`
- Lógica de copia automática de asistencia cuando se registra un adelanto
- Campo `professor_present` en la sesión (explícito, no derivado)

### Excluye explícitamente
- Portal del coordinador (feature separado)
- Estados `tarde` y `justificado` en asistencia (fase 2)
- Módulo de justificativos del estudiante (feature separado)
- Asistencia del profesor como módulo independiente (añadir luego)
- Vista de asistencia para estudiante/representante (fase 2)

---

## Reglas de negocio

1. **Sesión regular** — tiene `schedule_id` que identifica a qué slot del horario recurrente corresponde. El profesor la crea al dictar la clase.
2. **Sesión de recuperación (`type: makeup`)** — tiene `linked_session_id` apuntando a la sesión cancelada que recupera. Al crear el makeup, la sesión cancelada pasa automáticamente a `status: recovered`.
3. **Sesión adelantada (`type: advance`)** — tiene `linked_session_id` apuntando a la sesión futura regular que reemplaza. Al registrar asistencia en el advance, se copia automáticamente a la sesión regular vinculada, y esa sesión pasa a `status: advanced`.
4. **`professor_present`** — es `false` si: (a) el coordinador/admin sube la asistencia, o (b) el profesor sube asistencia de una sesión que él mismo marcó como no dictada.
5. **Unicidad** — solo un registro de asistencia por `(class_session_id, enrollment_detail_id)`. Edición permitida; doble creación no.
6. **Solo estudiantes inscritos** — solo los `enrollment_details` con `status: confirmed` de la sección pueden tener registros de asistencia.
7. **Total de inasistencias** — `COUNT(attendance_records WHERE status = 'absent')` filtrado por `enrollment_detail_id`. Solo sesiones en estado `held`, `recovered` o `advanced` cuentan.
8. **Temario** — campo de texto libre, opcional. Sin validación de estructura.

---

## Casos de error y comportamiento esperado

| Caso | Comportamiento |
|---|---|
| Intentar pasar asistencia en una sección que no es del profesor | 403 Forbidden (Policy) |
| Crear makeup vinculado a una sesión que ya tiene makeup | Validación: "Esta sesión ya fue recuperada" |
| Crear advance vinculado a una sesión que ya está marcada `advanced` | Validación: "Esta sesión ya fue adelantada" |
| Pasar lista dos veces para el mismo estudiante en la misma sesión | 422 con mensaje de unicidad |
| Sección sin `enrollment_details` confirmados | Se muestra lista vacía, sin error |
| Período sin sesiones creadas | Se muestra historial vacío, sin error |
