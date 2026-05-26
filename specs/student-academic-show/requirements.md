# Requirements — student-academic-show

**Feature ID:** `student-academic-show`
**QA Hallazgo:** HLZ-09
**Prioridad:** MEDIA

---

## Problema

Los botones **Ver perfil** (ojo) y **Editar** (lápiz) en la tabla `/academic/students` son placeholders sin wiring. Están presentes en los cuatro layouts de tabla (Todos, Primaria, Bachillerato, Universitario) pero no tienen comportamiento asociado.

Esto fue intencional en el sprint `admin-students` — `specs/admin-students/requirements.md` RF-07 dice explícitamente: *"Three buttons per row rendered but non-functional — wired when subpages exist."* Esta feature cumple esa promesa.

---

## Casos de uso aprobados

### UC-01 — Ver perfil de estudiante

**Precondición:** Admin autenticado en `/academic/students`

**Pasos:** Click botón ojo → navega a `/academic/students/{id}`

**Resultado esperado:** Página `admin/Students/Show.vue` con:
1. **Identidad:** nombre, email, cédula, código de estudiante
2. **Carrera / pensum activo:** nombre de carrera, nombre de pensum, créditos totales del pensum
3. **Datos académicos:** año académico, modalidad, turno, estado académico
4. **Inscripción del período activo:** estado, UC inscritas, UC disponibles
5. **Representantes** (solo primaria y bachillerato): nombre, relación de parentesco
6. **Historial de inscripciones por período:** período, estado, UC
7. **Notas y promedios:** promedio acumulado (`cumulative_gpa`), notas publicadas por período
8. **Secciones actuales con horario:** materia, código de sección, profesor principal, días y horas

### UC-02 — Editar estudiante desde índice académico

**Precondición:** Admin autenticado en `/academic/students`

**Pasos:** Click botón lápiz → navega a `/security/users/{student.user_id}/edit`

**Resultado esperado:** Carga la página `security/Users/Edit.vue` (formulario de 17 secciones, ya existente) con los datos del estudiante.

---

## Criterios de aceptación

1. Botón ojo en todos los layouts de tabla navega a `/academic/students/{id}` — ruta existente
2. Botón lápiz en todos los layouts navega a `/security/users/{user_id}/edit`
3. La página Show carga sin errores para cualquier estudiante (universitario, primaria, bachillerato)
4. La página Show muestra "—" o secciones colapsadas cuando el estudiante no tiene carrera, inscripción activa, o representantes
5. `StudentController::show()` devuelve 404 para un ID de estudiante inexistente
6. Tests de feature cubren: show exitoso (universitario con inscripción), show de estudiante de primaria (con representante), 404 para estudiante no existente

---

## Archivos actuales relacionados

- `resources/js/pages/admin/Students/Index.vue` — botones sin wiring (líneas 530–532, 594–596, 657–659, 730–732)
- `app/Http/Controllers/Academic/StudentController.php` — solo tiene `index()`, no tiene `show()`
- `app/Http/Resources/Academic/StudentListResource.php` — no expone `user_id`
- `routes/web.php` línea 167 — solo ruta `GET academic/students` (index)
- `resources/js/types/student.ts` — `StudentListItem` no tiene campo `user_id`
