# Design — Role Profiles

**Feature:** `02-role-profiles`
**Depends on:** `00-catalogs`, `01-user-profiles`

---

## Table Schemas

### students — new columns (migration adds to existing table)

| Column | Type | Notes |
|--------|------|-------|
| `student_code` | `varchar(20)` UNIQUE | Format: EST-YYYY-NNNNN. Generated on create. |
| `academic_status_id` | `bigint` FK → `academic_statuses` RESTRICT | Defaults to `active` |
| `modality_id` | `bigint` FK → `study_modalities` RESTRICT | Nullable |
| `shift_id` | `bigint` FK → `academic_shifts` RESTRICT | Nullable |
| `admission_type_id` | `bigint` FK → `admission_types` RESTRICT | Nullable. University only. |
| `cumulative_gpa` | `numeric(4,2)` | Nullable. CHECK 0–20. University only. |
| `grade_id` | `bigint` FK → `school_grades` RESTRICT | Nullable. Primary/secondary only. |
| `enrollment_date` | `date` | Nullable. Date enrolled at institution. |

**Column removed:** `guardian_id` (migrated to pivot)

---

### student_guardians (new pivot table)

| Column | Type | Notes |
|--------|------|-------|
| `student_id` | `bigint` FK → `students` RESTRICT | Composite PK |
| `guardian_id` | `bigint` FK → `guardians` RESTRICT | Composite PK |
| `kinship_type_id` | `bigint` FK → `kinship_types` RESTRICT | NOT NULL |
| `is_primary` | `boolean` NOT NULL DEFAULT false | Only one per student |
| `is_emergency_contact` | `boolean` NOT NULL DEFAULT false | |

**Primary key:** `(student_id, guardian_id)`

---

### guardian_profiles (new table)

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint` PK | |
| `guardian_id` | `bigint` FK → `guardians` RESTRICT UNIQUE | 1:1 with guardians |
| `occupation` | `varchar(150)` | Nullable |
| `employer` | `varchar(200)` | Nullable |
| `work_phone` | `varchar(20)` | Nullable |
| `education_level_id` | `bigint` FK → `education_levels` RESTRICT | Nullable |
| `marital_status_id` | `bigint` FK → `marital_statuses` RESTRICT | Nullable |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

### staff_profiles (new table)

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint` PK | |
| `professor_id` | `bigint` FK → `professors` RESTRICT UNIQUE | 1:1 with professors |
| `employee_code` | `varchar(20)` UNIQUE | Format: EMP-YYYY-NNNNN. Generated. |
| `academic_title` | `varchar(100)` | Nullable. Lic., MSc., Dr., etc. |
| `specialty` | `varchar(150)` | Nullable |
| `contract_type_id` | `bigint` FK → `contract_types` RESTRICT | NOT NULL |
| `dedication_type_id` | `bigint` FK → `dedication_types` RESTRICT | NOT NULL |
| `weekly_hour_load` | `smallint` | Nullable |
| `hire_date` | `date` | NOT NULL |
| `termination_date` | `date` | Nullable. CHECK ≥ hire_date |
| `employment_status_id` | `bigint` FK → `employment_statuses` RESTRICT | NOT NULL. Default: active |
| `is_coordinator` | `boolean` NOT NULL DEFAULT false | |
| `coordinated_department_id` | `bigint` FK → `departments` RESTRICT | Nullable. Requires is_coordinator |
| `coordinator_since` | `date` | Nullable. Requires is_coordinator |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

### guardians — columns removed

`name` and `relation` columns are dropped in this feature's migration after data is already in `users.first_name`/`last_name` (from `01-user-profiles`) and `student_guardians.kinship_type_id`.

---

## Models

### Student (updated)

New fillable: `student_code`, `academic_status_id`, `modality_id`, `shift_id`, `admission_type_id`, `cumulative_gpa`, `grade_id`, `enrollment_date`  
Removed fillable: `guardian_id`  
New relations:
- `guardians(): BelongsToMany(Guardian)` through `student_guardians` with pivot `kinship_type_id`, `is_primary`, `is_emergency_contact`
- `primaryGuardian(): HasOneThrough` or scoped via `guardians()->wherePivot('is_primary', true)->first()`
- `academicStatus(): BelongsTo(AcademicStatus)`
- `modality(): BelongsTo(StudyModality)`
- `shift(): BelongsTo(AcademicShift)`
- `admissionType(): BelongsTo(AdmissionType)`
- `grade(): BelongsTo(SchoolGrade)`

### Guardian (updated)

- Remove `name` and `relation` from `$fillable`
- Remove `students(): HasMany` (replaced by pivot)
- Add `students(): BelongsToMany(Student)` through `student_guardians`
- Add `profile(): HasOne(GuardianProfile)`

### GuardianProfile (new)

- `$fillable`: all columns except `guardian_id`
- Relations: `guardian(): BelongsTo`

### Professor (updated)

- Add `staffProfile(): HasOne(StaffProfile)`

### StaffProfile (new)

- `$fillable`: all columns except `professor_id`
- Relations: `professor(): BelongsTo`, `contractType(): BelongsTo`, `dedicationType(): BelongsTo`, `employmentStatus(): BelongsTo`, `coordinatedDepartment(): BelongsTo(Department)`

---

## Migration Strategy

The migration for this feature has 4 distinct steps:

1. **Add new columns to `students`** (student_code, academic_status_id, etc.)
2. **Create `student_guardians` pivot** and **data-migrate** `students.guardian_id` records:
   ```sql
   INSERT INTO student_guardians (student_id, guardian_id, kinship_type_id, is_primary, is_emergency_contact)
   SELECT id, guardian_id, (SELECT id FROM kinship_types WHERE code = 'other'), true, true
   FROM students WHERE guardian_id IS NOT NULL;
   ```
3. **Drop `students.guardian_id`** column.
4. **Drop `guardians.name` and `guardians.relation`** columns (already migrated to users in 01-user-profiles).

Rollback (`down()`) reverses in order: re-add `guardians.name`/`relation`, re-add `students.guardian_id` from pivot (take first `is_primary` record), drop pivot, drop new student columns.

---

## Backend Pipeline (new endpoints)

```
POST /admin/students/{student}/guardians
  → AttachGuardianRequest → StudentGuardianController → AttachGuardianAction
  (validates kinship_type_id, enforces is_primary uniqueness)

DELETE /admin/students/{student}/guardians/{guardian}
  → DetachGuardianRequest → StudentGuardianController::destroy()

POST/PUT /admin/guardians/{guardian}/profile
  → StoreGuardianProfileRequest → GuardianProfileController → UpsertGuardianProfileAction

POST/PUT /admin/professors/{professor}/staff-profile
  → StoreStaffProfileRequest → StaffProfileController → UpsertStaffProfileAction
  (validates coordinator fields consistency)
```
