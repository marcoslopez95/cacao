# Sin feature activa — backlog de QA crítico cerrado (2026-08-30)

Las 3 features de la cola (`16`, `17`, `18`) quedaron `completed`. Cierran HLZ-38, HLZ-39,
HLZ-40, HLZ-41, HLZ-42, HLZ-43 de `specs/qa/backlog.md`. `15-attendance-module` no aplica —
ya estaba completada externamente (ver historial abajo). Próximo paso: pedir al humano qué
sigue (no queda ninguna feature pendiente en `feature_list.json`).

## Cola de features (orden acordado) — cerrada

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
