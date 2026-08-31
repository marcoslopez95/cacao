# HLZ-38 + HLZ-43 — Guard de nivel educativo y resolución correcta del período activo en inscripción

## Problema

Dos hallazgos relacionados en `specs/qa/backlog.md` bloquean por completo la inscripción de estudiantes no universitarios:

**HLZ-38:** ningún punto del flujo de inscripción (`EnrollmentPolicy`, `EnrollmentController`, `StoreEnrollmentRequest`) valida `Student::educational_level`. Un estudiante `primary`/`secondary` puede autoinscribirse exactamente igual que uno `university`, violando la regla de negocio confirmada: solo universitarios se autoinscriben, el resto depende de su representante.

**Hallazgo adicional durante el diseño (no documentado en el backlog original):** `EnrollmentController::index()` crea el `Enrollment` draft con `Enrollment::firstOrCreate(...)` **sin llamar a ningún Gate** — así que corregir solo `EnrollmentPolicy::create()` no alcanza; hoy ese método ni siquiera se invoca en el flujo real de creación.

**HLZ-43:** `Period::where('status', Active)->first()` no filtra por `type` ni por nivel del estudiante. Hoy coexisten dos períodos "Activos" (`2026-I` semestral y `2025-2026` anual) — `first()` siempre devuelve el semestral. Como las secciones escolares están todas bajo períodos anuales, el catálogo de inscripción queda vacío ("0 materias") para todo estudiante `primary`/`secondary`, sea que la inscripción la intente el estudiante o su representante. Bonus: el label del período muestra "Xer trimestre" hardcodeado incluso para períodos anuales.

## Requisitos funcionales

- RF-01: `GET /enrollment` para un estudiante autenticado con `educational_level != University` no crea ningún draft de inscripción — responde 403 antes de tocar la DB.
- RF-02: `GET /enrollment` para un estudiante `University` sigue funcionando exactamente igual que hoy (regresión).
- RF-03: `GET /enrollment?student_id={id}` iniciado por un representante para un estudiante `primary`/`secondary` vinculado resuelve el período `Year` activo y muestra el catálogo de materias con secciones reales (no vacío). **Nota (2026-08-30):** esto requiere que `BuildEnrollmentCatalogAction` también resuelva secciones escolares vía la tabla pivote `section_subjects` — ver corrección de diseño en `design.md`. El fix de período por sí solo no alcanza.
- RF-04: `GET /enrollment?student_id={id}` iniciado por un representante para un estudiante `University` (si existiera el vínculo) responde 403.
- RF-05: el período activo se resuelve según `Student::educational_level`: `University` → `Period` con `type = Semester`; `Primary`/`Secondary` → `Period` con `type = Year`.
- RF-06: el label de período en la UI de inscripción muestra el nombre real del Lapso vigente (por fecha) cuando el período resuelto es de tipo `Year`, en vez de `"{academic_year}er trimestre"`.
- RF-07: el CTA "Ir a inscripciones →" en `student/Dashboard.vue` no se muestra (o se reemplaza por un mensaje explicativo) para estudiantes con `educational_level != University`.

## Alcance

**Incluye:** guard de nivel en la creación de inscripción (self-enroll y guardian-enroll), resolución de `Period` por tipo según nivel, label del Lapso vigente, ocultar CTA en dashboard del estudiante.

**Excluye explícitamente:**
- Resolver secciones por Lapso específico — confirmado que `sections` solo tiene `period_id` (no `lapse_id`); el catálogo sigue filtrando por el período anual completo.
- Cambios a `EnrollmentPolicy::view()`, `update()`, `confirm()`, `delete()` — sin cambios, ya validan pertenencia correctamente.
- Gestión de vínculo guardian↔estudiante — cubierto en el feature `18-guardian-grades-multi-student` (HLZ-40).
