# Feature completada

**Feature:** `09-admin-students`
**Plan:** `specs/admin-students/tasks.md`
**Estado:** COMPLETADA — 3/3 tasks implementadas

## Tareas

- [x] Task 1 — Backend: StudentController, StudentListResource, ruta, wayfinder
- [x] Task 2 — Frontend: types, useStudentFilters, Students/Index.vue
- [x] Task 3 — Pest feature tests (11 tests, 132 assertions)

## Contexto de implementación

- **Ruta:** `GET /academic/students` → `academic.students.index`
- **Query:** JOIN en users (para ordenar por nombre) + LEFT JOINs en pensums/careers/enrollments (período activo)
- **Filtros server-side:** search (ilike nombre/email), career_id[], academic_year[], enrollment_status[] (incluye 'none' para sin inscribir)
- **Quick views:** all, pending (draft+none), newcomers (year=1); top/risk retornan 0 (sin GPA)
- **Datos ausentes:** cédula, student_code, GPA → muestran — en la tabla
- **Vue:** checkbox-dropdowns para carrera/año/estado, debounced search, pagination con URL sync

## Feature anterior completada

**Feature:** `08-grades-module`
Todas las 9 tasks implementadas. Configuración institucional de notas, entrada por profesor, vista por estudiante y representante.
