# Spec 04 — Secciones Universitarias

**Fecha:** 2026-04-28
**Estado:** Borrador
**Depende de:** Spec 01 (Períodos), Spec 03 (Profesores), módulo académico (Subjects, Pensums), módulo de infraestructura (Classrooms)
**Bloquea:** Horarios (Specs 06-07), Inscripciones

---

## Contexto

Una **sección universitaria** es un grupo de estudiantes que cursa **una materia** específica durante un período semestral o trimestral. Es la unidad básica de inscripción y evaluación en el nivel universitario.

El horario de una sección universitaria define quién dicta cada sesión (un profesor diferente puede dictar distintos slots de la misma sección).

---

## Modelo de datos

### `sections` (university rows)

| campo | tipo | aplica | notas |
|---|---|---|---|
| `id` | bigint PK | | |
| `type` | enum: `university` | siempre `university` aquí | |
| `period_id` | FK → periods | | RESTRICT — el período debe ser `semester` o `trimester` |
| `subject_id` | FK → subjects | | RESTRICT — la materia del pensum |
| `code` | string(10) | | ej. `"01"`, `"02"` |
| `theory_classroom_id` | FK → classrooms, nullable | | RESTRICT |
| `lab_classroom_id` | FK → classrooms, nullable | | RESTRICT |
| `capacity` | smallint unsigned | | cupo definido por la sección |
| `created_at` / `updated_at` | timestamps | | |

**Unique:** `(period_id, subject_id, code)` — no repetir código para la misma materia en el mismo período.

Columnas exclusivas de secciones escolares (`pensum_id`, `grade`, `letter`, `main_teacher_id`, `classroom_id`) son `NULL` en filas universitarias — se agregan en Spec 05.

---

## Reglas de negocio

- El período padre debe tener `type IN ('semester', 'trimester')`.
- La materia (`subject_id`) debe pertenecer a un pensum cuyo `period_type` coincida con el tipo del período.
- `capacity` ≥ 1.
- No se puede eliminar una sección con inscripciones o horarios asignados (FK RESTRICT, se aplica en módulos posteriores).
- Solo se puede eliminar si no tiene horarios asignados (validado en la Action desde Spec 06).

---

## Backend

Patrón: `FormRequest → Controller → Wrapper → Action → Resource`.

### Rutas

```
GET    /scheduling/sections/university                   scheduling.sections.university.index
POST   /scheduling/sections/university                   scheduling.sections.university.store
PATCH  /scheduling/sections/university/{section}         scheduling.sections.university.update
DELETE /scheduling/sections/university/{section}         scheduling.sections.university.destroy
```

### Archivos PHP

```
app/Enums/SectionType.php                                       — university | school  con label()
app/Models/Section.php                                          — belongsTo Period, Subject, Classrooms
database/migrations/XXXX_create_sections_table.php              — solo columnas university por ahora
database/factories/SectionFactory.php                           — estado university() por defecto
app/Policies/SectionPolicy.php
app/Http/Requests/Scheduling/StoreUniversitySectionRequest.php
app/Http/Requests/Scheduling/UpdateUniversitySectionRequest.php
app/Http/Wrappers/Scheduling/UniversitySectionWrapper.php
app/Http/Resources/Scheduling/SectionResource.php
app/Actions/Scheduling/CreateUniversitySectionAction.php
app/Actions/Scheduling/UpdateUniversitySectionAction.php
app/Actions/Scheduling/DeleteSectionAction.php                  — retorna bool
app/Http/Controllers/Scheduling/UniversitySectionController.php
```

### Permisos (agregar a `database/data/permissions.yaml` y `roles.yaml`)

```
sections.view    sections.create    sections.update    sections.delete
```

### `SectionResource` shape (university)

```json
{
  "id": 1,
  "type": "university",
  "code": "01",
  "capacity": 30,
  "period": { "id": 2, "name": "2026-1", "type": "semester" },
  "subject": { "id": 5, "name": "Cálculo I", "code": "MAT-101" },
  "theoryClassroom": { "id": 3, "identifier": "A-201", "capacity": 35 },
  "labClassroom": null
}
```

---

## Frontend

### Página

```
resources/js/pages/scheduling/Sections/University.vue
```

- Tabla: Materia · Código · Período · Cupo · Aulas · Acciones.
- Filtros: período (select) y materia (búsqueda texto).
- Modales: Crear sección (seleccionar período, materia, código, cupo, aulas opcionales), Editar, Eliminar.

### Archivos frontend

```
resources/js/types/scheduling.ts                                     — añadir UniversitySection, *Collection
resources/js/composables/forms/useUniversitySectionForm.ts
resources/js/composables/permissions/useSectionPermissions.ts
resources/js/composables/filters/useUniversitySectionFilters.ts      — period_id + subject text
resources/js/components/scheduling/CreateUniversitySectionModal.vue
resources/js/components/scheduling/EditUniversitySectionModal.vue
resources/js/components/scheduling/DeleteSectionModal.vue
```

### Sidebar

Añadir ítem **Secciones Universitarias** al grupo "Horarios".

---

## Testing

Feature tests Pest — `tests/Feature/Scheduling/UniversitySectionControllerTest.php`:

- Admin puede listar secciones (con y sin filtros).
- Admin puede crear sección universitaria con período semestral.
- Admin **no puede** crear sección con período de tipo `year` → 422.
- Admin **no puede** repetir `(period_id, subject_id, code)` → 422.
- Admin puede actualizar sección.
- Admin puede eliminar sección sin horarios asignados.
- Sin autenticación → redirect `/login`.
- Sin permiso → 403.
