# Requirements — Student Background

**Feature:** `03-student-background`
**Depends on:** `00-catalogs`, `01-user-profiles`, `02-role-profiles`

---

## Objective

Record the academic history, language skills, family structure, and demographic data of students. This enriches the student profile for institutional reporting, inclusion programs, and MPPE/MPPEU requirements.

---

## Actors

| Actor | Description |
|-------|-------------|
| `admin` | Full read/write access to all background modules. |
| `coordinator` | Read access within their area; can record education background and family info. |
| `student` | Read their own data; can self-report language skills and demographic info (non-sensitive fields). |

---

## Business Rules

### Education background

- WHEN an education background record is created, THE SYSTEM SHALL enforce a 1:1 relationship with `students` — one record per student.
- IF `repeated_grade = true`, THEN `repeated_grade_description` SHOULD be provided (soft validation — warning, not rejection).
- IF `has_prior_studies = true` (university only), THEN `prior_studies_description` SHOULD be provided.
- `previous_gpa` MUST be between 0.00 and 20.00 if provided.

### Language skills

- A student can have multiple language records (one per language).
- WHEN a language is added, THE SYSTEM SHALL reject a duplicate `(student_id, language_id)` combination.
- WHEN a language is marked `is_mother_tongue = true`, THE SYSTEM SHALL allow only one mother tongue per student — any existing mother tongue record is updated to `false`.

### Family profile

- WHEN a family profile is created, THE SYSTEM SHALL enforce a 1:1 relationship with `students`.
- `sibling_position` MUST be ≤ `sibling_count` if both are provided.

### Demographic profile

- THE SYSTEM SHALL enforce a 1:1 relationship between `demographic_profiles` and `users` (applies to any role, not just students).
- IF `is_indigenous = true`, THEN `indigenous_community` SHOULD be provided.
- IF `is_returned_migrant = true`, THEN `previous_country_id` SHOULD be provided.
- IF `practices_sport = true`, THEN `sport` SHOULD be provided.
- `religion_id` is optional and treated as sensitive — access restricted to admin and coordinator roles.

---

## Technical Constraints

- All four tables have 1:1 relationships (`student_backgrounds` → `students`, `family_profiles` → `students`, `demographic_profiles` → `users`). Enforce with UNIQUE constraint on FK.
- `student_languages` is a pivot (many-to-many). Composite PK: `(student_id, language_id)`.
- All FK references: RESTRICT on delete.
- Follow the `FormRequest → Controller → Wrapper → Action → Resource` pipeline.
- `religion_id` must be excluded from the student-facing API response unless the authenticated user is admin or coordinator.
