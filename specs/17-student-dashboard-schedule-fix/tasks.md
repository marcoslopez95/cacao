# Tasks — Student Dashboard Schedule Fix (feature 17)

- [ ] Task 1 — `DashboardController::index()`: cambiar `DayOfWeek::from()` por `DayOfWeek::tryFrom()` con fallback `'Sin clases hoy'`
- [ ] Task 2 — `DashboardController::index()`: usar `confirmedDetails` en vez de `details` (eager-load y construcción de `today_schedules`)
- [ ] Task 3 — Test Feature: `GET /student/dashboard` con `now()` mockeado a domingo → 200, `today_schedules` vacío
- [ ] Task 4 — Test Feature: `EnrollmentDetail` `draft`/`rejected` del día actual no aparece en `today_schedules`
- [ ] Task 5 — Test Feature de regresión: día normal, todo `confirmed` → comportamiento sin cambios
- [ ] Task 6 — `vendor/bin/sail artisan test --compact tests/Feature/Student/` — todo en verde
- [ ] Task 7 — `vendor/bin/sail bin pint --dirty --format agent`
- [ ] Task 8 — QA Gate: qa_manager verifica todos los UCs de `specs/17-student-dashboard-schedule-fix/qa.md` en verde
