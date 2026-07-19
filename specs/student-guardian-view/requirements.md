# Requirements — student-guardian-view

**Feature ID:** `student-guardian-view`
**Origen:** Solicitud directa del usuario (no HLZ)
**Prioridad:** MEDIA
**Depende de:** `guardian-student-relations` (relación `Student::guardians()` ya existe)

---

## Problema

El estudiante no tiene forma de ver, desde su propio portal, quién es su representante. La relación `Student::guardians()` (belongsToMany vía `student_guardians`) y su resolución de parentesco ya existen y se usan hoy solo en el lado Admin (`admin/Students/Show.vue`, `admin/Guardians/Show.vue`). Falta exponerla en el self-service del estudiante.

---

## Caso de uso aprobado

### UC-01 — Ver mi(s) representante(s) en el dashboard del estudiante

**Precondición:** Usuario autenticado con rol Estudiante.

**Pasos:** Estudiante entra a `student/dashboard` (ya existente).

**Resultado esperado:** Nueva card "Mi representante" en `student/Dashboard.vue`, debajo del stats row y antes del timeline del día. Muestra, por cada representante vinculado: nombre, correo, teléfono (`phone_primary` del `User`), parentesco (texto legible) y si es representante principal. Si el estudiante no tiene representantes vinculados (caso universitario), la card no se muestra.

---

## Criterios de aceptación

1. `student.dashboard` expone un nuevo prop `guardians: StudentGuardianSummary[]` (array vacío si no tiene representantes).
2. Cada elemento resuelve `kinship_type_id` a texto legible con una sola consulta de catálogo (mismo patrón que `GuardianShowResource`) — no N+1.
3. El representante principal (`is_primary`) aparece primero si hay más de uno.
4. La card no se renderiza cuando `guardians` está vacío (nivel universitario sin representante).
5. Tests de feature: dashboard incluye `guardians` en los props; caso con representante principal + secundario (orden); caso sin representantes (array vacío).

## Fuera de alcance

- Edición del vínculo o de los datos del representante desde este dashboard — solo lectura.
- Página dedicada de perfil del estudiante — se decidió reusar `student/Dashboard.vue` en vez de crear una ruta nueva.
