# Students Multi-Level — Requirements

## Feature
Refactor of `/academic/students` to support three educational levels (Primaria, Bachillerato,
Universitario) with adaptive columns, filters, and quick views per level, based on the
final design from `CACAO Estudiantes.html`.

## Source
Design file: `CACAO Estudiantes.html` (Claude Design handoff, 2026-05-23 — chat 7)
Extends: `specs/admin-students/` (original university-only implementation)

## Functional requirements

### RF-01 — Level tabs
Four tabs at the top of the page:
| Tab key | Label | Filter on `educational_level` |
|---|---|---|
| all | Todos | no filter |
| primary | Primaria | primary |
| secondary | Bachillerato | secondary |
| university | Universitario | university |

Switching tabs resets all other filters and quick views.

### RF-02 — Quick views per level
Five preset filter shortcuts, adapted per level:

**Todos (all)**
| Key | Filter |
|---|---|
| all | no filter |
| pending | enrollment status = draft OR no enrollment |
| top | GPA ≥ 17 (disabled, GPA is null) |
| risk | GPA < 12 (disabled, GPA is null) |

**Primaria**
| Key | Filter |
|---|---|
| all | level = primary |
| no_guardian | students.guardian_id IS NULL |
| grade_1 | academic_year = 1 |
| grade_6 | academic_year = 6 |
| enrolled | enrollment status present |

**Bachillerato**
| Key | Filter |
|---|---|
| all | level = secondary |
| year_1 | academic_year = 1 |
| year_5 | academic_year = 5 |
| enrolled | enrollment status present |

**Universitario**
| Key | Filter |
|---|---|
| all | level = university |
| pending | enrollment status = draft OR none |
| top | GPA ≥ 17 (disabled) |
| risk | GPA < 12 (disabled) |
| newcomers | academic_year = 1 |

### RF-03 — Filters per level

**Todos / all**: search, enrollment_status[]
**Primaria / Bachillerato**: search, academic_year[], section_letter[], enrollment_status[]
**Universitario**: search, career_id[], academic_year[], enrollment_status[]

### RF-04 — Table columns per level

**Todos**
| Column | Source |
|---|---|
| Estudiante | name + email + avatar + level pill |
| Nivel | educational_level (colored pill) |
| Cohorte | academic_year + grade label |
| Detalle | GPA (university) or guardian name (school) |
| Inscripción | enrollment.status |

**Primaria**
| Column | Source |
|---|---|
| Estudiante | name + email + avatar |
| Grado | academic_year (1–6) |
| Sección | section.letter from active enrollment_details→sections |
| Representante | guardian.name + guardian.relation |
| Inscripción | enrollment.status |

**Bachillerato**
| Column | Source |
|---|---|
| Estudiante | name + email + avatar |
| Año | academic_year (1–5) |
| Sección | section.letter from active enrollment_details→sections |
| Representante | guardian.name + guardian.relation |
| Inscripción | enrollment.status |

**Universitario**
| Column | Source |
|---|---|
| Estudiante | name + email + avatar |
| Carrera | pensum.career.name |
| Año | academic_year |
| UC | uc_inscritas from active enrollment |
| Promedio | `—` (GPA null, column present but shows dash) |
| Inscripción | enrollment.status |

### RF-05 — Quick view active state (bug fix)
The selected quick view chip must use a visually distinct active state:
terracota accent + ring. The current implementation has low contrast.

### RF-06 — Result bar
Shows filtered count + breakdown: confirmed / pending (draft) / sin inscribir.
When a level tab is active, counts are scoped to that level.

### RF-07 — Pagination
Unchanged from current: configurable per-page (15/25/50/100), URL-synced.

## Out of scope
- GPA calculation (column shown with `—`)
- Create/Edit/Delete student
- Bulk actions
- CSV export
- Student profile drawer/page
