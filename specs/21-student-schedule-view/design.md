# Student Schedule View — Diseño

## Decisión de diseño

**Opción A — `WeeklyGrid.vue` para el calendario visual desktop + Opción B — `ScheduleListView.vue` para mobile, combinadas por CSS puro.**

Ambos componentes ya existen en `resources/js/components/scheduling/` (usados hoy por el módulo admin) y ya son agnósticos de permisos vía props (`can-update`, `can-delete`), por lo que se pueden montar en modo read-only sin tocarlos. Se renderizan ambos en la misma página y se alternan con Tailwind puro:

- `WeeklyGrid` envuelto en `hidden lg:block` (visible desde desktop `lg:` hacia arriba).
- `ScheduleListView` envuelto en `lg:hidden` (visible por debajo de `lg:`).

**Por qué no se creó un composable de breakpoint por JS:** el proyecto no tiene ningún composable de responsive por JS en ningún módulo existente — el toggle "Semana/Día/Lista" del admin es una selección manual del usuario (un `<select>` o tabs), no un breakpoint automático. Introducir un composable nuevo (`useBreakpoint`, `useMediaQuery`, etc.) para este único caso rompería la convención del proyecto y agregaría una dependencia de estado reactivo (resize listeners, hidratación SSR) que Tailwind resuelve de forma declarativa y sin JS. `hidden lg:block` / `lg:hidden` es la solución más simple que ya usa el mismo patrón de composición visual que el resto de CACAO.

## Opciones descartadas

- **Solo `WeeklyGrid` (sin `ScheduleListView`).** Descartada: en viewport mobile el grid semanal completo es ilegible (columnas de día demasiado angostas), y el proyecto ya resolvió este mismo problema en el módulo admin con `ScheduleListView` — reinventar una vista mobile ad-hoc sería duplicar trabajo ya hecho.
- **Solo `ScheduleListView` (sin `WeeklyGrid`).** Descartada: en desktop, una lista agrupada por día pierde la vista panorámica semana-completa que el grid ofrece de un vistazo (franjas horarias alineadas por día) — regresión de UX frente a lo que el admin ya tiene disponible para el mismo tipo de dato.

## Cambios técnicos por capa

### Backend

- `app/Http/Controllers/Student/ScheduleController.php` (nuevo) — método `index()`. Patrón similar a `Student\DashboardController` y `Student\GradeController` ya existentes (misma resolución de `$request->user()->student` y período activo).
- Ruta nueva en `routes/web.php`, dentro del grupo `Route::middleware(['auth','verified','role:Estudiante'])->prefix('student')->name('student.')` ya existente:
  ```php
  Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');
  ```
- Nuevo Resource `app/Http/Resources/Student/StudentScheduleResource.php` — transforma cada `Schedule` al shape exacto documentado en `requirements.md`.
- Fuente de datos: `Student::confirmedDetails()` (scope ya existente en `app/Models/Enrollment.php:44-52`, filtra `EnrollmentDetail::status = confirmed`) → `.section.schedules`.
- Eager-load necesario sobre `confirmedDetails.section.schedules`: `professor.user`, `classroom`, `subject` (y `subject.pensum.career` opcional, para el color de carrera en el grid — nullable, no bloqueante si la relación no existe).
- Filtro de vigencia aplicado en el query o en el Resource: `valid_from <= hoy` y (`valid_until` es null o `valid_until >= hoy`) — vía `whereDate`/comparación de columnas date, antes de serializar.

### Frontend

- `resources/js/pages/student/Schedule.vue` (nuevo) — monta `WeeklyGrid` (`hidden lg:block`) y `ScheduleListView` (`lg:hidden`), ambos con `can-update="false"` y `can-delete="false"`, sin wiring de ningún emit de click/apertura de detalle (comportamiento puramente visual, sin popover).
- `resources/js/pages/student/Dashboard.vue` — agregar link/botón "Ver horario completo →" hacia `student.schedule.index` vía Wayfinder (nunca URL hardcodeada).
- Regenerar Wayfinder (`npm run build` o el comando `wayfinder:generate` que use el proyecto) tras agregar la ruta.
- Tipos: reutilizar `resources/js/types/scheduling.ts` si el shape es 100% compatible (confirmarlo al implementar); si no, crear `resources/js/types/student-schedule.ts` como mirror del Resource.

## Dependencias con otros módulos

- Depende de `Enrollment::confirmedDetails()` (`app/Models/Enrollment.php:44-52`), ya existente desde el feature `17-student-dashboard-schedule-fix`.
- Depende de los componentes `resources/js/components/scheduling/WeeklyGrid.vue` y `ScheduleListView.vue`, ya existentes del módulo admin — se reutilizan sin modificar.
- No depende de `16-enrollment-level-guard-and-period-fix` (HLZ-38) ni de los features de asistencia (HLZ-40/HLZ-41), aunque están relacionados temáticamente (todos tocan el dominio de inscripciones/horario del estudiante).
