# QA — Inscripción de estudiante (self-service y por representante)
**Fecha de definición:** 2026-08-30
**Vista:** `/enrollment`
**Auditor:** QA Manager

---

## Contexto

El flujo de inscripción vive en un único prefijo `/enrollment/*`, compartido entre estudiante (self-service) y representante (inscribe a un estudiante vinculado). `Enrollment` es la cabecera (una por estudiante/período, estado `draft → confirmed → approved/rejected`); `EnrollmentDetail` es el pivote por sección inscrita.

**Regla de negocio confirmada con el humano (2026-08-30):** solo estudiantes de nivel `university` pueden auto-inscribirse. Estudiantes `primary`/`secondary` dependen exclusivamente de su representante — no deben poder inscribirse a sí mismos. **El código actual no aplica esta restricción en ningún punto** (Policy, Controller, FormRequest, Actions) — ver `HLZ-38` en `specs/qa/backlog.md`.

### Rutas

| Método | URI | Controller@método | Middleware |
|---|---|---|---|
| GET | `/enrollment` | `EnrollmentController@index` | `auth,verified,role:Estudiante,Representante` |
| POST | `/enrollment` | `EnrollmentController@store` | ídem |
| POST | `/enrollment/{enrollment}/detail` | `EnrollmentController@addDetail` | ídem |
| DELETE | `/enrollment/{enrollment}/detail/{enrollmentDetail}` | `EnrollmentController@removeDetail` | ídem |
| POST | `/enrollment/{enrollment}/confirm` | `EnrollmentController@confirm` | ídem |

### Validaciones de negocio ya implementadas (no relacionadas al nivel educativo)

1. Cupo por sección (caché, `EnrollmentCacheManager`)
2. Ya-inscrito (idempotencia por sección)
3. Prelaciones (`PrerequisiteValidator::canTake`)
4. Pertenencia al pensum activo del estudiante
5. Pertenencia guardian↔estudiante (`?student_id=` validado contra `guardian->students()`)
6. Solo se puede editar/confirmar un Enrollment en estado `draft`

---

## UCs de carga

- **UC-E01** — GET `/enrollment` como estudiante universitario autenticado: 200, catálogo de materias del pensum activo, `Enrollment` draft auto-creado si no existía. **Confirmado en vivo 2026-08-30** con `est041@utcacao.edu.ve`: 6 materias con secciones reales, inscripción completa (selección, cambio de sección, cálculo de UC/horas, confirmación) sin errores.
- **UC-E02** — GET `/enrollment?student_id=X` como representante, con X entre sus estudiantes vinculados: 200, catálogo para ese estudiante. **[RESUELTO — ver HLZ-43, feature `16-enrollment-level-guard-and-period-fix`, 2026-08-30]** El período activo ahora se resuelve por `educational_level` del estudiante (`Semester` vs `Year`) y el catálogo combina secciones directas (`sections`) + escolares vía pivote (`schoolSections`) — ya no da "0 materias" para `secondary`/`primary`.
- **UC-E02b** — [RESUELTO — ver **HLZ-43**] GET `/enrollment` o `/enrollment?student_id=X` para un estudiante `secondary`/`primary`: el catálogo ya no da "0 materias". Causa original: `EnrollmentController::index()` resolvía el período con `Period::where('status', Active)->first()` sin condicionar por `educational_level`; con período Anual (escolar) y Semestral (universitario) activos a la vez, siempre tomaba el Semestral. Fix: `->where('type', ...)` condicionado al nivel del estudiante, más `Subject::schoolSections()` + combinación en `BuildEnrollmentCatalogAction::handle()`. **Test Dusk:** `tests/Browser/Enrollment/EnrollmentLevelGuardAndPeriodFixTest.php::UC-QA-03` — PASS.
- **UC-E03** — GET `/enrollment` como representante sin `student_id`: usa el primer estudiante vinculado (`guardian->students()->first()`).
- **UC-E04** — GET `/enrollment?student_id=X` donde X no pertenece al representante autenticado: 403.
- **UC-E05** — GET `/enrollment` con usuario sin `student` ni `guardian` asociado: 403.

## UCs de auto-inscripción por nivel educativo

- **UC-E06** — Estudiante universitario autenticado ejecuta `POST /enrollment`, `POST .../detail`, `POST .../confirm` sobre sí mismo: **permitido** (comportamiento correcto, ya funciona). **Test Dusk:** `tests/Browser/Enrollment/EnrollmentLevelGuardAndPeriodFixTest.php::UC-QA-01` — PASS (regresión confirmada tras el fix de HLZ-38/HLZ-43).
- **UC-E07** — [RESUELTO — ver **HLZ-38**, feature `16-enrollment-level-guard-and-period-fix`] Estudiante de nivel `primary`/`secondary` autenticado intenta ejecutar `POST /enrollment` (o `addDetail`/`confirm`) sobre sí mismo: responde 403. `EnrollmentPolicy::create()` valida `educational_level !== University`; `EnrollmentController::index()` llama `Gate::authorize()` antes del `firstOrCreate`. El CTA "Ir a inscripciones →" también se oculta en `student/Dashboard.vue` para estos niveles, con mensaje "Tu representante debe inscribirte." **Tests:** `tests/Feature/EnrollmentLevelGuardAndPeriodFix/Acceptance/EnrollmentLevelGuardAcceptanceTest.php` (RF-01, RF-02), `StudentDashboardCtaAcceptanceTest.php`; **Test Dusk:** `EnrollmentLevelGuardAndPeriodFixTest.php::UC-QA-02` — PASS.
- **UC-E08** — Representante inscribe a un estudiante `primary`/`secondary` vinculado: permitido, y **ya no bloqueado por HLZ-43** — el botón "Inscribir" del dashboard del representante lleva a un catálogo con materias reales. **Test Dusk:** `EnrollmentLevelGuardAndPeriodFixTest.php::UC-QA-03` — PASS.
- **UC-E09** — [RESUELTO como caso defensivo — ver **HLZ-38**] Representante intenta inscribir a un estudiante `university` vinculado (escenario forzado por factory; no ocurre hoy en datos reales — depende de que `HLZ-40` restrinja el vínculo en origen, aún pendiente). `EnrollmentPolicy::create()` deniega con 403 en la rama con `$student` explícito cuando `$student->educational_level === University`, independientemente de cómo se haya formado el vínculo. **Test:** `tests/Feature/EnrollmentLevelGuardAndPeriodFix/Acceptance/EnrollmentLevelGuardAcceptanceTest.php::RF-04` — PASS.

## UCs de negocio (cupos, prelaciones, idempotencia)

- **UC-E10** — Agregar una sección con cupo disponible: crea `EnrollmentDetail` en `draft`, decrementa cupo en caché.
- **UC-E11** — Agregar una sección sin cupo disponible: rechazado, sin crear detail.
- **UC-E12** — Agregar una materia con prelación no cumplida: rechazado (`prereqs_ok=false` en catálogo).
- **UC-E13** — Cambiar de sección la misma materia (swap in-place): actualiza el detail existente, ajusta cupos de ambas secciones.
- **UC-E14** — Agregar una materia ya inscrita en la misma sección: idempotente, no duplica.
- **UC-E15** — Quitar un detail en estado `draft`: marcado `rejected`, libera cupo en caché.
- **UC-E16** — Confirmar con ≥1 detail en draft, cupos y prelaciones válidos: transacciona todos los details a `confirmed`, `Enrollment` pasa a `Confirmed`.
- **UC-E17** — Confirmar un `Enrollment` sin ningún detail: rechazado.
- **UC-E18** — Confirmar dos veces (ya `Confirmed`): rechazado, no re-procesa.
- **UC-E19** — Confirmar cuando el cupo cambió entre el add y el confirm (carrera con otro estudiante): recuento de cupos con `lockForUpdate()` al momento del confirm; rechaza si ya no hay cupo.

## UCs de permisos

- **UC-E20** — Usuario con rol `Admin`/`Profesor` accediendo a `/enrollment`: 403 (fuera de `role:Estudiante,Representante`).
- **UC-E21** — Representante intentando editar/confirmar un `Enrollment` que no está en `draft` (ya `Confirmed`/`Approved`/`Rejected`): 403.

## UCs de presentación del período (label)

- **UC-E22** — [RESUELTO — ver **HLZ-43**, feature `16-enrollment-level-guard-and-period-fix`] El encabezado de `/enrollment` muestra el nombre real del `Lapse` vigente (por fecha) cuando el período resuelto es de tipo `Year`, en vez de `"{academic_year}er trimestre"` hardcodeado. **Precondición:** estudiante `secondary`/`primary`, período `Year` activo con ≥1 `Lapse` cuyo rango de fechas cubre la fecha actual. **Test Dusk:** `tests/Browser/Enrollment/EnrollmentLevelGuardAndPeriodFixTest.php::UC-QA-05` — PASS. Nota de implementación del test: `.enr-page-eyebrow` tiene `text-transform: uppercase` en CSS — la aserción Dusk compara contra el texto ya transformado por el navegador (`mb_strtoupper($lapse->name)`), no contra el string crudo de DB.

---

## Tests existentes (Feature/Unit/Dusk)

| UC(s) | Archivo | Cobertura |
|---|---|---|
| UC-E01, E03, E05 | `tests/Feature/Enrollment/EnrollmentIndexTest.php` | Render de índice, resolución de estudiante para guardian con/sin `student_id`, 403 sin student/guardian |
| UC-E04 | `tests/Feature/Enrollment/EnrollmentIndexTest.php` | 403 para `student_id` no asignado |
| UC-E10–E19 | `tests/Feature/Enrollment/EnrollmentControllerTest.php` | Creación, alta/cambio/idempotencia de detail, control de cupo, remoción, confirmación, doble confirmación |
| UC-E12 | `tests/Unit/Enrollment/PrerequisiteValidatorTest.php` | `canTake`/`getMissingPrerequisites` |
| UC-E10, E11 | `tests/Unit/Enrollment/EnrollmentServiceTest.php` | `hasQuota`, `isAlreadyEnrolled`, `calculateEnrolledCredits` |
| UC-E01, E20 | `tests/Browser/Enrollment/EnrollmentFlowTest.php` | Ver lista, estado vacío, 403 admin, 403 estudiante viendo inscripción de otro |
| UC-E08 | `tests/Browser/Guardian/GuardianEnrollmentTest.php` | Guardian inscribe desde el CTA del dashboard |
| UC-E06, E07, E09 | `tests/Feature/EnrollmentLevelGuardAndPeriodFix/Acceptance/EnrollmentLevelGuardAcceptanceTest.php`, `StudentDashboardCtaAcceptanceTest.php` | Guard de nivel en Policy + Controller (self y guardian), CTA oculto en dashboard |
| UC-E01, E06 | `tests/Browser/Enrollment/EnrollmentLevelGuardAndPeriodFixTest.php::UC-QA-01` | Regresión: estudiante universitario sigue autoinscribiéndose sin cambios |
| UC-E07 | `tests/Browser/Enrollment/EnrollmentLevelGuardAndPeriodFixTest.php::UC-QA-02` | CTA oculto + 403 en visita directa a `/enrollment` para estudiante `secondary` |
| UC-E02, UC-E02b, UC-E08 | `tests/Feature/EnrollmentLevelGuardAndPeriodFix/Acceptance/EnrollmentPeriodResolutionAcceptanceTest.php`, `EnrollmentSchoolCatalogAcceptanceTest.php`, `tests/Browser/Enrollment/EnrollmentLevelGuardAndPeriodFixTest.php::UC-QA-03` | Resolución de período por tipo/nivel + catálogo escolar vía pivote no vacío |
| UC-E22 | `tests/Browser/Enrollment/EnrollmentLevelGuardAndPeriodFixTest.php::UC-QA-05` | Label de período muestra el Lapso real vigente |

---

## Notas de implementación

- `HLZ-43` y `HLZ-38` — **resueltos** en el feature `16-enrollment-level-guard-and-period-fix` (2026-08-30). Ver `specs/qa/backlog.md` para el detalle técnico completo y la verificación de cada uno.
- `HLZ-40` (`specs/qa/guardian/dashboard.md`) sigue **pendiente** — UC-E09 quedó cubierto como caso defensivo (Policy deniega aunque el vínculo exista), pero el guard de origen del vínculo guardian↔estudiante universitario no se ha implementado todavía.
