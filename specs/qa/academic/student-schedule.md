# QA — Horario del estudiante
**Fecha de definición:** 2026-08-30
**Última actualización:** 2026-09-13 (Ronda 3 — vista semanal `/student/schedule` implementada y probada)
**Vista:** `/student/dashboard` (widget "Hoy") + `/student/schedule` (vista semanal, implementada en Ronda 3)
**Auditor:** QA Manager

---

## Contexto

Lo que en Ronda 1 estaba documentado como feature pendiente (UC-SS07–SS11) **ya fue implementado** para esta ronda: `GET /student/schedule` (`Student\ScheduleController@index`, middleware `role:Estudiante`), que reutiliza `WeeklyGrid.vue`/`ScheduleListView.vue` del módulo admin — exactamente como se pedía en la decisión confirmada del 2026-08-30. Junto con esta vista, se introdujo un cambio global de layout: el sidebar colapsa a menú hamburguesa en viewports móviles (`resources/js/components/scheduling/WeeklyGrid.vue` + capa de layout general), afectando toda la app, no solo esta pantalla.

**Decisiones confirmadas con el humano (2026-08-30):**
1. El widget "Hoy" debe filtrar por `EnrollmentDetail::status = confirmed` — actualmente no filtra por estado en absoluto. Ver `HLZ-39`.
2. **Sí se necesita una vista semanal completa** (tipo `WeeklyGrid`, como en admin) — implementada y probada en esta ronda (ver UCs abajo).

---

## UCs del widget "Hoy" (dashboard actual)

- **UC-SS01** — GET `/student/dashboard` con inscripción confirmada y clases programadas hoy: `today_schedules` lista las secciones correspondientes a `EnrollmentDetail` en estado `confirmed` únicamente.
- **UC-SS02** — [ESTADO: BUG — ver **HLZ-39**] Comportamiento actual: `today_schedules` incluye secciones en estado `draft` y `rejected` mezcladas indistintamente con `confirmed` — no hay ningún filtro de status. Debe corregirse para cumplir UC-SS01.
- **UC-SS02b** — [ESTADO: BUG CRÍTICO — ver **HLZ-42**, confirmado en vivo 2026-08-30] `GET /student/dashboard` cualquier domingo: `DayOfWeek::from(strtolower(now()->format('l')))` en `DashboardController.php:26` lanza `ValueError` porque el enum `DayOfWeek` no tiene caso `Sunday` — 500 total (no solo el widget de horario, todo el dashboard), sin try/catch. Reproducido con cuentas universitarias y escolar por igual. Debe resolverse con `tryFrom()` + manejo de `null` como "sin clases hoy", antes o junto con el fix de HLZ-39 ya que tocan la misma línea.
- **UC-SS03** — Sin clases programadas hoy (día actual sin schedules coincidentes): estado vacío "Sin clases programadas hoy", sin error.
- **UC-SS04** — Sin período activo o sin `Enrollment` para el período activo: `today_schedules` vacío, sin error.
- **UC-SS05** — Cada item del widget expone: materia, código de sección, aula, hora inicio, hora fin, `is_current` (si la clase está en curso ahora mismo).
- **UC-SS06** — [GAP] El widget no expone el nombre del profesor, pese a que la relación `Schedule::professor()` existe en el modelo. Evaluar si se agrega al mismo tiempo que el fix de HLZ-39 o se difiere a la vista semanal (UC-SS08).

## UCs de la vista semanal `/student/schedule` (Ronda 3 — 2026-09-13)

Probado con `est109@utcacao.edu.ve` (universitario, inscripción confirmada, período activo) salvo donde se indique otra cuenta.

- **UC-SS07** — [FUNCIONA, confirmado en vivo] `GET /student/schedule` (`Student\ScheduleController@index`) muestra el grid semanal completo reutilizando `WeeklyGrid.vue` del módulo admin, alimentado por `StudentScheduleResolver` (resuelve siempre por `$request->user()->student`, sin parámetro de ruta).
- **UC-SS08** — [FUNCIONA, confirmado en vivo] Cada bloque del grid (desktop, pantalla ancha) expone materia, profesor, aula, código de sección, día y hora inicio/fin — vía `ScheduleEventCard.vue` con datos de `ScheduleResource`.
- **UC-SS09** — [FUNCIONA, confirmado en vivo] `role:Estudiante` en el middleware de la ruta (`routes/web.php`) + resolución exclusiva por `$request->user()->student` en el controller: no hay forma de ver el horario de otro estudiante.
- **UC-SS10** — [FUNCIONA, confirmado en vivo] El link "Ver horario completo →" del widget "Hoy" en `/student/dashboard` navega correctamente a `/student/schedule`.
- **UC-SS11** — [FUNCIONA, confirmado en vivo] Estudiante sin `EnrollmentDetail::confirmed` o sin período activo: la vista carga con estado vacío (grid/lista sin bloques), sin error 500 ni excepción.
- **UC-SS12** — [FUNCIONA, confirmado en vivo] Responsive: en viewport móvil (achicado o simulado), el grid semanal (`WeeklyGrid.vue`, `hidden lg:block`) desaparece y aparece `ScheduleListView.vue` (`lg:hidden`) — lista agrupada por día (Lunes a Sábado), con estado "Sin clases" por día vacío.
- **UC-SS13** — [FUNCIONA, confirmado en vivo] Click sobre un bloque del horario (desktop o mobile): no navega ni abre popover. `Schedule.vue` monta `WeeklyGrid`/`ScheduleListView` con `:can-update="false" :can-delete="false"` y **sin** listener para el evento `openEvent`/`open-event` que ambos componentes emiten al hacer click — el evento se pierde sin handler, consistente con "es de solo lectura". Confirmado además en código: ningún ícono de editar/eliminar se renderiza (`v-if="canUpdate || canDelete"` en `ScheduleEventCard.vue`, ambos `false`).
- **UC-SS14** — [FUNCIONA, confirmado en vivo] Estudiante de nivel `primary`/`secondary` puede entrar a `/student/schedule` sin bloqueo (el middleware solo exige `role:Estudiante`, sin condicionar por `educational_level` — a diferencia de la restricción de auto-inscripción de HLZ-38, aquí no aplica ninguna restricción de nivel, ni debería).
- **UC-SS15** — [FUNCIONA, confirmado en vivo] Con rol Admin, Profesor o Representante: `GET /student/schedule` responde 403 (fuera de `role:Estudiante` en `routes/web.php:304`). A diferencia de otros módulos vistos en rondas previas (p.ej. asistencia, donde Admin tiene bypass vía `Gate::before`), aquí Admin **no** tiene excepción — el middleware de ruta bloquea a cualquier rol que no sea Estudiante, sin importar cuál.
- **UC-SS16** — [FUNCIONA — confirmado por revisión de código, ver nota metodológica abajo] Clases muy cortas (~30 min) en el grid: no se ve texto cortado a la mitad. `ScheduleEventCard.vue` calcula `compact` (`height < PX_PER_HOUR`, 56px/hora) y `veryCompact` (`compact && height < 34`) explícitamente para este caso — el propio comentario en el código (líneas previas a `const veryCompact = ...`) documenta el cálculo exacto para una clase de 30 min: `scheduleHeight` = 28px → `height` renderizado = 26px, por debajo del umbral de 34px, por lo que el componente **oculta por completo** la fila de metadata compacta (`evt-compact-meta`) y muestra únicamente `evt-title` (nombre de la materia), con `-webkit-line-clamp:1` + `overflow:hidden` (corte limpio con ellipsis, nunca a mitad de glifo). Esto coincide exactamente con el comportamiento pedido ("debería mostrar solo el nombre de la materia si no entra el resto").
- **UC-SS17** — [FUNCIONA, confirmado en vivo] Sidebar → menú hamburguesa en mobile: verificado no solo en `/student/schedule` sino también en `/enrollment` y `/settings/profile` (ver nota metodológica de UC-SS16/HLZ-47 abajo) — el menú abre/cierra correctamente y la navegación funciona en las tres pantallas, sin romper el layout.

### Nota metodológica — UC-SS16 (verificación por código, no por click en vivo)

No se pudo construir una clase real de ~30 min en los datos de prueba para hacer un click-through 100% en vivo: **los 120 bloques de horario existentes en el sistema son, sin excepción, de 2h30m** (confirmado programáticamente escaneando el texto de toda la vista `/scheduling/schedules` sin filtro). Crear uno nuevo de prueba vía el admin ("Nuevo horario") quedó bloqueado por un hallazgo colateral genuino: **todos los profesores que dictan secciones reales están en o por encima de su límite configurado de horas/semana** (ver `HLZ-48` nuevo abajo), por lo que la validación de `CreateClassSessionAction`/`EditSchedule` rechaza cualquier bloque nuevo (incluso de 30 min) para cualquiera de ellos. Subir el límite de un profesor puntualmente para destrabar la prueba fue bloqueado por el propio entorno de este QA como modificación de un recurso compartido (la app está en desarrollo activo en paralelo por `cacao_dev`), así que se optó por verificar el UC leyendo directamente `resources/js/components/scheduling/ScheduleEventCard.vue` y `useScheduleLayout.ts` (código fuente real, no una suposición) — la lógica y el comentario del propio autor confirman el comportamiento exacto pedido para 30 min. Se recomienda a `cacao_dev` una confirmación visual liviana (con datos de prueba propios o subiendo temporalmente el límite de un profesor sin alumnos reales asignados) si se quiere un check visual adicional, pero el código deja poco margen de duda.

---

## Tests existentes (Feature/Dusk)

| UC(s) | Archivo | Cobertura |
|---|---|---|
| UC-SS03, SS04 | `tests/Feature/Student/DashboardTest.php` | `today_schedules` presente (solo `->has()`, sin validar contenido/filtrado); casos sin período activo y sin enrollment |
| UC-SS05 | `tests/Feature/Student/DashboardTest.php` | Presencia de props, no valida cada campo individualmente |
| **UC-SS01, SS02** | — | **sin cobertura — ningún test verifica el filtrado por status; requiere el fix de HLZ-39 primero** |
| **UC-SS02b** | — | **sin cobertura — ningún test cubre el caso domingo; requiere el fix de HLZ-42 primero** |
| UC-SS06 | — | sin cobertura (campo ausente) |
| **UC-SS07–SS15, SS17** | — | **sin test automatizado encontrado — todo lo confirmado esta ronda fue por click-through manual; recomendado un `tests/Browser/Student/StudentScheduleTest.php`** |
| **UC-SS16** | — | **sin test — recomendado un test de componente (Vitest) para `ScheduleEventCard.vue` fijando `schedule` con duración de 30 min y aseverando que `evt-compact-meta` no se renderiza y `evt-title` sí, para no depender de datos de horario reales** |

---

## Notas de implementación pendiente

- Ver `HLZ-42` en `specs/qa/backlog.md` — **prioridad CRÍTICA**, crash 500 total del dashboard cualquier domingo, confirmado en vivo. Recomendado arreglar antes que HLZ-39 (misma línea de código).
- Ver `HLZ-39` en `specs/qa/backlog.md` para el detalle técnico del fix de filtrado por status.
- Ver `HLZ-48` (nuevo, Ronda 3) en `specs/qa/backlog.md` — límite de horas/semana ya agotado o superado para todos los profesores activos, lo que bloquea cualquier alta nueva de horario en el admin, incluidos bloques de prueba cortos como el de UC-SS16.
- `DayOfWeek` (y por extensión el selector "Día" de "Nuevo horario"/"Editar horario" en el admin) sigue sin soportar Domingo — mismo gap ya documentado en Ronda 1 (HLZ-42), consistente con que el grid de `/student/schedule` también solo muestra Lunes–Sábado.
