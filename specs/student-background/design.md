# Design — Student Background

**Feature:** `03-student-background`

---

## Table Schemas

### student_backgrounds (1:1 with students)

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint` PK | |
| `student_id` | `bigint` FK → `students` RESTRICT UNIQUE | 1:1 |
| `previous_institution` | `varchar(200)` | Nullable |
| `institution_type_id` | `bigint` FK → `institution_types` RESTRICT | Nullable |
| `graduation_year` | `smallint` | Nullable. 4-digit year (e.g. 2022) |
| `previous_gpa` | `numeric(4,2)` | Nullable. CHECK 0–20 |
| `repeated_grade` | `boolean` | Nullable |
| `repeated_grade_description` | `varchar(100)` | Nullable. Conditional on repeated_grade |
| `transfer_reason_id` | `bigint` FK → `transfer_reasons` RESTRICT | Nullable |
| `has_prior_studies` | `boolean` | Nullable. University only |
| `prior_studies_description` | `text` | Nullable |
| `digital_level_id` | `bigint` FK → `digital_levels` RESTRICT | Nullable |
| `mother_education_level_id` | `bigint` FK → `education_levels` RESTRICT | Nullable. Primary/secondary only |
| `father_education_level_id` | `bigint` FK → `education_levels` RESTRICT | Nullable. Primary/secondary only |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

### student_languages (pivot, many-to-many)

| Column | Type | Notes |
|--------|------|-------|
| `student_id` | `bigint` FK → `students` RESTRICT | Composite PK |
| `language_id` | `bigint` FK → `languages` RESTRICT | Composite PK |
| `language_level_id` | `bigint` FK → `language_levels` RESTRICT | NOT NULL |
| `is_mother_tongue` | `boolean` NOT NULL DEFAULT false | |

**Primary key:** `(student_id, language_id)`

---

### family_profiles (1:1 with students)

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint` PK | |
| `student_id` | `bigint` FK → `students` RESTRICT UNIQUE | 1:1 |
| `guardian_marital_status_id` | `bigint` FK → `marital_statuses` RESTRICT | Nullable. Status of primary guardian |
| `children_count` | `smallint` | Nullable. Total children of primary guardian |
| `sibling_position` | `smallint` | Nullable. Student's position (e.g., 2 of 4) |
| `sibling_count` | `smallint` | Nullable. Total siblings including student |
| `living_arrangement_id` | `bigint` FK → `living_arrangements` RESTRICT | Nullable |
| `household_head_type_id` | `bigint` FK → `household_head_types` RESTRICT | Nullable |
| `household_head_name` | `varchar(150)` | Nullable. If different from registered guardian |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

### demographic_profiles (1:1 with users — any role)

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint` PK | |
| `user_id` | `bigint` FK → `users` RESTRICT UNIQUE | 1:1, any role |
| `birth_city` | `varchar(100)` | Nullable |
| `birth_state_id` | `bigint` FK → `states` RESTRICT | Nullable |
| `birth_country_id` | `bigint` FK → `countries` RESTRICT | Nullable |
| `is_indigenous` | `boolean` | Nullable |
| `indigenous_community` | `varchar(100)` | Nullable. Conditional |
| `native_language_id` | `bigint` FK → `languages` RESTRICT | Nullable |
| `is_returned_migrant` | `boolean` | Nullable |
| `previous_country_id` | `bigint` FK → `countries` RESTRICT | Nullable. Conditional |
| `religion_id` | `bigint` FK → `religions` RESTRICT | Nullable. Sensitive — restricted access |
| `practices_sport` | `boolean` | Nullable |
| `sport` | `varchar(100)` | Nullable. Conditional |
| `cultural_activities` | `text` | Nullable |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

## Models

### StudentBackground

- 1:1 with `Student` via `student_id`
- Relations: `student()`, `institutionType()`, `transferReason()`, `digitalLevel()`, `motherEducationLevel()` (→ EducationLevel), `fatherEducationLevel()` (→ EducationLevel)
- Cast: `previous_gpa` → `decimal:2`, `graduation_year` → integer, `repeated_grade` / `has_prior_studies` → boolean

### StudentLanguage (pivot model)

- `$primaryKey = null` (composite PK — use `incrementing = false`)
- Relations: `student()`, `language()`, `languageLevel()`

### FamilyProfile

- 1:1 with `Student` via `student_id`
- Relations: `student()`, `guardianMaritalStatus()` (→ MaritalStatus), `livingArrangement()`, `householdHeadType()`

### DemographicProfile

- 1:1 with `User` via `user_id`
- Relations: `user()`, `birthState()` (→ State), `birthCountry()` (→ Country), `nativeLanguage()` (→ Language), `previousCountry()` (→ Country), `religion()` (→ Religion)

### Student (updated)

Add relations:
- `background(): HasOne(StudentBackground)`
- `languages(): BelongsToMany(Language, 'student_languages')` with `withPivot(['language_level_id', 'is_mother_tongue'])`
- `familyProfile(): HasOne(FamilyProfile)`

### User (updated)

Add relation:
- `demographicProfile(): HasOne(DemographicProfile)`

---

## Backend Endpoints

```
PUT  /admin/students/{student}/background
     → StoreStudentBackgroundRequest → StudentBackgroundController → UpsertStudentBackgroundAction

POST /admin/students/{student}/languages
     → StoreStudentLanguageRequest  → StudentLanguageController → AttachStudentLanguageAction
     (enforces single mother_tongue, prevents duplicate)

DELETE /admin/students/{student}/languages/{language}
     → StudentLanguageController::destroy()

PUT  /admin/students/{student}/family-profile
     → StoreFamilyProfileRequest → FamilyProfileController → UpsertFamilyProfileAction

PUT  /admin/users/{user}/demographic-profile
     → StoreDemographicProfileRequest → DemographicProfileController → UpsertDemographicProfileAction
     (religion_id excluded from response if requester is student/guardian)
```

---

## Resource: religion_id visibility

`DemographicProfileResource` checks `$request->user()->hasRole(['Admin', 'Coordinador de Area'])` to decide whether to include `religion_id` in the response. Students and guardians never see the religion field.
