# HLZ-29 — Tasks

## Task 1 — Fix backend: study_date nullable [x]

**Archivos a modificar:**
1. `app/Http/Requests/Admin/StoreSocioeconomicProfileRequest.php`
   - Línea `'study_date' => ['required', 'date']` → `['nullable', 'date']`

2. `app/Http/Wrappers/Admin/SocioeconomicProfileWrapper.php`
   - `getStudyDate(): string` → `getStudyDate(): ?string`

3. `app/Actions/Admin/UpsertSocioeconomicProfileAction.php`
   - Añadir `use Illuminate\Support\Carbon;`
   - `'study_date' => $wrapper->getStudyDate()` → `$wrapper->getStudyDate() ?? Carbon::today()->toDateString()`

**Verificación:**
- `vendor/bin/sail bin pint --dirty --format agent`
- `vendor/bin/sail artisan test --compact --filter=SocioeconomicProfile`

## Task 2 — Test feature: acceptance tests para RF-01 a RF-04 [x]

Crear `tests/Feature/HLZ29SocioeconomicFix/Acceptance/SocioeconomicS12AcceptanceTest.php`

Tests a escribir:
- RF-01: POST sin `study_date` → HTTP 200
- RF-02: POST sin `study_date` → DB tiene `study_date = today()`
- RF-03: POST con `study_date` válido → DB tiene ese valor exacto
- RF-04: POST con `study_date` inválido (string no-fecha) → HTTP 422

**Verificación:**
- `vendor/bin/sail artisan test --compact --filter=SocioeconomicS12Acceptance`
- Confirmar que el test Dusk UC-S12 de `UserEditS08S15AcademicTest.php` pasa:
  `vendor/bin/sail dusk tests/Browser/Security/UserEditS08S15AcademicTest.php --filter=S12`
