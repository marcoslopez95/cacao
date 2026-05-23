# Feature completada

**Feature:** `08-grades-module`
**Plan:** `specs/grades-module/tasks.md`
**Estado:** COMPLETADA — 9/9 tasks implementadas

## Tareas

- [x] Task 1 — Migraciones, modelos, enums y factories
- [x] Task 2 — CRUD configuración de notas (Admin backend)
- [x] Task 3 — Configuración de notas (Admin frontend)
- [x] Task 4 — Entrada de notas del profesor (backend)
- [x] Task 5 — Planilla de notas del profesor (frontend)
- [x] Task 6 — Vista de notas estudiante/representante (backend)
- [x] Task 7 — Vista de notas estudiante/representante (frontend)
- [x] Task 8 — Reparación (backend + frontend)
- [x] Task 9 — Pest feature tests

## Contexto de diseño

- **Modelo auto-referencial**: `grade_entries` con `parent_id` — null = entrada de slot, not-null = sub-nota del profesor
- **Dos modos**: lapso (primaria/secundaria) y período (universitario) — determinado por nivel educativo en `grade_config.level`
- **Escala configurable**: numérica con rango (ambos niveles) o letras A-F (solo primaria/secundaria) con equivalencias numéricas
- **Sub-notas del profesor**: dentro de cada slot institucional, el profesor puede subdividir en N sub-notas con pesos libres (suman 100%)
- **Reparación**: slot especial `is_remedial = true`; habilitado por admin para enrollment_detail específico; nota final = max(definitiva, reparación)
- **Visibilidad**: `Team.grade_visibility` = `real_time` (default) o `manual`; en manual el profesor publica por slot+sección
- **Override por período**: la config global aplica siempre; si cambia para un período, se crea fila nueva `grade_configs` con `period_id`

## Feature anterior completada

**Feature:** `07-role-dashboards`
Todas las 8 tasks implementadas. Dashboards para Profesor (métricas + timeline), Estudiante (métricas + timeline + banner CTA) y Representante (cards por representado con mini-stats).
