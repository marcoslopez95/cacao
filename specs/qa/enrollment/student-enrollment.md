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
- **UC-E02** — GET `/enrollment?student_id=X` como representante, con X entre sus estudiantes vinculados: 200, catálogo para ese estudiante. **[ESTADO: BUG CRÍTICO — ver HLZ-43, confirmado en vivo 2026-08-30]** Para estudiantes `secondary`/`primary` el catálogo carga (200) pero siempre devuelve 0 materias — ver UC-E02b.
- **UC-E02b** — [NUEVO, ESTADO: BUG CRÍTICO — ver **HLZ-43**] GET `/enrollment` o `/enrollment?student_id=X` para un estudiante `secondary`/`primary`: el catálogo siempre da "0 materias", pase lo que pase con los filtros. Causa: `EnrollmentController::index()` resuelve el período con `Period::where('status', Active)->first()` sin condicionar por `educational_level`, y con período Anual (escolar) y Semestral (universitario) activos a la vez, siempre toma el Semestral — cuyas secciones nunca coinciden con las Secciones Escolares (creadas bajo el período Anual). Confirmado en vivo con `sec16@utcacao.edu.ve` (self) y `rep04@utcacao.edu.ve` (representante, sobre el mismo estudiante): ambos caminos llegan a "0 materias".
- **UC-E03** — GET `/enrollment` como representante sin `student_id`: usa el primer estudiante vinculado (`guardian->students()->first()`).
- **UC-E04** — GET `/enrollment?student_id=X` donde X no pertenece al representante autenticado: 403.
- **UC-E05** — GET `/enrollment` con usuario sin `student` ni `guardian` asociado: 403.

## UCs de auto-inscripción por nivel educativo

- **UC-E06** — Estudiante universitario autenticado ejecuta `POST /enrollment`, `POST .../detail`, `POST .../confirm` sobre sí mismo: **permitido** (comportamiento correcto, ya funciona).
- **UC-E07** — [ESTADO: BUG — ver **HLZ-38**] Estudiante de nivel `primary`/`secondary` autenticado intenta ejecutar `POST /enrollment` (o `addDetail`/`confirm`) sobre sí mismo: **debe** responder 403. Actualmente responde 200/201 — sin restricción de ningún tipo.
- **UC-E08** — Representante inscribe a un estudiante `primary`/`secondary` vinculado: permitido en términos de acceso (comportamiento correcto), pero en la práctica **bloqueado por HLZ-43** — el botón "Inscribir" del dashboard del representante lleva a un catálogo vacío (ver UC-E02b). Confirmado en vivo con `rep04` sobre `sec16`/`sec17`.
- **UC-E09** — [pendiente de decisión] Representante intenta inscribir a un estudiante `university` vinculado. Depende de `HLZ-40` (`specs/qa/guardian/dashboard.md`): si ese vínculo queda restringido en origen, este caso no debería poder ocurrir. Documentado como pendiente hasta resolver HLZ-40.

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
| **UC-E06, E07, E09** | — | **pendiente — requiere el fix de HLZ-38 antes de poder escribir el test de rechazo** |

---

## Notas de implementación pendiente

- Ver `HLZ-43` en `specs/qa/backlog.md` — **prioridad CRÍTICA**, confirmado en vivo: bloquea el 100% de la inscripción de Primaria/Bachillerato (catálogo siempre vacío por resolución incorrecta del período activo). Recomendado corregir antes que HLZ-38, ya que hoy en día HLZ-38 es difícil de ejercitar en la práctica (el estudiante no-universitario llega sin bloqueo hasta un catálogo vacío, pero nunca hasta confirmar una inscripción real).
- Ver `HLZ-38` en `specs/qa/backlog.md` para el detalle técnico y la acción sugerida del guard de nivel educativo.
