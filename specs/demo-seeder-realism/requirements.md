# Requirements — demo-seeder-realism

**Feature ID:** `demo-seeder-realism`
**Origen:** Solicitud directa del usuario (no HLZ)
**Prioridad:** MEDIA
**Depende de:** `demo-seeder` (ya completado — este feature lo extiende, no lo reemplaza)

---

## Problema

El `DemoSeeder` actual tiene varias limitaciones que lo alejan de un dataset realista:

1. **Representantes 1:1** — `DemoStudentsSeeder` crea exactamente un representante por cada estudiante de secundaria (`rep01↔sec01` … `rep20↔sec20`), siempre con parentesco `mother`. En la realidad un representante puede tener varios hijos en el sistema.
2. **Inscripción uniforme** — `DemoEnrollmentSeeder` siempre inscribe (cuando lo hace) las mismas 4 materias de `period_number = 1`, sin importar el `academic_year` real del estudiante. No existen estudiantes sin inscripción actual ni con inscripción parcial variada.
3. **Sin historial académico** — no existen `Enrollment`/`EnrollmentDetail`/`GradeEntry` de períodos anteriores. Un estudiante en año/semestre avanzado no tiene notas de los períodos que ya debería haber cursado.
4. **Currículum de secundaria inexistente** — los estudiantes de `EducationalLevel::Secondary` tienen `current_pensum_id = null`. El schema sí soporta pensum para todos los niveles (`pensums.period_type = enum('semester','year')`, `SectionType::School`), pero el seeder nunca creó ese pensum ni sus materias.
5. **Solo 2 períodos sembrados** — `2025-II` y `2026-I`, insuficientes para sostener historial de estudiantes en semestres/años avanzados.

---

## Casos de uso aprobados

### UC-01 — Representantes con 1 a 6 estudiantes a cargo

Un pool de representantes (menor al número de estudiantes de secundaria) se distribuye entre los 20 estudiantes actuales, con grupos de tamaño aleatorio entre 1 y 6. El `kinship_type` varía (madre, padre, abuelo, tío, otro) en vez de ser siempre `mother`.

### UC-02 — Currículum de bachillerato (secundaria)

Se crea una `CareerCategory`/`Career`/`Pensum(period_type: year, total_periods: 5)` de bachillerato con 6-7 materias obligatorias por año (~35 materias), sin prelaciones. Los 20 estudiantes de secundaria reciben `current_pensum_id` real.

### UC-03 — Estados de inscripción variados (período actual)

Sobre el período/año escolar **vigente**, cada estudiante (universidad y secundaria) cae en uno de tres casos:
- **Sin inscripción**: no existe `Enrollment` para el período actual.
- **A medias**: `Enrollment` en `Draft`/`Confirmed`, con menos `EnrollmentDetail` de las que le corresponden (universidad: subconjunto de sus materias del semestre; secundaria: subconjunto de las materias obligatorias del año).
- **Completa**: `Enrollment` `Approved`, con **todas** las `EnrollmentDetail` en `Confirmed` que le corresponden a su nivel.

### UC-04 — Historial académico completo

Para cada estudiante, se genera automáticamente `Enrollment` (`Approved`) + `EnrollmentDetail` (`Confirmed`) + `GradeEntry` (publicado) de **todos** los períodos anteriores que le correspondan según su semestre/año actual — nunca huecos en el pasado. Cada estudiante tiene un perfil aleatorio (bueno / regular / en riesgo) que sesga el rango de notas generadas (ver design.md).

---

## Criterios de aceptación

1. Ningún representante de secundaria queda sin al menos 1 estudiante; ningún representante tiene más de 6.
2. Existen estudiantes universitarios y de secundaria en los 3 estados de inscripción del período actual (sin/a medias/completa).
3. Un estudiante universitario en `academic_year = Y` tiene `Enrollment` `Approved` + `GradeEntry` publicados para **todos** los semestres `1..(2Y-1)` de su pensum, en períodos históricos reales (no ficticios).
4. Un estudiante de secundaria en `academic_year = Y` tiene historial completo para los años escolares `1..(Y-1)`.
5. Los 20 estudiantes de secundaria tienen `current_pensum_id` no nulo, apuntando al pensum de bachillerato.
6. Ninguna nota (`grade_entries.value`) sale del rango `0-20`.
7. `php artisan migrate:fresh --seed --seeder=DemoSeeder` corre sin errores y es idempotente (usa `firstOrCreate` en todos los puntos, igual que el resto del seeder).

## Fuera de alcance

- Nivel `Primary` — no hay estudiantes de primaria seedeados hoy; no se agregan en este feature.
- Cambios de UI/frontend — este feature es puro backend/seeder.
- Tests Pest automatizados — los seeders demo no tienen cobertura Pest en este proyecto (son datos de desarrollo). Verificación manual vía `database-query` + corrida completa del seeder.
- Horarios/aulas para secciones de secundaria — se crean `Section`/`EnrollmentDetail` de tipo `School` porque `section_id` es `NOT NULL`, pero sin `Schedule` asociado (no lo requiere el flujo actual de secundaria).
