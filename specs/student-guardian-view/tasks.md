# Tasks — student-guardian-view

- [x] T1 — Backend: agregar `guardians` al prop de `Student\DashboardController::index` (query + resolución de parentesco + orden por `is_primary`)
- [x] T2 — Frontend: agregar `StudentGuardianSummary` y `guardians` a `types/student-dashboard.ts`
- [x] T3 — Frontend: agregar sección "Mi representante" a `student/Dashboard.vue`
- [x] T4 — Tests: actualizar `tests/Feature/Student/DashboardTest.php` (prop presente, orden principal, caso vacío)
- [x] T5 — `vendor/bin/sail bin pint --dirty --format agent` + `vendor/bin/sail artisan test --compact --filter=DashboardTest` — 32 passed, pint pass
