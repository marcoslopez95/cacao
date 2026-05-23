# Students Multi-Level — Tasks

## Task 1 — Backend: extend StudentController + StudentListResource
Files to modify:
- `app/Http/Controllers/Academic/StudentController.php`
- `app/Http/Resources/Academic/StudentListResource.php`

Changes:
- [ ] Add `level` param → filter `educational_level` with `->when($level, ...)`
- [ ] Add LEFT JOIN to `guardians` → select `_guardian_name`, `_guardian_relation`
- [ ] Add LEFT JOIN to `enrollment_details` + `sections` (type='school') → select `_section_grade`, `_section_letter`
- [ ] Add `section_letter[]` filter param (filter on `sec_school.letter`)
- [ ] Adjust `quickCounts`: scope by level when set, add `no_guardian` key
- [ ] Extend `StudentListResource::toArray()` with 4 new fields
- [ ] Run `vendor/bin/sail bin pint --dirty`

## Task 2 — Frontend: types + composable
Files to modify:
- `resources/js/types/student.ts`
- `resources/js/composables/filters/useStudentFilters.ts`

Changes:
- [ ] Add `StudentLevel` type and 4 new nullable fields to `StudentListItem`
- [ ] Add `level` and `section_letter` to `StudentFilters`
- [ ] Add `level` ref and `sectionLetters` ref to composable
- [ ] Add `applyLevel(newLevel)` function — resets all other filters, applies level
- [ ] Update `paginationFilters` computed to include level + sectionLetters
- [ ] Update `applyQuickView` to respect current level context
- [ ] Run `vendor/bin/sail npm run build` (check TS errors)

## Task 3 — Frontend: refactor Index.vue
File to modify:
- `resources/js/pages/admin/Students/Index.vue`

Changes:
- [ ] Add level tabs (Todos / Primaria / Bachillerato / Universitario) above quick views
- [ ] Refactor quick views to per-level sets (QUICK_VIEWS computed from current level)
- [ ] Fix quick view active state: ring + terracota accent instead of current low-contrast style
- [ ] Refactor filter bar: show/hide career/year/section_letter selects based on active level
- [ ] Refactor table: conditional columns per level (v-if blocks)
  - Todos: Estudiante + Nivel pill + Cohorte + Detalle + Inscripción
  - Primaria / Bachillerato: Estudiante + Grado/Año + Sección + Representante + Inscripción
  - Universitario: Estudiante + Carrera + Año + UC + Promedio (—) + Inscripción
- [ ] Add level pill component inline (colored dot + label)
- [ ] Run `vendor/bin/sail npm run build` (check TS errors)

## Task 4 — Pest feature tests
File to modify:
- `tests/Feature/Academic/StudentControllerTest.php`

Changes:
- [ ] Verify all existing 11 tests still pass (no regressions)
- [ ] Add: `it('filters by educational level primary')` — creates students of different levels, asserts only primary ones returned
- [ ] Add: `it('filters by educational level secondary')` — same for secondary
- [ ] Add: `it('filters by educational level university')` — same for university
- [ ] Add: `it('includes guardian name and relation for primary student')`
- [ ] Add: `it('includes section grade and letter when student has school enrollment')`
- [ ] Add: `it('includes no_guardian in quick counts')`
- [ ] Run `vendor/bin/sail artisan test --compact --filter=StudentController`
