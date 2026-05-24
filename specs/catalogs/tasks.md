# Tasks — Catalogs

**Feature:** `00-catalogs`
**Prerequisite of:** user-profiles, role-profiles, student-background, socioeconomic-health

---

## Overall Progress

- [ ] Task 1 — Geographic catalogs: migrations, models, seed data files, seeders
- [ ] Task 2 — User/profile catalogs: genders, document_types, geographic_zones, attachment_document_types
- [ ] Task 3 — Academic catalogs: education_levels, academic_statuses, study_modalities, academic_shifts, admission_types, school_grades
- [ ] Task 4 — HR/staff catalogs: contract_types, dedication_types, employment_statuses
- [ ] Task 5 — Social/demographic catalogs: kinship_types, marital_statuses, institution_types, transfer_reasons, digital_levels, language_levels, languages, living_arrangements, household_head_types, religions
- [ ] Task 6 — Socioeconomic/health catalogs: income_ranges, income_sources, employment_types, institutional_benefits, housing_types, tenure_types, construction_materials, basic_services, commute_times, transport_types, disability_types, insurance_types, blood_types
- [ ] Task 7 — Base Catalog model + CatalogObserver + CatalogsSeeder orchestrator
- [ ] Task 8 — Pest feature tests

---

## Task Detail

---

### Task 1 — Geographic Catalogs

**Files:**
- `database/migrations/*_create_countries_table.php`
- `database/migrations/*_create_states_table.php`
- `database/migrations/*_create_municipalities_table.php`
- `database/migrations/*_create_parishes_table.php`
- `app/Models/Country.php`
- `app/Models/State.php`
- `app/Models/Municipality.php`
- `app/Models/Parish.php`
- `database/seeders/data/venezuela/states.json`
- `database/seeders/data/venezuela/municipalities.json`
- `database/seeders/data/venezuela/parishes.json`
- `database/seeders/data/countries.json`
- `database/seeders/Catalogs/GeographicSeeder.php`

**Done criteria:**
- Tables exist with correct FK chain: parishes → municipalities → states → countries, all RESTRICT
- `Country` model has `states()` hasMany; `State` has `country()` + `municipalities()`; `Municipality` has `state()` + `parishes()`; `Parish` has `municipality()`
- `GeographicSeeder` is idempotent via `firstOrCreate`
- After seeding: 24 Venezuelan states, 335 municipalities, 1,137 parishes exist in DB
- Venezuela exists as the first country (`iso2 = 'VE'`)
- `vendor/bin/sail artisan db:seed --class=\\Database\\Seeders\\Catalogs\\GeographicSeeder` runs without error

---

### Task 2 — User/Profile Catalogs

**Tables:** `genders`, `document_types`, `geographic_zones`, `attachment_document_types`

**Files:**
- `database/migrations/*_create_user_profile_catalogs_table.php` (one migration, all 4 tables)
- `app/Models/Catalogs/Gender.php`
- `app/Models/Catalogs/DocumentType.php`
- `app/Models/Catalogs/GeographicZone.php`
- `app/Models/Catalogs/AttachmentDocumentType.php`

**Seed values:**
- `genders`: male, female, non_binary, prefer_not_to_say
- `document_types`: V (Venezolano), E (Extranjero), P (Pasaporte), J (Jurídico)
- `geographic_zones`: urban, periurban, rural
- `attachment_document_types`: id_card, birth_certificate, academic_title, transcript, passport, other

**Done criteria:**
- All 4 tables exist and are seeded with values above
- All models extend `Catalog` and have `cached()` working
- Pint clean

---

### Task 3 — Academic Catalogs

**Tables:** `education_levels`, `academic_statuses`, `study_modalities`, `academic_shifts`, `admission_types`, `school_grades`

**Files:**
- `database/migrations/*_create_academic_catalogs_table.php`
- `app/Models/Catalogs/EducationLevel.php`
- `app/Models/Catalogs/AcademicStatus.php`
- `app/Models/Catalogs/StudyModality.php`
- `app/Models/Catalogs/AcademicShift.php`
- `app/Models/Catalogs/AdmissionType.php`
- `app/Models/Catalogs/SchoolGrade.php`

**Seed values:**
- `education_levels`: preschool, primary, secondary, technical_tsu, undergraduate, postgraduate
- `academic_statuses`: active, withdrawn, graduated, suspended, exchange, graduated_no_title
- `study_modalities`: in_person, online, hybrid
- `academic_shifts`: morning, afternoon, night
- `admission_types`: regular, transfer, equivalence
- `school_grades`: 1st–6th primary (primary_1 … primary_6), 1st–5th secondary (secondary_1 … secondary_5)

**Done criteria:**
- All 6 tables seeded correctly
- `school_grades` has `sort_order` reflecting grade order
- All models extend `Catalog`
- Pint clean

---

### Task 4 — HR/Staff Catalogs

**Tables:** `contract_types`, `dedication_types`, `employment_statuses`

**Files:**
- `database/migrations/*_create_staff_catalogs_table.php`
- `app/Models/Catalogs/ContractType.php`
- `app/Models/Catalogs/DedicationType.php`
- `app/Models/Catalogs/EmploymentStatus.php`

**Seed values:**
- `contract_types`: permanent, contracted, hourly, honoraria
- `dedication_types`: full_time, half_time, per_subject
- `employment_statuses`: active, on_leave, retired, suspended

**Done criteria:**
- All 3 tables seeded
- All models extend `Catalog`
- Pint clean

---

### Task 5 — Social/Demographic Catalogs

**Tables:** `kinship_types`, `marital_statuses`, `institution_types`, `transfer_reasons`, `digital_levels`, `language_levels`, `languages`, `living_arrangements`, `household_head_types`, `religions`

**Files:**
- `database/migrations/*_create_social_catalogs_table.php`
- `app/Models/Catalogs/KinshipType.php`
- `app/Models/Catalogs/MaritalStatus.php`
- `app/Models/Catalogs/InstitutionType.php`
- `app/Models/Catalogs/TransferReason.php`
- `app/Models/Catalogs/DigitalLevel.php`
- `app/Models/Catalogs/LanguageLevel.php`
- `app/Models/Catalogs/Language.php`
- `app/Models/Catalogs/LivingArrangement.php`
- `app/Models/Catalogs/HouseholdHeadType.php`
- `app/Models/Catalogs/Religion.php`

**Seed values:**
- `kinship_types`: father, mother, legal_guardian, grandparent, uncle, sibling, other
- `marital_statuses`: single, married, divorced, widowed, civil_union
- `institution_types`: public, private, fe_y_alegria, other
- `transfer_reasons`: relocation, economic, academic_performance, other
- `digital_levels`: none, basic, intermediate, advanced
- `language_levels`: a1, a2, b1, b2, c1, c2, native
- `languages`: Spanish (es), English (en), French (fr), Portuguese (pt), German (de), Italian (it), Chinese (zh), Arabic (ar), Other (other)
- `living_arrangements`: both_parents, mother_only, father_only, relative, independent, student_residence
- `household_head_types`: father, mother, student, other_relative
- `religions`: catholic, evangelical, protestant, jewish, muslim, agnostic, atheist, other (mark as optional/sensitive in description)

**Done criteria:**
- All 10 tables seeded
- All models extend `Catalog`
- Pint clean

---

### Task 6 — Socioeconomic/Health Catalogs

**Tables:** `income_ranges`, `income_sources`, `employment_types`, `institutional_benefits`, `housing_types`, `tenure_types`, `construction_materials`, `basic_services`, `commute_times`, `transport_types`, `disability_types`, `insurance_types`, `blood_types`

**Files:**
- `database/migrations/*_create_socioeconomic_catalogs_table.php`
- `database/migrations/*_create_health_catalogs_table.php`
- `app/Models/Catalogs/IncomeRange.php`
- `app/Models/Catalogs/IncomeSource.php`
- `app/Models/Catalogs/EmploymentType.php`
- `app/Models/Catalogs/InstitutionalBenefit.php`
- `app/Models/Catalogs/HousingType.php`
- `app/Models/Catalogs/TenureType.php`
- `app/Models/Catalogs/ConstructionMaterial.php`
- `app/Models/Catalogs/BasicService.php`
- `app/Models/Catalogs/CommuteTime.php`
- `app/Models/Catalogs/TransportType.php`
- `app/Models/Catalogs/DisabilityType.php`
- `app/Models/Catalogs/InsuranceType.php`
- `app/Models/Catalogs/BloodType.php`

**Seed values:**
- `income_ranges`: under_50_usd, 50_to_150_usd, 150_to_300_usd, 300_to_500_usd, over_500_usd
- `income_sources`: formal_employment, informal_employment, own_business, remittances, pension, other
- `employment_types` (student work): formal, informal, freelance, family_business
- `institutional_benefits`: cafeteria, transport, supplies, partial_scholarship, full_scholarship, other
- `housing_types`: house, apartment, rented_room, rancho, quinta, other
- `tenure_types`: owned, rented, borrowed, mortgaged
- `construction_materials`: reinforced_concrete, wood, zinc, mixed, other
- `basic_services`: potable_water, electricity, gas, internet, sewer, garbage_collection, landline
- `commute_times`: under_15min, 15_to_30min, 30_to_60min, over_1hour
- `transport_types`: own_vehicle, public_transport, on_foot, motorcycle, other
- `disability_types`: visual, hearing, motor, cognitive, speech, multiple, other
- `insurance_types`: ivss, private_hcm, private_other, none
- `blood_types`: a_pos, a_neg, b_pos, b_neg, ab_pos, ab_neg, o_pos, o_neg

**Done criteria:**
- All 13 tables seeded with values above
- All models extend `Catalog`
- Pint clean

---

### Task 7 — Base Catalog Model + Observer + Orchestrator Seeder

**Files:**
- `app/Models/Catalog.php`
- `app/Observers/CatalogObserver.php`
- `app/Providers/AppServiceProvider.php` (register observer for each Catalog subclass)
- `database/seeders/CatalogsSeeder.php` (orchestrates Tasks 1–6 seeders)

**Done criteria:**
- `Catalog` abstract model has: `scopeActive()`, `scopeOrdered()`, `static cached(): Collection`
- `cached()` uses `Cache::remember("catalog.{table}", 3600, ...)`
- `CatalogObserver` invalidates the correct cache key on `saved` and `deleted`
- All 36 catalog model classes are registered with the observer in `AppServiceProvider`
- `DatabaseSeeder` calls `CatalogsSeeder` before all other seeders
- `vendor/bin/sail artisan db:seed --class=CatalogsSeeder` runs and seeds all tables
- Pint clean: `vendor/bin/sail bin pint --dirty --format agent`

---

### Task 8 — Pest Feature Tests

**Files:**
- `tests/Feature/Catalogs/CatalogCachingTest.php`
- `tests/Feature/Catalogs/GeographicSeederTest.php`

**Done criteria:**
- `CatalogCachingTest`: `cached()` returns collection keyed by code; saving a record clears cache; deleted record clears cache
- `GeographicSeederTest`: after seeding, Venezuela exists; 24 states exist; at least 1 municipality per state
- `vendor/bin/sail artisan test --compact --filter=Catalog` — all tests pass
- Full suite: `vendor/bin/sail artisan test --compact` — no regressions
