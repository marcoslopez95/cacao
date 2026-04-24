# Academic Catalog — Design Spec

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build the academic catalog module — career categories, careers, pensums, subjects, and prerequisites — with full admin CRUD UI following existing CACAO patterns.

**Architecture:** Five entities in a hierarchy (`career_categories → careers → pensums → subjects ↔ subject_prerequisites`). Four Inertia pages with drill-down navigation under `/academic/`. CRUD via modals, same pattern as the Security module.

**Tech Stack:** Laravel 13 · Eloquent · Spatie Permission · Vue 3 · Inertia v3 · Wayfinder · Pest v4

---

## Data Model

### `career_categories`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string(100) | unique |
| timestamps | | |

### `careers`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| career_category_id | FK → career_categories | |
| name | string(255) | |
| code | string(10) | short abbreviation e.g. "INF", unique |
| active | boolean | default true |
| timestamps | | |

### `pensums`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| career_id | FK → careers | |
| name | string(255) | e.g. "Plan de Estudios 2020" |
| period_type | enum(semester, year) | declared per pensum |
| total_periods | tinyint | max periods in this pensum (e.g. 10 for 10 semesters) |
| is_active | boolean | default true; multiple pensums of the same career can be active simultaneously |
| timestamps | | |

### `subjects`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| pensum_id | FK → pensums | |
| coordination_id | FK → coordinations, nullable | "department" in CLAUDE.md |
| name | string(255) | |
| code | string(20) | auto-generated, editable, unique within pensum |
| credits_uc | tinyint | ≥ 1 |
| period_number | tinyint | 1 .. pensum.total_periods |
| description | text, nullable | |
| timestamps | | |

**Code auto-generation rule:** `{career.code}-{period_number}{sequence_within_period}` padded to 3 digits — e.g. `INF-101`, `INF-102`, `INF-201`. Unique within the pensum. Computed server-side on create, returned to the form as a default, overridable by the user before saving.

### `subject_prerequisites` (pivot, self-referential)
| Column | Type | Notes |
|---|---|---|
| subject_id | FK → subjects | the subject that has the requirement |
| prerequisite_id | FK → subjects | the required subject |

**Constraint:** prerequisite must belong to the same pensum AND have `period_number < subject.period_number`. Enforced at the application layer (validation + policy).

---

## Routes

All routes under `auth + verified` middleware, prefix `/academic/`, name prefix `academic.`.

```
GET    /academic/career-categories                                              academic.career-categories.index
POST   /academic/career-categories                                              academic.career-categories.store
PATCH  /academic/career-categories/{careerCategory}                             academic.career-categories.update
DELETE /academic/career-categories/{careerCategory}                             academic.career-categories.destroy

GET    /academic/careers                                                        academic.careers.index
POST   /academic/careers                                                        academic.careers.store
PATCH  /academic/careers/{career}                                               academic.careers.update
DELETE /academic/careers/{career}                                               academic.careers.destroy

GET    /academic/careers/{career}/pensums                                       academic.pensums.index
POST   /academic/careers/{career}/pensums                                       academic.pensums.store
PATCH  /academic/careers/{career}/pensums/{pensum}                              academic.pensums.update
DELETE /academic/careers/{career}/pensums/{pensum}                              academic.pensums.destroy

GET    /academic/careers/{career}/pensums/{pensum}/subjects                     academic.subjects.index
POST   /academic/careers/{career}/pensums/{pensum}/subjects                     academic.subjects.store
PATCH  /academic/careers/{career}/pensums/{pensum}/subjects/{subject}           academic.subjects.update
DELETE /academic/careers/{career}/pensums/{pensum}/subjects/{subject}           academic.subjects.destroy
POST   /academic/careers/{career}/pensums/{pensum}/subjects/{subject}/prerequisites/sync   academic.subjects.prerequisites.sync
```

---

## Controllers

```
app/Http/Controllers/Academic/
  CareerCategoryController.php   index, store, update, destroy
  CareerController.php           index, store, update, destroy
  PensumController.php           index, store, update, destroy
  SubjectController.php          index, store, update, destroy, syncPrerequisites
```

Each `index` returns an Inertia response. `store/update/destroy` return redirects with `Inertia::flash('toast', ...)`.

`SubjectController::index` generates the next available subject code and passes it as `nextCode` prop so the Create modal can pre-fill the field.

---

## Form Requests

```
app/Http/Requests/Academic/
  StoreCareerCategoryRequest.php
  UpdateCareerCategoryRequest.php
  StoreCareerRequest.php
  UpdateCareerRequest.php
  StorePensumRequest.php
  UpdatePensumRequest.php
  StoreSubjectRequest.php
  UpdateSubjectRequest.php
  SyncPrerequisitesRequest.php
```

`SyncPrerequisitesRequest` validates that every `prerequisite_id` in the array belongs to the same pensum and has `period_number < subject.period_number`.

---

## Models

```
app/Models/
  CareerCategory.php   hasMany careers
  Career.php           belongsTo careerCategory, hasMany pensums
  Pensum.php           belongsTo career, hasMany subjects
  Subject.php          belongsTo pensum, belongsTo coordination (nullable)
                       belongsToMany prerequisites (self, subject_prerequisites, subject_id, prerequisite_id)
                       belongsToMany dependents   (self, subject_prerequisites, prerequisite_id, subject_id)
```

---

## Policies

```
app/Policies/Academic/
  CareerCategoryPolicy.php   viewAny, create, update, delete
  CareerPolicy.php           viewAny, create, update, delete
  PensumPolicy.php           viewAny, create, update, delete
  SubjectPolicy.php          viewAny, create, update, delete, managePrerequisites
```

`Gate::before` in `AppServiceProvider` already grants all to Admin — no changes needed there.

---

## Permissions (Spatie)

Added to `PermissionSeeder`:

```
career-categories.view   career-categories.create
career-categories.update career-categories.delete

careers.view    careers.create
careers.update  careers.delete

pensums.view    pensums.create
pensums.update  pensums.delete

subjects.view    subjects.create
subjects.update  subjects.delete
subjects.manage-prerequisites
```

---

## Frontend Pages

```
resources/js/pages/academic/
  CareerCategories/Index.vue
  Careers/Index.vue
  Pensums/Index.vue          receives: career, pensums[], can{}
  Subjects/Index.vue         receives: career, pensum, subjects[], coordinations[], can{}
```

### CareerCategories/Index.vue
Table: name · actions (edit, delete). Modals: CreateCareerCategoryModal, EditCareerCategoryModal, DeleteCareerCategoryModal.

### Careers/Index.vue
Receives: `careers[]`, `categories[]`, `can{}`. Table: name · code · category · active badge · actions (edit, delete, "Ver pensums" → navigate). Filter by category. Modals: CreateCareerModal, EditCareerModal, DeleteCareerModal.

### Pensums/Index.vue
Receives: `career`, `pensums[]`, `can{}`. Breadcrumb includes career name. Table: name · period_type · total_periods · active badge · actions (edit, toggle active, delete, "Ver materias" → navigate). Modals: CreatePensumModal, EditPensumModal, DeletePensumModal. "Toggle active" is a direct PATCH to `pensums.update` (no confirmation modal needed — toggling active/inactive is reversible).

### Subjects/Index.vue
Receives: `career`, `pensum`, `subjects[]`, `coordinations[]`, `nextCode` (string, pre-filled in Create modal), `can{}`. Filter by period_number. Table: name + code · period · UC · coordination · prerequisites (chips) · actions (edit, delete, manage prerequisites). Modals: CreateSubjectModal, EditSubjectModal, DeleteSubjectModal, PrerequisitesModal.

**PrerequisitesModal:** lists subjects from the same pensum with `period_number < current subject's period_number`. Multi-select checkboxes. Saves via `POST .../prerequisites/sync`. Empty state if subject is in period 1 (cannot have prerequisites).

---

## Frontend Components

```
resources/js/components/academic/
  CreateCareerCategoryModal.vue
  EditCareerCategoryModal.vue
  DeleteCareerCategoryModal.vue
  CreateCareerModal.vue
  EditCareerModal.vue
  DeleteCareerModal.vue
  CreatePensumModal.vue
  EditPensumModal.vue
  DeletePensumModal.vue
  CreateSubjectModal.vue
  EditSubjectModal.vue
  DeleteSubjectModal.vue
  PrerequisitesModal.vue
```

---

## Types

```typescript
// resources/js/types/academic.ts
export type CareerCategory = { id: number; name: string }

export type Career = {
  id: number
  name: string
  code: string
  active: boolean
  category: CareerCategory
  pensumsCount: number
}

export type Pensum = {
  id: number
  careerId: number
  name: string
  periodType: 'semester' | 'year'
  totalPeriods: number
  isActive: boolean
  subjectsCount: number
}

export type SubjectRow = {
  id: number
  name: string
  code: string
  creditsUc: number
  periodNumber: number
  coordination: { id: number; name: string } | null
  prerequisites: { id: number; name: string; code: string }[]
}
```

---

## Sidebar

New "Académico" section in `AppSidebar.vue`, visible if user has `careers.view` or is Admin:
- **Categorías** (icon: `folder`) → `/academic/career-categories` — shown if `career-categories.view`
- **Carreras** (icon: `book`) → `/academic/careers` — shown if `careers.view`

Pensums and Subjects are accessed via drill-down, not directly from the sidebar.

---

## Error Handling

- Delete career: blocked if it has pensums → flash error toast
- Delete pensum: blocked if it has subjects → flash error toast
- Delete subject: blocked if other subjects list it as a prerequisite → flash error toast
- Delete career category: blocked if it has careers → flash error toast
- Sync prerequisites: validation error if any prerequisite_id is from a different pensum or same/later period
- Edit subject period_number: if the new period would make existing prerequisites invalid (their period_number ≥ new period), the update is rejected with a validation error — the user must remove those prerequisites first

---

## Testing

Feature tests (Pest) for each controller:
- Unauthorized access returns 403
- CRUD happy paths with correct redirects and flash toasts
- Validation errors return correct field errors
- Business rule violations (delete with dependencies, invalid prerequisites) return correct errors

One unit test for the subject code generation logic.
