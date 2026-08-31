# QA Spec — Enrollment Level Guard and Period Fix
**Feature:** 16-enrollment-level-guard-and-period-fix
**Fecha:** 2026-08-30

---

## UCs a cubrir con Dusk

### UC-QA-01 — Estudiante universitario se autoinscribe sin cambios (regresión)

**Precondición:** estudiante con `educational_level = university`, con pensum activo y secciones disponibles en el período `Semester` activo.

**Pasos:**
1. Login como el estudiante.
2. Navegar a `/enrollment`.
3. Seleccionar una sección y confirmar la inscripción.

**Resultado esperado:** catálogo muestra materias, la confirmación responde 200/201, `Enrollment.status = confirmed` en DB.

**Test Dusk:** pendiente

---

### UC-QA-02 — Estudiante de secundaria no puede autoinscribirse

**Precondición:** estudiante con `educational_level = secondary`.

**Pasos:**
1. Login como el estudiante.
2. Verificar que el dashboard NO muestra el CTA "Ir a inscripciones →" (o muestra el mensaje explicativo).
3. Navegar directamente a `/enrollment`.

**Resultado esperado:** paso 2 confirma la ausencia del CTA; paso 3 responde 403. No se crea ningún `Enrollment` en DB para ese estudiante.

**Test Dusk:** pendiente

---

### UC-QA-03 — Representante inscribe a un estudiante de bachillerato con catálogo no vacío

**Precondición:** representante vinculado a un estudiante `secondary` con pensum activo; existen secciones escolares creadas bajo el período `Year` activo.

**Pasos:**
1. Login como el representante.
2. Desde el dashboard, click en "Inscribir" sobre el estudiante.
3. Verificar el catálogo de materias.
4. Seleccionar una sección y confirmar.

**Resultado esperado:** el catálogo muestra materias con secciones reales (no "0 materias"); la inscripción se confirma exitosamente.

**Test Dusk:** pendiente

---

### UC-QA-04 — Representante no puede inscribir a un estudiante universitario

**Precondición:** vínculo guardian↔estudiante forzado vía factory con un estudiante `university` (escenario defensivo, no ocurre hoy en datos reales).

**Pasos:**
1. Login como el representante.
2. Intentar `GET /enrollment?student_id={id_universitario}`.

**Resultado esperado:** 403.

**Test Dusk:** pendiente (o Feature test, dado que es un escenario forzado por factory)

---

### UC-QA-05 — Label de período muestra el Lapso real vigente

**Precondición:** estudiante `secondary`, período `Year` activo con al menos un `Lapse` cuya fecha actual cae dentro de su rango.

**Pasos:**
1. Login como el estudiante (o su representante).
2. Navegar a `/enrollment`.
3. Verificar el encabezado de período.

**Resultado esperado:** el encabezado muestra el nombre real del Lapse vigente (ej. "1er Lapso"), no "Xer trimestre" hardcodeado.

**Test Dusk:** pendiente
