# HLZ-30 — Schedule actions: toast de confirmación nunca aparece

## Problema

Después de crear, editar o eliminar un horario, el toast de confirmación
("Horario creado.", "Horario actualizado.", "Horario eliminado.") nunca
aparece en pantalla aunque la acción se haya ejecutado correctamente en
la base de datos.

**Causa raíz:**
- `ScheduleController` llama `Inertia::flash('toast', ['type' => 'success', 'message' => '...'])`
- `flashToast.ts` captura el evento `router.on('flash', ...)` de Inertia v3
  y llama `toast.success(data.message)` importado de `vue-sonner`
- El layout `AppSidebarLayout.vue` usa `<Toast />` del componente custom
  (`components/feedback/Toast.vue` + `useToast.ts`), NO un `<Toaster>` de
  `vue-sonner`
- Sin el `<Toaster>` de vue-sonner montado en el árbol de Vue, todos los
  toasts disparados via `vue-sonner` se descartan silenciosamente

**Evidencia:** tests Dusk UC-H09, UC-H10, UC-H11, UC-H28, UC-H35, UC-H36,
UC-H41 fallan con `TimeoutException: Waited N seconds for text [Horario X.]`.
Screenshots confirman que la acción sí se ejecutó (el horario aparece/desaparece
en la grilla) pero el toast nunca se renderiza.

## Requisitos funcionales

- RF-01: Crear un horario válido muestra un toast de éxito "Horario creado."
  durante al menos 3 segundos.
- RF-02: Editar un horario válido muestra un toast de éxito "Horario actualizado."
  durante al menos 3 segundos.
- RF-03: Eliminar un horario muestra un toast de éxito "Horario eliminado."
  durante al menos 3 segundos.
- RF-04: Los tests Dusk UC-H09, UC-H10, UC-H11, UC-H28, UC-H35, UC-H36,
  UC-H41 pasan en verde con `waitForText('Horario X.', 8)`.

## Solución propuesta

**Opción A (recomendada):** Reemplazar el mecanismo de flash. En `flashToast.ts`,
cambiar `toast.success(data.message)` (vue-sonner) por `useToast().toast({...})`
(sistema custom) que sí está montado en el layout vía `<Toast />`.

**Opción B:** Montar `<Toaster>` de vue-sonner en `AppSidebarLayout.vue` y
eliminar `<Toast />` custom, unificando en vue-sonner.

La Opción A es menos invasiva: solo afecta `flashToast.ts` y elimina la
dependencia innecesaria de `vue-sonner`.

## Archivos afectados

- `resources/js/lib/flashToast.ts` — usa `toast` de `vue-sonner` incorrectamente
- `resources/js/layouts/app/AppSidebarLayout.vue` — monta `<Toast />` custom, no `<Toaster>` de sonner
- Tests que fallan: `tests/Browser/Academic/ScheduleCreateTest.php` (H09/H10/H11),
  `tests/Browser/Academic/ScheduleConflictsTest.php` (H28),
  `tests/Browser/Academic/ScheduleEditTest.php` (H35/H36),
  `tests/Browser/Academic/ScheduleDeleteTest.php` (H41)
