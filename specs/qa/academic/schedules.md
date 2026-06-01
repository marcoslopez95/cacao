# QA — Horarios de Secciones
**Fecha de definición:** 2026-05-30
**Vista:** `/scheduling/schedules`
**Auditor:** QA Manager

---

## Contexto

El módulo de horarios gestiona la grilla semanal de clases. La vista principal renderiza
un calendario semanal (WeeklyGrid), vista por día, y vista de lista. Los horarios
se crean, editan y eliminan via modales. Un `Schedule` vincula:
`section → professor → classroom → subject`, con día de la semana, hora inicio/fin,
tipo (teórica/lab) y vigencia (valid_from / valid_until).

### Filtros disponibles
| Filtro | Scope | Implementado actualmente |
|--------|-------|--------------------------|
| `period_id` | server-side | ✅ |
| `section_id` | server-side | ✅ |
| `professor_id` | server-side | ✅ |
| `day_of_week` | server-side | ✅ |
| `career_ids[]` | **server-side (pendiente)** | ❌ client-side solamente |

### Validaciones del backend (StoreScheduleRequest / UpdateScheduleRequest)
1. Todos los campos requeridos presentes con tipos correctos
2. `end_time` posterior a `start_time`
3. `valid_until` posterior o igual a `valid_from`
4. Sección universitaria: `subject_id` debe coincidir con `section.subject_id`
5. Sección escolar: `subject_id` debe pertenecer al pensum de la sección
6. Sección escolar con `main_teacher_id` asignado: `professor_id` debe ser ese mismo docente
7. `valid_from` no puede ser anterior al `period.start_date`
8. `valid_until` no puede superar el `period.end_date`
9. Conflicto de aula: misma aula, mismo día, solapamiento horario, período no cerrado
10. Conflicto de profesor: mismo profesor, mismo día, solapamiento horario, período no cerrado
11. Exceso de horas semanales del profesor vs `weekly_hour_limit`

---

## UCs de carga

- **UC-H01** — Navegar a `/scheduling/schedules` sin filtros: la página carga con código 200, el componente `scheduling/Schedules/Index` se renderiza, y el prop `schedules` contiene todos los horarios existentes en la DB.

- **UC-H02** — La grilla muestra la vista semanal por defecto (WeeklyGrid visible, ScheduleListView oculto), con los días lunes–sábado como columnas.

- **UC-H03** — La barra de estadísticas (ScheduleStats) muestra el conteo total de horarios cargados y el número de conflictos detectados.

- **UC-H04** — La leyenda de carreras (ScheduleLegend) muestra una entrada por cada carrera presente en los horarios cargados.

- **UC-H05** — Sin filtros activos, el prop `filters` llega con `period_id: null`, `section_id: null`, `professor_id: null`; los chips de filtro activo no se muestran.

- **UC-H06** — El usuario sin permiso `schedules.view` que intenta acceder a `/scheduling/schedules` recibe un 403 (Gate::authorize falla en `SchedulePolicy::viewAny`).

---

## UCs de creación (happy path)

- **UC-H07** — Con permiso `schedules.create`, el botón "+ Nuevo horario" es visible en la toolbar. Sin ese permiso, el botón no aparece.

- **UC-H08** — Clic en "+ Nuevo horario" abre el modal `CreateScheduleModal` con todos los campos vacíos / en su valor por defecto (sección pre-seleccionada si `sectionId` activo en filtros, día lunes, hora inicio 08:00).

- **UC-H09** — Crear un horario válido (sección + materia + profesor + aula + día + hora inicio + hora fin + tipo + valid_from): el formulario hace POST a `POST /scheduling/schedules`, el backend responde con redirect, el modal se cierra, aparece toast "Horario creado." y la grilla se actualiza con el nuevo horario.

- **UC-H10** — Tras crear el horario con filtros `section_id` y `period_id` activos, el redirect devuelve a la misma URL con esos parámetros preservados (`to_route` con `$request->only(['section_id', 'period_id', 'professor_id'])`).

- **UC-H11** — El campo `valid_until` es opcional: se puede crear un horario dejando ese campo vacío. El backend lo acepta como `null`.

- **UC-H12** — Al seleccionar un profesor en el modal de creación, el componente `ProfessorHoursBar` muestra sus horas semanales actuales y el límite (`weeklyHourLimit`).

- **UC-H13** — Crear desde la grilla semanal haciendo clic en una celda vacía (evento `@create` de `WeeklyGrid`): el modal abre pre-rellenado con el día y la hora correspondientes a la celda clicada.

---

## UCs de validación (uno por regla)

- **UC-H14** — Intentar crear con `section_id` ausente → respuesta 422 con error en `section_id`.

- **UC-H15** — Intentar crear con `professor_id` ausente → respuesta 422 con error en `professor_id`.

- **UC-H16** — Intentar crear con `classroom_id` ausente → respuesta 422 con error en `classroom_id`.

- **UC-H17** — Intentar crear con `subject_id` ausente → respuesta 422 con error en `subject_id`.

- **UC-H18** — Intentar crear con `end_time` igual o anterior a `start_time` → respuesta 422 con error en `end_time` ("after:start_time").

- **UC-H19** — Intentar crear con `valid_until` anterior a `valid_from` → respuesta 422 con error en `valid_until`.

- **UC-H20** — Intentar crear con `day_of_week` con valor fuera del enum `DayOfWeek` → respuesta 422 con error en `day_of_week`.

- **UC-H21** — Intentar crear con `type` con valor fuera del enum `ScheduleSessionType` → respuesta 422 con error en `type`.

- **UC-H22** — Sección universitaria: intentar crear con `subject_id` que no corresponde a la sección → error en `subject_id`: "La materia no corresponde a esta sección universitaria."

- **UC-H23** — Sección escolar: intentar crear con `subject_id` que no pertenece al pensum de la sección → error en `subject_id`: "La materia no pertenece al pensum de esta sección escolar."

- **UC-H24** — Sección escolar con `main_teacher_id` asignado: intentar crear con un `professor_id` diferente → error en `professor_id`: "El profesor debe ser el docente de aula asignado a esta sección."

- **UC-H25** — `valid_from` anterior al `period.start_date` de la sección → error en `valid_from` con mensaje que incluye la fecha del período.

- **UC-H26** — `valid_until` posterior al `period.end_date` de la sección → error en `valid_until` con mensaje que incluye la fecha del período.

---

## UCs de conflictos de negocio

- **UC-H27** — Conflicto de aula: intentar crear un horario en el mismo aula, mismo día, con horario solapado y el período no cerrado → respuesta 422 con error en `classroom_id` indicando el aula y el horario conflictivo.

- **UC-H28** — Sin conflicto de aula por período cerrado: si el horario solapado pertenece a un período con `status = closed`, no se genera error de conflicto de aula.

- **UC-H29** — Conflicto de profesor: intentar asignar al mismo profesor el mismo día en un horario que se solapa con uno existente (período no cerrado) → respuesta 422 con error en `professor_id` indicando el nombre del profesor y el horario conflictivo.

- **UC-H30** — Exceso de horas semanales: intentar crear un horario que haría que el profesor supere `weekly_hour_limit` horas/semana → respuesta 422 con error en `professor_id` indicando el límite, las horas actuales y las horas nuevas.

- **UC-H31** — Sin exceso de horas cuando el profesor ya está en el límite exacto pero el nuevo slot lo supera por 1 minuto → la validación devuelve 422 (no solo horas completas, sino fracciones).

- **UC-H32** — Detección de conflictos en frontend (ScheduleConflictsBanner): cuando dos horarios cargados se solapan en el mismo aula o mismo profesor y mismo día, el banner y el chip de ScheduleStats muestran el conteo de conflictos.

---

## UCs de edición

- **UC-H33** — Con permiso `schedules.update`, los controles de editar (lápiz) son visibles en los eventos de la grilla y en la lista. Sin ese permiso, los controles no aparecen.

- **UC-H34** — Clic en editar sobre un evento abre el `EditScheduleModal` con todos los campos pre-llenados con los valores actuales del horario (sección, materia, profesor, aula, día, hora inicio, hora fin, tipo, valid_from, valid_until).

- **UC-H35** — Editar y guardar un horario válido: el formulario hace PATCH a `PATCH /scheduling/schedules/{id}`, el backend responde con redirect, el modal se cierra, aparece toast "Horario actualizado." y la grilla refleja los cambios.

- **UC-H36** — Editar sin cambiar ningún campo (guardar idéntico): el backend responde con 302 sin errores de validación (las validaciones de conflicto excluyen al propio horario por su `id`).

- **UC-H37** — Las mismas reglas de validación de creación aplican en edición (UC-H14 a UC-H30), con la diferencia de que los conflictos de aula y profesor excluyen al propio horario (`WHERE id != $excludeScheduleId`).

- **UC-H38** — Usuario sin permiso `schedules.update` que intenta hacer PATCH directamente → respuesta 403 (`UpdateScheduleRequest::authorize()` falla).

---

## UCs de eliminación

- **UC-H39** — Con permiso `schedules.delete`, los controles de eliminar (basura) son visibles en los eventos de la grilla y en la lista. Sin ese permiso, los controles no aparecen.

- **UC-H40** — Clic en eliminar abre el modal `DeleteScheduleModal` con los datos del horario a eliminar.

- **UC-H41** — Confirmar eliminación: el frontend hace DELETE a `DELETE /scheduling/schedules/{id}`, el backend responde con redirect, el modal se cierra, aparece toast "Horario eliminado." y el evento desaparece de la grilla.

- **UC-H42** — Cancelar en el modal de eliminación: no se hace ningún request, el modal se cierra y la grilla no cambia.

- **UC-H43** — Usuario sin permiso `schedules.delete` que intenta hacer DELETE directamente → respuesta 403 (`Gate::authorize('delete', $schedule)` falla).

---

## UCs de filtros y navegación

### Filtros server-side (URL params)

- **UC-H44** — Filtro por `period_id` en URL: el controller devuelve solo los `schedules` cuya sección pertenece a ese período (`whereHas('section', fn => where('period_id', $id))`).

- **UC-H45** — Filtro por `section_id` en URL: el controller devuelve solo los `schedules` de esa sección (`where('section_id', $id)`).

- **UC-H46** — Filtro por `professor_id` en URL: el controller devuelve solo los horarios de ese profesor.

- **UC-H47** — Filtro por `day_of_week` en URL: el controller devuelve solo los horarios de ese día de la semana.

- **UC-H48** — Filtro por `career_ids[]` en URL: el controller devuelve solo los horarios cuya materia pertenece a una carrera en ese array (`whereHas('subject.pensum.career', fn => whereIn('id', $careerIds))`). **[PENDIENTE DE IMPLEMENTAR — actualmente el filtro de carrera es solo client-side]**

- **UC-H49** — Combinar `period_id + section_id`: el backend aplica ambas condiciones con AND. Solo se devuelven schedules que cumplan los dos criterios simultáneamente.

- **UC-H50** — Combinar `period_id + career_ids[]`: el backend aplica ambos filtros. **[PENDIENTE — depende de UC-H48]**

- **UC-H51** — Sin ningún filtro en URL: el controller devuelve todos los schedules sin restricción de período, sección ni carrera.

### Filtros client-side (sin round-trip)

- **UC-H52** — Búsqueda de texto en tiempo real: al escribir en el campo de búsqueda, `filteredSchedules` filtra por nombre y código de materia, nombre del profesor, identificador de aula y código de sección. El filtrado ocurre sin round-trip al servidor.

- **UC-H53** — Filtro de carrera via `ScheduleLegend`: clic en una carrera en la leyenda activa el filtro client-side `activeCareerIds`. Los eventos de otras carreras desaparecen de la grilla sin round-trip. **[Este comportamiento client-side se mantiene mientras UC-H48 no esté implementado. Una vez implementado UC-H48, los chips de carrera deberán hacer round-trip.]**

- **UC-H54** — Con `activeCareerIds` vacío (estado inicial), se muestran todos los schedules (no se aplica ningún filtro de carrera).

### Selector de período

- **UC-H55** — El dropdown de período (ScheduleToolbar) muestra todos los períodos disponibles. Al seleccionar uno, hace `applyFilters()` que navega a la misma URL con `period_id` como query param (router.get con replace:true).

- **UC-H56** — Al seleccionar "Todos los períodos" en el dropdown, se navega sin `period_id` en la URL y se devuelven todos los schedules.

### Panel de filtros adicionales (sección + profesor)

- **UC-H57** — Clic en "+ Filtro" abre el panel de filtros con selects de Sección y Profesor. Los selects muestran solo las secciones del período activo si hay `period_id` activo; de lo contrario, todas las secciones.

- **UC-H58** — Al hacer clic en "Aplicar" dentro del panel, `applyFilters()` navega con `section_id` y/o `professor_id` como query params. Los chips de filtro activo aparecen en la fila de filtros.

- **UC-H59** — Los chips de filtro activo tienen un botón "✕" que quita ese filtro específico y hace round-trip inmediato.

- **UC-H60** — "Limpiar todos" limpia `period_id`, `section_id` y `professor_id` y hace round-trip, devolviendo todos los schedules.

---

## UCs de modales con filtro de carrera activo

> Los UCs H61–H64 dependen de la implementación de UC-H48. Describen el comportamiento esperado una vez que el filtro de carrera pase a ser server-side.

- **UC-H61** — Con filtros de carrera activos (`activeCareerIds` con al menos una carrera), el select de `section_id` en `CreateScheduleModal` muestra solo secciones cuya carrera esté en `activeCareerIds`. Las secciones de otras carreras no aparecen como opciones. **[PENDIENTE DE IMPLEMENTAR]**

- **UC-H62** — Con filtros de carrera activos, `CreateScheduleModal` muestra un banner/nota visible al inicio del formulario: "Filtro de carrera activo: [nombre(s) de la(s) carrera(s)]". **[PENDIENTE DE IMPLEMENTAR]**

- **UC-H63** — Sin filtros de carrera activos (o `activeCareerIds` vacío), `CreateScheduleModal` muestra todas las secciones disponibles y no muestra el banner de carrera. **[PENDIENTE DE IMPLEMENTAR]**

- **UC-H64** — Los mismos comportamientos de UC-H61 y UC-H62 aplican en `EditScheduleModal`: el select de sección filtra por carrera activa y el banner es visible. **[PENDIENTE DE IMPLEMENTAR]**

---

## UCs de vistas

- **UC-H65** — El switcher de vista (Semana / Día / Lista) cambia entre `WeeklyGrid` (week/day) y `ScheduleListView` (list) sin round-trip.

- **UC-H66** — Vista "Día" en móvil: la grilla muestra solo un día a la vez, con navegación por día (mobileDay). El día activo por defecto es el día actual de la semana (todayKey).

- **UC-H67** — Vista Lista: `ScheduleListView` renderiza todos los `filteredSchedules` en formato tabla, con los mismos controles de editar/eliminar condicionados a permisos.

- **UC-H68** — Clic en un evento de la grilla abre el `ScheduleClusterPopover` con los datos del horario (materia, profesor, aula, día, hora, tipo). Desde el popover se puede abrir el modal de edición o eliminación.

- **UC-H69** — Si múltiples eventos se solapan en la misma celda de la grilla, aparece un tile de cluster. Clic en él abre el `ScheduleClusterPopover` con el listado de eventos solapados.

- **UC-H70** — Clic en el chip de conflictos en ScheduleStats abre el popover de conflictos listando todos los schedules con conflicto detectado.

---

## UCs de permisos

- **UC-H71** — Usuario con rol que tiene `schedules.view` pero no `schedules.create`: ve la grilla, no ve "+ Nuevo horario", no ve controles de editar/eliminar.

- **UC-H72** — Usuario con `schedules.create` y `schedules.update` y `schedules.delete`: ve todos los controles. Los props `can.create`, `can.update`, `can.delete` llegan como `true` desde el controller.

- **UC-H73** — Usuario no autenticado que accede a `/scheduling/schedules`: redirige a `/login`.

---

## UCs de bugs actuales (pendientes de fix)

- **UC-H74** — [ESTADO: GAP — NO IMPLEMENTADO] Filtro `career_ids[]` server-side: el controller actualmente no tiene lógica para `career_ids[]`. La URL `/scheduling/schedules?career_ids[]=1&career_ids[]=2` devuelve todos los schedules sin filtrar. El filtro de carrera solo opera client-side. Ver UC-H48.

- **UC-H75** — [ESTADO: GAP — NO IMPLEMENTADO] El modal `CreateScheduleModal` no recibe información de carreras activas y no pre-filtra el select de sección por carrera. Ver UC-H61–H63.

- **UC-H76** — [ESTADO: GAP — NO IMPLEMENTADO] El modal `EditScheduleModal` no recibe información de carreras activas y no pre-filtra el select de sección por carrera. Ver UC-H64.

---

## Tests Dusk

| UC | Archivo | Método | Estado |
|----|---------|--------|--------|
| UC-H01 | ScheduleLoadTest.php | UC-H01 | PASS |
| UC-H02 | ScheduleLoadTest.php | UC-H02 | SKIP (selector .sch-view-switcher no existe) |
| UC-H03 | ScheduleLoadTest.php | UC-H03 | PASS |
| UC-H04 | ScheduleLoadTest.php | UC-H04 | PASS |
| UC-H05 | ScheduleLoadTest.php | UC-H05 | PASS |
| UC-H06 | ScheduleLoadTest.php | UC-H06 | PASS |
| UC-H07 | ScheduleCreateTest.php | UC-H07a / UC-H07b | PASS |
| UC-H08 | ScheduleCreateTest.php | UC-H08 | PASS |
| UC-H09 | ScheduleCreateTest.php | UC-H09 | FALLO — HLZ-30 (toast nunca aparece) |
| UC-H10 | ScheduleCreateTest.php | UC-H10 | FALLO — HLZ-30 (toast nunca aparece) |
| UC-H11 | ScheduleCreateTest.php | UC-H11 | FALLO — HLZ-30 (toast nunca aparece) |
| UC-H12 | ScheduleCreateTest.php | UC-H12 | PASS |
| UC-H13 | ScheduleCreateTest.php | UC-H13 | PASS |
| UC-H14 | — | — | skip (validación básica cubierta por H18; campos requeridos no testeados individualmente) |
| UC-H15 | — | — | skip |
| UC-H16 | — | — | skip |
| UC-H17 | — | — | skip |
| UC-H18 | ScheduleCreateTest.php | UC-H18 | PASS |
| UC-H19 | ScheduleCreateTest.php | UC-H19 | PASS |
| UC-H20 | — | — | skip |
| UC-H21 | — | — | skip |
| UC-H22 | ScheduleCreateTest.php | UC-H22 | PASS |
| UC-H23 | — | — | skip |
| UC-H24 | ScheduleCreateTest.php | UC-H24 | FALLO — HLZ-31 (migration down() crash con school sections) |
| UC-H25 | ScheduleCreateTest.php | UC-H25 | PASS |
| UC-H26 | ScheduleCreateTest.php | UC-H26 | PASS |
| UC-H27 | ScheduleConflictsTest.php | UC-H27 | PASS |
| UC-H28 | ScheduleConflictsTest.php | UC-H28 | FALLO — HLZ-30 (toast nunca aparece; la validación sí excluye períodos cerrados) |
| UC-H29 | ScheduleConflictsTest.php | UC-H29 | PASS |
| UC-H30 | ScheduleConflictsTest.php | UC-H30 | PASS |
| UC-H31 | ScheduleConflictsTest.php | UC-H31 | PASS |
| UC-H32 | ScheduleConflictsTest.php | UC-H32 | PASS |
| UC-H33 | ScheduleEditTest.php | UC-H33a / UC-H33b | PASS |
| UC-H34 | ScheduleEditTest.php | UC-H34 | PASS |
| UC-H35 | ScheduleEditTest.php | UC-H35 | FALLO — HLZ-30 (toast nunca aparece) |
| UC-H36 | ScheduleEditTest.php | UC-H36 | FALLO — HLZ-30 (toast nunca aparece) |
| UC-H37 | ScheduleEditTest.php | UC-H37 | PASS |
| UC-H38 | ScheduleEditTest.php | UC-H38 | PASS |
| UC-H39 | ScheduleDeleteTest.php | UC-H39a / UC-H39b | PASS |
| UC-H40 | ScheduleDeleteTest.php | UC-H40 | PASS |
| UC-H41 | ScheduleDeleteTest.php | UC-H41 | FALLO — HLZ-30 (toast nunca aparece) |
| UC-H42 | ScheduleDeleteTest.php | UC-H42 | PASS |
| UC-H43 | ScheduleDeleteTest.php | UC-H43 | PASS |
| UC-H44 | ScheduleFiltersTest.php | UC-H44 | PASS |
| UC-H45 | ScheduleFiltersTest.php | UC-H45 | PASS |
| UC-H46 | ScheduleFiltersTest.php | UC-H46 | PASS |
| UC-H47 | ScheduleFiltersTest.php | UC-H47 | PASS |
| UC-H48 | — | — | SKIP (requiere implementación server-side de career_ids) |
| UC-H49 | ScheduleFiltersTest.php | UC-H49 | PASS |
| UC-H50 | — | — | SKIP (requiere implementación previa UC-H48) |
| UC-H51 | ScheduleFiltersTest.php | UC-H51 | PASS |
| UC-H52 | ScheduleFiltersTest.php | UC-H52 / UC-H52b | PASS |
| UC-H53 | ScheduleFiltersTest.php | UC-H53 | PASS |
| UC-H54 | ScheduleFiltersTest.php | UC-H54 | PASS |
| UC-H55 | ScheduleFiltersTest.php | UC-H55 | PASS |
| UC-H56 | ScheduleFiltersTest.php | UC-H56 | PASS |
| UC-H57 | ScheduleFiltersTest.php | UC-H57 | PASS |
| UC-H58 | ScheduleFiltersTest.php | UC-H58 | PASS |
| UC-H59 | ScheduleFiltersTest.php | UC-H59 | PASS |
| UC-H60 | ScheduleFiltersTest.php | UC-H60 | PASS |
| UC-H61 | — | — | SKIP (requiere implementación previa UC-H48) |
| UC-H62 | — | — | SKIP (requiere implementación previa UC-H48) |
| UC-H63 | — | — | SKIP (requiere implementación previa UC-H48) |
| UC-H64 | — | — | SKIP (requiere implementación previa UC-H48) |
| UC-H65 | ScheduleViewsTest.php | UC-H65 | PASS |
| UC-H66 | ScheduleViewsTest.php | UC-H66 | PASS |
| UC-H67 | ScheduleViewsTest.php | UC-H67 / UC-H67b | PASS |
| UC-H68 | ScheduleViewsTest.php | UC-H68 | PASS |
| UC-H69 | ScheduleViewsTest.php | UC-H69 | PASS |
| UC-H70 | ScheduleViewsTest.php | UC-H70 | PASS |
| UC-H71 | ScheduleViewsTest.php | UC-H71 | PASS |
| UC-H72 | ScheduleViewsTest.php | UC-H72 | PASS |
| UC-H73 | ScheduleViewsTest.php | UC-H73 | PASS |
| UC-H74 | — | — | SKIP (GAP — no implementado) |
| UC-H75 | — | — | SKIP (GAP — no implementado) |
| UC-H76 | — | — | SKIP (GAP — no implementado) |

---

## Notas de implementación pendiente

### GAP: filtro `career_ids[]` server-side (UC-H48, H50, H61–H64, H74–H76)

El backend actual no tiene filtro por carrera. La implementación requiere:

1. **`ScheduleController::index()`** — agregar:
   ```php
   ->when($request->input('career_ids'), fn ($q, $ids) =>
       $q->whereHas('subject.pensum.career', fn ($q2) =>
           $q2->whereIn('id', (array) $ids)
       )
   )
   ```
   Y exponer en `filters`:
   ```php
   'career_ids' => $request->input('career_ids') ? array_map('intval', (array) $request->input('career_ids')) : [],
   ```

2. **`useScheduleFilters.ts`** — agregar `careerIds: ref<number[]>([])` y enviarlo como `career_ids[]` en `applyFilters()`.

3. **`ScheduleLegend`** — al hacer toggle de carrera, llamar `applyFilters()` para hacer round-trip en vez de solo actualizar `activeCareerIds` local.

4. **`CreateScheduleModal` / `EditScheduleModal`** — recibir prop `activeCareerNames: string[]` (o `activeCareerIds: number[]`) y filtrar el array `sections` pasado, mostrando el banner si la prop no está vacía.

5. **`Index.vue`** — pasar `activeCareerIds` / `activeCareerNames` a los modales.
