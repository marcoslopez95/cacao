# Requirements — Socioeconomic & Health

**Feature:** `04-socioeconomic-health`
**Depends on:** `00-catalogs`, `01-user-profiles`, `02-role-profiles`, `03-student-background`

---

## Objective

Record the sensitive profile modules: socioeconomic study of the student's family, institutional benefits, housing conditions, health information, and LOPD-compliant data processing consents. All modules require strict access control — most are restricted to coordinator/admin roles.

---

## Actors

| Actor | Description |
|-------|-------------|
| `admin` / `superadmin` | Full read/write. Can see all data including sensitive fields. |
| `coordinator` | Read/write within their department's students. Cannot access health data. |
| `orientation_staff` (future role) | Records socioeconomic studies (`recorded_by` FK). |
| `student` | Read-only for own data (except health — read-only). Cannot see income/religion data. |
| `guardian` | Cannot access any sensitive module data. |
| `professor` | No access to sensitive modules. |

---

## Business Rules

### Socioeconomic profile

- THE SYSTEM SHALL enforce a 1:1 relationship between `socioeconomic_profiles` and `students`.
- WHEN `receives_remittances = true`, `remittance_country_id` SHOULD be provided.
- WHEN `student_works = true`, `employment_type_id` and `weekly_work_hours` SHOULD be provided.
- WHEN `has_scholarship = true`, `scholarship_name` SHOULD be provided.
- WHEN a socioeconomic profile is created or updated, `recorded_by` (admin/coordinator user id) and `study_date` MUST be set.
- Access: admin and coordinator only — professors, students, guardians cannot read or write.

### Student benefits

- A student can receive multiple concurrent benefits.
- THE SYSTEM SHALL reject duplicate `(student_id, benefit_id)` combinations.
- WHEN a benefit has an `until` date, THE SYSTEM SHALL validate `until >= since` if both provided.
- Access: admin and coordinator only.

### Health profile

- THE SYSTEM SHALL enforce a 1:1 relationship between `health_profiles` and `users` (applies to students and guardians).
- WHEN `has_disability = true`, `disability_type_id` SHOULD be provided.
- WHEN `has_special_needs = true`, `special_needs_description` SHOULD be provided.
- WHEN `has_medical_insurance = true`, `insurance_type_id` SHOULD be provided.
- Access: admin only — coordinators, professors, students, and guardians cannot access health data.

### Housing profile

- THE SYSTEM SHALL enforce a 1:1 relationship between `housing_profiles` and `students`.
- THE SYSTEM SHALL compute `is_overcrowded` as a PostgreSQL GENERATED STORED column:
  `(household_members::numeric / NULLIF(room_count, 0)) > 2.5`
- `housing_services` records which basic services are available in the student's home.
- THE SYSTEM SHALL reject duplicate `(housing_profile_id, basic_service_id)` in `housing_services`.
- Access: admin and coordinator only.

### User consents (LOPD / Ley de Infogobierno)

- `user_consents` allows MULTIPLE records per user (consent history by policy version).
- WHEN saving data to `health_profiles`, `socioeconomic_profiles`, or `housing_profiles`, THE SYSTEM SHALL verify that a consent record exists with `accepts_data_processing = true` AND `revoked_at IS NULL`.
- WHEN a user revokes consent, THE SYSTEM SHALL set `revoked_at = now()` on the active record — existing data is NOT automatically deleted (legal hold).
- `policy_version` tracks which version of the privacy policy was accepted (e.g., `v1.0`, `v2.0`).
- Access: user can create their own consent; admin can revoke; no one can delete consent records.

---

## Technical Constraints

- All sensitive tables (health_profiles, socioeconomic_profiles, housing_profiles) require a valid consent check before write operations.
- `is_overcrowded` in `housing_profiles`: PostgreSQL `GENERATED ALWAYS AS (...) STORED` — not a Laravel accessor, a real DB column.
- All FK references: RESTRICT on delete.
- `student_benefits` composite PK: `(student_id, benefit_id)`.
- `housing_services` composite PK: `(housing_profile_id, basic_service_id)`.
- Follow the `FormRequest → Controller → Wrapper → Action → Resource` pipeline.
- Add dedicated Laravel Policies for each module — no role checks inline in controllers.
