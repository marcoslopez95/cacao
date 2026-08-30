# Students Multi-Level — Tasks

**Reconciliado 2026-08-30**: código verificado como implementado (19 tests en verde), tracking nunca se había sincronizado.

## Task 1 — Backend: extend StudentController + StudentListResource
Files to modify:
- `app/Http/Controllers/Academic/StudentController.php`
- `app/Http/Resources/Academic/StudentListResource.php`

Changes:
- [x] Add `level` param → filter `educational_level` with `->when($level, ...)`
- [x] Add LEFT JOIN to `guardians` → select `_guardian_name`, `_guardian_relation`
- [x] Add LEFT JOIN to `enrollment_details` + `sections` (type='school') → select `_section_grade`, `_section_letter`
- [x] Add `section_letter[]` filter param (filter on `sec_school.letter`)
- [x] Adjust `quickCounts`: scope by level when set, add `no_guardian` key
- [x] Extend `StudentListResource::toArray()` with 4 new fields
- [x] Run `vendor/bin/sail bin pint --dirty`

## Task 2 — Frontend: types + composable
Files to modify:
- `resources/js/types/student.ts`
- `resources/js/composables/filters/useStudentFilters.ts`

Changes:
- [x] Add `StudentLevel` type and 4 new nullable fields to `StudentListItem`
- [x] Add `level` and `section_letter` to `StudentFilters`
- [x] Add `level` ref and `sectionLetters` ref to composable
- [x] Add `applyLevel(newLevel)` function — resets all other filters, applies level
- [x] Update `paginationFilters` computed to include level + sectionLetters
- [x] Update `applyQuickView` to respect current level context
- [x] Run `vendor/bin/sail npm run build` (check TS errors)

## Task 3 — Frontend: refactor Index.vue
File to modify:
- `resources/js/pages/admin/Students/Index.vue`

Changes:
- [x] Add level tabs (Todos / Primaria / Bachillerato / Universitario) above quick views
- [x] Refactor quick views to per-level sets (QUICK_VIEWS computed from current level)
- [x] Fix quick view active state: ring + terracota accent instead of current low-contrast style
- [x] Refactor filter bar: show/hide career/year/section_letter selects based on active level
- [x] Refactor table: conditional columns per level (v-if blocks)
  - Todos: Estudiante + Nivel pill + Cohorte + Detalle + Inscripción
  - Primaria / Bachillerato: Estudiante + Grado/Año + Sección + Representante + Inscripción
  - Universitario: Estudiante + Carrera + Año + UC + Promedio (—) + Inscripción
- [x] Add level pill component inline (colored dot + label)
- [x] Run `vendor/bin/sail npm run build` (check TS errors)

## Task 4 — Pest feature tests
File to modify:
- `tests/Feature/Academic/StudentControllerTest.php`

Changes:
- [x] Verify all existing 11 tests still pass (no regressions)
- [x] Add: `it('filters by educational level primary')` — creates students of different levels, asserts only primary ones returned
- [x] Add: `it('filters by educational level secondary')` — same for secondary
- [x] Add: `it('filters by educational level university')` — same for university
- [x] Add: `it('includes guardian name and relation for primary student')`
- [x] Add: `it('includes section grade and letter when student has school enrollment')`
- [x] Add: `it('includes no_guardian in quick counts')`
- [x] Run `vendor/bin/sail artisan test --compact --filter=StudentController`
