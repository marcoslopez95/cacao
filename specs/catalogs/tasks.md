# Tasks — Catalogs

**Feature:** `00-catalogs`
**Prerequisite of:** user-profiles, role-profiles, student-background, socioeconomic-health

---

## Overall Progress

- [x] Task 1 — Geographic catalogs: migrations, models, seed data files, seeders
- [x] Task 2 — User/profile catalogs: genders, document_types, geographic_zones, attachment_document_types
- [x] Task 3 — Academic catalogs: education_levels, academic_statuses, study_modalities, academic_shifts, admission_types, school_grades
- [x] Task 4 — HR/staff catalogs: contract_types, dedication_types, employment_statuses
- [x] Task 5 — Social/demographic catalogs: kinship_types, marital_statuses, institution_types, transfer_reasons, digital_levels, language_levels, languages, living_arrangements, household_head_types, religions
- [x] Task 6 — Socioeconomic/health catalogs: income_ranges, income_sources, employment_types, institutional_benefits, housing_types, tenure_types, construction_materials, basic_services, commute_times, transport_types, disability_types, insurance_types, blood_types
- [x] Task 7 — Base Catalog model + CatalogObserver + CatalogsSeeder orchestrator
- [x] Task 8 — Pest feature tests

**Reconciliado 2026-08-30**: código verificado como implementado (45 tests en verde), tracking nunca se había sincronizado.

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

**Seed values** (`code` → `name` en español):
- `genders`: `male` → Masculino · `female` → Femenino · `non_binary` → No binario · `prefer_not_to_say` → Prefiero no indicar
- `document_types`: `V` → Venezolano · `E` → Extranjero · `P` → Pasaporte · `J` → Jurídico
- `geographic_zones`: `urban` → Urbano · `periurban` → Periurbano · `rural` → Rural
- `attachment_document_types`: `id_card` → Cédula de identidad · `birth_certificate` → Acta de nacimiento · `academic_title` → Título académico · `transcript` → Notas certificadas · `passport` → Pasaporte · `other` → Otro

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

**Seed values** (`code` → `name` en español):
- `education_levels`: `preschool` → Preescolar · `primary` → Primaria · `secondary` → Secundaria · `technical_tsu` → Técnico Superior Universitario · `undergraduate` → Pregrado · `postgraduate` → Posgrado
- `academic_statuses`: `active` → Activo · `withdrawn` → Retirado · `graduated` → Egresado · `suspended` → Suspendido · `exchange` → Intercambio · `graduated_no_title` → Egresado sin título
- `study_modalities`: `in_person` → Presencial · `online` → En línea · `hybrid` → Híbrida
- `academic_shifts`: `morning` → Matutino · `afternoon` → Vespertino · `night` → Nocturno
- `admission_types`: `regular` → Regular · `transfer` → Traslado · `equivalence` → Equivalencia
- `school_grades`: `primary_1` → 1er grado · `primary_2` → 2do grado · `primary_3` → 3er grado · `primary_4` → 4to grado · `primary_5` → 5to grado · `primary_6` → 6to grado · `secondary_1` → 1er año · `secondary_2` → 2do año · `secondary_3` → 3er año · `secondary_4` → 4to año · `secondary_5` → 5to año

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

**Seed values** (`code` → `name` en español):
- `contract_types`: `permanent` → Fijo · `contracted` → Contratado · `hourly` → Por horas · `honoraria` → Honorarios profesionales
- `dedication_types`: `full_time` → Dedicación exclusiva · `half_time` → Medio tiempo · `per_subject` → Por asignatura
- `employment_statuses`: `active` → Activo · `on_leave` → En licencia · `retired` → Jubilado · `suspended` → Suspendido

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

**Seed values** (`code` → `name` en español):
- `kinship_types`: `father` → Padre · `mother` → Madre · `legal_guardian` → Tutor legal · `grandparent` → Abuelo/a · `uncle` → Tío/a · `sibling` → Hermano/a · `other` → Otro
- `marital_statuses`: `single` → Soltero/a · `married` → Casado/a · `divorced` → Divorciado/a · `widowed` → Viudo/a · `civil_union` → Unión libre
- `institution_types`: `public` → Pública · `private` → Privada · `fe_y_alegria` → Fe y Alegría · `other` → Otra
- `transfer_reasons`: `relocation` → Cambio de residencia · `economic` → Razones económicas · `academic_performance` → Rendimiento académico · `other` → Otro
- `digital_levels`: `none` → Sin conocimiento · `basic` → Básico · `intermediate` → Intermedio · `advanced` → Avanzado
- `language_levels`: `a1` → A1 · `a2` → A2 · `b1` → B1 · `b2` → B2 · `c1` → C1 · `c2` → C2 · `native` → Nativo/a
- `languages`: `es` → Español · `en` → Inglés · `fr` → Francés · `pt` → Portugués · `de` → Alemán · `it` → Italiano · `zh` → Chino · `ar` → Árabe · `other` → Otro
- `living_arrangements`: `both_parents` → Ambos padres · `mother_only` → Solo con la madre · `father_only` → Solo con el padre · `relative` → Con un familiar · `independent` → Independiente · `student_residence` → Residencia estudiantil
- `household_head_types`: `father` → Padre · `mother` → Madre · `student` → El/la estudiante · `other_relative` → Otro familiar
- `religions`: `catholic` → Católico · `evangelical` → Evangélico · `protestant` → Protestante · `jewish` → Judío · `muslim` → Musulmán · `agnostic` → Agnóstico · `atheist` → Ateo · `other` → Otro (campo opcional/sensible — solo estadísticas institucionales)

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

**Seed values** (`code` → `name` en español):
- `income_ranges`: `under_50_usd` → Menos de $50 · `50_to_150_usd` → $50 – $150 · `150_to_300_usd` → $150 – $300 · `300_to_500_usd` → $300 – $500 · `over_500_usd` → Más de $500
- `income_sources`: `formal_employment` → Empleo formal · `informal_employment` → Empleo informal · `own_business` → Negocio propio · `remittances` → Remesas · `pension` → Pensión o jubilación · `other` → Otro
- `employment_types` (trabajo del estudiante): `formal` → Formal · `informal` → Informal · `freelance` → Independiente/freelance · `family_business` → Negocio familiar
- `institutional_benefits`: `cafeteria` → Comedor · `transport` → Transporte · `supplies` → Útiles y materiales · `partial_scholarship` → Beca parcial · `full_scholarship` → Beca completa · `other` → Otro
- `housing_types`: `house` → Casa · `apartment` → Apartamento · `rented_room` → Habitación alquilada · `rancho` → Rancho · `quinta` → Quinta · `other` → Otro
- `tenure_types`: `owned` → Propia · `rented` → Alquilada · `borrowed` → Cedida/prestada · `mortgaged` → Hipotecada
- `construction_materials`: `reinforced_concrete` → Concreto/bloque · `wood` → Madera · `zinc` → Zinc · `mixed` → Mixto · `other` → Otro
- `basic_services`: `potable_water` → Agua potable · `electricity` → Electricidad · `gas` → Gas · `internet` → Internet · `sewer` → Cloacas · `garbage_collection` → Recolección de basura · `landline` → Teléfono fijo
- `commute_times`: `under_15min` → Menos de 15 min · `15_to_30min` → 15 – 30 min · `30_to_60min` → 30 – 60 min · `over_1hour` → Más de 1 hora
- `transport_types`: `own_vehicle` → Vehículo propio · `public_transport` → Transporte público · `on_foot` → A pie · `motorcycle` → Moto · `other` → Otro
- `disability_types`: `visual` → Visual · `hearing` → Auditiva · `motor` → Motora · `cognitive` → Cognitiva · `speech` → Del habla · `multiple` → Múltiple · `other` → Otra
- `insurance_types`: `ivss` → IVSS · `private_hcm` → Póliza HCM privada · `private_other` → Seguro privado (otro) · `none` → Sin seguro
- `blood_types`: `a_pos` → A+ · `a_neg` → A− · `b_pos` → B+ · `b_neg` → B− · `ab_pos` → AB+ · `ab_neg` → AB− · `o_pos` → O+ · `o_neg` → O−

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
