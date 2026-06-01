# QA Spec — Attendance Module
**Feature:** `15-attendance-module`
**Fecha:** 2026-06-01

---

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
**Test Dusk:** pendiente

---

### UC-QA-02 — Admin crea sesión de recuperación y sube asistencia manual
**Precondición:** Admin autenticado. Existe una sesión con `status: cancelled` en la sección
**Pasos:**
1. Navegar a `/admin/sections/{section}/attendance`
2. Crear sesión de tipo `makeup`, vincular a la sesión cancelada
3. Subir asistencia: todos presentes
4. Guardar
**Resultado esperado:** Sesión makeup en DB con `professor_present: false`. Sesión cancelada vinculada cambia a `status: recovered`. Attendance_records creados con `status: present`.
**Test Dusk:** pendiente

---

### UC-QA-03 — Profesor crea adelanto y se copia asistencia a sesión futura
**Precondición:** Profesor autenticado. Existe una sesión futura regular con `status: scheduled`
**Pasos:**
1. Crear sesión de tipo `advance`, vincular a la sesión futura
2. Pasar asistencia: estudiante A present, B absent
3. Guardar
**Resultado esperado:** Sesión advance con `status: held`. Sesión futura vinculada cambia a `status: advanced`. Se crean attendance_records IGUALES en la sesión futura (copia): A=present, B=absent.
**Test Dusk:** pendiente

---

### UC-QA-04 — Totales de inasistencia por estudiante
**Precondición:** Estudiante B tiene 2 ausencias en sesiones `held` y 1 en sesión `recovered` en la misma sección
**Pasos:**
1. Consultar el total de inasistencias de estudiante B en la sección
**Resultado esperado:** Total = 3 ausencias. Solo sesiones con `status IN (held, recovered, advanced)` cuentan.
**Test Dusk:** pendiente

---

### UC-QA-05 — Validación: no se puede recuperar una sesión ya recuperada
**Precondición:** Sesión X ya tiene `status: recovered` con un makeup vinculado
**Pasos:**
1. Intentar crear un segundo makeup vinculado a la sesión X
**Resultado esperado:** Error de validación 422: "Esta sesión ya fue recuperada". No se crea el segundo makeup.
**Test Dusk:** pendiente
