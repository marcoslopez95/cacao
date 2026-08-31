# Tasks — Attendance Scheduling and Recovery (feature 20)

- [x] Task 1 — `ClassSessionPolicy`: agregar `isWithinScheduleWindow()` privado; extender `create()` con parámetro `?string $type` y aplicar el guard solo para `regular`; extender `takeAttendance()` aplicando el guard solo si `$classSession->type === Regular`; agregar `cancel()`
- [x] Task 2 — `StoreClassSessionRequest` y `AdminStoreClassSessionRequest`: pasar `$this->input('type')` al `can('create', ...)`
- [x] Task 3 — `app/Actions/Attendance/CancelClassSessionAction.php`: nueva Action, guard de estado (`scheduled`/`held` → `cancelled`, rechaza el resto)
- [x] Task 4 — Rutas `PATCH .../sessions/{classSession}/cancel` (Profesor y Admin) + regenerar Wayfinder
- [x] Task 5 — `Professor\AttendanceController::cancelSession()` y `Admin\AttendanceController::cancelSession()`
- [x] Task 6 — Frontend: botón "Cancelar" en `professor/attendance/Index.vue` y `admin/attendance/SectionIndex.vue`, visible en `scheduled`/`held`, con confirmación
- [x] Task 7 — Tests Feature: RF-01 a RF-09 (cubierto por `Acceptance/` del tester en `pre`, verificado en `task-gate`)
- [x] Task 8 — `vendor/bin/sail artisan test --tia --compact` — 957 passed, 0 failures
- [x] Task 9 — `vendor/bin/sail bin pint --dirty --format agent` — pass
- [x] Task 10 — QA Gate: tester en modo `feature-gate` verifica todos los UCs de `specs/20-attendance-scheduling-and-recovery/qa.md` en verde (2026-08-31 — 7/7 UCs PASS, sin HLZs nuevos)
