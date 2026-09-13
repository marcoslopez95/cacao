# QA Spec — QA Round 3 Follow-up Fixes
**Feature:** 22-qa-round3-followup-fixes
**Fecha:** 2026-09-13

---

## UCs a cubrir con Dusk

### UC-QA-01 — Estudiante universitario ve el label de período correcto

**Precondición:** estudiante con `educational_level = university`, `academic_year` poblado, período `Semester` activo.

**Pasos:**
1. Login como el estudiante.
2. Navegar a `/enrollment`.
3. Verificar el encabezado/copy de período.

**Resultado esperado:** el label muestra el nombre real del período (o queda vacío si no aplica), nunca "Xer trimestre". El copy de ayuda tampoco menciona "este trimestre".

**Test Dusk:** pendiente

---

### UC-QA-02 — Estudiante de bachillerato sigue viendo el label de Lapso correcto (regresión)

**Precondición:** estudiante `secondary`, período `Year` activo con un `Lapse` vigente por fecha.

**Pasos:**
1. Login como el estudiante.
2. Navegar a `/enrollment`.
3. Verificar el encabezado de período.

**Resultado esperado:** sigue mostrando el nombre real del Lapse vigente (ej. "1er Lapso") — sin regresión respecto al fix de la feature 16.

**Test Dusk:** pendiente

---

### UC-QA-03 — Cualquier rol ve su perfil prellenado en `/settings/profile`

**Precondición:** usuario autenticado de cualquier rol (admin, profesor, estudiante, representante), con `name`/`email` reales en DB.

**Pasos:**
1. Login.
2. Navegar a `/settings/profile`.
3. Verificar los inputs "Nombre" y "Correo electrónico".

**Resultado esperado:** ambos inputs muestran los valores reales del usuario autenticado, no vacíos. Probar en desktop y mobile.

**Test Dusk:** pendiente

---

### UC-QA-04 — Ningún profesor demo nace sobre su propio límite de horas

**Precondición:** ninguna — se verifica el estado post-seed.

**Pasos:**
1. `vendor/bin/sail artisan migrate:fresh --seed`.
2. Para cada profesor con secciones reales, calcular su carga horaria semanal total y compararla contra `weekly_hour_limit`.

**Resultado esperado:** 0 profesores con carga horaria semanal > `weekly_hour_limit` propio. Como efecto colateral verificable: el modal de alta de horario permite crear un nuevo bloque de al menos 30 min para al menos un profesor con secciones reales, sin el error "superaría su límite".

**Test Dusk:** pendiente
