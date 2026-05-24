# Requirements — Catalogs

**Feature:** `00-catalogs`
**Prerequisite of:** user-profiles, role-profiles, student-background, socioeconomic-health

---

## Objective

Create all lookup/catalog tables that the rest of the CACAO data dictionary depends on: Venezuelan geographic hierarchy and the ~36 system catalog tables. Provide a shared base model with caching so other modules can use catalogs without N+1 queries.

---

## Scope

### Geographic Hierarchy (data-heavy, seeded from real Venezuelan data)

| Table | Rows (approx.) | Description |
|-------|----------------|-------------|
| `countries` | ~250 | ISO countries. Venezuela seeded first. |
| `states` | 24 | Venezuelan federal states |
| `municipalities` | 335 | Venezuelan municipalities |
| `parishes` | 1,137 | Venezuelan parishes |

### System Catalog Tables (all share base structure)

| Table | Description |
|-------|-------------|
| `genders` | Male, female, non-binary, prefer not to say |
| `document_types` | V (Venezuelan), E (foreign), P (passport), J (corporate) |
| `geographic_zones` | urban, periurban, rural |
| `attachment_document_types` | id card, birth certificate, title, transcript, etc. |
| `education_levels` | preescolar → posgrado (7 levels, used for students AND parents) |
| `academic_statuses` | active, withdrawn, graduated, suspended, exchange |
| `study_modalities` | in-person, online, hybrid |
| `academic_shifts` | morning, afternoon, night |
| `admission_types` | regular, transfer, equivalence (university only) |
| `school_grades` | 1st–6th primary, 1st–5th secondary |
| `contract_types` | permanent, contracted, hourly, honoraria |
| `dedication_types` | full-time, half-time, per-subject |
| `employment_statuses` | active, on leave, retired, suspended |
| `kinship_types` | father, mother, legal guardian, grandparent, uncle, other |
| `marital_statuses` | single, married, divorced, widowed, civil union |
| `institution_types` | public, private, Fe y Alegría, other |
| `transfer_reasons` | relocation, economic, academic performance, other |
| `digital_levels` | none, basic, intermediate, advanced |
| `language_levels` | A1, A2, B1, B2, C1, C2, native |
| `languages` | Spanish, English, French, Portuguese, German, etc. |
| `living_arrangements` | both parents, mother only, father only, relative, independent, student residence |
| `household_head_types` | father, mother, student, other relative |
| `religions` | optional/sensitive — used only for institutional statistics |
| `income_ranges` | USD-referenced brackets (< $50, $50–$150, … , > $500) |
| `income_sources` | formal employment, informal, remittances, business, pension |
| `employment_types` | formal, informal, freelance, unpaid family business |
| `institutional_benefits` | cafeteria, transport, supplies, partial scholarship, other |
| `housing_types` | house, apartment, rented room, rancho, quinta, other |
| `tenure_types` | owned, rented, borrowed, mortgaged |
| `construction_materials` | reinforced concrete, wood, zinc, mixed, other |
| `basic_services` | potable water, electricity, gas, internet, sewer, garbage, landline |
| `commute_times` | < 15 min, 15–30 min, 30–60 min, > 1 hour |
| `transport_types` | own vehicle, public, on foot, motorcycle, other |
| `disability_types` | visual, hearing, motor, cognitive, speech, multiple, other |
| `insurance_types` | IVSS, private, HCM, none |
| `blood_types` | A+, A−, B+, B−, AB+, AB−, O+, O− |

---

## Business Rules

- WHEN a catalog record is requested, THE SYSTEM SHALL serve it from the application cache (TTL: 1 hour) to avoid repeated DB queries.
- WHEN a catalog record's `active` field is false, THE SYSTEM SHALL exclude it from selection dropdowns but MUST preserve it for existing FK references.
- WHEN an admin marks a catalog record inactive, THE SYSTEM SHALL NOT cascade-delete related records — FKs are RESTRICT.
- WHEN geographic data is seeded, THE SYSTEM SHALL seed Venezuela first, then remaining countries, so Venezuelan geography is always available.
- WHEN `document_types` are seeded, THE SYSTEM SHALL include exactly: V, E, P, J — these match the Venezuelan legal framework and LOPD requirements.

---

## Technical Constraints

- All FK references to catalog tables: `RESTRICT` on delete (no cascades).
- System catalogs share a base structure: `id`, `code` (unique slug), `name`, `description`, `active`, `sort_order`, `created_at`.
- Geographic tables have a simpler structure without `sort_order` or `description`.
- No `updated_at` on catalog tables — they are append-only from the application's perspective.
- A shared `Catalog` abstract Eloquent model provides the `code`/`name`/`active`/`sortOrder` scope and a static `cached()` method.
- Seeders use `firstOrCreate` to be idempotent — safe to re-run.
