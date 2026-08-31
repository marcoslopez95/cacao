# Tasks — Guardian Grades Multi-Student (feature 18)

- [x] Task 1 — `Guardian\GradeController::index()`: aceptar `student_id`, validar contra `$guardian->students()->where('educational_level', '!=', University)->find()`, fallback a `first()` sin `student_id`, `abort_if`/`abort_unless` correspondientes
- [x] Task 2 — `Guardian\GradeController::index()`: agregar props `student_id` y `students` (lista de elegibles) al render de Inertia
- [x] Task 3 — `Guardian\DashboardController::index()`: agregar `where('educational_level', '!=', University)` al `studentsQuery`
- [x] Task 4 — `guardian/Grades/Index.vue`: selector de estudiante visible cuando `students.length > 1`, navega con `router.get` pasando `student_id`
- [x] Task 5 — Test Feature: representante con 2 estudiantes, `student_id` del segundo → notas del segundo (RF-01)
- [x] Task 6 — Test Feature: `student_id` ajeno al representante → 403 (RF-03)
- [x] Task 7 — Test Feature: vínculo forzado con estudiante `university` (factory) → no aparece en dashboard ni accesible en grades (RF-05); regresión detectada y corregida en `tests/Browser/Guardian/GuardianEnrollmentTest.php` (fixture `->secondary()`)
- [x] Task 8 — `vendor/bin/sail artisan test --tia --compact` — 927 passed, 0 failures
- [x] Task 9 — `vendor/bin/sail bin pint --dirty --format agent` — pass
- [x] Task 10 — QA Gate: tester en modo `feature-gate` verifica todos los UCs de `specs/18-guardian-grades-multi-student/qa.md` en verde (2026-08-30 — 4/4 UCs PASS, incluida regresión de `GuardianEnrollmentTest.php`)
