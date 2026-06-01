# HLZ-29 — Socioeconomic Profile S12: study_date required causa 500

## Problema

`PUT /security/students/{student}/socioeconomic-profile` devuelve **500** cuando el
frontend no envía el campo `study_date`. La UI de S12 no muestra ese campo al
usuario normal — es un campo de gestión interna (admin only) que el composable no
incluye en el payload del save.

**Causa raíz:**
- `StoreSocioeconomicProfileRequest` declara `study_date => ['required', 'date']`
- El frontend no lo envía → validación 422 o el campo llega como `null` y el wrapper
  lanza tipo incorrecto → 500
- La columna `socioeconomic_profiles.study_date` es `NOT NULL` sin default en DB

**Evidencia:** test Dusk `UserEditS08S15AcademicTest.php` → UC-S12 failing.

## Requisitos funcionales

- RF-01: `PUT /security/students/{student}/socioeconomic-profile` sin `study_date`
  en el payload devuelve HTTP 200 (no 422, no 500)
- RF-02: cuando `study_date` no se envía, la columna en DB queda con la fecha actual
  (`today()`) como valor — no con null (columna NOT NULL)
- RF-03: cuando `study_date` sí se envía con un valor válido, ese valor se persiste
  (comportamiento existente sin cambio)
- RF-04: el test Dusk UC-S12 de `UserEditS08S15AcademicTest.php` pasa en verde
