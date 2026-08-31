# Feature activa: `16-enrollment-level-guard-and-period-fix`

**Plan:** `specs/16-enrollment-level-guard-and-period-fix/tasks.md`
**Estado:** PENDIENTE — 0/8 tasks completadas

Prioridad decidida con el usuario (2026-08-30): atacar primero el backlog de QA crítico
(HLZ-38, HLZ-43, HLZ-39, HLZ-42, HLZ-40, HLZ-41) antes de continuar con `15-attendance-module`,
que queda en cola sin empezar.

## Tareas

- [ ] Task 1 — `EnrollmentPolicy::create()`: guard de `educational_level`
- [ ] Task 2 — `EnrollmentController::index()`: Gate::authorize + resolución de período por tipo
- [ ] Task 3 — `buildRules()`: label del Lapso vigente
- [ ] Task 4 — `student/Dashboard.vue`: ocultar CTA de inscripción para no-universitarios
- [ ] Task 5 — Tests Feature RF-01 a RF-05
- [ ] Task 6 — Test suite en verde
- [ ] Task 7 — Pint
- [ ] Task 8 — QA Gate

## Cola de features (orden acordado)

1. `16-enrollment-level-guard-and-period-fix` — activa (arriba)
2. `17-student-dashboard-schedule-fix` — HLZ-39+42, pendiente
3. `18-guardian-grades-multi-student` — HLZ-40+41, pendiente
4. `15-attendance-module` — ya no aplica, ver nota abajo

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
