# Students Multi-Level — Design

## Route
`GET /academic/students` — same route, same controller, extended.
No new route needed.

## Backend architecture

```
GET /academic/students?level=&search=&career_id[]=&academic_year[]=&section_letter[]=&enrollment_status[]=&per_page=
  → StudentController@index
    → Student::query()
        ->join(users)
        ->leftJoin(pensums, careers)     // university: career display + filter
        ->leftJoin(guardians)            // school: guardian name + relation
        ->leftJoin(enrollments WHERE period_id = active)
        ->leftJoin(enrollment_details + sections WHERE period_id = active)  // school: section letter
        ->when($level, fn => whereIn educational_level)
        ->filters applied inline
        ->paginate($perPage)
    → StudentListResource (extended)
    → Inertia::render
```

### New query param: `level`
Maps `'primary'|'secondary'|'university'` directly to `educational_level` column.
When absent or `'all'`, no filter applied.

### Extended joins

**Guardian join** (always LEFT JOIN — nullable):
```sql
LEFT JOIN guardians ON guardians.id = students.guardian_id
```
SELECT adds: `guardians.name AS _guardian_name`, `guardians.relation AS _guardian_relation`

**Section join** (always LEFT JOIN — nullable, for school levels):
```sql
LEFT JOIN enrollment_details ed_school
  ON ed_school.enrollment_id = enrollments.id
LEFT JOIN sections sec_school
  ON sec_school.id = ed_school.section_id
  AND sec_school.type = 'school'
```
SELECT adds: `sec_school.grade AS _section_grade`, `sec_school.letter AS _section_letter`

Note: for university students these join columns will be NULL (correct behavior).

### Extended `StudentListResource` fields
```php
'guardian_name'     => $this->_guardian_name,        // nullable
'guardian_relation' => $this->_guardian_relation,    // nullable
'section_grade'     => $this->_section_grade ? (int) $this->_section_grade : null,
'section_letter'    => $this->_section_letter,       // nullable string e.g. 'A'
```

### Quick view counts (adjusted)
`quickCounts` is now level-aware. When `$level` is set, counts are scoped to that level.

```php
// base builder scoped to level
$base = fn() => Student::query()
    ->join('users', ...)
    ->leftJoin('enrollments', ...)
    ->when($level, fn($q) => $q->where('students.educational_level', $level));

$quickCounts = [
    'all'       => $base()->count(),
    'pending'   => $base()->where(fn($q) => $q->where('enrollments.status','draft')
                       ->orWhereNull('enrollments.status'))->count(),
    'top'       => 0,   // GPA not yet available
    'risk'      => 0,
    'newcomers' => $base()->where('students.academic_year', 1)->count(),
    'no_guardian' => Student::whereNull('guardian_id')
                       ->when($level, fn($q) => $q->where('educational_level', $level))
                       ->count(),
];
```

## Frontend architecture

### Types — `resources/js/types/student.ts`

```ts
export type StudentLevel = 'all' | 'primary' | 'secondary' | 'university'

// Add to StudentListItem:
guardian_name:     string | null
guardian_relation: string | null
section_grade:     number | null
section_letter:    string | null
```

Add to `StudentFilters`:
```ts
level?: StudentLevel
section_letter?: string[]
```

### Composable — `resources/js/composables/filters/useStudentFilters.ts`

Add `level` ref (default `'all'`).
Add `sectionLetters` ref (default `[]`).

New `applyLevel(newLevel: StudentLevel)` function:
- Resets search, careerIds, academicYears, enrollStatuses, sectionLetters to empty
- Sets `level.value = newLevel`
- Calls `applyFilters()` with all resets + new level

### Page — `resources/js/pages/admin/Students/Index.vue`

**Structure:**
```
<LevelTabs>         ← new: Todos / Primaria / Bachillerato / Universitario
<QuickViewChips>    ← refactored: per-level set, fixed active state (terracota + ring)
<FilterBar>         ← refactored: per-level filter inputs
<ResultBar>         ← unchanged
<StudentTable>      ← refactored: per-level columns
<AppPagination>     ← unchanged
```

**Level tab active state**: uses `level === activeLevel` — tab gets `border-b-2 border-terracota
text-terracota font-medium` class.

**Quick view active state fix**: chip gets `ring-2 ring-terracota/30 bg-terracota/10
text-terracota font-medium` when active. Current implementation uses a low-contrast class.

**Column rendering**: `v-if` on level for each column group — no dynamic component, direct
conditional rendering is clearer and easier to test.

**Level pill colors** (Todos tab only):
| Level | Color |
|---|---|
| primary | `#2E7D5C` (green) |
| secondary | `#7C5A3A` (brown) |
| university | terracota `#C8521A` |

## Props from controller

```ts
type Props = {
    students:     StudentCollection        // unchanged
    careers:      Array<{id,name}>        // unchanged
    activePeriod: string | null           // unchanged
    quickCounts:  Record<string, number>  // extended with no_guardian key
    filters:      StudentFilters          // extended with level + section_letter
}
```

## URL shape
```
/academic/students?level=primary&academic_year[]=3&section_letter[]=A&enrollment_status[]=confirmed
/academic/students?level=university&career_id[]=2&enrollment_status[]=draft
/academic/students?search=camila&level=all
```
