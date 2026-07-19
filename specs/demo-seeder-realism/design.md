# Design — demo-seeder-realism

## Archivos afectados

| Archivo | Cambio |
|---|---|
| `database/seeders/Demo/DemoAcademicSeeder.php` | + materias universitarias `period_number` 3-8 por carrera (2-3 c/u) + `CareerCategory`/`Career`/`Pensum(year,5)`/materias de bachillerato (6-7 por año) |
| `database/seeders/Demo/DemoPeriodSeeder.php` | + 6 semestres históricos universitarios + 5 años escolares (`Year`, 3 momentos c/u) |
| `database/seeders/Demo/DemoSectionsSeeder.php` | Crea secciones para el `period_number` **actual** de cada cohorte universitaria (no solo 1-2) + secciones `SectionType::School` por año de bachillerato en el año escolar vigente |
| `database/seeders/Demo/DemoStudentsSeeder.php` | Pool de representantes con reparto aleatorio 1-6, kinship variado; secundaria recibe `current_pensum_id` |
| `database/seeders/Demo/DemoEnrollmentSeeder.php` | Genera la variedad sin/a medias/completa sobre el período **actual**, para ambos niveles |
| `database/seeders/Demo/DemoAcademicHistorySeeder.php` **(nuevo)** | Backfill de `Enrollment`+`EnrollmentDetail`+`GradeEntry`+`Section` histórico |
| `database/seeders/DemoSeeder.php` | Registra `DemoAcademicHistorySeeder` en el pipeline, después de `DemoEnrollmentSeeder` |

## Modelo temporal — universidad

Cada pensum universitario declara `total_periods = 8` (semestres). Se define:

```
T(student) = student.academic_year * 2   // semestre que cursa AHORA, período 2026-I
```

`academic_year` va de 1 a 4 (ya sembrado así), así que `T ∈ {2, 4, 6, 8}`. El estudiante ya completó (y tiene historial de) `period_number = 1..(T-1)`.

Los períodos se ordenan cronológicamente con índice `1..8`, terminando en `2026-I = 8`:

| Índice | Período | Status |
|---|---|---|
| 1 | 2022-II | Closed (nuevo) |
| 2 | 2023-I | Closed (nuevo) |
| 3 | 2023-II | Closed (nuevo) |
| 4 | 2024-I | Closed (nuevo) |
| 5 | 2024-II | Closed (nuevo) |
| 6 | 2025-I | Closed (nuevo) |
| 7 | 2025-II | Closed (ya existe) |
| 8 | 2026-I | Active (ya existe) |

Para un estudiante con semestre actual `T`, el semestre `period_number = p` (con `p < T`) se cursó `j = T - p` períodos calendario atrás, es decir en el período de índice cronológico `8 - j`. Ejemplo: año 4 (`T=8`) tiene historial en `period_number 1..7`, mapeado 1:1 a los índices cronológicos `1..7` (2022-II…2025-II). Año 1 (`T=2`) solo tiene historial en `period_number 1`, mapeado al índice `7` (2025-II) — su único semestre previo fue el período calendario inmediatamente anterior al actual.

`DemoSectionsSeeder` debe crear secciones en `2026-I` para `period_number ∈ {2,4,6,8}` (los semestres que alguna cohorte está cursando *ahora*), y `DemoAcademicHistorySeeder` crea secciones ad-hoc (mismo patrón `firstOrCreate`) en cada período histórico para el `period_number` que corresponda, reutilizando profesores/aulas existentes.

## Modelo temporal — secundaria

Pensum de bachillerato: `period_type = year`, `total_periods = 5`. Se siembran 5 períodos `Year` (`2021-2022` … `2025-2026`), 3 lapsos c/u ("Primer/Segundo/Tercer Momento"). El año escolar vigente es `2025-2026`.

```
T(student) = student.academic_year   // 1..5, año que cursa AHORA
```

Historial: `academic_year_number = 1..(T-1)`, mapeado 1:1 al período `Year` de ese mismo número de año (año 3 → historial en los períodos de año 1 y año 2). Todas las materias de un año son obligatorias (no hay selección parcial en el historial — el estudiante cursó y aprobó/reprobó todas las del año).

## Estados de inscripción (período actual)

Se particiona la población (120 universitarios + 20 secundaria) en 3 grupos aproximadamente iguales, aleatorizados (no por rango fijo de ID como hoy):

- **Sin inscripción**: no se crea `Enrollment` para el período/año actual. El historial (períodos pasados) sí se genera igual — la ausencia de inscripción es solo del período vigente.
- **A medias**: `Enrollment::Draft` o `::Confirmed` (aleatorio). `EnrollmentDetail` solo para un subconjunto aleatorio (no vacío, no completo) de las materias que le corresponden.
- **Completa**: `Enrollment::Approved`. `EnrollmentDetail::Confirmed` para el 100% de las materias que le corresponden (universidad: las de `period_number = T`; secundaria: todas las del año `T`, porque ahí es obligatorio).

## Perfiles de estudiante y notas históricas

Cada estudiante recibe un perfil aleatorio ponderado, determinado por hash determinístico de su `student_id` (no `rand()` puro, para que corridas repetidas del seeder con los mismos IDs sean consistentes):

| Perfil | Peso | Rango de notas (escala 0-20) |
|---|---|---|
| Bueno | 30% | 15-19, con posibilidad mínima (~5%) de un reprobado suelto |
| Regular | 50% | 10-16, ~15% de materias reprobadas (5-10) |
| En riesgo | 20% | 6-14, ~35% de materias reprobadas |

Todas las `GradeEntry` históricas se crean con `is_published = true`, `weight` acorde al `grade_slot` existente (reusar `GradeEntryFactory` en vez de reinventar la estructura de slots).

## Representantes (secundaria)

Se crea un pool de guardianes de tamaño variable (no 1:1). Algoritmo: se recorren los 20 estudiantes de secundaria y se van agrupando en lotes de tamaño aleatorio entre 1 y 6 hasta agotar la lista (último lote se ajusta al remanente). Cada lote comparte un representante nuevo. El `kinship_type_id` se elige aleatoriamente del catálogo (`father`, `mother`, `legal_guardian`, `grandparent`, `uncle`, `other`) por cada vínculo `student_guardians`, y `is_primary = true` para todos (cada estudiante sigue teniendo un solo representante en este feature — no se agregan representantes secundarios por estudiante, eso no fue pedido).

## Errores/edge cases

- Si `Subject` de un `period_number` no tiene `Section` creada aún en el período correspondiente (histórico o actual), se omite esa materia del enrollment de ese estudiante para ese período (mismo patrón defensivo que ya usa `DemoEnrollmentSeeder` hoy con `if ($subjects->isEmpty()) continue;`).
- Todo `firstOrCreate` — correr el seeder dos veces no duplica datos ni revienta por unique constraints.
