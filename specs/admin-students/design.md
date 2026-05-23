# Admin Students Index — Design

## Route
`GET /academic/students` → `academic.students.index`
Middleware: `auth, verified` (same as rest of academic routes — no extra role gate for now, consistent with existing pattern)

## Backend architecture

```
GET /academic/students?search=&career_id[]=&academic_year[]=&enrollment_status[]=&per_page=
  → StudentController@index
    → Student::query()
        ->join(users)          // for name ordering
        ->leftJoin(pensums, careers)  // for career filter/display
        ->leftJoin(enrollments WHERE period_id = active)  // for status
        ->filters applied inline (no FormRequest needed — read-only)
        ->paginate($perPage)
    → StudentCollection (StudentListResource)
    → Inertia::render
```

No Wrapper or Action needed — read-only query.

### `StudentController@index`
- Resolves active period via `Period::where('status', 'active')->first()`
- Builds query with joins for ordering + filter
- Applies filters inline using `->when()`
- Returns: students (paginated resource), careers (for dropdown), activePeriod (name), filters (echoed back)

### `StudentListResource`
Fields:
```php
id, name, email,
career_name (nullable),
career_id (nullable),
academic_year (nullable),
educational_level,
enrollment_status,   // 'confirmed'|'draft'|'rejected'|null
uc_inscritas,
gpa: null,
cedula: null,
code: null,
```

### Enrollment status display mapping
| DB value | Display | Pill style |
|---|---|---|
| confirmed | Confirmada | green |
| draft | Pendiente | yellow |
| approved | Aprobada | green (treat as confirmed) |
| rejected | Rechazada | red |
| null | Sin inscribir | neutral |

## Frontend architecture

```
resources/js/
  types/student.ts                          StudentListItem, StudentCollection, StudentFilters
  composables/filters/useStudentFilters.ts  filter state + URL sync + debounced search
  pages/admin/Students/Index.vue            page: quick views + toolbar + table + pagination
```

### Quick view counts
Passed from controller: `quickCounts: { all, pending, top, risk, newcomers }`.
- `pending`: students with enrollment draft OR no enrollment for active period
- `top`/`risk`: always 0 (no GPA data) — counts are 0, not hidden
- `newcomers`: students with academic_year = 1

### Filter composable
Same pattern as `useUserFilters` but with:
- `search`, `careerIds`, `academicYears`, `enrollmentStatuses` refs
- `applyFilters()` navigates to `index.url()` with all params
- `onSearchInput()` debounce 350ms

### Vue page structure
```
<Head>
<div>
  <!-- Header: title + description + "Nuevo estudiante" button -->
  <!-- Quick view pills (5) with counts -->
  <!-- table-wrap card -->
    <!-- Filters toolbar: search + career select + year select + status select + per_page -->
    <!-- Result bar: N estudiantes de M + breakdown dots -->
    <!-- Empty state OR table + pagination -->
```

Reuses `AppPagination`, `AppButton`, `AppBadge` UI components.
No new shared components needed — all student-specific markup inline in the page.

## File list
| File | Type |
|---|---|
| `app/Http/Controllers/Academic/StudentController.php` | new |
| `app/Http/Resources/Academic/StudentListResource.php` | new |
| `routes/web.php` | modified (add route) |
| `resources/js/types/student.ts` | new |
| `resources/js/composables/filters/useStudentFilters.ts` | new |
| `resources/js/pages/admin/Students/Index.vue` | new |
| `tests/Feature/Academic/StudentControllerTest.php` | new |
