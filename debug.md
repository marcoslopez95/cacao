# Debug — QA de flujos estudiante / representante (CACAO)

**Fecha:** 2026-08-30
**Probado por:** Claude (Cowork), a pedido de Manu
**Entorno:** `http://localhost:8000` (sesión admin ya activa al iniciar), Laravel 13.29.0 / PHP 8.5.7, `APP_DEBUG` produce página amigable con código de incidente en vez de traza cruda
**Usuarios de prueba:** seed existente en `security/users`, contraseña compartida `password` para todos. Usados: `est041@utcacao.edu.ve` (universitario, sin inscribir), `est109@utcacao.edu.ve` (universitario, confirmada), `sec16@utcacao.edu.ve` (Bachillerato 4° año, con representante), `rep04@utcacao.edu.ve` (representante de sec16 y sec17), `admin@cacao.edu.ve`.

Este documento es un complemento vivo de `specs/qa/backlog.md` (que ya tenía HLZ-01 a HLZ-41 documentados). Los hallazgos nuevos de esta sesión quedaron propuestos como **HLZ-42** y **HLZ-43** directamente en ese archivo — aquí queda el detalle narrativo y la evidencia de reproducción en vivo.

---

## Resumen ejecutivo

Se pidió verificar tres flujos: (1) estudiante se inscribe y ve su horario, (2) representante ve la situación de su estudiante (nivel no universitario), (3) estudiante universitario se autoinscribe sin representante. Resultado:

| Flujo pedido | Resultado |
|---|---|
| Estudiante universitario se autoinscribe | ✅ Funciona de punta a punta (probado en vivo, ver abajo) |
| Estudiante ve su horario / dashboard | 🔴 Crashea con 500 — **HLZ-42**, nuevo |
| Representante ve situación de su estudiante | ✅ Funciona (dashboard, notas, botón inscribir) |
| Representante/estudiante de Bachillerato se inscribe | 🔴 Catálogo siempre vacío ("0 materias") — **HLZ-43**, nuevo |

Además se confirmó en vivo que dos bugs ya documentados en `specs/qa/backlog.md` siguen sin corregir: **HLZ-38** (sin guard de nivel educativo en inscripción) y, por diseño de datos, no fue posible ejercitar **HLZ-40** (no hay estudiantes universitarios vinculados a un representante en el seed actual).

**Gap de datos de prueba:** no existe ningún estudiante de nivel **Primaria** en la base — el filtro "Primaria" en `academic/students` devuelve 0 resultados. Solo hay Bachillerato (20) y Universidad (261). Usé Bachillerato como el nivel "no universitario con representante" más cercano a lo que pediste probar, ya que el comportamiento de representante/inscripción es el mismo para ambos niveles según las reglas de negocio documentadas en `specs/qa/guardian/dashboard.md` y `specs/qa/enrollment/student-enrollment.md`.

---

## 🔴 HLZ-42 (nuevo) — Dashboard del estudiante crashea con 500 cualquier domingo, para cualquier estudiante

**Severidad:** Crítica — bloquea el 100% del acceso del estudiante a su propio portal, no solo el horario.

**Reproducción:** iniciar sesión con cualquier usuario de rol Estudiante (probado con `est041`, `est109`, `sec16` — universitarios y escolar por igual) y cargar `GET /student/dashboard` un domingo (hoy, 2026-08-30, es domingo). Resultado: página de error 500 "Algo se rompió de nuestro lado", incidente `CAC-43B8B7`, reproducible siempre.

**Causa raíz exacta:**
`app/Http/Controllers/Student/DashboardController.php:26`:
```php
$todayValue = strtolower($now->format('l'));   // "sunday" un domingo
$todayLabel = DayOfWeek::from($todayValue)->label();
```
`app/Enums/DayOfWeek.php` solo define `Monday`..`Saturday` (sin caso `Sunday`, presumiblemente porque no hay clases los domingos). `DayOfWeek::from('sunday')` lanza `ValueError: "sunday" is not a valid backing value for enum App\Enums\DayOfWeek`, sin try/catch, tumbando la carga completa del controller (no solo el widget de horario — también período, inscripción, UC del pensum y representantes, porque todo se calcula en el mismo `index()` antes del `return`).

**Evidencia en log** (`storage/logs/laravel.log`):
```
[2026-08-30 21:07:18] local.ERROR: "sunday" is not a valid backing value for enum App\Enums\DayOfWeek {"userId":57,"exception":"[object] (ValueError(code: 0): \"sunday\" is not a valid backing value for enum App\\Enums\\DayOfWeek at /var/www/html/app/Http/Controllers/Student/DashboardController.php:26)
[stacktrace]
#0 .../Student/DashboardController.php(26): App\Enums\DayOfWeek::from()
```
(userId 57 = mi sesión de prueba; el mismo error aparece decenas de veces más temprano en el log con otros userIds, en corridas de test previas — es decir, este crash ya existía, no lo introduje yo).

**Fix sugerido:** cambiar `DayOfWeek::from($todayValue)` por `DayOfWeek::tryFrom($todayValue)` y manejar `null` como "sin clases hoy" (mismo estado vacío que ya contempla `UC-SS03` en `specs/qa/academic/student-schedule.md`), o envolver en try/catch. No hace falta agregar un caso `Sunday` al enum si de verdad no hay clases ese día — pero si el fin de semana completo (sábado también tiene casos) puede tener horario real, revisar si conviene agregarlo igual por completitud.

**Relación con specs existentes:** `specs/qa/academic/student-schedule.md` ya documenta (`HLZ-39`) que el widget "Hoy" no filtra por `EnrollmentDetail::status = confirmed` — ese es un bug de datos incorrectos mostrados; este es un crash total, en la misma línea de código pero de naturaleza distinta (tipo de dato/enum, no de scope de query). Actualizado ese doc con esta entrada (ver sección de cambios en specs, abajo).

---

## 🔴 HLZ-43 (nuevo) — Catálogo de inscripción vacío ("0 materias") para todo estudiante no universitario, por resolución incorrecta del período activo

**Severidad:** Crítica — bloquea el 100% de la inscripción para Bachillerato y Primaria, sea que la haga el representante o el estudiante mismo.

**Reproducción:**
1. Como representante (`rep04@utcacao.edu.ve`) → dashboard → botón "Inscribir" sobre Brooks Davis Stroman (Bachillerato, 4° año) → `/enrollment?student_id=137` → "0 materias", "Sin resultados", pase lo que pase con los filtros (Todas/Obligatorias/Electivas, Prereqs OK, Ocultar aprobadas — probé quitando todos).
2. Como el propio estudiante (`sec16@utcacao.edu.ve`) → `/enrollment` (sin `student_id`) → mismo resultado: 0 materias.
3. Contraste: el mismo flujo para un estudiante universitario (`est041@utcacao.edu.ve`) → `/enrollment` → 6 materias disponibles, inscripción completa exitosa (ver sección "Lo que sí funciona").

**Causa raíz exacta:**
`EnrollmentController::index()` (`app/Http/Controllers/Enrollment/EnrollmentController.php:40`):
```php
$period = Period::where('status', PeriodStatus::Active)->first();
```
No filtra por `type` de período ni por nivel educativo del estudiante — toma **el primer período con estado "Activo" que encuentre**, sin importar a quién pertenece. Hoy hay **dos períodos simultáneamente "Activo"** en `/scheduling/periods`:
- `2026-I` — tipo Semestral (universitario)
- `2025-2026` — tipo Anual, con 3 Lapsos (escolar/primaria-bachillerato)

`->first()` siempre devuelve `2026-I` (aparentemente por orden de id/inserción). Luego `BuildEnrollmentCatalogAction::handle()` (`app/Actions/Enrollment/BuildEnrollmentCatalogAction.php:25`) filtra las secciones de cada materia del pensum con `->where('period_id', $period->id)` — es decir, busca secciones cuyo `period_id` sea el de `2026-I`. Pero **todas** las Secciones Escolares (`/scheduling/sections/school`) están creadas bajo períodos anuales (`2025-2026`, y los anteriores `2021-2022`..`2024-2025`) — ninguna bajo `2026-I`, porque ese período es exclusivamente universitario. Resultado: 0 coincidencias siempre, para cualquier estudiante Primaria/Bachillerato — **no es un problema de datos faltantes de "este" período puntual, es que el sistema nunca puede resolver el período correcto para estos niveles mientras períodos Anual y Semestral coexistan como "Activo"**, que es justamente el diseño esperado (el año escolar y el semestre universitario corren en paralelo).

**Bonus, mismo origen:** el encabezado de `/enrollment` muestra `"PERÍODO 2026-I · 4TO TRIMESTRE"` incluso para Brooks (Bachillerato) — la palabra "trimestre" está hardcodeada en `EnrollmentController::buildRules()` línea 208 (`"{$student->academic_year}er trimestre"`), sin condicionar por el `type` real del período (que para escolares es Anual/Lapso, no Trimestral).

**Evidencia:**
- `/scheduling/sections/school`: 9 secciones de "Educación Media General (Bachillerato)", ninguna con período `2026-I` (todas en `2021-2022`..`2025-2026`).
- `/scheduling/periods`: `2026-I` (Semestral) y `2025-2026` (Anual) ambos con estado "Activo" al mismo tiempo.

**Fix sugerido:** resolver el período según `Student::educational_level` — p. ej. `Period::where('status', Active)->where('type', $student->educational_level === EducationalLevel::University ? PeriodType::Semester : PeriodType::Year)->first()`, más, para escolares, resolver también el Lapso vigente dentro de ese período anual. Ajustar `buildRules()` para no asumir "trimestre" en niveles con período Anual.

**Impacto en lo pedido:** el representante SÍ puede ver la situación de su estudiante (dashboard funciona), pero el botón "Inscribir" no sirve de nada hoy para Bachillerato/Primaria — llega a una pantalla sin materias que mostrar.

---

## Confirmación en vivo de bugs ya documentados en `specs/qa/backlog.md`

- **HLZ-38** (pendiente, "falta guard de nivel educativo en inscripción"): confirmado en vivo — `sec16` (Bachillerato) pudo cargar `/enrollment` con 200 normal, sin ningún 403, exactamente como describe el backlog. No llegué a probar el `POST` de confirmación porque el catálogo está vacío por HLZ-43, pero el acceso de lectura sin guard queda confirmado.
- **HLZ-40** ("vínculo guardian↔estudiante sin restricción de nivel"): no se pudo ejercitar — no existe en el seed actual ningún estudiante universitario vinculado a un representante, así que no hay forma de observar el problema con los datos actuales (consistente con la nota del propio backlog: "no hay evidencia de que ocurra hoy en datos reales").

---

## Lo que sí funciona correctamente (verificado en vivo, no solo leído en código)

1. **Login multi-rol** (`/login`) — funciona igual para Admin, Estudiante (universitario y escolar) y Representante, con la misma contraseña de seed.
2. **Dashboard del representante** (`/guardian/dashboard`) — carga sin error, lista todos los estudiantes vinculados (probé rep04 con 2), con UC inscritas, % pensum, materias inscritas, y botón "Inscribir/Continuar/Ver inscripción" según el estado real de cada estudiante. Coincide con lo documentado en `specs/qa/guardian/dashboard.md` (UC-G01, UC-G05, UC-G08).
3. **Inscripción universitaria de punta a punta** — con `est041@utcacao.edu.ve`: 6 materias disponibles con secciones reales (horario, profesor, aula, cupo), selección de sección, cambio de sección, cálculo de UC/horas/conflictos en tiempo real, validación de mínimo de 12 UC, y **confirmación exitosa** ("Inscripción confirmada", botón bloqueado). Sin errores en ningún paso.
4. **Panel admin** — dashboard, Usuarios (con filtro por rol), Estudiantes (con filtro por nivel Primaria/Bachillerato/Universitario), Representantes (con detalle de estudiantes a cargo), Secciones Esc./Univ., Períodos — todo carga y filtra correctamente.

---

## Notas menores (no bloquean nada, pero vale la pena registrarlas)

- El dashboard admin muestra `"Período académico 2025-2 · Semana 14"` — ese nombre de período no existe literalmente en `/scheduling/periods` (los nombres reales son `2025-II`, `2025-2026`, etc.). Parece un texto de ejemplo/hardcodeado en el widget, no un dato real. Prioridad baja, solo cosmético.
- No hay ningún estudiante de nivel **Primaria** en los datos de prueba — si quieres que valide ese nivel específicamente (a diferencia de Bachillerato), habría que sembrar al menos un estudiante `primary` con representante.

---

## Sobre "cacao_dev"

Mencionaste una sesión de Claude Code local llamada `cacao_dev` corriendo en `/var/www/cacao` para que coordinemos el arreglo. No pude contactarla desde este entorno (mi herramienta de mensajería entre agentes no la detecta — parece ser un proceso completamente separado de este entorno en la nube). Este archivo y las actualizaciones en `specs/qa/` quedan como el punto de partida para que esa sesión (o tú) los revise directamente.
