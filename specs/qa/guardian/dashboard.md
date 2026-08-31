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

- **UC-G06** — [ESTADO: BUG — ver **HLZ-40**] No existe ningún guard (modelo, migración, Policy, controller) que impida que un estudiante `university` esté vinculado a un `Guardian`, ni que lo excluya de `/guardian/dashboard` o `/guardian/grades` si el vínculo llegara a existir.
- **UC-G07** — [PENDIENTE tras fix] Guardian vinculado a un estudiante `university` (vía pivote, aunque no haya UI que lo cree hoy): no debe aparecer en `/guardian/dashboard` ni ser accesible vía `/guardian/grades?student_id=`.
- **UC-G08** — Estudiante `primary`/`secondary` vinculado: aparece correctamente en el dashboard del representante (comportamiento ya correcto, sin cambios).

## UCs de notas (`/guardian/grades`)

- **UC-G09** — GET `/guardian/grades` con 1 estudiante vinculado: notas del período activo, con estado aprobado/reprobado por materia.
- **UC-G10** — [ESTADO: BUG — ver **HLZ-41**] Con 2+ estudiantes vinculados: siempre muestra las notas del primero (`->first()`), sin selector ni parámetro para cambiar de estudiante — a diferencia del dashboard, que sí lista a todos.
- **UC-G11** — [PENDIENTE tras fix] GET `/guardian/grades?student_id=X`: muestra las notas de X si pertenece al representante autenticado; 403 si no pertenece (mismo patrón que `EnrollmentController::resolveStudent`).
- **UC-G12** — Sin inscripción activa para el estudiante mostrado: estado vacío, sin error.

## UCs de permisos

- **UC-G13** — Usuario con rol `Estudiante` o `Profesor` accediendo a `/guardian/*`: 403 (fuera de `role:Representante`).
- **UC-G14** — [PENDIENTE tras fix de HLZ-41] Representante intentando ver notas de un estudiante no vinculado vía manipulación de `student_id` en la URL: 403.

---

## Tests existentes (Feature/Dusk)

| UC(s) | Archivo | Cobertura |
|---|---|---|
| UC-G01, G02, G03, G04, G13 | `tests/Feature/Guardian/DashboardTest.php` | 200 con props correctos, estudiantes vacío, 404 sin perfil guardian, `nota_promedio`/`inasistencias` en null, aislamiento de roles |
| UC-G09, G12, G13 | `tests/Feature/Guardian/GradeViewTest.php` | Redirect no autenticado, notas del estudiante vinculado, sin período activo, notas publicadas |
| UC-G05 | `tests/Browser/Guardian/GuardianEnrollmentTest.php` | Guardian inscribe desde el CTA del dashboard |
| UC-G08 | `tests/Feature/Admin/StudentGuardianPivotTest.php` | Attach/detach del pivote, `primaryGuardian()` |
| **UC-G06, G07** | — | **sin cobertura — requiere el fix de HLZ-40 primero** |
| **UC-G10, G11, G14** | — | **sin cobertura — requiere el fix de HLZ-41 primero** |

---

## Notas de implementación pendiente

Ver `HLZ-40` y `HLZ-41` en `specs/qa/backlog.md` para el detalle técnico y la acción sugerida de cada fix.
