# Tasks — Student Background

**Feature:** `03-student-background`
**Depends on:** `00-catalogs` ✓, `01-user-profiles` ✓, `02-role-profiles` ✓

---

## Overall Progress

- [ ] Task 1 — `student_backgrounds` migration + model + pipeline
- [ ] Task 2 — `student_languages` pivot migration + model + pipeline
- [ ] Task 3 — `family_profiles` migration + model + pipeline
- [ ] Task 4 — `demographic_profiles` migration + model + pipeline
- [ ] Task 5 — Update `Student` and `User` model relations
- [ ] Task 6 — Pest feature tests

---

## Task Detail

---

### Task 1 — `student_backgrounds`

**Files:**
- `database/migrations/*_create_student_backgrounds_table.php`
- `app/Models/StudentBackground.php`
- `app/Http/Requests/Admin/StoreStudentBackgroundRequest.php`
- `app/Http/Wrappers/Admin/StudentBackgroundWrapper.php`
- `app/Actions/Admin/UpsertStudentBackgroundAction.php`
- `app/Http/Resources/Admin/StudentBackgroundResource.php`
- `app/Http/Controllers/Admin/StudentBackgroundController.php`
- `app/Policies/StudentBackgroundPolicy.php`
- `routes/web.php`

**Done criteria:**
- Table exists with correct UNIQUE on `student_id` and FK RESTRICT on all foreign keys
- DB CHECK: `previous_gpa BETWEEN 0.00 AND 20.00`
- `UpsertStudentBackgroundAction` uses `updateOrCreate(['student_id' => ...], [...])`
- `StoreStudentBackgroundRequest` validates: `graduation_year` is 4 digits; `previous_gpa` 0–20
- Route: `PUT /admin/students/{student}/background` — named `admin.students.background.upsert`
- `StudentBackgroundPolicy`: admin/coordinator can write; student can read own
- Pint clean

---

### Task 2 — `student_languages` Pivot

**Files:**
- `database/migrations/*_create_student_languages_table.php`
- `app/Models/StudentLanguage.php`
- `app/Http/Requests/Admin/StoreStudentLanguageRequest.php`
- `app/Http/Wrappers/Admin/StudentLanguageWrapper.php`
- `app/Actions/Admin/AttachStudentLanguageAction.php`
- `app/Http/Resources/Admin/StudentLanguageResource.php`
- `app/Http/Controllers/Admin/StudentLanguageController.php`
- `routes/web.php`

**Done criteria:**
- Table has composite PK `(student_id, language_id)` and FK RESTRICT on both FKs
- `AttachStudentLanguageAction::handle()` wraps in a transaction:
  1. If `is_mother_tongue = true`, update all existing `student_languages` for this student to `is_mother_tongue = false`
  2. Insert or update via `sync`/`attach` with pivot values
- Route: `POST /admin/students/{student}/languages`, `DELETE /admin/students/{student}/languages/{language}`
- Duplicate `(student_id, language_id)` insertion returns HTTP 422 with clear message
- Pint clean

---

### Task 3 — `family_profiles`

**Files:**
- `database/migrations/*_create_family_profiles_table.php`
- `app/Models/FamilyProfile.php`
- `app/Http/Requests/Admin/StoreFamilyProfileRequest.php`
- `app/Http/Wrappers/Admin/FamilyProfileWrapper.php`
- `app/Actions/Admin/UpsertFamilyProfileAction.php`
- `app/Http/Resources/Admin/FamilyProfileResource.php`
- `app/Http/Controllers/Admin/FamilyProfileController.php`
- `app/Policies/FamilyProfilePolicy.php`
- `routes/web.php`

**Done criteria:**
- Table exists with UNIQUE on `student_id`
- `StoreFamilyProfileRequest` validates: `sibling_position` ≤ `sibling_count` when both present
- `UpsertFamilyProfileAction` uses `updateOrCreate`
- Route: `PUT /admin/students/{student}/family-profile`
- Pint clean

---

### Task 4 — `demographic_profiles`

**Files:**
- `database/migrations/*_create_demographic_profiles_table.php`
- `app/Models/DemographicProfile.php`
- `app/Http/Requests/Admin/StoreDemographicProfileRequest.php`
- `app/Http/Wrappers/Admin/DemographicProfileWrapper.php`
- `app/Actions/Admin/UpsertDemographicProfileAction.php`
- `app/Http/Resources/Admin/DemographicProfileResource.php`
- `app/Http/Controllers/Admin/DemographicProfileController.php`
- `app/Policies/DemographicProfilePolicy.php`
- `routes/web.php`

**Done criteria:**
- Table exists with UNIQUE on `user_id`
- `DemographicProfileResource` conditionally includes `religion_id`: only when requester has `Admin` or `Coordinador de Area` role
- Route: `PUT /admin/users/{user}/demographic-profile`
- `UpsertDemographicProfileAction` uses `updateOrCreate`
- Pint clean

---

### Task 5 — Update Model Relations

**Files:**
- `app/Models/Student.php`
- `app/Models/User.php`

**Student model additions:**
- `background(): HasOne(StudentBackground)`
- `languages(): BelongsToMany(Language, 'student_languages', 'student_id', 'language_id')`
  with `withPivot(['language_level_id', 'is_mother_tongue'])`
  using pivot model `StudentLanguage`
- `familyProfile(): HasOne(FamilyProfile)`

**User model additions:**
- `demographicProfile(): HasOne(DemographicProfile)`

**Done criteria:**
- `$student->background` returns `StudentBackground|null`
- `$student->languages` returns a collection with pivot data
- `$student->familyProfile` returns `FamilyProfile|null`
- `$user->demographicProfile` returns `DemographicProfile|null`
- Pint clean

---

### Task 6 — Pest Feature Tests

**Files:**
- `tests/Feature/Admin/StudentBackgroundTest.php`
- `tests/Feature/Admin/StudentLanguageTest.php`
- `tests/Feature/Admin/FamilyProfileTest.php`
- `tests/Feature/Admin/DemographicProfileTest.php`

**Done criteria:**
- `StudentBackgroundTest`: upsert creates on first call; upsert updates on second call; previous_gpa > 20 rejected; 1:1 constraint works
- `StudentLanguageTest`: attach language; duplicate rejected (422); is_mother_tongue replaces previous; detach language
- `FamilyProfileTest`: sibling_position > sibling_count rejected; upsert works
- `DemographicProfileTest`: admin sees religion_id in response; student does NOT see religion_id; is_indigenous flag with community name
- `vendor/bin/sail artisan test --compact --filter="StudentBackground|StudentLanguage|FamilyProfile|DemographicProfile"` — all pass
- Full suite: `vendor/bin/sail artisan test --compact` — no regressions
