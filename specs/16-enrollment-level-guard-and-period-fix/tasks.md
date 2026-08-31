# Tasks — Enrollment Level Guard and Period Fix (feature 16)

- [ ] Task 1 — `EnrollmentPolicy::create()`: agregar guard de `educational_level` en ambas ramas (self-enroll solo `University`; guardian-enroll solo no-`University`), importar `EducationalLevel`
- [ ] Task 2 — `EnrollmentController::index()`: agregar `Gate::authorize('create', [Enrollment::class, $student])` antes del `firstOrCreate`; resolver `$period` filtrando por `type` según `$student->educational_level`
- [ ] Task 3 — `EnrollmentController::buildRules()`: resolver el `Lapse` vigente por fecha cuando `$period->type === PeriodType::Year` y usar su `name` como label en vez de `"{academic_year}er trimestre"`
- [ ] Task 4 — `student/Dashboard.vue` (+ `DashboardController` si falta el dato): ocultar/deshabilitar el CTA "Ir a inscripciones →" cuando `educational_level !== 'university'`, con mensaje explicativo
- [ ] Task 5 — Tests Feature: RF-01 a RF-05 (autoinscripción bloqueada para no-universitario, permitida para universitario, representante inscribe secondary con catálogo no vacío, representante bloqueado en university, label muestra Lapso real)
- [ ] Task 6 — `vendor/bin/sail artisan test --compact tests/Feature/Enrollment/` — todo en verde
- [ ] Task 7 — `vendor/bin/sail bin pint --dirty --format agent`
- [ ] Task 8 — QA Gate: qa_manager verifica todos los UCs de `specs/16-enrollment-level-guard-and-period-fix/qa.md` en verde
