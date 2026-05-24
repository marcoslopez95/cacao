# Design — Catalogs

**Feature:** `00-catalogs`

---

## Architecture

All catalog tables are read-heavy and write-never (from the app's perspective — only admin can modify). The design uses:

- **One abstract base model** (`app/Models/Catalog.php`) that all system catalog models extend.
- **Static `cached()` method** on base model — returns a keyed collection by `code`, cached for 1 hour.
- **Geographic models** (`Country`, `State`, `Municipality`, `Parish`) extend Laravel's base Model directly (different structure from system catalogs).
- **Seeders** under `database/seeders/Catalogs/` — all idempotent via `firstOrCreate`.

```
app/Models/
  Catalog.php          ← abstract base for all system catalogs
  Catalogs/
    Gender.php
    DocumentType.php
    GeographicZone.php
    AttachmentDocumentType.php
    EducationLevel.php
    AcademicStatus.php
    StudyModality.php
    AcademicShift.php
    AdmissionType.php
    SchoolGrade.php
    ContractType.php
    DedicationType.php
    EmploymentStatus.php
    KinshipType.php
    MaritalStatus.php
    InstitutionType.php
    TransferReason.php
    DigitalLevel.php
    LanguageLevel.php
    Language.php
    LivingArrangement.php
    HouseholdHeadType.php
    Religion.php
    IncomeRange.php
    IncomeSource.php
    EmploymentType.php
    InstitutionalBenefit.php
    HousingType.php
    TenureType.php
    ConstructionMaterial.php
    BasicService.php
    CommuteTime.php
    TransportType.php
    DisabilityType.php
    InsuranceType.php
    BloodType.php
  Country.php
  State.php
  Municipality.php
  Parish.php
```

---

## Base Catalog Model

```php
// app/Models/Catalog.php
abstract class Catalog extends Model
{
    public $timestamps = false;      // only created_at, no updated_at
    protected $dates = ['created_at'];

    public function scopeActive($query) { return $query->where('active', true); }
    public function scopeOrdered($query) { return $query->orderBy('sort_order')->orderBy('name'); }

    public static function cached(): Collection  // keyed by 'code'
    // 1-hour cache, invalidated when any record changes (Observer)
}
```

---

## Table Schemas

### System catalogs (shared structure)

```
id          bigint PK generated always as identity
code        varchar(50) NOT NULL UNIQUE
name        varchar(150) NOT NULL
description text nullable
active      boolean NOT NULL DEFAULT true
sort_order  smallint DEFAULT 0
created_at  timestamp NOT NULL DEFAULT now()
```

### countries

```
id      bigint PK
iso2    varchar(2) NOT NULL UNIQUE   -- 'VE', 'US', 'CO'
iso3    varchar(3) NOT NULL UNIQUE   -- 'VEN', 'USA', 'COL'
name    varchar(150) NOT NULL
active  boolean NOT NULL DEFAULT true
```

### states

```
id          bigint PK
country_id  bigint FK → countries RESTRICT
code        varchar(10) NOT NULL
name        varchar(150) NOT NULL
active      boolean NOT NULL DEFAULT true
UNIQUE (country_id, code)
```

### municipalities

```
id        bigint PK
state_id  bigint FK → states RESTRICT
code      varchar(10) NOT NULL
name      varchar(150) NOT NULL
active    boolean NOT NULL DEFAULT true
UNIQUE (state_id, code)
```

### parishes

```
id               bigint PK
municipality_id  bigint FK → municipalities RESTRICT
code             varchar(10) NOT NULL
name             varchar(150) NOT NULL
active           boolean NOT NULL DEFAULT true
UNIQUE (municipality_id, code)
```

---

## Seed Data Sources

- `countries`: ITU/ISO 3166 country list. Venezuela (`VE`, `VEN`) seeded first.
- `states` (24): INE Venezuela — Amazonas, Anzoátegui, Apure, Aragua, Barinas, Bolívar, Carabobo, Cojedes, Delta Amacuro, Falcón, Guárico, Lara, Mérida, Miranda, Monagas, Nueva Esparta, Portuguesa, Sucre, Táchira, Trujillo, Vargas, Yaracuy, Zulia, Distrito Capital.
- `municipalities` (335) and `parishes` (1,137): can be loaded from the `venezuela-geografica` npm package JSON files or INE CSV exports. Seeder reads from `database/seeders/data/venezuela/` JSON files.

---

## Seeder Organization

```
database/seeders/
  Catalogs/
    GeographicSeeder.php      ← countries → states → municipalities → parishes
    SystemCatalogsSeeder.php  ← all 36 system catalog tables
  DatabaseSeeder.php          ← calls CatalogsSeeder first
  CatalogsSeeder.php          ← orchestrates GeographicSeeder + SystemCatalogsSeeder
database/seeders/data/
  venezuela/
    states.json
    municipalities.json
    parishes.json
  countries.json
```

---

## Caching Strategy

- Cache key pattern: `catalog.{table_name}` (e.g., `catalog.genders`, `catalog.blood_types`)
- TTL: 3600 seconds (1 hour)
- Invalidation: `CatalogObserver::saved()` and `CatalogObserver::deleted()` call `Cache::forget()`
- Usage: `Gender::cached()` returns `Collection` keyed by `code` — use `Gender::cached()->get('male')`
