# Tasks — Enrollment Level Guard and Period Fix (feature 16)

- [x] Task 1 — `EnrollmentPolicy::create()`: agregar guard de `educational_level` en ambas ramas (self-enroll solo `University`; guardian-enroll solo no-`University`), importar `EducationalLevel`
- [x] Task 2 — `EnrollmentController::index()`: agregar `Gate::authorize('create', [Enrollment::class, $student])` antes del `firstOrCreate`; resolver `$period` filtrando por `type` según `$student->educational_level`
- [x] Task 3 — `EnrollmentController::buildRules()`: resolver el `Lapse` vigente por fecha cuando `$period->type === PeriodType::Year` y usar su `name` como label en vez de `"{academic_year}er trimestre"`
- [x] Task 4 — `student/Dashboard.vue` (+ `DashboardController` si falta el dato): ocultar/deshabilitar el CTA "Ir a inscripciones →" cuando `educational_level !== 'university'`, con mensaje explicativo
- [x] Task 5 — `app/Models/Subject.php`: agregar `schoolSections(): BelongsToMany` (inversa de `Section::sectionSubjects()`); `BuildEnrollmentCatalogAction::handle()`: cargar `schoolSections` con los mismos filtros que `sections` y combinar ambas colecciones por materia (ver corrección de diseño 2026-08-30 en `design.md`)
- [x] Task 6 — Actualizar los 3 tests existentes que el `tester` en `pre` identificó como afectados por el guard nuevo (`tests/Feature/Enrollment/EnrollmentControllerTest.php` y `EnrollmentIndexTest.php` — casos de representante con estudiante `university` por defecto deben pasar a usar `->secondary()`/`->primary()`)
- [x] Task 7 — Tests Feature: RF-01 a RF-07 (cubierto por `Acceptance/` del tester en `pre`, verificado en `task-gate`)
- [x] Task 8 — `vendor/bin/sail artisan test --tia --compact` — 911 passed, 0 failures (verificado en `task-gate`)
- [x] Task 9 — `vendor/bin/sail bin pint --dirty --format agent` — pass (verificado en `task-gate`)
- [x] Task 10 — QA Gate: tester en modo `feature-gate` verifica todos los UCs de `specs/16-enrollment-level-guard-and-period-fix/qa.md` en verde (2026-08-30 — 4 Dusk PASS + 1 Feature PASS)
