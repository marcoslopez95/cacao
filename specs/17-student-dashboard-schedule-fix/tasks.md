# Tasks — Student Dashboard Schedule Fix (feature 17)

- [x] Task 1 — `DashboardController::index()`: ya resuelto fuera del arnés (commit `2a91c45`) — verificado en `pre` (RF-01 en verde, regresión confirmada)
- [x] Task 2 — `DashboardController::index()`: usar `confirmedDetails` en vez de `details` (eager-load y construcción de `today_schedules`), preservando `details` para `subjects_count`
- [x] Task 3 — Test Feature: `GET /student/dashboard` un domingo → 200, `today_schedules` vacío (RF-01)
- [x] Task 4 — Test Feature: `EnrollmentDetail` `draft`/`rejected` del día actual no aparece en `today_schedules` (RF-03)
- [x] Task 5 — Test Feature de regresión: día normal, todo `confirmed` → comportamiento sin cambios (RF-02)
- [x] Task 6 — `vendor/bin/sail artisan test --tia --compact` — 915 passed, 0 failures
- [x] Task 7 — `vendor/bin/sail bin pint --dirty --format agent` — pass
- [x] Task 8 — QA Gate: tester en modo `feature-gate` verifica todos los UCs de `specs/17-student-dashboard-schedule-fix/qa.md` en verde (2026-08-30 — 3/3 UCs PASS, sin HLZs nuevos)
