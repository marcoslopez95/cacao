# Feature anterior completada

**Feature:** `15-attendance-module`
**Plan:** `specs/15-attendance-module/tasks.md`
**Estado:** COMPLETADA — 15/15 tasks completadas (reconciliado 2026-08-30 vía auditoría, código ya estaba implementado y commiteado pero tasks.md/feature_list.json no se habían sincronizado)

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
- [x] Task 15 — QA Gate: primer intento (2026-08-30) rechazado por Dusk (0/5 UCs — HLZ-38/39/40/41). `implementer` corrigió los 4 hallazgos, `reviewer` aprobó sin observaciones, `tester` re-corrió el gate: **5/5 UCs en verde vía Dusk** (`tests/Browser/Attendance/AttendanceQATest.php`, 36 assertions) + 87 tests Pest en verde. Feature marcada `completed` en `feature_list.json`.

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
