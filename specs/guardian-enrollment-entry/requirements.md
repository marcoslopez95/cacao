# Requirements — guardian-enrollment-entry

**Feature ID:** `guardian-enrollment-entry`
**Origen:** Solicitud directa del usuario (no HLZ)
**Prioridad:** MEDIA
**Depende de:** ninguno (backend ya existe)

---

## Problema

El representante puede inscribir a sus estudiantes a cargo — el backend ya lo soporta por completo:

- `EnrollmentController::resolveStudent()` ya acepta `?student_id=` y valida contra `$guardian->students()` (403 si no coincide).
- `EnrollmentPolicy::create/view/update/confirm/delete` ya validan pertenencia guardian↔estudiante.
- `resources/js/pages/enrollment/Index.vue` ya muestra el nombre del estudiante (`rules.student_name` → `EnrollmentSummaryPanel`, línea "{{ rules.period }} · {{ rules.studentName }}").
- Tests de backend ya cubren "guardian sees enrollment for assigned student via student_id param" y "guardian cannot create enrollment for unassigned student" (`tests/Feature/Enrollment/`).

Lo que falta es exclusivamente el **punto de entrada**: `guardian/Dashboard.vue` (`app/Http/Controllers/Guardian/DashboardController.php`) es de solo lectura hoy — no enlaza a `/enrollment`.

---

## Caso de uso aprobado

### UC-01 — Representante inscribe a un estudiante a cargo

**Precondición:** Usuario autenticado con rol Representante, con ≥1 estudiante vinculado.

**Pasos:** Representante entra a `guardian/dashboard` (ya existente) → en la card de un estudiante, click en el botón de inscripción.

**Resultado esperado:** Navega a `/enrollment?student_id={id}` (mismo `enrollment/Index.vue` que usa el estudiante). El nombre del estudiante que se está inscribiendo ya es visible en el panel resumen (`rules.studentName`). Adicionalmente, el breadcrumb de la página (hoy hardcodeado como "Estudiante" en `Index.vue:33`) muestra el nombre real del estudiante para mayor claridad cuando quien navega es el representante.

## Criterios de aceptación

1. Cada card de estudiante en `guardian/Dashboard.vue` tiene un botón que enlaza a `enrollment.index` con `student_id` del estudiante de esa card (vía Wayfinder `index.url({ query: { student_id } })`).
2. El texto del botón refleja el estado de inscripción del estudiante: sin inscripción → "Inscribir"; `draft` → "Continuar inscripción"; `confirmed`/`approved`/`rejected` → "Ver inscripción".
3. El breadcrumb de `enrollment/Index.vue` muestra el nombre real del estudiante (`uiRules.studentName`) en vez del texto estático "Estudiante".
4. No se requiere ningún cambio de backend ni de autorización — ya está cubierto por tests existentes.

## Fuera de alcance

- `Guardian\GradeController` sigue limitado a `$guardian->students()->first()` — no se toca en este feature (ya identificado como deuda existente, fuera de alcance de esta solicitud).
- Selección múltiple de estudiante dentro de la propia página de inscripción — la selección ocurre en el dashboard, antes de entrar.
