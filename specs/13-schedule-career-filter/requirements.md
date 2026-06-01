# HLZ-35+36+37 — Filtro por carrera server-side + pre-filtro en modals + URL persistence

## Contexto

Durante la auditoría QA del módulo de horarios (H74-H76), se identificó que el filtro por
carrera (leyenda de colores) opera 100% client-side y no se persiste en la URL. Además, al
abrir los modales de creación/edición, la lista de secciones muestra todas las secciones sin
tener en cuenta las carreras activas en la vista principal.

## Requerimientos

### HLZ-35 — Filtro por carrera server-side (H74)

- `ScheduleController::index()` acepta `career_ids[]` en la query string.
- Los horarios devueltos se filtran por las carreras especificadas.
- Se agrega prop `careers` al response: lista de todas las carreras con horarios en el
  período/sección/profesor activos (sin aplicar el filtro de carrera), para que la leyenda
  siempre muestre todas las carreras disponibles.
- Se agrega `career_ids` a `filters` prop.

### HLZ-36 — URL persistence de carreras activas (H76)

- Los IDs de carrera activos se incluyen en la URL como `career_ids[]=N`.
- Al recargar la página, el filtro se restaura.
- `useScheduleFilters.ts` gestiona el estado de `careerIds` y lo envía en `applyFilters`.
- Al hacer toggle de una carrera desde la leyenda, se llama `applyFilters()` (Inertia visit).

### HLZ-37 — Pre-filtro de secciones en modals + banner (H75)

- `CreateScheduleModal` y `EditScheduleModal` reciben prop `activeCareerIds: number[]`.
- Si hay carreras activas, solo se muestran secciones cuyo `careerId` coincida.
- Se muestra un banner informativo: "Las secciones se muestran filtradas por las carreras
  activas en la vista principal."
- `ScheduleAvailableSection` incluye `careerId: number | null`.

## Archivos afectados

| Archivo | Cambio |
|---|---|
| `ScheduleController.php` | career_ids filter + careers prop + careerId en sections |
| `types/scheduling.ts` | careerId en ScheduleAvailableSection |
| `useScheduleFilters.ts` | careerIds param + applyFilters |
| `ScheduleLegend.vue` | careers prop (no derives de schedules) |
| `Index.vue` (Schedules) | toggle server-side, pass careers/careerIds |
| `CreateScheduleModal.vue` | activeCareerIds + visibleSections + banner |
| `EditScheduleModal.vue` | activeCareerIds + visibleSections + banner |
