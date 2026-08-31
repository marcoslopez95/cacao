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

---

# Ronda 2 — QA de asistencia como "profesor" (adelantos, recuperación, restricción horaria)

**Fecha:** 2026-08-31
**Probado por:** Claude (Cowork), a pedido de Manu
**Usuario de prueba:** `prof02@utcacao.edu.ve` (Ana García), sección Microeconomía (`section_id=3`)

Pediste probar el módulo de asistencia como profesor, verificando específicamente: (1) que un profesor **solo** pueda marcar asistencia en la clase que tiene actualmente, y (2) la lógica de clases "adelantadas" y "de recuperación", que sospechabas con problemas. Las tres sospechas se confirmaron — dos de código (revisión estática) y una además reproducida en vivo con datos reales, incluyendo un intento deliberado de "doble adelanto" que corrompió silenciosamente la asistencia de la sesión objetivo. Quedaron propuestos como **HLZ-44**, **HLZ-45** y **HLZ-46** en `specs/qa/backlog.md`, con el detalle narrativo aquí.

| Sospecha del usuario | Resultado |
|---|---|
| "Un profesor solo puede marcar asistencia en la clase que tiene actualmente" | 🔴 Falso — no existe ninguna restricción horaria, en ningún nivel (Policy/Request/Action). Confirmado en vivo: se creó y se pasó lista a una sesión con fecha ~4 meses en el futuro, al instante. **HLZ-44** |
| "Problemas de lógica en clases adelantadas" | 🔴 Confirmado y reproducido en vivo — se puede "adelantar" la misma sesión futura dos veces; la segunda pisa silenciosamente la asistencia copiada por la primera, sin ningún error ni aviso. **HLZ-45** (crítica) |
| "Problemas de lógica en clases de recuperación" | 🔴 Confirmado, pero por ausencia total del flujo — no existe en todo el código ningún camino que ponga una sesión en `status: cancelled`, que es el único candidato que el selector "Sesión vinculada" de Recuperación acepta. El selector está **siempre vacío**, para cualquier sección. **HLZ-46** |

---

## 🔴 HLZ-44 (nuevo) — Ninguna restricción horaria/de fecha al crear sesiones o pasar asistencia

**Severidad:** Alta — contradice directamente la regla de negocio que describiste ("un profesor solo puede marcar asistencia en la clase que tiene actualmente").

**Reproducción en vivo:**
1. Como `prof02` → Microeconomía → "Nueva sesión" → tipo Regular, fecha `15/12/2026` (~3.5 meses en el futuro respecto al reloj del servidor) → se crea sin ninguna advertencia.
2. Esa sesión aparece **inmediatamente** etiquetada como "CLASE DE HOY · PENDIENTE" en el panel principal de la sección, con el botón "Pasar lista" habilitado.
3. Se pasó lista sin ningún bloqueo (2 presente, 1 ausente) — la sesión pasó a `status: held`.
4. Efecto secundario confirmado: al guardar la asistencia, `held_at` se sobrescribió silenciosamente con la fecha real del servidor (`hoy()->format('Y-m-d')`), **descartando la fecha `15/12/2026` que se había elegido al crear la sesión** — se verificó visualmente el cambio de "sábado 12 de dic de 2026" a la fecha real del día.

**Causa raíz exacta:**
- `app/Policies/ClassSessionPolicy.php` — los 4 métodos (`viewAny`, `create`, `update`, `takeAttendance`) solo verifican `$section->main_teacher_id === $professor->id`. Ninguno compara fecha/hora contra el horario (`Schedule`) de la sección.
- `app/Http/Requests/Professor/StoreClassSessionRequest.php` — reglas de validación (`type`, `linked_session_id`, `topic`, `held_at`) sin ninguna regla de fecha/hora.
- `app/Actions/Attendance/CreateClassSessionAction.php` y `TakeAttendanceAction.php` — ninguno valida la fecha de la sesión contra el horario real ni contra "ahora".
- La columna `schedule_id` que `specs/15-attendance-module/design.md` documenta como parte del diseño (línea 36, vínculo `class_sessions.schedule_id → schedules`) **no existe** en la migración real (`database/migrations/2026_06_01_171450_create_class_sessions_table.php`) ni en `ClassSessionWrapper` (sin `getScheduleId()`) — es decir, una `class_session` no tiene ningún vínculo estructural con el `Schedule` que dice representar, así que no hay ni siquiera el dato necesario para construir la validación horaria.
- Curiosamente, la lógica para *saber* si una sesión es "la clase actual" ya existe, pero solo a nivel de **presentación**, no de autorización: `app/Http/Controllers/Professor/DashboardController.php:59-60` calcula `is_current` comparando `$currentTime` contra `$schedule->start_time`/`end_time` — ese mismo criterio nunca se reutiliza en `ClassSessionPolicy` ni en ningún FormRequest.

**Evidencia:**
- Sesión "Sesion regular en fecha arbitraria (prueba QA - no es la clase actual)" creada con fecha `15/12/2026`, mostrada como "CLASE DE HOY" y con asistencia tomada sin bloqueo — capturas de pantalla durante la sesión.
- `held_at` sobrescrito de la fecha elegida a la fecha real del servidor tras pasar lista (código en `TakeAttendanceAction.php:31`).

**Acción sugerida:**
1. Agregar `schedule_id` real a `class_sessions` (tal como ya lo documenta `design.md` pero nunca se implementó), para poder vincular cada sesión a un horario concreto.
2. En `ClassSessionPolicy::takeAttendance` (y opcionalmente `create`), reutilizar el mismo cálculo de ventana horaria que ya existe en `Professor\DashboardController` (`$currentTime >= start_time && $currentTime <= end_time`, sobre el día correspondiente) para autorizar solo si la sesión corresponde al horario y franja horaria vigentes — o, como alternativa menos estricta, solo el día correspondiente.
3. Decidir con el humano si esta restricción debe ser dura (403) o blanda (advertencia + permiso de forzar, con auditoría) — hay flujos legítimos (recuperación tardía de asistencia, por ejemplo) donde una restricción 100% dura podría ser indeseable; de cualquier forma, la restricción declarada por el usuario debe existir en algún nivel, y hoy no existe en ninguno.
4. Test de regresión: profesor intenta pasar lista/crear sesión en una fecha/hora fuera de su horario real → debe rechazarse (o marcarse explícitamente como excepcional), no aceptarse en silencio.

**Estado:** pendiente
**Prioridad:** ALTA

---

## 🔴 HLZ-45 (nuevo) — Doble "Adelanto" sobre la misma sesión futura corrompe silenciosamente la asistencia copiada (CRÍTICO)

**Severidad:** Crítica — corrupción de datos de asistencia sin ningún error visible al usuario.

`specs/15-attendance-module/requirements.md` documenta explícitamente el comportamiento esperado para este caso en su tabla de "Casos de error": *"Crear advance vinculado a una sesión que ya está marcada `advanced`"* → *"Validación: 'Esta sesión ya fue adelantada'"*. Esa validación **no existe** en el código — y se confirmó en vivo que su ausencia permite corromper datos reales.

**Reproducción en vivo (paso a paso, con IDs reales):**
1. Se creó una sesión Regular futura ("Sesion futura regular - target...", `session_id=7`, fecha 2027-01-03, `status: scheduled`) como objetivo del adelanto.
2. Se creó "Adelanto #1" (`session_id=8`) vinculado a la sesión 7 desde el selector normal de la UI ("Sesión vinculada"). Al crearse, la sesión 7 pasó inmediatamente a `status: advanced` — **antes incluso de pasar lista en el adelanto**, es decir, el cambio de estado ocurre en la creación (`CreateAdvanceSessionAction`), no cuando se toma asistencia.
3. Se pasó lista en "Adelanto #1" marcando a un estudiante (Marlen Kihn Conroy) ausente. Se confirmó que esos registros se copiaron correctamente a la sesión 7 (`TakeAttendanceAction::maybeCopyRecordsToLinkedSession`).
4. Se intentó crear un **segundo** adelanto ("Nueva sesión" → Adelanto) vinculado a la misma sesión 7: el selector "Sesión vinculada" de la UI **ya no la ofrece como opción** (queda vacío, sin candidatos) — es decir, la única protección contra el doble-adelanto es un filtro del lado de presentación (la consulta que arma la lista de sesiones candidatas excluye las que ya están `advanced`), no una validación real en el backend.
5. Se confirmó que el backend **no** tiene esa validación: se envió directamente la petición `POST /professor/sections/3/attendance/sessions` con `type=advance` y `linked_session_id=7` (saltando el filtro de la UI). La petición **se aceptó sin error**, creando "Adelanto #2" (`session_id=9`) también vinculado a la sesión 7, y la sesión 7 volvió a marcarse `advanced` (su `linked_session_id` interno pasó de apuntar a 8 a apuntar a 9, silenciosamente).
6. Se pasó lista en "Adelanto #2" con un patrón de asistencia **distinto** (Rhoda Carter Abernathy ausente, Marlen Kihn Conroy presente). Al guardar, esto **volvió a copiarse a la sesión 7**, pisando los registros que había dejado "Adelanto #1" un paso antes: Marlen pasó de "ausente" (según Adelanto #1) a "presente" (según Adelanto #2) en la sesión 7, sin ningún conflicto, advertencia ni rastro de que hubo una versión anterior distinta.
7. Resultado final verificado: "Adelanto #1" y "Adelanto #2" conservan cada uno su propia asistencia original (correcta, cada uno "Dada" con su propio detalle), pero la sesión 7 —la que en teoría representa "la clase real que se adelantó"— quedó con la asistencia de **el último adelanto guardado**, sin ninguna forma de saber, desde la UI, que hubo un adelanto anterior cuya asistencia fue descartada.

**Causa raíz exacta:**
- `app/Actions/Attendance/CreateAdvanceSessionAction.php::handle()` no valida el estado de la sesión vinculada antes de crear el adelanto — a diferencia de `app/Actions/Attendance/CreateMakeupSessionAction.php::handle()`, que sí valida explícitamente:
  ```php
  if ($linkedSession !== null && $linkedSession->status === ClassSessionStatus::Recovered) {
      throw ValidationException::withMessages(['linked_session_id' => ['Esta sesión ya fue recuperada.']]);
  }
  ```
  El equivalente para `Advance`/`Advanced` simplemente no existe.
- `app/Actions/Attendance/TakeAttendanceAction.php::maybeCopyRecordsToLinkedSession()` copia usando `AttendanceRecord::updateOrCreate(['class_session_id' => $session->linked_session_id, 'enrollment_detail_id' => ...], ['status' => ...])` — como hay una restricción única en `(class_session_id, enrollment_detail_id)` (`attendance_records` migration), cada copia **actualiza en el lugar** el registro anterior en vez de detectar el conflicto. No hay ningún control de "última escritura gana silenciosamente" — que es exactamente lo que ocurrió.
- El campo `linked_session_id` de la sesión objetivo (7) también se sobrescribe en cada nueva creación de adelanto (`ClassSession::where('id', $linkedSessionId)->update([..., 'linked_session_id' => $newSession->id])`), así que además se pierde la referencia hacia el primer adelanto — la sesión 7 ya no "sabe" que Adelanto #1 existió.

**Evidencia:**
- Sesiones reales creadas durante la prueba: `session_id=7` (target, Adelantada), `session_id=8` (Adelanto #1, ausente: Marlen Kihn Conroy), `session_id=9` (Adelanto #2, ausente: Rhoda Carter Abernathy) — ambas vinculadas a la sesión 7.
- Capturas de pantalla de la asistencia de la sesión 7 **antes** de guardar Adelanto #2 (Marlen ausente) y **después** (Marlen presente, Rhoda ausente) — el cambio ocurre únicamente por guardar la asistencia de Adelanto #2, sin tocar la sesión 7 directamente.
- `specs/15-attendance-module/requirements.md`, tabla "Casos de error": describe la validación esperada ("Esta sesión ya fue adelantada") que no está implementada — vale la pena que `cacao_dev` revise ese documento porque promete un comportamiento que el código no cumple.

**Acción sugerida:**
1. Portar a `CreateAdvanceSessionAction::handle()` el mismo patrón de guard que ya existe en `CreateMakeupSessionAction::handle()`: si la sesión vinculada ya tiene `status: advanced` (o ya tiene un `linked_session_id` distinto de null apuntando a una sesión no descartada), rechazar con `ValidationException` ("Esta sesión ya fue adelantada").
2. Considerar una restricción adicional a nivel de dominio (no solo el filtro de UI) para que un mismo `linked_session_id` no pueda ser destino de más de un `class_session` de tipo `advance`/`makeup` activo simultáneamente.
3. Test de regresión: crear un adelanto vinculado a una sesión ya `advanced` (vía request directo, no solo vía UI) → debe responder con error de validación, no 200/201.
4. Test de regresión adicional: si de alguna forma llegaran a coexistir dos adelantos sobre el mismo target (por ejemplo, datos legados), pasar lista en ambos no debería perder silenciosamente los registros del primero — al menos debería quedar log/auditoría de la sobreescritura.

**Estado:** pendiente
**Prioridad:** CRÍTICA (corrupción de datos de asistencia sin ningún aviso al usuario, reproducida en vivo con datos reales)

---

## 🔴 HLZ-46 (nuevo) — El flujo de "Recuperación" es inalcanzable en la práctica: nada en el código pone una sesión en `status: cancelled`

**Severidad:** Alta — funcionalidad completa (recuperación de clases) inutilizable desde la UI normal, para cualquier sección.

**Reproducción:** en Microeconomía (con 4 sesiones ya creadas, de distintos tipos y estados), "Nueva sesión" → tipo "Recuperación" → el selector "Sesión vinculada" (que su propio texto de ayuda describe como *"Elegí la sesión cancelada que se está recuperando"*) aparece **siempre vacío** — sin ningún candidato, sin importar cuántas sesiones existan en la sección.

**Causa raíz exacta:** se buscó en todo el código (`app/`, `routes/web.php`) cualquier referencia a poner una `class_session` en `status: cancelled` (o similar) — **no existe ninguna**. Ni `Professor\AttendanceController`, ni `Admin\AttendanceController` (que tienen los mismos 4 endpoints — index/storeSession/sheet/upsertAttendance — sin ningún endpoint de cancelación), ni ninguna Action, expone una forma de cancelar una sesión programada. El enum de estados (`scheduled | held | cancelled | recovered | advanced`, según `specs/15-attendance-module/design.md`) y `CreateMakeupSessionAction` (que sí valida correctamente contra sesiones ya `Recovered`) están implementados asumiendo que en algún momento existirán sesiones `cancelled` — pero el paso previo que las genera nunca se construyó. En la práctica, esto significa que el selector de Recuperación jamás tendrá nada que ofrecer en un ambiente real (fuera de datos sembrados manualmente para tests, como hace `specs/15-attendance-module/qa.md` en su precondición "Existe una sesión con `status: cancelled`").

**Evidencia:**
- Búsqueda de `cancel` (insensible a mayúsculas) en todo `app/` y `routes/web.php`: cero resultados de código que asigne `status: cancelled`.
- `specs/15-attendance-module/design.md:61-63` documenta el flujo "Cancelada" como paso previo a la recuperación, pero no hay controller/route/action que lo implemente.
- `specs/15-attendance-module/qa.md:36` — el propio test Dusk que valida el flujo de recuperación arranca desde una precondición sembrada directamente en base de datos ("Existe una sesión con `status: cancelled`"), no desde un flujo real de la aplicación — es decir, el test pasa, pero no porque el flujo completo (cancelar → recuperar) sea alcanzable por un usuario real.

**Acción sugerida:**
1. Implementar la acción de cancelar sesión (probablemente para Profesor y/o Admin/Coordinador): un endpoint que transicione una sesión `scheduled` a `cancelled`, que es el prerequisito real que le falta a la Recuperación para ser usable.
2. Revisar con `cacao_dev` si esto fue simplemente pospuesto (feature parcialmente implementada) o si hay otra vía prevista para llegar a `cancelled` que no encontré — vale la pena que la sesión de desarrollo confirme antes de asumir que falta construirlo desde cero.
3. Una vez exista el flujo de cancelación, volver a probar en vivo el selector de Recuperación (hoy no se pudo ejercitar ningún UC de este tipo por falta de datos alcanzables).

**Estado:** pendiente
**Prioridad:** ALTA

---

## Notas adicionales de esta ronda (menores / fuera del pedido original, pero detectadas en el camino)

- **Dashboard del profesor muestra "Horas / Semana: -25"** (`/professor/dashboard`) — valor negativo, claramente un bug de cálculo o de datos. `Professor\DashboardController::index()` suma `Carbon::parse(end_time)->diffInMinutes(start_time) / 60` sobre todos los `Schedule` del profesor — si algún `Schedule` tiene `end_time` anterior a `start_time` (dato mal cargado), `diffInMinutes` puede devolver un valor negativo según el orden de los argumentos. Vale la pena revisar los `Schedule` reales del profesor de prueba (`prof02`) para encontrar la fila con el dato invertido.
- **Dos secciones del mismo profesor con el mismo horario exacto** — "Microeconomía" e "Ingeniería de Software II" aparecen ambas programadas 13:00–15:30 el mismo día para `prof02` — no es un bug de código, sino un problema de calidad de datos en `schedules` (o su seeder), pero produce una UI confusa (¿cuál es "la clase actual" si hay dos al mismo tiempo?).
- **`/admin/attendance*` sin middleware `role:Admin`** — a diferencia de otros grupos de rutas admin (que sí aplican `role:Admin` explícitamente), el grupo `Route::middleware(['auth', 'verified'])->prefix('admin')->group(...)` que contiene las rutas de asistencia (`routes/web.php:171-176`) no restringe por rol a nivel de middleware; depende enteramente de que `ClassSessionPolicy` (con su `Gate::before` para Admin, según los comentarios del propio archivo) haga el trabajo. En la práctica no parece explotable hoy (un profesor o estudiante autenticado sin perfil de profesor sería rechazado por la Policy igual), pero rompe el patrón de "defensa en profundidad" que sí siguen otros módulos admin — vale la pena agregar `role:Admin` explícito por consistencia.
- Estas notas no estaban en el pedido original de esta ronda, pero se dejan registradas por si sirven de contexto adicional para `cacao_dev`.
