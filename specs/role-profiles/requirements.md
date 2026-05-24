# Requirements — Role Profiles

**Feature:** `02-role-profiles`
**Depends on:** `00-catalogs`, `01-user-profiles`

---

## Objective

Extend the three "person" roles with their full profile data: students get additional academic fields and a proper multi-guardian relationship; guardians get a profile model with occupational data; professors get a staff profile with contract/dedication information. Includes migrating `students.guardian_id` (single FK) to the `student_guardians` pivot table.

---

## Actors

| Actor | Description |
|-------|-------------|
| `admin` | Creates and manages all profiles. |
| `coordinator` | Views staff profiles within their department. |
| `student` | Views their own academic profile. |
| `guardian` | Views their own profile and linked students. |
| `professor` | Views their own staff profile. |

---

## Business Rules

### Student profile

- THE SYSTEM SHALL generate a unique `student_code` for each student on creation using the format `EST-YYYY-NNNNN` (e.g., `EST-2026-00001`).
- WHEN a student's `educational_level` is `university`, THE SYSTEM SHALL allow `admission_type_id`, `cumulative_gpa`, `current_pensum_id`, and `academic_year`.
- WHEN a student's `educational_level` is `primary` or `secondary`, THE SYSTEM SHALL allow `grade_id` and `section_id`; `admission_type_id` and `cumulative_gpa` do not apply.
- `cumulative_gpa` MUST be between 0.00 and 20.00 (Venezuelan grading scale) — enforced with a DB CHECK constraint.
- WHEN an `academic_status_id` is not provided on creation, THE SYSTEM SHALL default to the `active` status.

### Guardian relationship (pivot migration)

- WHEN the `02-role-profiles` migration runs, THE SYSTEM SHALL migrate every existing `students.guardian_id` record into a `student_guardians` row with `is_primary = true` and `is_emergency_contact = true`.
- WHEN the data migration completes, THE SYSTEM SHALL drop the `students.guardian_id` column.
- WHEN a student has a `student_guardians` record added, only one record per student can have `is_primary = true` — enforced at the application layer.
- THE SYSTEM SHALL allow a student to have multiple guardians (father + mother, for example).
- WHEN a guardian is linked to a student, `kinship_type_id` MUST be provided.

### Guardian profile

- WHEN a guardian (Representante) user is created, THE SYSTEM SHALL allow (but not require) creating a `guardian_profiles` record with occupational and civil data.
- The `Guardian` model retains its user_id 1:1 relation with `users`. The `name` and `relation` columns on `guardians` are deprecated by this feature and removed after data migration.

### Staff profile

- WHEN a professor user is created, THE SYSTEM SHALL allow (but not require) creating a `staff_profiles` record.
- WHEN `is_coordinator = true`, THE SYSTEM SHALL require `coordinated_department_id` and `coordinator_since`.
- WHEN `is_coordinator = false`, `coordinated_department_id` and `coordinator_since` MUST be null.
- `hire_date` MUST be before or equal to `termination_date` if both are provided.

---

## Technical Constraints

- `student_guardians` is a pivot table — no `id` column needed (composite PK: `student_id + guardian_id`).
- `students.guardian_id` FK is dropped after data migration — this is a **destructive migration**. Rollback restores the column from pivot data.
- `guardians.name` and `guardians.relation` are dropped in this feature (data is in `users.first_name`/`last_name` after `01-user-profiles`, and relation moves to pivot).
- `staff_profiles.employee_code` format: `EMP-YYYY-NNNNN`.
- `cumulative_gpa` DB CHECK: `CHECK (cumulative_gpa BETWEEN 0.00 AND 20.00)`.
- All new FK references: RESTRICT on delete.
- Follow the `FormRequest → Controller → Wrapper → Action → Resource` pipeline for all new endpoints.
