# HLZ-40 + HLZ-41 — Selector multi-estudiante en notas del representante + guard defensivo de nivel universitario

## Problema

**HLZ-41:** `Guardian\GradeController::index()` resuelve el estudiante con `$guardian->students()->first()`, sin aceptar `student_id`. Un representante con 2+ estudiantes vinculados (hermanos) solo puede ver las notas del primero — no hay forma de cambiar de estudiante desde `/guardian/grades`, aunque el dashboard (`/guardian/dashboard`) sí lista a todos.

**HLZ-40:** la relación `Guardian::students()` / `Student::guardians()` no valida `educational_level` en ningún punto (modelo, migración, Policy, controller). Regla de negocio confirmada: los estudiantes universitarios no tienen representante legal con acceso al sistema. Si un `Student` `university` llegara a estar vinculado a un `Guardian` (vía seed, importación manual o una feature futura), el sistema lo mostraría sin ningún rechazo en `/guardian/dashboard` y `/guardian/grades`. No hay evidencia de que ocurra hoy en datos reales, pero no existe ningún guard que lo impida.

## Requisitos funcionales

- RF-01: `GET /guardian/grades?student_id={id}` muestra las notas del estudiante `{id}` cuando está vinculado al representante autenticado.
- RF-02: `GET /guardian/grades` sin `student_id` mantiene el fallback al primer estudiante vinculado (compatibilidad con el comportamiento actual).
- RF-03: `GET /guardian/grades?student_id={id}` con un `{id}` no vinculado al representante autenticado responde 403.
- RF-04: `guardian/Grades/Index.vue` muestra un selector de estudiante quando el representante tiene 2+ estudiantes vinculados; con 1 solo estudiante, no se muestra selector (sin cambio visual).
- RF-05: ningún estudiante con `educational_level = university` aparece en las consultas de `Guardian\GradeController` ni `Guardian\DashboardController`, incluso si existiera un vínculo forzado en la tabla pivote `student_guardians`.

## Alcance

**Incluye:** parámetro `student_id` en `GET /guardian/grades` (mismo patrón que `EnrollmentController::resolveStudent()`), selector de estudiante en el frontend, filtro defensivo de nivel en ambos controllers de guardian.

**Excluye explícitamente:**
- Gestión de creación/edición del vínculo guardian↔estudiante — no existe ningún Action/Controller que haga `attach()`/`sync()` sobre `student_guardians` en el código actual; el vínculo se gestiona fuera del código auditado (seed o DB directa). Queda fuera de este feature.
- Cambios al modelo de datos (`student_guardians`) — el filtro es a nivel de query, no de constraint en DB.
