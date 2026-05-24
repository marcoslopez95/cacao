# Requirements — User Profiles

**Feature:** `01-user-profiles`
**Depends on:** `00-catalogs`

---

## Objective

Extend the `users` table with personal identification and contact data. Add normalized geographic addresses (`user_addresses`) and document attachments (`user_documents`). Update Fortify's registration flow to accept `first_name` and `last_name` instead of a single `name` field.

---

## Actors

| Actor | Description |
|-------|-------------|
| `admin` | Creates and manages user accounts. Sees all profile data. |
| `user` | Owns their own profile. Can update personal data and upload documents. |
| `admin/coordinator` | Can verify uploaded documents. |

---

## Business Rules

### User identification

- WHEN a user is created, THE SYSTEM SHALL require `first_name`, `last_name`, `email`, and `password`.
- THE SYSTEM SHALL maintain a `name` column (GENERATED STORED in PostgreSQL) as `first_name || ' ' || last_name` for backward compatibility with Fortify, Spatie, and any existing query using `users.name`.
- WHEN a `document_type_id` and `document_number` are both provided, THE SYSTEM SHALL enforce uniqueness of `(document_type_id, document_number)` with a partial index that excludes soft-deleted users.
- WHEN a user is deleted, THE SYSTEM SHALL use soft delete (`deleted_at`) — no hard deletes on users.
- THE SYSTEM SHALL auto-generate a `uuid` (v4) for every new user at creation time.

### Addresses

- WHEN a user has multiple addresses, THE SYSTEM SHALL allow exactly one `is_primary = true` address per user — enforced at the application layer (Policy + Action).
- WHEN `state_id` is provided in an address, THE SYSTEM SHALL validate that the state belongs to the given `country_id`.
- WHEN `municipality_id` is provided, THE SYSTEM SHALL validate it belongs to the given `state_id`.
- WHEN `parish_id` is provided, THE SYSTEM SHALL validate it belongs to the given `municipality_id`.

### Document uploads

- WHEN a user uploads a document, THE SYSTEM SHALL accept only: `image/jpeg`, `image/png`, `application/pdf`.
- WHEN a document is uploaded, `is_verified` SHALL default to `false`.
- WHEN an admin or coordinator verifies a document, THE SYSTEM SHALL record `verified_by` (user id) and `verified_at`.
- WHEN a user attempts to verify their own document, THE SYSTEM SHALL reject with HTTP 403.

---

## Technical Constraints

- `name` GENERATED column: PostgreSQL `GENERATED ALWAYS AS (first_name || ' ' || last_name) STORED`. Fortify and Spatie continue reading `users.name` with zero changes.
- `first_name` and `last_name` added as nullable in the migration (to not break existing records), then a data migration populates them by splitting the existing `name` on the first space.
- `uuid` auto-filled via `Str::uuid()` in the `User` model's `boot()` or a `creating` event.
- FKs in `users`: `document_type_id` → `document_types`, `gender_id` → `genders`, `nationality_id` → `countries` — all RESTRICT.
- FKs in `user_addresses`: `country_id`, `state_id`, `municipality_id`, `parish_id`, `geographic_zone_id` — all RESTRICT.
- FKs in `user_documents`: `user_id` RESTRICT, `attachment_type_id` RESTRICT, `verified_by` RESTRICT nullable.
- Follow the `FormRequest → Controller → Wrapper → Action → Resource` pipeline.
