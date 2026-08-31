# Tasks — Guardian Grades Multi-Student (feature 18)

- [ ] Task 1 — `Guardian\GradeController::index()`: aceptar `student_id`, validar contra `$guardian->students()->where('educational_level', '!=', University)->find()`, fallback a `first()` sin `student_id`, `abort_if`/`abort_unless` correspondientes
- [ ] Task 2 — `Guardian\GradeController::index()`: agregar props `student_id` y `students` (lista de elegibles) al render de Inertia
- [ ] Task 3 — `Guardian\DashboardController::index()`: agregar `where('educational_level', '!=', University)` al `studentsQuery`
- [ ] Task 4 — `guardian/Grades/Index.vue`: selector de estudiante visible cuando `students.length > 1`, navega con `router.get` pasando `student_id`
- [ ] Task 5 — Test Feature: representante con 2 estudiantes, `student_id` del segundo → notas del segundo
- [ ] Task 6 — Test Feature: `student_id` ajeno al representante → 403
- [ ] Task 7 — Test Feature: vínculo forzado con estudiante `university` (factory) → no aparece en dashboard ni accesible en grades
- [ ] Task 8 — `vendor/bin/sail artisan test --compact tests/Feature/Guardian/` — todo en verde
- [ ] Task 9 — `vendor/bin/sail bin pint --dirty --format agent`
- [ ] Task 10 — QA Gate: qa_manager verifica todos los UCs de `specs/18-guardian-grades-multi-student/qa.md` en verde
