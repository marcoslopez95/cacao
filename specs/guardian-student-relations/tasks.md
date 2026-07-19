# Tasks — guardian-student-relations

## Backend

- [x] `App\Http\Resources\Academic\GuardianListResource` (id, user_id, name, email, students_count)
- [x] `App\Http\Resources\Academic\GuardianShowResource` (id, user_id, name, email, students[])
- [x] `App\Http\Controllers\Academic\GuardianController@index` — paginado + búsqueda, `withCount('students')`
- [x] `App\Http\Controllers\Academic\GuardianController@show` — 404 si no existe, parentesco resuelto vía `KinshipType::pluck('name','id')` (sin N+1)
- [x] Rutas `academic.guardians.index` / `academic.guardians.show` en `routes/web.php` (grupo `academic` existente)
- [x] `UserController::index()` — eager load `student.guardians:id`, `guardian:id,user_id`, `guardian.students:id`
- [x] `UserResource` — agregar `student_id`, `guardians_count`, `guardian_id`, `students_count`

## Frontend

- [x] `resources/js/types/guardian.ts` — `GuardianListItem`, `GuardianShowData`, `GuardianStudentRow`, `GuardianCollection`
- [x] `resources/js/types/security.ts` — `UserRow` gana `student_id`, `guardian_id`, `guardians_count`, `students_count`
- [x] `resources/js/pages/admin/Guardians/Index.vue` — tabla + búsqueda + paginación (mismo patrón visual que `security/Users/Index.vue`)
- [x] `resources/js/pages/admin/Guardians/Show.vue` — identidad + tabla de estudiantes a cargo, link a `academic/students/{id}` (mismo patrón visual que `admin/Students/Show.vue`)
- [x] `AppSidebar.vue` — nav item "Representantes" en `academicItems`, mismo gating que "Estudiantes" (rol Admin)
- [x] `security/Users/Index.vue` — columna "Relación" (link a representantes/estudiantes según rol, "—" si no aplica)
- [x] `resources/js/composables/filters/useGuardianFilters.ts` — search + per_page, mismo patrón que `useUserFilters.ts` (no estaba en el plan original, necesario para el buscador del índice)

## Verificación

- [x] `vendor/bin/pint --dirty --format agent`
- [x] Tests Pest: `GuardianControllerTest` (index con/sin search, show con estudiantes/sin estudiantes/404, parentesco resuelto), `UserControllerTest` (student_id/guardians_count, guardian_id/students_count) — 58 tests verdes
- [x] `npm run build` + smoke visual (Dusk) de `/academic/guardians`, `/academic/guardians/{id}` y la columna "Relación" en `/security/users` — confirmado con screenshots
- [x] Actualizar `specs/feature_list.json` y `specs/progress/current.md`
