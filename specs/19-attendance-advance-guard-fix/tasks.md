# Tasks — Attendance Advance Guard Fix (feature 19)

- [x] Task 1 — `CreateAdvanceSessionAction::handle()`: agregar guard que rechaza (`ValidationException`, mensaje "Esta sesión ya fue adelantada.") si la sesión vinculada ya tiene `status = Advanced`, portando el patrón de `CreateMakeupSessionAction::handle()`
- [x] Task 2 — Test Feature: RF-01/RF-02/RF-04 (request directo con `linked_session_id` ya `advanced` → 422 con el mensaje correcto, sin crear sesión nueva)
- [x] Task 3 — Test Feature de regresión: RF-03 (adelanto sobre sesión `scheduled` sigue funcionando)
- [x] Task 4 — `vendor/bin/sail artisan test --tia --compact` — 932 passed, 0 failures
- [x] Task 5 — `vendor/bin/sail bin pint --dirty --format agent` — pass
- [x] Task 6 — QA Gate: tester en modo `feature-gate` verifica todos los UCs de `specs/19-attendance-advance-guard-fix/qa.md` en verde (2026-08-31 — 2/2 UCs PASS, sin HLZs nuevos)
