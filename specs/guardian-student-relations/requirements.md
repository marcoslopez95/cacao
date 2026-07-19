# Requirements — guardian-student-relations

**Feature ID:** `guardian-student-relations`
**Origen:** Solicitud directa del usuario (no HLZ)
**Prioridad:** MEDIA

---

## Problema

Hoy la relación estudiante↔representante solo es visible en una dirección: `admin/Students/Show.vue` ya muestra los representantes de un estudiante (sección "Representantes", condicionada a primaria/bachillerato). No existe:

1. Una forma de ver, dado un representante, cuáles estudiantes tiene a cargo.
2. Un listado administrativo de representantes (`Guardian` no tiene índice propio — solo existe su portal personal `/guardian/dashboard` y `GuardianProfileController` para editar su perfil).
3. Esta relación visible desde `security/Users/Edit.vue` — hoy el formulario de edición de usuario no muestra representantes de un estudiante ni estudiantes de un representante.

---

## Casos de uso aprobados

### UC-01 — Ver estudiantes de un representante (índice académico nuevo)

**Precondición:** Admin autenticado

**Pasos:** Sidebar → Académico → "Representantes" → click en un representante

**Resultado esperado:** Página `admin/Guardians/Show.vue` con identidad del representante (nombre, email) y tabla de estudiantes a cargo: nombre, email, nivel educativo, parentesco, si es principal / contacto de emergencia. Cada fila enlaza a `academic/students/{id}` (Show ya existente).

### UC-02 — Listado de representantes

**Pasos:** Sidebar → Académico → "Representantes"

**Resultado esperado:** Página `admin/Guardians/Index.vue` con tabla: nombre, email, cantidad de estudiantes a cargo. Buscador por nombre/correo. Paginado (mismo patrón que `security/Users/Index.vue`).

### UC-03 — Ver acceso rápido a la relación desde `security/Users/Index.vue`

**Contexto:** `security/Users/Edit.vue` es un wizard de 17 secciones (`UF_SECTIONS`) sin espacio libre para paneles de solo lectura sin invadir esa arquitectura; `EditUserModal.vue` es un modal liviano (nombre/correo/roles) fuera de alcance para esto. Se descarta tocar cualquiera de los dos.

**Precondición:** Admin autenticado en `security/users` (índice)

**Resultado esperado:** Nueva columna "Relación" en la tabla:
- Usuario con rol Estudiante → badge/link "Representantes (N)" que navega a `academic/students/{student_id}` (ya muestra la sección "Representantes").
- Usuario con rol Representante → badge/link "Estudiantes (N)" que navega a `academic/guardians/{guardian_id}` (UC-01, página nueva).
- Cualquier otro usuario → "—".

### UC-04 — (fusionado con UC-03, ver arriba)

---

## Criterios de aceptación

1. `GET academic/guardians` devuelve listado paginado de representantes con conteo de estudiantes; soporta `?search=`.
2. `GET academic/guardians/{guardian}` devuelve 404 si no existe; si existe, devuelve estudiantes a cargo con parentesco resuelto a texto legible (no ID crudo).
3. Sidebar muestra "Representantes" en el grupo Académico, mismo criterio de visibilidad que "Estudiantes" (rol Admin).
4. `security/users` (índice) expone conteo + link de la relación por fila, según el rol del usuario. Solo lectura — no se agrega edición de la relación en este alcance.
5. Ningún N+1 nuevo: resolución de parentesco (`kinship_type_id`) se hace con una sola consulta de catálogo, no una por fila.
6. Tests de feature cubren: index de representantes (con y sin búsqueda), show de representante (con y sin estudiantes), 404 para representante inexistente, y que `UserController::edit` expone `guardians`/`students` en los props correctos según el rol del usuario.

## Fuera de alcance

- Edición de la relación (crear/quitar vínculo estudiante-representante) — ya existe una UI mock para esto en `UserFormS15Guardians.vue` (sección S15 del formulario de creación/edición), no se toca en este feature.
- Permisos granulares (`guardians.view`) — se sigue el mismo patrón que `StudentController` (solo middleware `auth,verified` + gating de sidebar por rol Admin), sin Policy dedicada, ya que no existe ninguna en el resto del módulo académico.
