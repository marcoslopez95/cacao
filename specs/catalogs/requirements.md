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
| `genders` | Masculino, Femenino, No binario, Prefiero no indicar |
| `document_types` | V (Venezolano), E (Extranjero), P (Pasaporte), J (Jurídico) |
| `geographic_zones` | Urbano, Periurbano, Rural |
| `attachment_document_types` | Cédula de identidad, Acta de nacimiento, Título académico, Notas certificadas, etc. |
| `education_levels` | Preescolar → Posgrado (6 niveles, usado para estudiantes Y representantes) |
| `academic_statuses` | Activo, Retirado, Egresado, Suspendido, Intercambio |
| `study_modalities` | Presencial, En línea, Híbrida |
| `academic_shifts` | Matutino, Vespertino, Nocturno |
| `admission_types` | Regular, Traslado, Equivalencia (solo universitario) |
| `school_grades` | 1er–6to grado (primaria), 1er–5to año (secundaria) |
| `contract_types` | Fijo, Contratado, Por horas, Honorarios profesionales |
| `dedication_types` | Dedicación exclusiva, Medio tiempo, Por asignatura |
| `employment_statuses` | Activo, En licencia, Jubilado, Suspendido |
| `kinship_types` | Padre, Madre, Tutor legal, Abuelo/a, Tío/a, Hermano/a, Otro |
| `marital_statuses` | Soltero/a, Casado/a, Divorciado/a, Viudo/a, Unión libre |
| `institution_types` | Pública, Privada, Fe y Alegría, Otra |
| `transfer_reasons` | Cambio de residencia, Razones económicas, Rendimiento académico, Otro |
| `digital_levels` | Sin conocimiento, Básico, Intermedio, Avanzado |
| `language_levels` | A1, A2, B1, B2, C1, C2, Nativo/a |
| `languages` | Español, Inglés, Francés, Portugués, Alemán, Italiano, Chino, Árabe, Otro |
| `living_arrangements` | Ambos padres, Solo con la madre, Solo con el padre, Con un familiar, Independiente, Residencia estudiantil |
| `household_head_types` | Padre, Madre, El/la estudiante, Otro familiar |
| `religions` | opcional/sensible — solo para estadísticas institucionales |
| `income_ranges` | Menos de $50, $50–$150, $150–$300, $300–$500, Más de $500 |
| `income_sources` | Empleo formal, Empleo informal, Negocio propio, Remesas, Pensión o jubilación, Otro |
| `employment_types` | Formal, Informal, Independiente/freelance, Negocio familiar |
| `institutional_benefits` | Comedor, Transporte, Útiles y materiales, Beca parcial, Beca completa, Otro |
| `housing_types` | Casa, Apartamento, Habitación alquilada, Rancho, Quinta, Otro |
| `tenure_types` | Propia, Alquilada, Cedida/prestada, Hipotecada |
| `construction_materials` | Concreto/bloque, Madera, Zinc, Mixto, Otro |
| `basic_services` | Agua potable, Electricidad, Gas, Internet, Cloacas, Recolección de basura, Teléfono fijo |
| `commute_times` | Menos de 15 min, 15–30 min, 30–60 min, Más de 1 hora |
| `transport_types` | Vehículo propio, Transporte público, A pie, Moto, Otro |
| `disability_types` | Visual, Auditiva, Motora, Cognitiva, Del habla, Múltiple, Otra |
| `insurance_types` | IVSS, Póliza HCM privada, Seguro privado (otro), Sin seguro |
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
