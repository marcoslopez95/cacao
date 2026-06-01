# Feature activa: `15-attendance-module`

**Plan:** `specs/15-attendance-module/tasks.md`
**Estado:** PENDIENTE — 0/12 tasks completadas

## Tareas

- [ ] Task 1 — Migraciones: `class_sessions` + `attendance_records`
- [ ] Task 2 — Enums + Modelos `ClassSession` y `AttendanceRecord` con relaciones
- [ ] Task 3 — `ClassSessionPolicy` + `AttendancePolicy`
- [ ] Task 4 — Actions base: `CreateClassSessionAction` + `TakeAttendanceAction`
- [ ] Task 5 — Actions de vínculo: `CreateMakeupSessionAction` + `CreateAdvanceSessionAction`
- [ ] Task 6 — Backend profesor: `Professor\AttendanceController` + FormRequests + Resources
- [ ] Task 7 — Backend admin: `Admin\AttendanceController` + FormRequests
- [ ] Task 8 — Rutas + Wayfinder regenerado
- [ ] Task 9 — Types TypeScript + composables
- [ ] Task 10 — Frontend profesor: Index.vue + Sheet.vue
- [ ] Task 11 — Frontend admin: Index.vue
- [ ] Task 12 — QA Gate

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
