# Coordinaciones Module — Design Spec

**Date:** 2026-04-24  
**Status:** Approved

---

## Overview

A coordination (`coordinación`) is an administrative unit responsible for managing the academic life of a career (university level) or a school year/grade (secondary/basic level). Each coordination has exactly one active coordinator at a time. The system tracks the full history of coordinator assignments.

This spec also introduces a global `Role` enum (backend + frontend) to replace loose role strings throughout the codebase.

---

## Scope

- CRUD for coordinations
- Separate "Assign Coordinator" action with history tracking
- Global `Role` enum (PHP + TypeScript)
- Permissions and Policy for the module
- CASL guards on the frontend

**Out of scope (future):** `academic` coordination type that groups career coordinations. The data model supports it via the `type` enum, but no UI or logic is built now.

---

## Data Model

### `coordinations` table

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | e.g. "Coordinación de Ing. de Sistemas" |
| `type` | enum | `career`, `grade`, `academic` |
| `education_level` | enum | `university`, `secondary` |
| `career_id` | nullable FK → `careers` | Only when `type = career` |
| `secondary_type` | nullable enum | `media_general`, `bachillerato`. Only when `type = grade` |
| `grade_year` | nullable tinyint (1–6) | Only when `type = grade`. Max 5 for `media_general`, max 6 for `bachillerato` |
| `active` | boolean | Default `true` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Constraint rules (enforced in Form Request, not schema):**
- `type = career` → `career_id` required, `grade_year` null
- `type = grade` → `secondary_type` required, `grade_year` required, `career_id` null. Max grade_year is 5 for `media_general` and 6 for `bachillerato`
- `education_level = university` → `type` must be `career`
- `education_level = secondary` → `type` must be `grade`

### `coordination_assignments` table

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `coordination_id` | FK → `coordinations` (cascade delete) | |
| `user_id` | FK → `users` (restrict delete) | The coordinator assigned |
| `assigned_by` | FK → `users` (restrict delete) | Who made the assignment |
| `assigned_at` | timestamp | When the assignment started |
| `ended_at` | nullable timestamp | Null = currently active |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Assignment logic:** When a new coordinator is assigned, the current active assignment (`ended_at IS NULL`) is closed (`ended_at = now()`), then a new record is inserted. A coordination can have zero active assignments (no coordinator yet).

---

## Global Role Enum

Replaces all loose role strings in the codebase.

### Backend — `App\Enums\Role`

```php
enum Role: string
{
    case Admin       = 'Admin';
    case Professor   = 'Professor';
    case Student     = 'Student';
    case Guardian    = 'Guardian';
    case Coordinator = 'Coordinator';

    public function label(): string { ... }
}
```

### Frontend — `resources/js/enums/Role.ts`

```ts
export const Role = {
    Admin:       'Admin',
    Professor:   'Professor',
    Student:     'Student',
    Guardian:    'Guardian',
    Coordinator: 'Coordinator',
} as const;

export type RoleValue = typeof Role[keyof typeof Role];
```

The `TeamRole` enum (owner/admin/member) remains separate — it belongs to the multi-tenant team system, not the academic domain.

---

## Permissions

Added to `database/data/permissions.yaml`:

```
coordinations.view
coordinations.create
coordinations.edit
coordinations.delete
coordinations.assign
coordinations.view_history
```

All 6 assigned to `Admin` in `roles.yaml`.

---

## Backend

### Policy — `App\Policies\CoordinationPolicy`

| Method | Permission |
|---|---|
| `viewAny` | `coordinations.view` |
| `create` | `coordinations.create` |
| `update` | `coordinations.edit` |
| `delete` | `coordinations.delete` |
| `assign` | `coordinations.assign` |
| `viewHistory` | `coordinations.view_history` |

### Controllers

**`App\Http\Controllers\Security\CoordinationController`**

- `index` — paginated list with filters (name search, type, education_level, active status). Each row includes `current_coordinator` (name + id, or null).
- `store` — validates and creates coordination.
- `update` — validates and updates coordination.
- `destroy` — deletes coordination. Soft-blocks if it has an active assignment (returns 422 with message).

**`App\Http\Controllers\Security\CoordinationAssignmentController`**

- `store` — assigns a coordinator:
  1. Validates `user_id` has role `Coordinator` (uses `Role::Coordinator`)
  2. Closes active assignment if exists
  3. Creates new assignment with `assigned_by = auth()->id()`, `assigned_at = now()`
- `index` — returns the full assignment history for a given coordination (for the history modal)

### Form Requests

- `StoreCoordinationRequest` — validates fields + type/level coherence rules above
- `UpdateCoordinationRequest` — same rules, `sometimes` on name
- `StoreCoordinationAssignmentRequest` — validates `user_id` exists and has role `Coordinator`

### Models

**`Coordination`**
```php
// Relationships
career(): BelongsTo
assignments(): HasMany (CoordinationAssignment)
currentAssignment(): HasOne (where ended_at IS NULL)
currentCoordinator(): HasOneThrough (User via currentAssignment)

// Scopes
scopeActive($query)
scopeByType($query, string $type)
scopeByLevel($query, string $level)
```

**`CoordinationAssignment`**
```php
coordination(): BelongsTo
user(): BelongsTo
assignedBy(): BelongsTo (User)
```

---

## Frontend

### CASL

Add `'Coordination'` to `AppSubjects` in `resources/js/casl/ability.ts`.

### Types — `resources/js/types/security.ts` (additions)

```ts
type CoordinationRow = {
    id: number;
    name: string;
    type: 'career' | 'grade' | 'academic';
    education_level: 'university' | 'secondary';
    secondary_type: 'media_general' | 'bachillerato' | null;
    career_id: number | null;
    grade_year: number | null;
    active: boolean;
    current_coordinator: { id: number; name: string } | null;
};

type CoordinationAssignment = {
    id: number;
    user: { id: number; name: string };
    assigned_by: { id: number; name: string };
    assigned_at: string;
    ended_at: string | null;
};

type CoordinationPaginator = {
    data: CoordinationRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};
```

### Page — `resources/js/pages/security/Coordinations/Index.vue`

Table columns: Nombre, Tipo, Nivel, Coordinador actual (name or "Sin asignar" badge), Estado, Acciones.

Header filters: text search, type select, education level select, status select.

Per-row actions (CASL-guarded):
- Edit → opens `EditCoordinationModal`
- Assign coordinator → opens `AssignCoordinatorModal`
- View history → opens `CoordinationHistoryModal`
- Delete → opens `DeleteCoordinationModal`

### Modals

| Modal | Key behavior |
|---|---|
| `CreateCoordinationModal` | Fields: name, type (select), education_level (select). On type=career: career dropdown. On type=grade: secondary_type select (media_general/bachillerato) + grade_year select (1–5 for media_general, 1–6 for bachillerato). |
| `EditCoordinationModal` | Same fields + active toggle |
| `AssignCoordinatorModal` | Shows current coordinator (if any) at top. Dropdown filtered to users with role `Coordinator` only. Confirm button. |
| `CoordinationHistoryModal` | Read-only table: coordinator name, assigned by, assigned_at, ended_at (or "Activo" badge). |
| `DeleteCoordinationModal` | Warning if coordination has active coordinator. Confirm/cancel. |

### Navigation — `AppSidebar.vue`

Add "Coordinaciones" entry under Security group, guarded by `coordinations.view`.

---

## Testing

### Factories

- `CoordinationFactory` — states: `career()`, `grade()`, `inactive()`
- `CoordinationAssignmentFactory` — states: `active()` (ended_at null), `closed()`

### Feature tests (Pest)

**`tests/Feature/Security/CoordinationControllerTest.php`**
- Lists coordinations with pagination
- Filters by name, type, education_level, status
- Creates coordination (career type, grade type)
- Rejects invalid type/level combinations (422)
- Updates coordination
- Deletes coordination without active coordinator
- Blocks deletion when active coordinator exists (422)
- 403 for each endpoint when user lacks permission

**`tests/Feature/Security/CoordinationAssignmentControllerTest.php`**
- Assigns coordinator to coordination with no prior assignment
- Closes previous assignment when reassigning
- Rejects user without `Coordinator` role (422)
- Returns full assignment history for a coordination

**`tests/Feature/Security/CoordinationPolicyTest.php`**
- Each policy method returns correct result based on user permissions

---

## File Map

### New files
- `database/migrations/*_create_coordinations_table.php` — includes `secondary_type` enum column
- `database/migrations/*_create_coordination_assignments_table.php`
- `app/Enums/Role.php`
- `app/Models/Coordination.php`
- `app/Models/CoordinationAssignment.php`
- `database/factories/CoordinationFactory.php`
- `database/factories/CoordinationAssignmentFactory.php`
- `app/Policies/CoordinationPolicy.php`
- `app/Http/Requests/Security/StoreCoordinationRequest.php`
- `app/Http/Requests/Security/UpdateCoordinationRequest.php`
- `app/Http/Requests/Security/StoreCoordinationAssignmentRequest.php`
- `app/Http/Controllers/Security/CoordinationController.php`
- `app/Http/Controllers/Security/CoordinationAssignmentController.php`
- `resources/js/enums/Role.ts`
- `resources/js/pages/security/Coordinations/Index.vue`
- `resources/js/components/security/CreateCoordinationModal.vue`
- `resources/js/components/security/EditCoordinationModal.vue`
- `resources/js/components/security/AssignCoordinatorModal.vue`
- `resources/js/components/security/CoordinationHistoryModal.vue`
- `resources/js/components/security/DeleteCoordinationModal.vue`
- `tests/Feature/Security/CoordinationControllerTest.php`
- `tests/Feature/Security/CoordinationAssignmentControllerTest.php`
- `tests/Feature/Security/CoordinationPolicyTest.php`

### Modified files
- `database/data/permissions.yaml` — add 6 `coordinations.*` permissions
- `database/data/roles.yaml` — assign `coordinations.*` to Admin
- `app/Providers/AppServiceProvider.php` — register `CoordinationPolicy`
- `routes/web.php` — add coordination + assignment routes
- `resources/js/types/security.ts` — add `CoordinationRow`, `CoordinationAssignment`, `CoordinationPaginator`
- `resources/js/casl/ability.ts` — add `'Coordination'` to `AppSubjects`
- `resources/js/components/AppSidebar.vue` — add Coordinaciones nav item
