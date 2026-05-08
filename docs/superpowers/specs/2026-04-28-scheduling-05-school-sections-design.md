# Spec 05 — Secciones Escolares y Materias por Sección

**Fecha:** 2026-04-28
**Estado:** Borrador
**Depende de:** Spec 01 (Períodos), Spec 03 (Profesores), Spec 04 (tabla `sections` ya creada), módulo académico (Subjects, Pensums)
**Bloquea:** Horarios (Specs 06-07), Inscripciones

---

## Contexto

Una **sección escolar** es un grupo fijo de estudiantes (ej. "3er Año A") que cursa **todas las materias** de su grado juntos durante un período anual. Las materias vienen al grupo, no al revés.

Cada sección tiene un **docente de aula** (tutor/homeroom) que enseña la mayoría de materias. Las materias con "especialistas" (Inglés, Ed. Física, Matemática avanzada, etc.) pueden tener otro profesor asignado — hasta que se asigne, la materia queda sin especialista (`professor_id = null`).

---

## Modelo de datos

### Columnas adicionales en `sections` (migration ALTER TABLE)

| campo | tipo | notas |
|---|---|---|
| `pensum_id` | FK → pensums, nullable | RESTRICT — requerido para secciones escolares |
| `grade` | tinyint unsigned, nullable | año/grado: 1–12 |
| `letter` | string(5), nullable | `"A"`, `"B"`, `"C"` |
| `main_teacher_id` | FK → professors, nullable | RESTRICT — docente de aula |
| `classroom_id` | FK → classrooms, nullable | RESTRICT — aula principal (cupo viene de aquí) |

**Unique en `sections`:** `(period_id, pensum_id, grade, letter)` — no repetir grado+letra en el mismo pensum y período.

### `section_subjects`

| campo | tipo | notas |
|---|---|---|
| `id` | bigint PK | |
| `section_id` | FK → sections | RESTRICT on delete |
| `subject_id` | FK → subjects | RESTRICT on delete |
| `professor_id` | FK → professors, nullable | RESTRICT — null = sin especialista asignado |
| `created_at` / `updated_at` | timestamps | |
| unique | (`section_id`, `subject_id`) | |

---

## Reglas de negocio

- El período padre debe tener `type = 'year'`.
- El pensum debe tener `period_type = 'year'`.
- Al **crear** una sección escolar, el sistema auto-genera registros en `section_subjects` para todas las materias del pensum donde `subjects.period_number = grade` (las materias de ese grado).
- Si `professor_id` en `section_subjects` es `null`, el docente de aula (`main_teacher_id`) es quien dicta la materia implícitamente.
- La asignación de especialistas es un endpoint separado (PATCH).
- No se puede eliminar una sección con inscripciones o horarios asignados.

---

## Backend

### Rutas

```
GET    /scheduling/sections/school                                        scheduling.sections.school.index
POST   /scheduling/sections/school                                        scheduling.sections.school.store
PATCH  /scheduling/sections/school/{section}                              scheduling.sections.school.update
DELETE /scheduling/sections/school/{section}                              scheduling.sections.school.destroy
PATCH  /scheduling/sections/school/{section}/subjects/{subject}/assign    scheduling.sections.school.subjects.assign
```

### Archivos PHP

```
app/Models/SectionSubject.php
database/migrations/XXXX_add_school_columns_to_sections_table.php   — ALTER TABLE sections ADD ...
database/migrations/XXXX_create_section_subjects_table.php
database/factories/SectionSubjectFactory.php
app/Http/Requests/Scheduling/StoreSchoolSectionRequest.php
app/Http/Requests/Scheduling/UpdateSchoolSectionRequest.php
app/Http/Requests/Scheduling/AssignSectionSubjectProfessorRequest.php
app/Http/Wrappers/Scheduling/SchoolSectionWrapper.php
app/Http/Resources/Scheduling/SectionSubjectResource.php
app/Actions/Scheduling/CreateSchoolSectionAction.php     — crea sección + auto-genera section_subjects
app/Actions/Scheduling/UpdateSchoolSectionAction.php
app/Actions/Scheduling/AssignSectionSubjectProfessorAction.php
app/Http/Controllers/Scheduling/SchoolSectionController.php
```

Reutiliza: `SectionPolicy`, `DeleteSectionAction`, `SectionResource` (ya creados en Spec 04).

### `SectionResource` actualizado (school)

```json
{
  "id": 7,
  "type": "school",
  "grade": 3,
  "letter": "A",
  "period": { "id": 6, "name": "2025-2026", "type": "year" },
  "pensum": { "id": 2, "name": "3er Año" },
  "mainTeacher": { "id": 1, "user": { "name": "Ana Pérez" } },
  "classroom": { "id": 8, "identifier": "201", "capacity": 35 },
  "subjects": [
    { "id": 10, "subject": { "id": 3, "name": "Matemática" }, "professor": null },
    { "id": 11, "subject": { "id": 4, "name": "Lengua" }, "professor": { "id": 1, "user": { "name": "Ana Pérez" } } }
  ]
}
```

---

## Frontend

### Página

```
resources/js/pages/scheduling/Sections/School.vue
```

- Tabla: Grado · Letra · Pensum · Período · Docente de aula · Cupo (del aula) · Acciones.
- Filtros: período (select) y grado (select 1–12).
- Panel de materias: al expandir una fila, muestra la lista de `section_subjects` con el botón "Asignar especialista" por materia.
- Modales: Crear sección, Editar, Eliminar, Asignar especialista (select de profesores activos).

### Archivos frontend

```
resources/js/types/scheduling.ts                                  — añadir SchoolSection, SectionSubject
resources/js/composables/forms/useSchoolSectionForm.ts
resources/js/composables/forms/useSectionSubjectForm.ts
resources/js/composables/filters/useSchoolSectionFilters.ts
resources/js/components/scheduling/CreateSchoolSectionModal.vue
resources/js/components/scheduling/EditSchoolSectionModal.vue
resources/js/components/scheduling/SectionSubjectsPanel.vue       — lista expandible de materias
resources/js/components/scheduling/AssignSpecialistModal.vue
```

### Sidebar

Añadir ítem **Secciones Escolares** al grupo "Horarios".

---

## Testing

Feature tests Pest — `tests/Feature/Scheduling/SchoolSectionControllerTest.php`:

- Admin puede crear sección escolar con período `year`.
- Al crear sección, se auto-generan `section_subjects` para las materias del grado.
- Admin **no puede** crear sección escolar con período `semester` → 422.
- Admin **no puede** repetir `(period_id, pensum_id, grade, letter)` → 422.
- Admin puede asignar especialista (`professor_id`) a un `section_subject`.
- Admin puede quitar especialista (enviar `professor_id: null`).
- Admin puede actualizar sección.
- Admin puede eliminar sección sin horarios.
- Sin permiso → 403.
