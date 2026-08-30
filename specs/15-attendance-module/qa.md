# QA Spec — Attendance Module
**Feature:** `15-attendance-module`
**Fecha:** 2026-06-01

---

## Resultado del Feature Gate

### Primera corrida (2026-08-30) — RECHAZADO
0/5 UCs verificados en verde vía Dusk. Los 87 tests Pest (HTTP/Action/DB) seguían en verde — la
lógica de negocio del backend era correcta. Los defectos eran de contrato Inertia↔Vue
(serialización) y de una funcionalidad de UI nunca implementada. Se documentaron HLZ-38, HLZ-39,
HLZ-40 y HLZ-41.

### Re-verificación (2026-08-30) — APROBADO
El implementer corrigió los 4 hallazgos y el reviewer aprobó el código (SOLID/Typing/PHPDoc/Naming/
Limpieza OK, sin observaciones). Se re-corrió el Feature Gate completo: **5/5 UCs en verde vía
Dusk** + 87 tests Pest en verde. Task 15 marcada `[x]`; la feature puede pasar a `completed`.

## UCs a cubrir con Dusk

### UC-QA-01 — Profesor crea sesión regular y pasa asistencia
**Precondición:** Profesor autenticado con sección activa y estudiantes con `enrollment_detail.status = confirmed`
**Pasos:**
1. Navegar a `/professor/sections/{section}/attendance`
2. Crear nueva sesión: fecha hoy, schedule_id del horario, topic "Tema 1: Introducción"
3. Abrir hoja de asistencia de la sesión
4. Marcar estudiante A como `present`, estudiante B como `absent`
5. Guardar
**Resultado esperado:** Sesión en DB con `type: regular`, `status: held`, `professor_present: true`. Dos `attendance_records`: A=present, B=absent. UI muestra confirmación.
**Test Dusk:** `tests/Browser/Attendance/AttendanceQATest.php::UC-QA-01` — **PASS** (incluye además regresión round-trip: reabrir la hoja muestra las marcas realmente guardadas — verifica el fix de HLZ-41)

---

### UC-QA-02 — Admin crea sesión de recuperación y sube asistencia manual
**Precondición:** Admin autenticado. Existe una sesión con `status: cancelled` en la sección
**Pasos:**
1. Navegar a `/admin/sections/{section}/attendance`
2. Crear sesión de tipo `makeup`, vincular a la sesión cancelada
3. Subir asistencia: todos presentes
4. Guardar
**Resultado esperado:** Sesión makeup en DB con `professor_present: false`. Sesión cancelada vinculada cambia a `status: recovered`. Attendance_records creados con `status: present`.
**Test Dusk:** `tests/Browser/Attendance/AttendanceQATest.php::UC-QA-02` — **PASS** (selecciona la sesión cancelada en el nuevo selector "Sesión vinculada"; verifica el fix de HLZ-38 y HLZ-40)

---

### UC-QA-03 — Profesor crea adelanto y se copia asistencia a sesión futura
**Precondición:** Profesor autenticado. Existe una sesión futura regular con `status: scheduled`
**Pasos:**
1. Crear sesión de tipo `advance`, vincular a la sesión futura
2. Pasar asistencia: estudiante A present, B absent
3. Guardar
**Resultado esperado:** Sesión advance con `status: held`. Sesión futura vinculada cambia a `status: advanced`. Se crean attendance_records IGUALES en la sesión futura (copia): A=present, B=absent.
**Test Dusk:** `tests/Browser/Attendance/AttendanceQATest.php::UC-QA-03` — **PASS** (verifica el fix de HLZ-38, HLZ-39 y HLZ-40, más la copia de registros a la sesión futura)

---

### UC-QA-04 — Totales de inasistencia por estudiante
**Precondición:** Estudiante B tiene 2 ausencias en sesiones `held` y 1 en sesión `recovered` en la misma sección
**Pasos:**
1. Consultar el total de inasistencias de estudiante B en la sección
**Resultado esperado:** Total = 3 ausencias. Solo sesiones con `status IN (held, recovered, advanced)` cuentan.
**Test Dusk:** `tests/Browser/Attendance/AttendanceQATest.php::UC-QA-04` — **PASS** (panel de inasistencias visible tras el fix de HLZ-38/HLZ-39; `summaryForSection()` ya estaba correcto y cubierto en Pest)

---

### UC-QA-05 — Validación: no se puede recuperar una sesión ya recuperada
**Precondición:** Sesión X ya tiene `status: recovered` con un makeup vinculado
**Pasos:**
1. Intentar crear un segundo makeup vinculado a la sesión X
**Resultado esperado:** Error de validación 422: "Esta sesión ya fue recuperada". No se crea el segundo makeup.
**Test Dusk:** `tests/Browser/Attendance/AttendanceQATest.php::UC-QA-05` — **PASS**. El selector "Sesión vinculada" ya excluye sesiones `recovered` de las candidatas (defensa de UI, solo lista `cancelled`); el test confirma esa exclusión y además simula una condición de carrera (inyección DOM de la opción stale) para verificar que el backend sigue bloqueando con el mensaje esperado en vez de un 500 genérico.

---

## Hallazgos de la primera corrida (ya corregidos por el implementer, verificados en la re-corrida)

- **HLZ-38** — `section`/`sessions` se pasaban como instancias `JsonResource`/`ResourceCollection`
  crudas a `Inertia::render()`, e Inertia las serializaba envueltas en `{"data": ...}` sin que el
  frontend las desenvolviera. Fix: los controllers llaman `->toArray($request)` antes de pasar las
  props. Verificado con `tests/Browser/Attendance/AttendanceQATest.php` (páginas ya no crashean).
- **HLZ-39** — `ClassSessionResource`, `AttendanceSheetResource` y `AdminPendingSessionResource`
  devolvían snake_case mientras `resources/js/types/attendance.ts` y los componentes Vue esperaban
  camelCase. Fix: las tres Resources devuelven camelCase (`sectionId`, `professorPresent`,
  `heldAt`, `uploadedBy`, `linkedSession`, `hasRecord`, `enrollmentDetailId`, `studentId`,
  `absenceTotals`, `sessionsCounted`, `careerColor`, `teacherName`).
- **HLZ-40** — Ningún modal "Nueva sesión" exponía un selector "Sesión vinculada" para
  makeup/advance. Fix: selector agregado en ambos modales (profesor y admin), con
  `required_if:type,makeup,advance` en los FormRequests y un handler global en `bootstrap/app.php`
  que convierte `InvalidArgumentException` en una respuesta 422/back-with-errors en vez de un 500.
- **HLZ-41** — `Sheet.vue` (profesor y admin) siempre inicializaba `marks` a "present" para todos,
  ignorando `roster[].status`. Fix: `marks` se inicializa desde `st.status ?? 'present'`.
