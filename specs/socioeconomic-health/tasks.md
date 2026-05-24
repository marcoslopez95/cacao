# Tasks — Socioeconomic & Health

**Feature:** `04-socioeconomic-health`
**Depends on:** `00-catalogs` ✓, `01-user-profiles` ✓, `02-role-profiles` ✓, `03-student-background` ✓

---

## Overall Progress

- [x] Task 1 — `ConsentService` + `user_consents` migration + model + pipeline
- [x] Task 2 — `socioeconomic_profiles` migration + model + pipeline
- [x] Task 3 — `student_benefits` pivot migration + model + pipeline
- [x] Task 4 — `health_profiles` migration + model + pipeline
- [x] Task 5 — `housing_profiles` + `housing_services` migration + model + pipeline
- [x] Task 6 — Update `User` and `Student` model relations
- [x] Task 7 — Pest feature tests

---

## Task Detail

---

### Task 1 — `ConsentService` + `user_consents`

**Files:**
- `database/migrations/*_create_user_consents_table.php`
- `app/Models/UserConsent.php`
- `app/Services/ConsentService.php`
- `app/Http/Requests/StoreUserConsentRequest.php`
- `app/Http/Wrappers/StoreUserConsentWrapper.php`
- `app/Actions/CreateUserConsentAction.php`
- `app/Actions/RevokeUserConsentAction.php`
- `app/Http/Resources/UserConsentResource.php`
- `app/Http/Controllers/UserConsentController.php`
- `app/Policies/UserConsentPolicy.php`
- `routes/web.php`

**Done criteria:**
- Table exists — multiple records allowed per user (no UNIQUE on user_id)
- `UserConsent::scopeActive()`: `whereNull('revoked_at')`
- `ConsentService::hasActiveConsent(User): bool` — returns true only if active record with `accepts_data_processing = true`
- `ConsentService::requireConsent(User): void` — throws `ConsentRequiredException` if no active consent
- `CreateUserConsentAction` records `ip_address` from `$request->ip()` and `user_agent` from `$request->userAgent()`
- `RevokeUserConsentAction` sets `revoked_at = now()` — does not delete
- `UserConsentPolicy::create()`: authenticated user can create their own consent
- `UserConsentPolicy::revoke()`: only self or admin
- Route: `POST /users/{user}/consents`, `PATCH /users/{user}/consents/{consent}/revoke`
- Pint clean

---

### Task 2 — `socioeconomic_profiles`

**Files:**
- `database/migrations/*_create_socioeconomic_profiles_table.php`
- `app/Models/SocioeconomicProfile.php`
- `app/Http/Requests/Admin/StoreSocioeconomicProfileRequest.php`
- `app/Http/Wrappers/Admin/SocioeconomicProfileWrapper.php`
- `app/Actions/Admin/UpsertSocioeconomicProfileAction.php`
- `app/Http/Resources/Admin/SocioeconomicProfileResource.php`
- `app/Http/Controllers/Admin/SocioeconomicProfileController.php`
- `app/Policies/SocioeconomicProfilePolicy.php`
- `routes/web.php`

**Done criteria:**
- Table exists with UNIQUE on `student_id`, all FK RESTRICT
- `UpsertSocioeconomicProfileAction::handle()` calls `ConsentService::requireConsent()` before `updateOrCreate`
- If consent is missing → action throws `ConsentRequiredException` → controller returns HTTP 422 with message
- `SocioeconomicProfilePolicy`: admin and coordinator only (students/guardians/professors → 403)
- `StoreSocioeconomicProfileRequest` validates: `study_date` required; `recorded_by` set from authenticated user id if not provided
- Route: `PUT /admin/students/{student}/socioeconomic-profile`
- Pint clean

---

### Task 3 — `student_benefits`

**Files:**
- `database/migrations/*_create_student_benefits_table.php`
- `app/Models/StudentBenefit.php`
- `app/Http/Requests/Admin/StoreStudentBenefitRequest.php`
- `app/Http/Wrappers/Admin/StudentBenefitWrapper.php`
- `app/Actions/Admin/AttachStudentBenefitAction.php`
- `app/Http/Resources/Admin/StudentBenefitResource.php`
- `app/Http/Controllers/Admin/StudentBenefitController.php`
- `app/Policies/StudentBenefitPolicy.php`
- `routes/web.php`

**Done criteria:**
- Table has composite PK `(student_id, benefit_id)` and CHECK `until >= since`
- `AttachStudentBenefitAction` uses `$student->benefits()->syncWithoutDetaching()` or `attach()` with duplicate check
- Duplicate attach → HTTP 422 "El beneficio ya está asignado al estudiante."
- `StudentBenefitPolicy`: admin and coordinator only
- Routes: `POST /admin/students/{student}/benefits/{benefit}`, `DELETE /admin/students/{student}/benefits/{benefit}`
- Pint clean

---

### Task 4 — `health_profiles`

**Files:**
- `database/migrations/*_create_health_profiles_table.php`
- `app/Models/HealthProfile.php`
- `app/Http/Requests/Admin/StoreHealthProfileRequest.php`
- `app/Http/Wrappers/Admin/HealthProfileWrapper.php`
- `app/Actions/Admin/UpsertHealthProfileAction.php`
- `app/Http/Resources/Admin/HealthProfileResource.php`
- `app/Http/Controllers/Admin/HealthProfileController.php`
- `app/Policies/HealthProfilePolicy.php`
- `routes/web.php`

**Done criteria:**
- Table exists with UNIQUE on `user_id`, all FK RESTRICT
- `UpsertHealthProfileAction::handle()` calls `ConsentService::requireConsent()` before write
- `HealthProfilePolicy`: admin only — everyone else (including coordinators) → 403
- Route: `PUT /admin/users/{user}/health-profile`
- Pint clean

---

### Task 5 — `housing_profiles` + `housing_services`

**Files:**
- `database/migrations/*_create_housing_profiles_table.php`
- `database/migrations/*_create_housing_services_table.php`
- `app/Models/HousingProfile.php`
- `app/Models/HousingService.php`
- `app/Http/Requests/Admin/StoreHousingProfileRequest.php`
- `app/Http/Wrappers/Admin/HousingProfileWrapper.php`
- `app/Actions/Admin/UpsertHousingProfileAction.php`
- `app/Actions/Admin/SyncHousingServicesAction.php`
- `app/Http/Resources/Admin/HousingProfileResource.php`
- `app/Http/Controllers/Admin/HousingProfileController.php`
- `app/Policies/HousingProfilePolicy.php`
- `routes/web.php`

**Done criteria:**
- `housing_profiles`: UNIQUE on `student_id`; `is_overcrowded` added via raw SQL GENERATED column:
  ```php
  DB::statement("ALTER TABLE housing_profiles ADD COLUMN is_overcrowded boolean
      GENERATED ALWAYS AS (
          (household_members::numeric / NULLIF(room_count, 0)) > 2.5
      ) STORED");
  ```
- `HousingProfile` model: `is_overcrowded` is readable (`$user->housingProfile->is_overcrowded`) — no accessor needed; DO NOT add it to `$fillable` (GENERATED columns cannot be set)
- `UpsertHousingProfileAction` calls `ConsentService::requireConsent()` before write
- `housing_services`: composite PK `(housing_profile_id, basic_service_id)`
- `SyncHousingServicesAction` uses `$housingProfile->services()->sync()` with `is_available` pivot value
- `HousingProfilePolicy`: admin and coordinator only
- Routes: `PUT /admin/students/{student}/housing-profile`, `PATCH /admin/students/{student}/housing-profile/services`
- Pint clean

---

### Task 6 — Update Model Relations

**Files:**
- `app/Models/User.php`
- `app/Models/Student.php`

**User model additions:**
- `healthProfile(): HasOne(HealthProfile)`
- `consents(): HasMany(UserConsent)`
- Helper: `activeConsent(): ?UserConsent` — returns most recent active consent

**Student model additions:**
- `socioeconomicProfile(): HasOne(SocioeconomicProfile)`
- `housingProfile(): HasOne(HousingProfile)`
- `benefits(): BelongsToMany(InstitutionalBenefit, 'student_benefits', 'student_id', 'benefit_id')` with `withPivot(['is_active', 'since', 'until'])`

**Done criteria:**
- All new relations resolve correctly
- `$user->healthProfile` returns `HealthProfile|null`
- `$user->consents()` returns `HasMany` collection
- `$student->socioeconomicProfile` returns `SocioeconomicProfile|null`
- `$student->housingProfile->is_overcrowded` reads the GENERATED column
- `$student->benefits` returns `Collection` with pivot data
- Pint clean

---

### Task 7 — Pest Feature Tests

**Files:**
- `tests/Feature/Admin/UserConsentTest.php`
- `tests/Feature/Admin/SocioeconomicProfileTest.php`
- `tests/Feature/Admin/StudentBenefitTest.php`
- `tests/Feature/Admin/HealthProfileTest.php`
- `tests/Feature/Admin/HousingProfileTest.php`

**Done criteria:**
- `UserConsentTest`: create consent; active consent check passes; revoke consent; no active consent after revoke; multiple versions coexist
- `SocioeconomicProfileTest`: admin can upsert; coordinator can upsert; student gets 403; no active consent → 422; consent added → upsert succeeds
- `StudentBenefitTest`: attach benefit; duplicate → 422; detach benefit; until < since → 422
- `HealthProfileTest`: admin can upsert; coordinator gets 403; student gets 403; no consent → 422
- `HousingProfileTest`: upsert housing profile; is_overcrowded auto-computed (4 people / 1 room = overcrowded); sync services; housing_services pivot updated
- `vendor/bin/sail artisan test --compact --filter="UserConsent|Socioeconomic|StudentBenefit|Health|Housing"` — all pass
- Full suite: `vendor/bin/sail artisan test --compact` — no regressions
