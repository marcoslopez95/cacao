# Tasks — User Profiles

**Feature:** `01-user-profiles`
**Depends on:** `00-catalogs` (all tasks must complete before starting this arnés)

---

## Overall Progress

- [ ] Task 1 — Extend `users` table: new columns + GENERATED `name` + data migration
- [ ] Task 2 — Update `User` model (SoftDeletes, new fillable, uuid boot, new relations)
- [ ] Task 3 — Fortify actions + registration form update (first_name / last_name)
- [ ] Task 4 — `user_addresses` migration + model + pipeline (Request, Wrapper, Action, Resource)
- [ ] Task 5 — `user_documents` migration + model + pipeline (Request, Wrapper, Action, Resource)
- [ ] Task 6 — Pest feature tests

---

## Task Detail

---

### Task 1 — Extend `users` Table

**Files:**
- `database/migrations/*_extend_users_with_profile_fields.php`

**Migration steps (in order inside `up()`):**

1. `$table->string('first_name', 100)->nullable()->after('id')`
2. `$table->string('last_name', 100)->nullable()->after('first_name')`
3. Data migration: `UPDATE users SET first_name = split_part(name, ' ', 1), last_name = NULLIF(TRIM(SUBSTRING(name FROM POSITION(' ' IN name))), '')` — handles single-word names gracefully.
4. Drop the existing `name` varchar column.
5. Add `name` as GENERATED STORED: `DB::statement("ALTER TABLE users ADD COLUMN name varchar(255) GENERATED ALWAYS AS (COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')) STORED")`
6. `$table->uuid('uuid')->unique()->nullable()->after('last_name')` — nullable for existing records, filled by migration.
7. Data migration: `UPDATE users SET uuid = gen_random_uuid() WHERE uuid IS NULL`
8. `$table->unsignedBigInteger('document_type_id')->nullable()`
9. `$table->string('document_number', 20)->nullable()`
10. `$table->date('birth_date')->nullable()`
11. `$table->unsignedBigInteger('gender_id')->nullable()`
12. `$table->unsignedBigInteger('nationality_id')->nullable()`
13. `$table->string('phone_primary', 20)->nullable()`
14. `$table->string('phone_secondary', 20)->nullable()`
15. `$table->string('profile_photo_url', 500)->nullable()`
16. `$table->softDeletes()`
17. Add FKs: `document_type_id` → `document_types`, `gender_id` → `genders`, `nationality_id` → `countries` — all RESTRICT
18. Add partial unique index: `DB::statement("CREATE UNIQUE INDEX users_document_unique ON users (document_type_id, document_number) WHERE deleted_at IS NULL")`

**Done criteria:**
- Migration runs cleanly on existing data: `vendor/bin/sail artisan migrate`
- `users.name` is a GENERATED column: `SELECT name FROM users LIMIT 1` returns concatenated name
- All existing user records have `uuid` populated after migration
- `vendor/bin/sail artisan migrate:rollback` works (down() restores original `name` varchar, drops new columns)

---

### Task 2 — Update `User` Model

**Files:**
- `app/Models/User.php`

**Changes:**
- Add `SoftDeletes` trait
- Add to `$fillable`: `first_name`, `last_name`, `document_type_id`, `document_number`, `birth_date`, `gender_id`, `nationality_id`, `phone_primary`, `phone_secondary`, `profile_photo_url`
- Add to `casts()`: `birth_date` → `date`, `deleted_at` → `datetime`
- Add `boot()` method with `creating` event: `$model->uuid = Str::uuid()->toString()` if not set
- Add relations:
  - `documentType(): BelongsTo` → `DocumentType`
  - `gender(): BelongsTo` → `Gender`
  - `nationality(): BelongsTo` → `Country`
  - `addresses(): HasMany` → `UserAddress`
  - `documents(): HasMany` → `UserDocument`
- Remove `name` from `$fillable` (it's now GENERATED — writing to it throws a DB error)

**Done criteria:**
- `User::create(['first_name' => 'Ana', 'last_name' => 'López', 'email' => '...', 'password' => '...'])` → `$user->name` returns `'Ana López'`
- `$user->uuid` is auto-set on create
- `User::find(1)->delete()` → soft delete (sets `deleted_at`, not actual delete)
- `User::withTrashed()->find(1)` works
- Pint clean

---

### Task 3 — Fortify + Registration Form

**Files:**
- `app/Actions/Fortify/CreateNewUser.php`
- `app/Actions/Fortify/UpdateUserProfileInformation.php`
- `resources/js/pages/auth/Register.vue` (split `name` input into `first_name` + `last_name`)
- Any Fortify validation that referenced `name` field

**Changes:**
- `CreateNewUser::create()`: accept `first_name` + `last_name` in validation, pass to `User::create()`
- `UpdateUserProfileInformation::update()`: same — accept `first_name` + `last_name`
- Registration form: two separate inputs (`Nombre(s)` and `Apellido(s)`)

**Done criteria:**
- New user registration works end-to-end: user fills first_name + last_name, account is created with correct GENERATED name
- Profile update works with first_name + last_name
- No TypeScript errors: build clean
- Pint clean

---

### Task 4 — `user_addresses`

**Files:**
- `database/migrations/*_create_user_addresses_table.php`
- `app/Models/UserAddress.php`
- `app/Http/Requests/Admin/StoreUserAddressRequest.php`
- `app/Http/Wrappers/Admin/UserAddressWrapper.php`
- `app/Actions/Admin/CreateUserAddressAction.php`
- `app/Actions/Admin/UpdateUserAddressAction.php`
- `app/Http/Resources/Admin/UserAddressResource.php`
- `app/Http/Controllers/Admin/UserAddressController.php`
- `app/Policies/UserAddressPolicy.php`
- `routes/web.php` (add address routes under /admin/users/{user}/addresses)

**Done criteria:**
- Table exists with all FK columns RESTRICT
- `UserAddress` model has all relations (country, state, municipality, parish, geographicZone)
- `StoreUserAddressRequest` validates geographic FK chain consistency (state belongs to country, etc.)
- `CreateUserAddressAction` wraps in a transaction: if `is_primary = true`, sets all other user addresses to `is_primary = false` first
- Routes: `GET addresses`, `POST addresses`, `PUT addresses/{address}`, `DELETE addresses/{address}` — named `users.addresses.*`
- `UserAddressPolicy` registered in `AppServiceProvider`
- Pint clean

---

### Task 5 — `user_documents`

**Files:**
- `database/migrations/*_create_user_documents_table.php`
- `app/Models/UserDocument.php`
- `app/Http/Requests/Admin/StoreUserDocumentRequest.php`
- `app/Http/Wrappers/Admin/UserDocumentWrapper.php`
- `app/Actions/Admin/CreateUserDocumentAction.php`
- `app/Actions/Admin/VerifyUserDocumentAction.php`
- `app/Http/Resources/Admin/UserDocumentResource.php`
- `app/Http/Controllers/Admin/UserDocumentController.php`
- `app/Policies/UserDocumentPolicy.php`
- `routes/web.php`

**Done criteria:**
- Table exists with correct structure
- `StoreUserDocumentRequest` validates `mime_type` in `['image/jpeg', 'image/png', 'application/pdf']` and max file size 10MB
- `CreateUserDocumentAction` stores file in `storage/app/private/documents/` and creates the record
- `VerifyUserDocumentAction` sets `is_verified = true`, `verified_by`, `verified_at`
- `UserDocumentPolicy::verify()` returns false if `$user->id === $document->user_id` (can't self-verify)
- Routes: `GET`, `POST`, `DELETE`, `PATCH /verify` — named `users.documents.*`
- Pint clean

---

### Task 6 — Pest Feature Tests

**Files:**
- `tests/Feature/Admin/UserProfileExtensionTest.php`
- `tests/Feature/Admin/UserAddressTest.php`
- `tests/Feature/Admin/UserDocumentTest.php`

**Done criteria:**
- `UserProfileExtensionTest`: user creation with first/last name → name GENERATED correct; soft delete works; uuid auto-generated; document unique index rejects duplicate
- `UserAddressTest`: create address; set primary flips others to non-primary; geographic chain validation rejects mismatched state/country
- `UserDocumentTest`: upload accepted mime; upload rejected mime; admin can verify; user cannot self-verify (403)
- `vendor/bin/sail artisan test --compact --filter="UserProfile|UserAddress|UserDocument"` — all pass
- Full suite: `vendor/bin/sail artisan test --compact` — no regressions
