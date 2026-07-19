# Tasks — demo-seeder-realism

- [x] 1. `DemoAcademicSeeder.php` — agregar materias universitarias `period_number` 3-8 por carrera (INF/CIV/CON/ADM/EDU, 2-3 por semestre, nombres temáticos coherentes con cada carrera)
- [x] 2. `DemoAcademicSeeder.php` — agregar `CareerCategory` "Educación Media General", `Career` bachillerato, `Pensum(period_type: year, total_periods: 5)`, y 6-7 materias por año (1-5), sin prelaciones
- [x] 3. `DemoPeriodSeeder.php` — agregar 6 períodos `Semester` históricos (`2022-II`…`2025-I`, `Closed`, 2 lapsos c/u)
- [x] 4. `DemoPeriodSeeder.php` — agregar 5 períodos `Year` (`2021-2022`…`2025-2026`, `Closed` salvo el último `Active`, 3 momentos c/u: "Primer/Segundo/Tercer Momento")
- [x] 5. `DemoSectionsSeeder.php` — extender para crear secciones en `2026-I` para `period_number ∈ {2,4,6,8}` (no solo 1-2), y secciones `SectionType::School` para las materias del año escolar vigente
- [x] 6. `DemoStudentsSeeder.php` — reemplazar el reparto 1:1 representante-estudiante por el algoritmo de lotes aleatorios (1-6 estudiantes por representante), kinship variado; asignar `current_pensum_id` real a los estudiantes de secundaria
- [x] 7. `DemoEnrollmentSeeder.php` — reescribir para: calcular `T` por estudiante (universidad y secundaria), particionar la población en sin/a medias/completa, generar `Enrollment`+`EnrollmentDetail` del período actual según el estado que le tocó
- [x] 8. `DemoAcademicHistorySeeder.php` (nuevo) — para cada estudiante, recorrer `period_number`/`academic_year` históricos según la fórmula de `design.md`, crear `Section` histórica si falta, `Enrollment::Approved` + `EnrollmentDetail::Confirmed` + `GradeEntry` publicados con el rango de notas según perfil del estudiante
- [x] 9. `DemoSeeder.php` — registrar `DemoAcademicHistorySeeder` en el pipeline, después de `DemoEnrollmentSeeder`
- [x] 10. Verificación: `php artisan migrate:fresh --seed --seeder=DemoSeeder` (vía Sail) sin errores; correr dos veces para confirmar idempotencia
- [x] 11. Verificación: `database-query` — conteos por estado de inscripción actual (sin/a medias/completa) para ambos niveles; representantes con conteo de estudiantes (min 1, max 6); notas fuera de rango 0-20 (debe dar 0 filas); estudiantes de secundaria con `current_pensum_id` no nulo
- [x] 12. `vendor/bin/sail bin pint --dirty --format agent` sobre todos los archivos tocados
