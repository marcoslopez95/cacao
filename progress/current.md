# Feature en progreso

**Feature:** `07-role-dashboards`
**Plan:** `specs/role-dashboards/tasks.md`
**Estado:** COMPLETADA — 8/8 tasks implementadas

## Tareas

- [x] Task 1 — Relación `Professor::sections()` + `Professor\DashboardController`
- [x] Task 2 — `Student\DashboardController`
- [x] Task 3 — `Guardian\DashboardController`
- [x] Task 4 — TypeScript types para los tres dashboards
- [x] Task 5 — `professor/Dashboard.vue`
- [x] Task 6 — `student/Dashboard.vue`
- [x] Task 7 — `guardian/Dashboard.vue`
- [x] Task 8 — Pest feature tests

## Contexto de diseño

- **Profesor**: métricas (secciones, estudiantes totales, horas/sem) + timeline de hoy con badge AHORA
- **Estudiante**: métricas (materias, UC inscritas, progreso pensum) + mismo patrón timeline; banner CTA si sin inscripción
- **Representante**: cards por representado con mini-stats (UC inscritas, nota `null`, inasistencias `null`, % pensum), barra de progreso, lista de materias inscritas
- "AHORA" calculado en backend por hora actual vs `start_time`/`end_time` del schedule
- "En clase" del representante → placeholder hasta módulo de asistencia

## Feature anterior completada

**Feature:** `06-error-pages`
Todas las 8 tasks implementadas. Páginas 404, 401/403 y 500 con animaciones SVG y suite de tests completa.
