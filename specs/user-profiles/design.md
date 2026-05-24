# Design — User Profiles

**Feature:** `01-user-profiles`
**Depends on:** `00-catalogs`

---

## Table Schemas

### users — new columns added via migration

| Column | Type | Notes |
|--------|------|-------|
| `first_name` | `varchar(100)` | Required. Nullable in migration for compat. |
| `last_name` | `varchar(100)` | Required. Nullable in migration for compat. |
| `name` | `varchar(255)` GENERATED STORED | `first_name \|\| ' ' \|\| last_name` — replaces existing `name` column |
| `uuid` | `uuid` | NOT NULL UNIQUE. Auto-generated on create. |
| `document_type_id` | `bigint` FK → `document_types` RESTRICT | Nullable. V/E/P/J |
| `document_number` | `varchar(20)` | Nullable. Unique per `document_type_id` (partial index). |
| `birth_date` | `date` | Nullable. |
| `gender_id` | `bigint` FK → `genders` RESTRICT | Nullable. |
| `nationality_id` | `bigint` FK → `countries` RESTRICT | Nullable. |
| `phone_primary` | `varchar(20)` | Nullable. Recommended format: E.164 (+58...) |
| `phone_secondary` | `varchar(20)` | Nullable. |
| `profile_photo_url` | `varchar(500)` | Nullable. Relative storage path. |
| `deleted_at` | `timestamp` | SoftDeletes. |

**New unique index:**
```sql
CREATE UNIQUE INDEX users_document_unique
  ON users (document_type_id, document_number)
  WHERE deleted_at IS NULL;
```

**GENERATED column migration note:**
The migration first adds `first_name`/`last_name` as nullable `varchar`, runs a data migration splitting `name` (first word → `first_name`, remainder → `last_name`), then replaces the original `name` `varchar` column with a GENERATED STORED column.

### user_addresses

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint` PK | |
| `user_id` | `bigint` FK → `users` RESTRICT | |
| `country_id` | `bigint` FK → `countries` RESTRICT | |
| `state_id` | `bigint` FK → `states` RESTRICT | Nullable |
| `municipality_id` | `bigint` FK → `municipalities` RESTRICT | Nullable |
| `parish_id` | `bigint` FK → `parishes` RESTRICT | Nullable |
| `geographic_zone_id` | `bigint` FK → `geographic_zones` RESTRICT | Nullable |
| `address_line1` | `varchar(255)` NOT NULL | Street, number, building |
| `address_line2` | `varchar(255)` | Nullable. Apt, floor, landmark |
| `is_primary` | `boolean` NOT NULL DEFAULT true | |
| `created_at` | `timestamp` | |

### user_documents

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint` PK | |
| `user_id` | `bigint` FK → `users` RESTRICT | |
| `attachment_type_id` | `bigint` FK → `attachment_document_types` RESTRICT | |
| `file_url` | `varchar(500)` NOT NULL | |
| `original_filename` | `varchar(255)` NOT NULL | |
| `mime_type` | `varchar(100)` NOT NULL | |
| `file_size_bytes` | `bigint` | Nullable |
| `is_verified` | `boolean` NOT NULL DEFAULT false | |
| `verified_by` | `bigint` FK → `users` RESTRICT | Nullable |
| `verified_at` | `timestamp` | Nullable |
| `created_at` | `timestamp` | |

---

## Models

### User (updated)

New additions:
- `SoftDeletes` trait
- `$fillable` extended with new fields
- `uuid` auto-set in `boot()` via `creating` event using `Str::uuid()`
- New relations: `documentType()` BelongsTo, `gender()` BelongsTo, `nationality()` BelongsTo (Country), `addresses()` HasMany, `documents()` HasMany
- `name` is now a GENERATED DB column — no change needed in model (still readable as `$user->name`)
- `first_name` and `last_name` added to `$fillable`

### UserAddress

- `$fillable`: all columns
- Relations: `user()` BelongsTo, `country()` BelongsTo, `state()` BelongsTo, `municipality()` BelongsTo, `parish()` BelongsTo, `geographicZone()` BelongsTo

### UserDocument

- `$fillable`: all columns except `verified_by`, `verified_at` (set by Action)
- Relations: `user()` BelongsTo, `attachmentType()` BelongsTo, `verifiedBy()` BelongsTo(User)
- Cast: `is_verified` → boolean, `verified_at` → datetime

---

## Backend Pipeline

```
PUT /admin/users/{user}/profile
  → StoreUserProfileRequest    validates first_name, last_name, document fields, phone format
  → UserController::update()   ≤ 8 lines
  → UserWrapper                getFirstName(), getLastName(), getDocumentTypeId()...
  → UpdateUserProfileAction    writes to users table
  → UserResource               returns updated user

POST /admin/users/{user}/addresses
  → StoreUserAddressRequest    validates geographic FK chain
  → UserAddressController
  → UserAddressWrapper
  → CreateUserAddressAction    enforces single is_primary per user in transaction
  → UserAddressResource

POST /admin/users/{user}/documents
  → StoreUserDocumentRequest   validates mime type, max file size
  → UserDocumentController
  → UserDocumentWrapper
  → CreateUserDocumentAction   stores file, creates record
  → UserDocumentResource

PATCH /admin/users/{user}/documents/{document}/verify
  → VerifyUserDocumentRequest  authorize: admin/coordinator only
  → UserDocumentController::verify()
  → VerifyDocumentAction       sets is_verified, verified_by, verified_at
```

---

## Fortify Update

- `app/Actions/Fortify/CreateNewUser.php`: accept `first_name` + `last_name` instead of `name`
- Registration form (`resources/js/pages/auth/Register.vue`): split name field into two inputs
- `UpdateUserProfileInformation.php`: accept `first_name` + `last_name`
