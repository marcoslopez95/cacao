# Design — Attendance Module
**Feature:** `15-attendance-module`
**Fecha:** 2026-06-01

---

## Decisión de diseño: Opción A — `class_sessions` + `attendance_records`

Se eligió la arquitectura de dos tablas sobre la alternativa de tabla única (`attendances` con solo `enrollment_detail_id + date`) porque:

- Las sesiones de recuperación y adelanto requieren una entidad explícita para rastrear el vínculo entre la sesión cancelada/futura y la sesión que la cubre.
- `professor_present` necesita vivir en la sesión, no en cada registro individual.
- Los reportes futuros (¿cuántas sesiones dictó el profesor? ¿cuántas se recuperaron?) requieren sesiones como entidad de primera clase.
- El temario (`topic`) pertenece a la sesión, no a cada fila de asistencia.

### Opción descartada — `attendances` tabla única
Descartada porque no tiene representación de "sesión cancelada" (solo ausencia de registros), no soporta `professor_present`, y complica las clases adelantadas (no hay a dónde copiar).

### Opción descartada — contador agregado en `enrollment_details`
Descartada porque elimina el historial auditable por fecha.

---

## Modelo de datos

### `class_sessions`
```
id
section_id          FK → sections (RESTRICT)
schedule_id         FK → schedules (RESTRICT, nullable) — null para makeup/advance
date                date
type                enum: regular | makeup | advance
status              enum: scheduled | held | cancelled | recovered | advanced
topic               text (nullable)
professor_present   boolean, default true
linked_session_id   FK → class_sessions (RESTRICT, nullable) — makeup→cancelada, advance→futura
uploaded_by_id      FK → users (RESTRICT, nullable) — quien registró la asistencia
timestamps
```

### `attendance_records`
```
id
class_session_id       FK → class_sessions (RESTRICT)
enrollment_detail_id   FK → enrollment_details (RESTRICT)
status                 enum: present | absent
timestamps
UNIQUE(class_session_id, enrollment_detail_id)
```

---

## Flujos por tipo de sesión

### Regular (profesor presente)
1. Profesor abre su sección → ve lista de sesiones del período
2. Crea nueva sesión: elige fecha, elige slot del horario (`schedule_id`), escribe temario (opcional)
3. Sistema crea sesión con `type: regular`, `status: held`, `professor_present: true`
4. Profesor marca cada estudiante como `present` o `absent`

### Cancelada
1. Al crear la sesión, el profesor (o admin) marca que el profesor no estuvo (`professor_present: false`)
2. La sesión queda en `status: cancelled`
3. La asistencia puede subirse igual (alguien la recogió en papel)

### Recuperación (makeup)
1. Admin/coordinador crea nueva sesión de tipo `makeup`
2. Selecciona la sesión cancelada a recuperar (`linked_session_id`)
3. Sistema automáticamente cambia la cancelada a `status: recovered`
4. Se sube asistencia para la sesión makeup (`professor_present` según quien dictó)

### Adelanto (advance)
1. Profesor crea sesión de tipo `advance` en una fecha previa
2. Selecciona la sesión futura regular que está adelantando (`linked_session_id`)
3. Sistema marca la sesión futura como `status: advanced`
4. Profesor pasa asistencia en la sesión advance
5. Sistema **copia automáticamente** los `attendance_records` del advance a la sesión regular vinculada

---

## Capas técnicas

### Backend
```
app/
  Models/
    ClassSession.php
    AttendanceRecord.php
  Enums/
    ClassSessionType.php    (regular | makeup | advance)
    ClassSessionStatus.php  (scheduled | held | cancelled | recovered | advanced)
    AttendanceStatus.php    (present | absent)
  Policies/
    ClassSessionPolicy.php
  Http/
    Controllers/
      Professor/AttendanceController.php   (index, store session, upsert attendance)
      Admin/AttendanceController.php       (store session, upsert attendance)
    Requests/
      Professor/StoreClassSessionRequest.php
      Professor/UpsertAttendanceRequest.php
      Admin/StoreClassSessionRequest.php
    Resources/
      ClassSessionResource.php
      AttendanceSheetResource.php
    Wrappers/
      ClassSessionWrapper.php
  Actions/
    Attendance/
      CreateClassSessionAction.php
      TakeAttendanceAction.php
      CreateMakeupSessionAction.php    (+ marcar linked como recovered)
      CreateAdvanceSessionAction.php   (+ marcar linked como advanced + copiar records)
```

### Frontend
```
resources/js/
  pages/
    professor/attendance/Index.vue     (lista de sesiones de una sección)
    professor/attendance/Sheet.vue     (pasar lista: tabla estudiantes × present/absent)
    admin/attendance/Index.vue         (subir asistencia manual para sesión)
  composables/
    forms/useClassSessionForm.ts
    forms/useAttendanceForm.ts
  types/
    classSession.ts
    attendanceRecord.ts
```

### Rutas
```
# Profesor
GET  /professor/sections/{section}/attendance            → AttendanceController@index
POST /professor/sections/{section}/attendance/sessions   → AttendanceController@storeSession
PUT  /professor/sections/{section}/attendance/sessions/{session} → AttendanceController@upsertAttendance

# Admin
GET  /admin/sections/{section}/attendance                → Admin\AttendanceController@index
POST /admin/sections/{section}/attendance/sessions       → Admin\AttendanceController@storeSession
PUT  /admin/sections/{section}/attendance/sessions/{session} → Admin\AttendanceController@upsertAttendance
```

---

## Dependencias con otros módulos

- `sections` → sección padre de cada sesión
- `schedules` → slot horario de referencia (optional FK)
- `enrollment_details` → estudiantes inscritos confirmados de la sección
- `Period` → para filtrar secciones activas del profesor en el dashboard
- `Professor` → para validar que el profesor solo accede a sus secciones (Policy)
