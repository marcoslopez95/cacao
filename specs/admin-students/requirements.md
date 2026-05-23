# Admin Students Index — Requirements

## Feature
Admin page at `/academic/students` listing all students with rich filtering, matching the CACAO Estudiantes.html design.

## Source
Design file: `CACAO Estudiantes.html` (Claude Design handoff, 2026-05-23)

## Functional requirements

### RF-01 — List
- Paginated list of all students (25 per page default)
- Ordered by user name by default

### RF-02 — Quick views
Five preset filter shortcuts with live counts:
| Key | Filter |
|---|---|
| all | no filter |
| pending | enrollment status = draft OR no enrollment |
| top | GPA ≥ 17 (returns 0 until GPA module; shown but inactive) |
| risk | GPA < 12 (same) |
| newcomers | academic_year = 1 |

### RF-03 — Filters (composable)
- `search`: text match on users.name and users.email
- `career_id[]`: multi-select by career
- `academic_year[]`: multi-select by year
- `enrollment_status[]`: confirmed / draft (pending) / none / rejected (withdrawn)

### RF-04 — Table columns
| Column | Source |
|---|---|
| Estudiante | users.name + users.email + avatar initials |
| Cédula · Código | `—` (fields don't exist yet) |
| Carrera | pensum.career.name (null-safe) |
| Año | students.academic_year |
| Promedio | `—` (GPA not yet stored) |
| Inscripción [período activo] | enrollment.status for active period |

### RF-05 — Result bar
Shows filtered count + breakdown: confirmed / pending (draft) / sin inscribir.

### RF-06 — Pagination
Configurable per-page (15/25/50/100), URL-synced.

### RF-07 — Action buttons
Three buttons per row (Ver perfil, Editar, Más) rendered but non-functional — wired when subpages exist.

## Out of scope
- Create/Edit/Delete student
- GPA column data
- Cédula/código fields
- Bulk actions
- CSV export
