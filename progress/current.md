# Feature completada: 23-user-form-hlz-fixes (2026-10-17)

**Estado:** COMPLETADA — 6/6 tasks.
**Plan:** `specs/23-user-form-hlz-fixes/tasks.md`

Resuelve HLZ-50..54 (Task 22 de user-form-views). RED: 6 tests de `UserFormTask22VisualVerificationTest.php`.

---

# Feature completada: 22-qa-round3-followup-fixes (2026-09-13)

**Estado:** COMPLETADA — 4/4 tasks completadas.
**Plan:** `specs/22-qa-round3-followup-fixes/tasks.md`

Agrupó 3 correcciones puntuales de la Ronda 3 de QA: HLZ-43 (ampliación — label de período
"Xer trimestre" corregido para universitarios), HLZ-47 (`/settings/profile` ahora recibe `user`
como prop tipada vía `ProfileUserResource`, en vez de leer `usePage().props.auth.user` en la
página) y HLZ-48 (seeders demo suben `weekly_hour_limit` de los profesores sobre su límite real).
Durante la implementación se encontró y corrigió un bug latente de Vue: `:default-value` nunca
funcionó en `Profile.vue` (ni presumiblemente en `teams/Edit.vue`, que usa el mismo patrón sin
test que lo cubra) — cambiado a `:value`, verificado sin regresión en el flujo de guardado.

## Feature Gate (Task 4) — 2026-09-13
✅ APROBADO — 4/4 UCs de `specs/22-qa-round3-followup-fixes/qa.md` en verde. 9 tests Feature
(`tests/Feature/QaRound3FollowupFixes/Acceptance/`) + 7 tests Dusk
(`tests/Browser/Enrollment/QaRound3PeriodLabelTest.php`,
`tests/Browser/Settings/QaRound3ProfilePrefillTest.php`). Sin HLZs generados.

## Task 3 completada — HLZ-48

Verificado antes de tocar nada: `git status` sobre `database/seeders/` estaba limpio (sin cambios sin
commitear) y el único solapamiento real de `demo-seeder-realism` con `DemoSectionsSeeder.php` (su
Task 5, rango `period_number ∈ {2,4,6,8}`) ya estaba mergeado en master — sin conflicto, se procedió.

`database/seeders/Demo/DemoSectionsSeeder.php`: nuevo método privado
`raiseWeeklyHourLimitsForOverloadedProfessors()`, llamado al final de `run()` (después de
`seedSchoolSections()`, que es lo último que corre en el seeder). Para cada profesor con al menos un
`Schedule` sembrado, calcula su carga real con
`ScheduleConflictService::professorCurrentWeeklyHours()` (el mismo cálculo que usa la validación de
producción — reutilizado, no reimplementado) y, si supera su `weekly_hour_limit` actual, lo sube a
`ceil(carga_real) + 5`. Margen elegido: 5h fijas (`WEEKLY_HOUR_LIMIT_MARGIN`), documentado en el
PHPDoc del método — aproximadamente un day-pair adicional de 2.5h (2 días), para dejar margen real a
una futura alta de horario sin volver a tocar el límite. Idempotente: se recalcula desde los
`Schedule` reales en cada corrida, solo sube el límite (nunca lo baja), y no hace nada si el límite
ya cubre la carga — seguro en `migrate:fresh --seed` repetido.

Estado real verificado en la DB de desarrollo antes del fix (13 profesores con `Schedule`): 8 en
25.0h/límite 20 (excedidos) y 5 en 20.0h/límite 20 (exactos, sin margen, no tocados por no ser
`>`). Con el fix, los 8 excedidos quedan en límite 30 (25 + 5) — con margen real para un bloque
nuevo de 30 min o más.

Tests: `DemoSeederWeeklyHourLimitAcceptanceTest` (2/2, `--tia --compact`) en verde — no se tocó (es
el contrato del tester). `DemoSeederTest` completo (8/8, 60 assertions) sin regresión. Pint sin
observaciones (`pint --dirty --format agent` → pass).

Nada bloqueó la task: sin conflicto real con `demo-seeder-realism`, archivo intocable no tocado.

## Task 2 completada — HLZ-47

`app/Http/Resources/Settings/ProfileUserResource.php` (nuevo): `toArray()` devuelve `id`, `name`,
`email`, `email_verified_at` (campo agregado tras el bloqueo del tester en `pre`, ver design.md
nota 2026-09-13, para no romper el badge "Correo verificado."). `ProfileController::edit()` pasa
`'user' => new ProfileUserResource($request->user())` como prop explícita junto a `mustVerifyEmail`
y `status`. `Profile.vue` reemplaza `const user = computed(() => page.props.auth.user)` por
`defineProps<Props>()` tipada (`user: { id, name, email, email_verified_at }`), eliminando el uso de
`usePage()` en el cuerpo de la página (violación de convención señalada en `CLAUDE.md`).

**Hallazgo no previsto en el diseño, corregido dentro del mismo archivo (Profile.vue, no protegido):**
`ProfileUserResource` necesitó `public static $wrap = null;` (mismo patrón ya usado en
`DemographicProfileResource`) — sin esto, Inertia envuelve el resource en `{"data": {...}}` y el
prop queda en `user.data.id` en vez de `user.id`, rompiendo el contrato de
`ProfileEditPropsAcceptanceTest` (`user.id` no existe). Confirmado con `->dump()` de
`AssertableInertia` antes de aplicar el fix.

**Segundo hallazgo, más importante — bug preexistente en el binding, no introducido por esta task:**
el Dusk test (`QaRound3ProfilePrefillTest`) seguía en rojo (inputs con valor `''`) incluso con el
prop correcto y el build de Vite regenerado. Verificado con el HTML real renderizado (dump de
`getPageSource()` en un test Dusk temporal, borrado después): Vue emitía literalmente el atributo
`default-value="X"` en el DOM — no el atributo nativo `value` — porque `shouldSetAsProp` en
`@vue/runtime-dom` (`runtime-dom.cjs.js:749`) evalúa `key in el` con la clave tal cual se escribe en
el template (`"default-value"`, con guion), y esa cadena nunca es una propiedad real del elemento
(la IDL real es `defaultValue`, camelCase) — cae a `setAttribute`, que no afecta el `.value` real del
input. Esto ya afectaba a `Profile.vue` ANTES del fix de esta task (la instrucción original de no
cambiar el binding asumía, sin verificarlo con un test de browser, que `:default-value` ya
funcionaba — cita como referencia `teams/Edit.vue:111`, que usa el mismo patrón pero nunca tuvo un
test Dusk que lo verificara). Fix aplicado: `:default-value` → `:value` en los dos inputs
(`#name`/`#email`) de `Profile.vue`. No se tocó ningún archivo protegido — el fix vive enteramente
en `Profile.vue`, que es el archivo que esta task ya tenía autorización para modificar. El `v-if`
del badge y el resto del comportamiento no cambiaron.

Tests en verde: `ProfileEditPropsAcceptanceTest` (5/5, Feature, `--tia`) +
`QaRound3ProfilePrefillTest` (5/5, Dusk, UC-QA-03 desktop+mobile, 4 roles). Pint sin cambios
necesarios (los 2 archivos PHP tocados ya cumplían el formato). Se corrió `npm run build` dos veces
(prop nuevo + fix de binding) porque Dusk usa el bundle compilado, no Vite dev.

**Reportar al leader:** el fix de `:value` en `Profile.vue` es una desviación de la instrucción
literal de la delegación ("los `:default-value` ... no cambian de comportamiento"). Los tests no
estaban mal — el bug era real y preexistente; sin el cambio, `QaRound3ProfilePrefillTest` queda en
rojo indefinidamente. Ningún archivo intocable fue tocado.

Agrupa 3 correcciones puntuales de la Ronda 3 de QA: HLZ-43 (ampliación — label de período
"Xer trimestre" también afecta a universitarios), HLZ-47 (`/settings/profile` carga vacío por
leer `usePage().props.auth.user` en la página en vez de una prop tipada) y HLZ-48 (profesores
demo sembrados por encima de su `weekly_hour_limit`, sube el límite en vez de tocar
secciones/horarios ya usados por el QA de la feature 21).

## Task 1 completada — HLZ-43 (ampliación)

`EnrollmentController::buildRules()` ahora condiciona `$periodLabel` explícitamente por
`$period->type`: `PeriodType::Semester` usa `$period->name` (nombre real del período,
nunca el fallback "Xer trimestre"); cualquier otro caso (incluido `Year`, antes de que el
bloque `if` posterior lo pise con el `Lapse` vigente) preserva exactamente la expresión
original basada en `$student->academic_year`, sin cambio de comportamiento para `Year`.
`resources/js/pages/enrollment/Index.vue`: el copy de ayuda hardcodeado "este trimestre" se
reemplazó por `{{ uiRules.trimester }}` (ya resuelto por backend), igual que el encabezado de
la línea de arriba.

Se corrió `npm run build` porque el Dusk inicial falló contra el bundle viejo (`este trimestre`
aún compilado) — después del build, verde.

Tests Task 1 en verde: `EnrollmentPeriodLabelAcceptanceTest` (2/2, Feature) +
`QaRound3PeriodLabelTest` (2/2, Dusk, UC-QA-01/02). Pint sin cambios necesarios.

---

# Feature completada: 21-student-schedule-view (2026-09-13)

**Estado:** COMPLETADA — 7/7 tasks completadas.
**Plan:** `specs/21-student-schedule-view/tasks.md`

Nueva vista `/student/schedule` con el horario semanal completo del estudiante, reutilizando
`WeeklyGrid`/`ScheduleListView` sin modificarlos (solo montaje read-only). Durante el desarrollo se
encontraron y corrigieron 2 bugs reales preexistentes en componentes compartidos con el módulo admin
(aprobado por el humano como extensión de Task 2): `ScheduleEventCard.vue` ocultaba profesor/aula en
modo `compact` (clases < 60 min) y recortaba contenido a mitad de glifo en clases muy cortas
(fix: nuevo umbral `veryCompact`); `AppSidebarLayout.vue` no tenía breakpoint responsivo, rompiendo
cualquier página de la app en viewport < 1024px (fix: drawer/scrim con `useMediaQuery` de
`@vueuse/core`, patrón ya existente en el proyecto). Ambos fixes verificados sin regresión contra el
módulo admin de horarios (`ScheduleViewsTest.php`, 10/10).

## Feature Gate (Task 7) — 2026-09-13
✅ APROBADO — 9/9 UCs de `specs/21-student-schedule-view/qa.md` en verde. 9 tests Dusk
(`tests/Browser/Academic/StudentScheduleTest.php`) + 14 tests Feature
(`tests/Feature/StudentScheduleView/Acceptance/StudentScheduleAcceptanceTest.php`). Suite completa:
971 tests, 0 failures. Sin HLZs generados.

## Task 6 completada — Wayfinder + Pint

Wayfinder regenerado sin diff (ya reflejaba `student.schedule.index` desde Task 3). Pint formateó
solo `tests/Browser/Academic/StudentScheduleTest.php` (orden de imports/tipos, sin tocar lógica ni
assertions). Suite Pest completa (971) y Dusk de la feature (9/9) en verde.

## Tasks 4-5 completadas — Auditoría de cobertura de tests

Todos los escenarios de Task 4 (Feature) ya estaban cubiertos por los 14 tests de `pre` de Task 1.
Task 5 (Dusk) tenía un gap real: "estado vacío" no tenía test Dusk propio (`qa.md` lo marcaba
"pendiente"). El tester agregó 2 tests Dusk (`UC-QA-03` desktop/mobile) — pasaron en verde de
inmediato, el código de aplicación ya soportaba el caso correctamente. Suite Dusk de la feature:
9/9 verde. Suite Pest completa: 971 tests verde.

## Task 3 completada — Link del dashboard

`student/Dashboard.vue`: `<Link dusk="view-full-schedule-link">` vía Wayfinder (`@/routes/student/schedule`)
en el widget "Hoy", hacia `student.schedule.index`. Con esta task, los 9 UCs de
`specs/21-student-schedule-view/qa.md` ya tienen cobertura verde: UC-QA-01/02/06/07/08 vía Dusk
(`StudentScheduleTest.php`, 7/7), UC-QA-03/04/05/09 vía Feature (`StudentScheduleAcceptanceTest.php`,
14/14). Tasks 4 y 5 (escribir tests Feature/Dusk) ya están de facto satisfechas por los tests
escritos en modo `pre` de Tasks 1-3 — falta confirmar formalmente que cubren cada escenario listado
en `tasks.md` y cerrar Task 6 (Wayfinder regenerado + Pint) antes del Feature Gate (Task 7).

## Task 2 completada (+ 2b/2c) — Frontend

`student/Schedule.vue` monta `WeeklyGrid` (desktop, `hidden lg:block`) y `ScheduleListView` (mobile,
`lg:hidden`), ambos read-only (`can-update`/`can-delete` en `false`, sin wiring de emits/click).

Durante el gate se descubrieron y corrigieron 2 bugs preexistentes en componentes compartidos con el
módulo admin de horarios (aprobado por el humano ampliar el alcance de esta task):
- **Task 2b** — `ScheduleEventCard.vue` ocultaba profesor/aula en modo `compact` (clases < 60 min).
  Fix: rediseño de la línea de metadata compacta + nuevo umbral `veryCompact` (height < 34px) que
  oculta la metadata en vez de recortarla a mitad de glifo en clases muy cortas (~30 min). No se tocó
  `useScheduleLayout.ts`/`PX_PER_HOUR`. 1 ronda de rechazo del reviewer (faltaba el umbral defensivo
  vertical) → corregida y re-aprobada.
- **Task 2c** — `AppSidebarLayout.vue` no tenía breakpoint responsivo (240px fijos rompían cualquier
  página en viewport < 1024px, no solo esta feature). Fix: `useMediaQuery` de `@vueuse/core`
  (patrón ya usado en `enrollment/Index.vue`, sin librerías nuevas) + drawer/scrim + botón hamburguesa
  en `AppTopbar.vue`.

Sin regresión: `ScheduleViewsTest.php` (admin, mismos componentes) 10/10 verde. `StudentScheduleTest.php`
6/6 verde. Suite Pest completa 971 tests verde. UC-QA-01/02/07/08 de `qa.md` en verde. UC-QA-06 (link
del dashboard) pendiente para Task 3.

## Task 1 completada — Backend

`Student\ScheduleController@index` (4 líneas, sin lógica de negocio) + `StudentScheduleResolver`
(Service, no Action — lectura pura, sigue el patrón de `GradeConfigResolver`/`PrerequisiteValidator`)
+ ruta `student.schedule.index` dentro del grupo `role:Estudiante`. Reutiliza
`App\Http\Resources\Scheduling\ScheduleResource` existente (mismo shape, sin duplicar). Filtra
`EnrollmentDetail::confirmed` del período activo + vigencia del `Schedule` (`valid_from`/`valid_until`,
borde `valid_until = hoy` incluido). `resources/js/pages/student/Schedule.vue` es un stub mínimo —
el montaje real de `WeeklyGrid`/`ScheduleListView` es Task 2.

14 tests Feature en verde (`tests/Feature/StudentScheduleView/Acceptance/StudentScheduleAcceptanceTest.php`).
Reviewer aprobó sin observaciones. Tester (task-gate) aprobó: UCs backend-verificables de `qa.md`
(UC-QA-03/04/05/09 + mitad backend de UC-QA-01/07) en verde; UCs que dependen de UI
(UC-QA-01 mitad frontend, 02, 06, 07 mitad frontend, 08) quedan pendientes para Tasks 2/3/5 y se
verifican en el Feature Gate (Task 7).

Resumen: nueva vista `/student/schedule` con el horario semanal completo del estudiante
(secciones `EnrollmentDetail::confirmed` del período activo, filtradas por vigencia),
reutilizando sin modificar `WeeklyGrid.vue` (desktop) y `ScheduleListView.vue` (mobile),
alternados por CSS puro (sin composable de breakpoint por JS — el proyecto no tiene ese
patrón). Link "Ver horario completo →" agregado al dashboard del estudiante.

---

# Sin feature activa — ronda de QA de asistencia cerrada (2026-08-31)

Las 2 features de la cola (`19`, `20`) quedaron `completed`. Cierran HLZ-44, HLZ-45, HLZ-46 de
`specs/qa/backlog.md` — no queda ningún HLZ pendiente en el backlog. Próximo paso: pedir al
humano qué sigue (no queda ninguna feature pendiente en `feature_list.json`).

## Cola de features (orden acordado) — cerrada

1. `19-attendance-advance-guard-fix` — ✅ completada (HLZ-45, crítico)
2. `20-attendance-scheduling-and-recovery` — ✅ completada (HLZ-44 + HLZ-46)

---

## Feature anterior completada

**Feature:** `20-attendance-scheduling-and-recovery`
**Plan:** `specs/20-attendance-scheduling-and-recovery/tasks.md`
**Estado:** COMPLETADA — 10/10 tasks completadas (2026-08-31)

Cierra HLZ-44 (sin restricción horaria al crear/pasar lista en sesiones Regulares — ahora 403
duro fuera de la ventana real del `Schedule` de la sección; Adelanto/Recuperación exentos) y
HLZ-46 (faltaba el flujo de "Cancelar sesión", prerequisito real de Recuperación — nueva
`CancelClassSessionAction`, ruta y botón en Profesor + Admin). El `tester` detectó y corrigió en
`task-gate` una regresión cruzada real en `tests/Browser/Attendance/AttendanceQATest.php`
(feature 15): su fixture creaba sesiones sin ningún `Schedule`, incompatibles con el guard nuevo.

## Tareas

- [x] Task 1 — `ClassSessionPolicy`: guard horario (`create`/`takeAttendance`) + `cancel()`
- [x] Task 2 — `StoreClassSessionRequest`/`AdminStoreClassSessionRequest`: pasar `type` al Gate
- [x] Task 3 — `CancelClassSessionAction` nueva
- [x] Task 4 — Rutas `PATCH .../cancel` (Profesor + Admin) + Wayfinder
- [x] Task 5 — `cancelSession()` en ambos controllers
- [x] Task 6 — Botón "Cancelar" en `AttSessionCard.vue` (compartido Profesor/Admin vía prop `variant`)
- [x] Task 7-9 — Tests Feature RF-01 a RF-09, suite en verde (957 passed), Pint
- [x] Task 10 — QA Gate (7/7 UCs PASS)

---

## Feature anterior completada

**Feature:** `19-attendance-advance-guard-fix`
**Plan:** `specs/19-attendance-advance-guard-fix/tasks.md`
**Estado:** COMPLETADA — 6/6 tasks completadas (2026-08-31)

Cierra HLZ-45 (crítico): `CreateAdvanceSessionAction::handle()` ahora rechaza vincular un
adelanto a una sesión que ya está `advanced`, portando el mismo guard que ya existía en
`CreateMakeupSessionAction`. Sin cambios de diseño durante la implementación — el fix era
mecánico desde el mapa inicial.

## Tareas

- [x] Task 1 — Guard en `CreateAdvanceSessionAction`
- [x] Task 2-3 — Tests Feature RF-01/02/03/04
- [x] Task 4 — Suite en verde (932 passed)
- [x] Task 5 — Pint
- [x] Task 6 — QA Gate (2/2 UCs PASS)

---

## Historial — backlog de QA crítico cerrado (2026-08-30)

Las 3 features de la cola anterior (`16`, `17`, `18`) quedaron `completed`. Cerraron HLZ-38,
HLZ-39, HLZ-40, HLZ-41, HLZ-42, HLZ-43 de `specs/qa/backlog.md`. `15-attendance-module` no
aplicó — ya estaba completada externamente.

1. `16-enrollment-level-guard-and-period-fix` — ✅ completada
2. `17-student-dashboard-schedule-fix` — ✅ completada
3. `18-guardian-grades-multi-student` — ✅ completada

---

## Feature anterior completada

**Feature:** `18-guardian-grades-multi-student`
**Plan:** `specs/18-guardian-grades-multi-student/tasks.md`
**Estado:** COMPLETADA — 10/10 tasks completadas (2026-08-30)

Cierra HLZ-40 (vínculo guardian↔estudiante sin restricción de nivel) y HLZ-41 (GradeController
solo mostraba el primer estudiante vinculado). `GradeController` y `DashboardController` de
guardian ahora filtran `educational_level != University`; `GradeController` acepta `?student_id=`
con selector nuevo en `guardian/Grades/Index.vue`. El `tester` detectó y corrigió en `task-gate`
una regresión real en `tests/Browser/Guardian/GuardianEnrollmentTest.php` (fixture dependía de un
estudiante `university` vinculado a un guardian, ya no válido bajo HLZ-40).

## Tareas

- [x] Task 1 — `GradeController`: aceptar `student_id` + filtro `educational_level`
- [x] Task 2 — `GradeController`: props `student_id` + `students`
- [x] Task 3 — `DashboardController`: filtro `educational_level`
- [x] Task 4 — `guardian/Grades/Index.vue`: selector de estudiante
- [x] Task 5-7 — Tests Feature RF-01, RF-03, RF-05 (+ regresión Dusk corregida)
- [x] Task 8 — Suite en verde (927 passed)
- [x] Task 9 — Pint
- [x] Task 10 — QA Gate (4/4 UCs PASS)

---

## Feature anterior completada

**Feature:** `17-student-dashboard-schedule-fix`
**Plan:** `specs/17-student-dashboard-schedule-fix/tasks.md`
**Estado:** COMPLETADA — 8/8 tasks completadas (2026-08-30)

Cierra HLZ-39 (horario "Hoy" no filtraba por `EnrollmentDetail::status = confirmed`) y confirma
HLZ-42 (crash 500 los domingos), que ya estaba resuelto fuera del arnés en el commit `2a91c45`
mientras esta sesión trabajaba en las specs 16-18. Único cambio de código: `DashboardController`
usa `confirmedDetails` para `today_schedules`, preservando `details` para `subjects_count` (el
reviewer detectó y corrigió una regresión de N+1 en la primera ronda). Sin tests Dusk — los 3 UCs
son de solo lectura, sin formulario.

## Tareas

- [x] Task 1 — HLZ-42 verificado (ya resuelto externamente)
- [x] Task 2 — `DashboardController`: `confirmedDetails` para `today_schedules`
- [x] Task 3 — Test domingo → 200, vacío
- [x] Task 4 — Test excluye draft/rejected
- [x] Task 5 — Test regresión día normal
- [x] Task 6 — Suite en verde (915 passed)
- [x] Task 7 — Pint
- [x] Task 8 — QA Gate (3/3 UCs PASS)

---

## Feature anterior completada

**Feature:** `16-enrollment-level-guard-and-period-fix`
**Plan:** `specs/16-enrollment-level-guard-and-period-fix/tasks.md`
**Estado:** COMPLETADA — 10/10 tasks completadas (2026-08-30)

Cierra HLZ-38 (guard de nivel educativo en inscripción) y HLZ-43 (período mal resuelto + catálogo
vacío para no-universitarios). Durante `tester [pre]` se descubrió que el diseño original de
HLZ-43 estaba incompleto — `BuildEnrollmentCatalogAction` solo resolvía secciones vía FK directa
(`Subject::sections()`), pero las secciones escolares usan la tabla pivote `section_subjects` con
`subject_id = null`. El humano aprobó ampliar el diseño (Opción A): se agregó
`Subject::schoolSections(): BelongsToMany` y se combinaron ambas colecciones en la Action.

## Tareas

- [x] Task 1 — `EnrollmentPolicy::create()`: guard de `educational_level`
- [x] Task 2 — `EnrollmentController::index()`: Gate::authorize + resolución de período por tipo
- [x] Task 3 — `buildRules()`: label del Lapso vigente
- [x] Task 4 — `student/Dashboard.vue`: ocultar CTA de inscripción para no-universitarios
- [x] Task 5 — `Subject::schoolSections()` + `BuildEnrollmentCatalogAction` combinando ambas colecciones
- [x] Task 6 — Actualizar 3 tests existentes rotos por el guard nuevo
- [x] Task 7 — Tests Feature RF-01 a RF-07
- [x] Task 8 — Test suite en verde (911 passed, 0 failures)
- [x] Task 9 — Pint (pass)
- [x] Task 10 — QA Gate (4 Dusk PASS + 1 Feature PASS, sin HLZs nuevos)

---

## Feature anterior completada (fuera de esta sesión, mergeada vía PR)

**Feature:** `15-attendance-module`
**Plan:** `specs/15-attendance-module/tasks.md`
**Estado:** COMPLETADA — 15/15 tasks completadas (mergeada por PR #4/#5 mientras esta sesión trabajaba en las specs 16-18; reconciliada 2026-08-30, `feature_list.json` y `tasks.md` ya reflejaban el código implementado)

## Tareas

- [x] Task 1 — Migraciones: `class_sessions` + `attendance_records`
- [x] Task 2 — Enums + Modelos `ClassSession` y `AttendanceRecord` con relaciones
- [x] Task 3 — `ClassSessionPolicy`
- [x] Task 4 — Actions base: `CreateClassSessionAction` + `TakeAttendanceAction`
- [x] Task 5 — Actions de vínculo: `CreateMakeupSessionAction` + `CreateAdvanceSessionAction`
- [x] Task 6 — Backend profesor: `Professor\AttendanceController` + FormRequests + Resources
- [x] Task 7 — Backend admin: `Admin\AttendanceController` + FormRequests
- [x] Task 8 — Rutas + Wayfinder regenerado
- [x] Task 9 — Types TypeScript + composables (divergencia: consolidados en `attendance.ts`)
- [x] Task 10 — Componentes UI compartidos de asistencia
- [x] Task 11 — Frontend profesor: Index.vue
- [x] Task 12 — Frontend profesor: Sheet.vue + modal (divergencia: modal inline en Index.vue)
- [x] Task 13 — AttTotalsPanel.vue
- [x] Task 14 — Frontend admin: Index.vue
- [x] Task 15 — QA Gate: primer intento (2026-08-30) rechazado por Dusk (0/5 UCs). `implementer` corrigió 4 hallazgos documentados en `specs/15-attendance-module/qa.md` como "HLZ-38/39/40/41" — **⚠️ estos son números locales a esa feature, reutilizados por error/desconocimiento y NO tienen ninguna relación con HLZ-38/39/40/41 de `specs/qa/backlog.md`** (que son los que cubren las specs 16-18 de esta sesión: guard de nivel en inscripción, horario confirmado, vínculo guardian-universitario, selector multi-estudiante). `reviewer` aprobó, `tester` re-corrió el gate: **5/5 UCs en verde vía Dusk** (`tests/Browser/Attendance/AttendanceQATest.php`, 36 assertions) + 87 tests Pest en verde. Feature marcada `completed` en `feature_list.json`.

## Deuda técnica documentada (no bloqueante)
- `class_sessions.linked_session_id` usa `nullOnDelete()` en vez de `RESTRICT` (contradice convención FK RESTRICT del proyecto), cambiado deliberadamente en commit `9143cf6` por ciclo de FK, sin documentar como excepción formal.

---

## Feature anterior completada

**Feature:** `14-admin-team-institutional`
**Plan:** `specs/14-admin-team-institutional/tasks.md`
**Estado:** COMPLETADA — 6/6 tasks completadas

## Tareas

- [x] Task 1 — AdminTeamSeeder: crear team `admin` (slug=`admin`, is_personal=false) + asignación idempotente de admins existentes
- [x] Task 2 — CreateUserAction: auto-asignación al team `admin` al crear usuario con rol Admin
- [x] Task 3 — ResolvesLoginRedirect: resolver team por slug `admin` para rol Admin → `/admin/dashboard`; fallback a personalTeam()
- [x] Task 4 — TeamPolicy: bloquear delete del team con slug `admin`
- [x] Task 5 — DatabaseSeeder: registrar AdminTeamSeeder + ejecutar en dev para verificar idempotencia
- [x] Task 6 — QA Gate: qa_manager verifica todos los UCs de specs/14-admin-team-institutional/qa.md en verde

---

## Feature anterior completada

**Feature:** `13-schedule-career-filter`
HLZ-35 (filtro carrera server-side), HLZ-36 (URL persistence), HLZ-37 (pre-filtro en modals). 8/8 tasks.

## Tareas completadas

- [x] Task 1 — Backend: career_ids filter + careers prop + careerId en sections (HLZ-35)
- [x] Task 2 — Types: careerId en ScheduleAvailableSection (HLZ-37)
- [x] Task 3 — useScheduleFilters: careerIds param + applyFilters (HLZ-36)
- [x] Task 4 — ScheduleLegend: careers prop en vez de schedules (HLZ-35)
- [x] Task 5 — Index.vue: toggle server-side, wire careers + careerIds a legend y modals (HLZ-35+36)
- [x] Task 6 — CreateScheduleModal: activeCareerIds + visibleSections + banner (HLZ-37)
- [x] Task 7 — EditScheduleModal: activeCareerIds + visibleSections + banner (HLZ-37)
- [x] Task 8 — Dusk tests H74, H75, H76 (3/3 en verde)

---

# Feature anterior: `12-schedule-validation-and-hours-bar`

**Plan:** `specs/12-schedule-validation-and-hours-bar/tasks.md`
**Estado:** COMPLETADA — 4/4 tasks completadas

## Tareas

- [x] Task 1 — Fix StoreScheduleRequest: `after_or_equal:07:00` + `before_or_equal:18:00` (HLZ-33)
- [x] Task 2 — Fix UpdateScheduleRequest: mismas reglas (HLZ-33)
- [x] Task 3 — ProfessorHoursBar en EditScheduleModal: computed + import + template (HLZ-34)
- [x] Task 4 — Tests Dusk UC-H16, H17, H18b, H19 en verde

## Feature anterior completada

**Feature:** `11-hlz-30-31-schedule-fixes`
HLZ-30 (toast), HLZ-31 (migración down), HLZ-32 (redirect period_id). 33/33 tests.
