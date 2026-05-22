# Feature en progreso

**Feature:** `07-role-dashboards`
**Plan:** `specs/role-dashboards/tasks.md`
**Estado:** EN PROGRESO — 0/8 tasks implementadas

## Tareas

- [ ] Task 1 — Relación `Professor::sections()` + `Professor\DashboardController`
- [ ] Task 2 — `Student\DashboardController`
- [ ] Task 3 — `Guardian\DashboardController`
- [ ] Task 4 — TypeScript types para los tres dashboards
- [ ] Task 5 — `professor/Dashboard.vue`
- [ ] Task 6 — `student/Dashboard.vue`
- [ ] Task 7 — `guardian/Dashboard.vue`
- [ ] Task 8 — Pest feature tests

## Contexto de diseño

- **Profesor**: métricas (secciones, estudiantes totales, horas/sem) + timeline de hoy con badge AHORA
- **Estudiante**: métricas (materias, UC inscritas, progreso pensum) + mismo patrón timeline; banner CTA si sin inscripción
- **Representante**: cards por representado con mini-stats (UC inscritas, nota `null`, inasistencias `null`, % pensum), barra de progreso, lista de materias inscritas
- "AHORA" calculado en backend por hora actual vs `start_time`/`end_time` del schedule
- "En clase" del representante → placeholder hasta módulo de asistencia

## Feature anterior completada

**Feature:** `06-error-pages`
Todas las 8 tasks implementadas. Páginas 404, 401/403 y 500 con animaciones SVG y suite de tests completa.
