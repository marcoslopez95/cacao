# Design — Socioeconomic & Health

**Feature:** `04-socioeconomic-health`

---

## Table Schemas

### socioeconomic_profiles (1:1 with students)

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint` PK | |
| `student_id` | `bigint` FK → `students` RESTRICT UNIQUE | 1:1 |
| `income_range_id` | `bigint` FK → `income_ranges` RESTRICT | Nullable |
| `income_source_id` | `bigint` FK → `income_sources` RESTRICT | Nullable |
| `household_earners` | `smallint` | Nullable |
| `receives_remittances` | `boolean` | Nullable |
| `remittance_country_id` | `bigint` FK → `countries` RESTRICT | Nullable. Conditional |
| `student_works` | `boolean` | Nullable. University only |
| `employment_type_id` | `bigint` FK → `employment_types` RESTRICT | Nullable. Conditional |
| `weekly_work_hours` | `smallint` | Nullable. Conditional |
| `has_scholarship` | `boolean` | Nullable |
| `scholarship_name` | `varchar(150)` | Nullable. Conditional |
| `has_institutional_benefit` | `boolean` | Nullable |
| `recorded_by` | `bigint` FK → `users` RESTRICT | Nullable. Admin/coordinator who recorded |
| `study_date` | `date` | Nullable |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

### student_benefits (pivot — many-to-many)

| Column | Type | Notes |
|--------|------|-------|
| `student_id` | `bigint` FK → `students` RESTRICT | Composite PK |
| `benefit_id` | `bigint` FK → `institutional_benefits` RESTRICT | Composite PK |
| `is_active` | `boolean` NOT NULL DEFAULT true | |
| `since` | `date` | Nullable |
| `until` | `date` | Nullable. CHECK ≥ since |

**Primary key:** `(student_id, benefit_id)`

---

### health_profiles (1:1 with users)

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint` PK | |
| `user_id` | `bigint` FK → `users` RESTRICT UNIQUE | 1:1, any role |
| `blood_type_id` | `bigint` FK → `blood_types` RESTRICT | Nullable |
| `weight_kg` | `numeric(5,2)` | Nullable |
| `height_cm` | `numeric(5,2)` | Nullable |
| `has_disability` | `boolean` | Nullable |
| `disability_type_id` | `bigint` FK → `disability_types` RESTRICT | Nullable. Conditional |
| `disability_description` | `text` | Nullable |
| `has_special_needs` | `boolean` | Nullable |
| `special_needs_description` | `text` | Nullable |
| `chronic_condition` | `text` | Nullable |
| `regular_medication` | `text` | Nullable |
| `allergies` | `text` | Nullable |
| `has_medical_insurance` | `boolean` | Nullable |
| `insurance_type_id` | `bigint` FK → `insurance_types` RESTRICT | Nullable. Conditional |
| `emergency_contact_name` | `varchar(150)` | Nullable |
| `emergency_contact_phone` | `varchar(20)` | Nullable |
| `emergency_contact_relation` | `varchar(50)` | Nullable |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

### housing_profiles (1:1 with students)

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint` PK | |
| `student_id` | `bigint` FK → `students` RESTRICT UNIQUE | 1:1 |
| `housing_type_id` | `bigint` FK → `housing_types` RESTRICT | Nullable |
| `tenure_type_id` | `bigint` FK → `tenure_types` RESTRICT | Nullable |
| `construction_material_id` | `bigint` FK → `construction_materials` RESTRICT | Nullable |
| `room_count` | `smallint` | Nullable |
| `bathroom_count` | `smallint` | Nullable |
| `household_members` | `smallint` | Nullable |
| `is_overcrowded` | `boolean` GENERATED STORED | `(household_members::numeric / NULLIF(room_count, 0)) > 2.5` |
| `commute_time_id` | `bigint` FK → `commute_times` RESTRICT | Nullable |
| `transport_type_id` | `bigint` FK → `transport_types` RESTRICT | Nullable |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

### housing_services (pivot)

| Column | Type | Notes |
|--------|------|-------|
| `housing_profile_id` | `bigint` FK → `housing_profiles` RESTRICT | Composite PK |
| `basic_service_id` | `bigint` FK → `basic_services` RESTRICT | Composite PK |
| `is_available` | `boolean` NOT NULL DEFAULT true | |

**Primary key:** `(housing_profile_id, basic_service_id)`

---

### user_consents (multi-record per user)

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint` PK | |
| `user_id` | `bigint` FK → `users` RESTRICT | Multiple records allowed (history) |
| `policy_version` | `varchar(20)` NOT NULL | e.g., 'v1.0', 'v2.1' |
| `accepts_data_processing` | `boolean` NOT NULL | Mandatory before sensitive data |
| `accepts_image_use` | `boolean` NOT NULL | |
| `accepts_whatsapp_contact` | `boolean` NOT NULL | |
| `accepts_email_contact` | `boolean` NOT NULL | |
| `ip_address` | `varchar(45)` | Nullable. IPv4 or IPv6 |
| `user_agent` | `text` | Nullable |
| `granted_at` | `timestamp` NOT NULL | |
| `revoked_at` | `timestamp` | Nullable. Null = active |

**Active consent query:**
```sql
SELECT * FROM user_consents
WHERE user_id = ? AND revoked_at IS NULL
ORDER BY granted_at DESC
LIMIT 1;
```

---

## Models

### SocioeconomicProfile

- 1:1 with `Student` via `student_id`
- Relations: `student()`, `incomeRange()`, `incomeSource()`, `remittanceCountry()` (→ Country), `employmentType()`, `recordedBy()` (→ User)

### StudentBenefit (pivot model)

- Composite PK, no `id`
- Relations: `student()`, `benefit()` (→ InstitutionalBenefit)

### HealthProfile

- 1:1 with `User` via `user_id`
- Relations: `user()`, `bloodType()`, `disabilityType()`, `insuranceType()`

### HousingProfile

- 1:1 with `Student` via `student_id`
- `is_overcrowded` is a DB-generated column: mark `$appends = []` — do not create an accessor, the GENERATED column is read directly
- Relations: `student()`, `housingType()`, `tenureType()`, `constructionMaterial()`, `commuteTime()`, `transportType()`, `services(): BelongsToMany(BasicService, 'housing_services')` with pivot `is_available`

### UserConsent

- Many-per-user
- Relations: `user()`
- Scope `active()`: `whereNull('revoked_at')`
- Static helper: `UserConsent::hasActiveConsentFor(User): bool`

### User / Student (updated)

- `User`: add `healthProfile(): HasOne(HealthProfile)`, `consents(): HasMany(UserConsent)`
- `Student`: add `socioeconomicProfile(): HasOne(SocioeconomicProfile)`, `housingProfile(): HasOne(HousingProfile)`, `benefits(): BelongsToMany(InstitutionalBenefit, 'student_benefits')` with pivot `['is_active', 'since', 'until']`

---

## Consent Check Service

```php
// app/Services/ConsentService.php
class ConsentService
{
    public function hasActiveConsent(User $user): bool
    // Checks user_consents for active record with accepts_data_processing = true

    public function requireConsent(User $user): void
    // Throws ConsentRequiredException if no active consent
    // Called from Actions before writing to health_profiles, socioeconomic_profiles, housing_profiles
}
```

---

## Backend Endpoints

```
PUT  /admin/students/{student}/socioeconomic-profile
     → SocioeconomicProfilePolicy (admin + coordinator only)
     → StoreSocioeconomicProfileRequest → UpsertSocioeconomicProfileAction
     (Action calls ConsentService::requireConsent before writing)

POST /admin/students/{student}/benefits/{benefit}
DELETE /admin/students/{student}/benefits/{benefit}
     → StudentBenefitPolicy (admin + coordinator only)

PUT  /admin/users/{user}/health-profile
     → HealthProfilePolicy (admin only)
     → StoreHealthProfileRequest → UpsertHealthProfileAction
     (Action calls ConsentService::requireConsent before writing)

PUT  /admin/students/{student}/housing-profile
     → HousingProfilePolicy (admin + coordinator)
     → StoreHousingProfileRequest → UpsertHousingProfileAction
     (Action calls ConsentService::requireConsent before writing)

PATCH /admin/students/{student}/housing-profile/services
     → sync basic_services for housing profile

POST  /users/{user}/consents
     → StoreUserConsentRequest (any authenticated user for own record)
     → CreateUserConsentAction (records IP, user_agent, granted_at)

PATCH /users/{user}/consents/{consent}/revoke
     → UserConsentPolicy (only self or admin can revoke)
     → RevokeUserConsentAction (sets revoked_at = now())
```
