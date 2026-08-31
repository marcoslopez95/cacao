# QA — Dashboard del representante
**Fecha de definición:** 2026-08-30
**Vista:** `/guardian/dashboard`, `/guardian/grades`
**Auditor:** QA Manager

---

## Contexto

El representante ve la situación de sus estudiantes vinculados (relación muchos-a-muchos vía tabla pivote `student_guardians`). **Regla de negocio confirmada con el humano (2026-08-30):** la figura de representante con acceso al sistema solo aplica a estudiantes de nivel **no universitario** (primaria/secundaria) — un estudiante universitario se inscribe a sí mismo y no debería tener un representante vinculado con acceso. **El código actual no tiene ningún guard que lo garantice** — ver `HLZ-40`.

### Rutas

| Método | URI | Controller@método | Middleware |
|---|---|---|---|
| GET | `/guardian/dashboard` | `Guardian\DashboardController@index` | `auth,verified,role:Representante` |
| GET | `/guardian/grades` | `Guardian\GradeController@index` | ídem |

Solo estas dos rutas existen bajo `/guardian/*`. No hay horario ni asistencia real en este portal — el dashboard muestra `nota_promedio` e `inasistencias` **hardcodeados a `null`**.

---

## UCs de carga del dashboard

- **UC-G01** — GET `/guardian/dashboard` con 1+ estudiantes vinculados: 200, una tarjeta por estudiante con nombre, nivel educativo, pensum, UC inscritas/UC del pensum, estado de inscripción, materias inscritas del período activo. **Confirmado en vivo 2026-08-30** con `rep04@utcacao.edu.ve` (2 estudiantes vinculados, Bachillerato): carga sin error, datos correctos.
- **UC-G02** — GET `/guardian/dashboard` sin estudiantes vinculados: estado vacío, sin error.
- **UC-G03** — GET `/guardian/dashboard` con rol `Representante` asignado pero sin fila en `guardians`: 404 (`abort_unless`).
- **UC-G04** — [GAP] `nota_promedio` e `inasistencias` se muestran como placeholder (`—`) — no hay integración real con el módulo de notas/asistencia en este dashboard todavía. Documentado como gap conocido, no como HLZ (no se pidió corregir en este ciclo).
- **UC-G05** — El botón "Inscribir / Continuar / Ver inscripción" enlaza a `/enrollment?student_id={id}` con el texto correcto según el estado de inscripción del estudiante (sin inscripción / draft / confirmed). **Confirmado en vivo el enlace en sí (correcto)**, pero el destino llega a un catálogo vacío para estudiantes no universitarios — ver **HLZ-43** en `specs/qa/backlog.md` y UC-E02b en `specs/qa/enrollment/student-enrollment.md`.

## UCs de restricción por nivel educativo

- **UC-G06** — [RESUELTO 2026-08-30 — feature `18-guardian-grades-multi-student`, ver HLZ-40] `Guardian\GradeController` y `Guardian\DashboardController` filtran `educational_level != University` en toda consulta de estudiantes elegibles.
- **UC-G07** — [RESUELTO 2026-08-30] Guardian vinculado (forzado vía pivote) a un estudiante `university`: no aparece en `/guardian/dashboard` ni es accesible vía `/guardian/grades?student_id=` (403). Si es el único vinculado, `/guardian/dashboard` muestra 0 estudiantes y `/guardian/grades` responde 404 (sin estudiante elegible).
- **UC-G08** — Estudiante `primary`/`secondary` vinculado: aparece correctamente en el dashboard del representante (comportamiento ya correcto, sin cambios).

## UCs de notas (`/guardian/grades`)

- **UC-G09** — GET `/guardian/grades` con 1 estudiante vinculado: notas del período activo, con estado aprobado/reprobado por materia.
- **UC-G10** — [RESUELTO 2026-08-30 — feature `18-guardian-grades-multi-student`, ver HLZ-41] Con 2+ estudiantes vinculados, `guardian/Grades/Index.vue` muestra un selector (`[dusk="guardian-grades-student-select"]`) para cambiar de estudiante sin recargar navegación fuera de la vista.
- **UC-G11** — [RESUELTO 2026-08-30] GET `/guardian/grades?student_id=X`: muestra las notas de X si pertenece al representante autenticado; 403 si no pertenece (mismo patrón que `EnrollmentController::resolveStudent`). Sin `student_id`, cae al primer estudiante elegible (fallback preservado).
- **UC-G12** — Sin inscripción activa para el estudiante mostrado: estado vacío, sin error.

## UCs de permisos

- **UC-G13** — Usuario con rol `Estudiante` o `Profesor` accediendo a `/guardian/*`: 403 (fuera de `role:Representante`).
- **UC-G14** — [RESUELTO 2026-08-30] Representante intentando ver notas de un estudiante no vinculado vía manipulación de `student_id` en la URL: 403.

---

## Tests existentes (Feature/Dusk)

| UC(s) | Archivo | Cobertura |
|---|---|---|
| UC-G01, G02, G03, G04, G13 | `tests/Feature/Guardian/DashboardTest.php` | 200 con props correctos, estudiantes vacío, 404 sin perfil guardian, `nota_promedio`/`inasistencias` en null, aislamiento de roles |
| UC-G09, G12, G13 | `tests/Feature/Guardian/GradeViewTest.php` | Redirect no autenticado, notas del estudiante vinculado, sin período activo, notas publicadas |
| UC-G05 | `tests/Browser/Guardian/GuardianEnrollmentTest.php` | Guardian inscribe desde el CTA del dashboard (fixture corregido a `->secondary()` tras RF-05 — ver nota abajo) |
| UC-G08 | `tests/Feature/Admin/StudentGuardianPivotTest.php` | Attach/detach del pivote, `primaryGuardian()` |
| UC-G06, G07, G11, G14 | `tests/Feature/GuardianGradesMultiStudent/Acceptance/GuardianGradesMultiStudentTest.php` | RF-01 a RF-05: `student_id` explícito, fallback, 403 ajeno, filtro `university` forzado en ambos controllers |
| UC-G10 | `tests/Browser/Guardian/GuardianGradesMultiStudentTest.php` | UC-QA-01/02: selector visible con 2+ estudiantes, ausente con 1 solo, cambio de estudiante sin perder notas |

---

## Notas de implementación

`HLZ-40` y `HLZ-41` resueltos por la feature `18-guardian-grades-multi-student` (task-gate 2026-08-30). Detalle técnico en `specs/18-guardian-grades-multi-student/requirements.md` y `specs/qa/backlog.md`.

**Regresión detectada y corregida en el mismo gate:** `tests/Browser/Guardian/GuardianEnrollmentTest.php` (feature `guardian-enrollment-entry`, preexistente) usaba `Student::factory()->create()` (default `educational_level = university`) vinculado a un guardian. Con el filtro RF-05 activo, ese estudiante ya no aparece en `/guardian/dashboard`, y el test fallaba esperando verlo. Diagnóstico confirmado leyendo el fixture y `StudentFactory::definition()` (default `University`) — no había otra causa. Corregido cambiando el fixture a `Student::factory()->secondary()->create(...)`, consistente con la regla de negocio HLZ-40 (universitarios no tienen representante con acceso al sistema); las aserciones de negocio del test no se tocaron.
