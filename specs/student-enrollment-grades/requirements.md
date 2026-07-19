# Requirements — student-enrollment-grades

**Feature ID:** `student-enrollment-grades`
**Origen:** Solicitud directa del usuario (no HLZ)
**Prioridad:** MEDIA
**Depende de:** ninguna (extiende `admin/Students/Show.vue`, feature `student-academic-show`)

---

## Problema

En `/academic/students/{student}` (admin), la sección "Historial de inscripciones" solo muestra período, estado y UC — no hay forma de ver las notas por materia de una inscripción puntual. Además, la card "Promedio acumulado" muestra `students.cumulative_gpa`, una columna estática que nunca se calcula desde las notas reales (solo se setea manualmente en tests/seeders) — por eso el usuario percibe que "no funciona".

---

## Caso de uso aprobado

### UC-01 — Ver detalle de notas de una inscripción

**Precondición:** Usuario admin autenticado, viendo `/academic/students/{student}`.

**Pasos:** Click en "Ver detalle" sobre una fila del historial de inscripciones.

**Resultado esperado:** Nueva página con las notas por materia de esa inscripción (mismo desglose por slot + reparación que ya usa el estudiante en `student/Grades/Index.vue`, pero siempre en tiempo real — el admin ve todo, sin importar la política de visibilidad del equipo).

### UC-02 — Ver promedio del periodo por inscripción

**Resultado esperado:** La tabla de historial de inscripciones agrega una columna "Promedio del periodo" — promedio simple de las notas finales (ya ponderadas por peso de slot) de las materias de esa inscripción. Si ninguna materia tiene nota definitiva aún, se muestra "—".

### UC-03 — Promedio acumulado real

**Resultado esperado:** La card "Promedio acumulado" deja de leer la columna estática `students.cumulative_gpa` y se calcula como promedio simple de los "promedios del periodo" (UC-02) que tengan valor. Si ninguna inscripción tiene promedio calculable, se muestra "—".

---

## Criterios de aceptación

1. `StudentShowResource`: cada elemento de `enrollments[]` expone `period_average: string | null`.
2. `StudentShowResource`: `cumulative_gpa` se calcula (no se lee de la columna) como promedio simple de los `period_average` no nulos de todas las inscripciones del estudiante.
3. Nueva ruta `GET academic/students/{student}/enrollments/{enrollment}` — 404 si la inscripción no pertenece al estudiante de la ruta; autoriza vía `EnrollmentPolicy::view` (admin pasa siempre por `Gate::before`).
4. Nueva vista reutiliza la lógica de cálculo de nota final existente en `GradeCardResource` (extraída a un servicio compartido, sin duplicar código) con `GradeVisibility::RealTime` fijo.
5. Tabla de historial en `admin/Students/Show.vue`: columna "Promedio del periodo" + link "Ver detalle" por fila.
6. Tests de feature: `period_average` y `cumulative_gpa` calculados correctamente (con y sin notas); ruta de detalle autoriza y filtra correctamente; nota final se sigue calculando igual que antes en `GradeCardResource` (no regresión).

## Fuera de alcance

- Recalcular o migrar la columna `students.cumulative_gpa` en base de datos — se deja de leer en esta vista, pero la columna permanece intacta (puede tener otros usos, ej. estudiantes transferidos).
- Promedio ponderado por UC/créditos — se confirmó con el usuario que es promedio simple.
- Edición de notas desde esta vista — es de solo lectura para el admin.
