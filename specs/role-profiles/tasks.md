# Tasks — Role Profiles

**Feature:** `02-role-profiles`
**Depends on:** `00-catalogs` ✓ and `01-user-profiles` ✓ must be complete first

---

## Overall Progress

- [x] Task 1 — Extend `students` table: new profile columns + DB constraints
- [x] Task 2 — Create `student_guardians` pivot + data migration of `guardian_id`
- [x] Task 3 — Update `Student` and `Guardian` models; update factories + DemoStudentsSeeder
- [x] Task 4 — `guardian_profiles` migration + model + pipeline
- [x] Task 5 — Drop obsolete `guardians.name` / `guardians.relation` columns
- [x] Task 6 — `staff_profiles` migration + model + pipeline
- [x] Task 7 — Pest feature tests

**Reconciliado 2026-08-30**: código verificado como implementado (25 tests en verde), tracking nunca se había sincronizado. Divergencia menor: `GuardianProfilePolicy` no existe como archivo separado (Task 4 lo pedía), la autorización funciona por otro mecanismo y los tests de permisos pasan igual.

---

## Task Detail

---

### Task 1 — Extend `students` Table

**Files:**
- `database/migrations/*_extend_students_with_profile_fields.php`

**Migration adds:**
- `student_code varchar(20) unique nullable` (nullable to not break existing rows; populated by Action on new creates going forward; existing rows can be backfilled via a separate artisan command)
- `academic_status_id bigint FK → academic_statuses RESTRICT nullable`
- `modality_id bigint FK → study_modalities RESTRICT nullable`
- `shift_id bigint FK → academic_shifts RESTRICT nullable`
- `admission_type_id bigint FK → admission_types RESTRICT nullable`
- `cumulative_gpa numeric(4,2) nullable` with CHECK: `ADD CONSTRAINT students_gpa_check CHECK (cumulative_gpa BETWEEN 0.00 AND 20.00)`
- `grade_id bigint FK → school_grades RESTRICT nullable`
- `enrollment_date date nullable`

**Done criteria:**
- Migration runs cleanly on existing DB
- All FK constraints exist and are RESTRICT
- `cumulative_gpa` CHECK constraint exists: `vendor/bin/sail artisan db:table students` shows it
- Pint clean

---

### Task 2 — `student_guardians` Pivot + Data Migration

**Files:**
- `database/migrations/*_create_student_guardians_and_migrate_guardian_id.php`

**Migration steps:**
1. Create `student_guardians` table with composite PK `(student_id, guardian_id)`, `kinship_type_id` NOT NULL, `is_primary`, `is_emergency_contact`
2. Data migration — insert from existing `students.guardian_id`:
   ```php
   $otherId = DB::table('kinship_types')->where('code', 'other')->value('id');
   DB::table('students')
       ->whereNotNull('guardian_id')
       ->select('id as student_id', 'guardian_id')
       ->lazy()
       ->each(fn($row) => DB::table('student_guardians')->insert([
           'student_id'           => $row->student_id,
           'guardian_id'          => $row->guardian_id,
           'kinship_type_id'      => $otherId,
           'is_primary'           => true,
           'is_emergency_contact' => true,
       ]));
   ```
3. Drop `students.guardian_id` column and its FK constraint

**Rollback (`down()`):**
1. Re-add `students.guardian_id bigint nullable`
2. Restore FK: `guardian_id` → `guardians` RESTRICT
3. Populate from pivot: `UPDATE students s SET guardian_id = sg.guardian_id FROM student_guardians sg WHERE sg.student_id = s.id AND sg.is_primary = true`
4. Drop `student_guardians` table

**Done criteria:**
- Migration runs: every student that had a `guardian_id` now has a row in `student_guardians` with `is_primary = true`
- `students.guardian_id` column no longer exists
- Rollback restores the column with correct FK
- Pint clean

---

### Task 3 — Update Models + Factories + DemoStudentsSeeder

**Files:**
- `app/Models/Student.php`
- `app/Models/Guardian.php`
- `database/factories/StudentFactory.php`
- `database/factories/GuardianFactory.php`
- `database/seeders/Demo/DemoStudentsSeeder.php`

**Student model changes:**
- Remove `guardian_id` from `$fillable`
- Add to `$fillable`: `student_code`, `academic_status_id`, `modality_id`, `shift_id`, `admission_type_id`, `cumulative_gpa`, `grade_id`, `enrollment_date`
- Add cast: `cumulative_gpa` → `decimal:2`, `enrollment_date` → `date`
- Add relation: `guardians(): BelongsToMany(Guardian, 'student_guardians')` with `withPivot(['kinship_type_id', 'is_primary', 'is_emergency_contact'])`
- Remove: `guardian(): BelongsTo`
- Add helper: `primaryGuardian()` — returns `guardians()->wherePivot('is_primary', true)->first()`
- Add catalog relations: `academicStatus()`, `modality()`, `shift()`, `admissionType()`, `grade()`

**Guardian model changes:**
- Remove `name` and `relation` from `$fillable`
- Replace `students(): HasMany` with `students(): BelongsToMany(Student, 'student_guardians')` with pivot fields
- Add: `profile(): HasOne(GuardianProfile)`

**Factory/Seeder updates:**
- `StudentFactory`: remove `guardian_id`; add optional `academic_status_id`, `modality_id`
- `GuardianFactory`: remove `name` and `relation` (name now comes from User)
- `DemoStudentsSeeder`: replace `guardian_id` assignment with `$student->guardians()->attach($guardian->id, ['kinship_type_id' => $motherId, 'is_primary' => true, 'is_emergency_contact' => true])`

**Done criteria:**
- `$student->guardians` returns a collection (not a single model)
- `$student->primaryGuardian()` returns the guardian marked as primary
- `DemoStudentsSeeder` runs without errors
- Pint clean

---

### Task 4 — `guardian_profiles` Migration + Model + Pipeline

**Files:**
- `database/migrations/*_create_guardian_profiles_table.php`
- `app/Models/GuardianProfile.php`
- `app/Http/Requests/Admin/StoreGuardianProfileRequest.php`
- `app/Http/Wrappers/Admin/GuardianProfileWrapper.php`
- `app/Actions/Admin/UpsertGuardianProfileAction.php`
- `app/Http/Resources/Admin/GuardianProfileResource.php`
- `app/Http/Controllers/Admin/GuardianProfileController.php`
- `app/Policies/GuardianProfilePolicy.php`
- `routes/web.php`

**Done criteria:**
- Table exists with all FK columns RESTRICT
- `GuardianProfile` model has relations: `guardian()`, `educationLevel()`, `maritalStatus()`
- `UpsertGuardianProfileAction` uses `updateOrCreate(['guardian_id' => ...], [...])` — safe to call on create or update
- Route: `PUT /admin/guardians/{guardian}/profile` — named `admin.guardians.profile.upsert`
- `GuardianProfilePolicy` allows admin; guardian can upsert their own
- Pint clean

---

### Task 5 — Drop Obsolete Guardian Columns

**Files:**
- `database/migrations/*_drop_name_relation_from_guardians.php`

**Migration:** Drop `guardians.name` and `guardians.relation` columns.

**Rollback:** Re-add both columns as nullable varchar.

**Pre-condition:** This task runs AFTER Task 3 (DemoStudentsSeeder updated) and AFTER confirming no code references `guardians.name` or `guardians.relation` (grep check).

**Done criteria:**
- `vendor/bin/sail artisan db:table guardians` shows neither `name` nor `relation`
- `grep -r "guardians\.name\|guardian->name\|->relation" app/ resources/` — no results
- Full test suite still passes
- Pint clean

---

### Task 6 — `staff_profiles` Migration + Model + Pipeline

**Files:**
- `database/migrations/*_create_staff_profiles_table.php`
- `app/Models/StaffProfile.php`
- `app/Http/Requests/Admin/StoreStaffProfileRequest.php`
- `app/Http/Wrappers/Admin/StaffProfileWrapper.php`
- `app/Actions/Admin/UpsertStaffProfileAction.php`
- `app/Http/Resources/Admin/StaffProfileResource.php`
- `app/Http/Controllers/Admin/StaffProfileController.php`
- `app/Policies/StaffProfilePolicy.php`
- `routes/web.php`
- `app/Models/Professor.php` (add `staffProfile()` HasOne)

**Done criteria:**
- Table exists with all constraints (RESTRICT FKs, termination_date CHECK ≥ hire_date)
- `StoreStaffProfileRequest` validates: `is_coordinator = true` requires `coordinated_department_id` and `coordinator_since`; `termination_date` if present must be ≥ `hire_date`
- `employee_code` auto-generated in Action: `EMP-{year}-{zero-padded count}`
- `UpsertStaffProfileAction` uses `updateOrCreate` by `professor_id`
- Route: `PUT /admin/professors/{professor}/staff-profile` — named `admin.professors.staff-profile.upsert`
- Pint clean

---

### Task 7 — Pest Feature Tests

**Files:**
- `tests/Feature/Admin/StudentProfileExtensionTest.php`
- `tests/Feature/Admin/StudentGuardianPivotTest.php`
- `tests/Feature/Admin/GuardianProfileTest.php`
- `tests/Feature/Admin/StaffProfileTest.php`

**Done criteria:**
- `StudentProfileExtensionTest`: cumulative_gpa rejects < 0 and > 20; student_code auto-generated; academic_status defaults to active
- `StudentGuardianPivotTest`: attach guardian; second attach with is_primary=true replaces previous primary; detach guardian; migration check (pivot has data for seeded students)
- `GuardianProfileTest`: admin can upsert; guardian can upsert own; guardian cannot upsert another's (403)
- `StaffProfileTest`: coordinator requires department + since date; termination before hire rejected; employee_code auto-generated
- `vendor/bin/sail artisan test --compact --filter="StudentProfile|StudentGuardian|GuardianProfile|StaffProfile"` — all pass
- Full suite: `vendor/bin/sail artisan test --compact` — no regressions
