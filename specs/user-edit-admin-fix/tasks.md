# Tasks — user-edit-admin-fix

**Feature:** `user-edit-admin-fix`
**Scope:** 1 archivo PHP + 1 test
**Depends on:** ninguno (independiente)
**HLZs resueltos:** HLZ-14

---

## Task 01 — Fix DemographicProfileResource: agregar 'Admin' a hasAnyRole

- [x] Abrir `app/Http/Resources/Admin/DemographicProfileResource.php`
- [x] Cambiar la línea 15:
  - ANTES: `$isPrivileged = $request->user()->hasAnyRole(['Administrador', 'Coordinador']);`
  - DESPUÉS: `$isPrivileged = $request->user()->hasAnyRole(['Admin', 'Administrador', 'Coordinador']);`
- [x] Verificar que las líneas que usan `$isPrivileged` no requieren ningún otro cambio (usan la misma variable)
- [x] Ejecutar `vendor/bin/sail bin pint --dirty --format agent` sobre `DemographicProfileResource.php`

**Acceptance:** `(new DemographicProfileResource($profile))->resolve()` devuelve `religion_id` no nulo para un usuario con rol `Admin`.

---

## Task 02 — Test: Admin puede guardar S04 sin destruir religion_id

- [x] Abrir `tests/Feature/Security/UserEditUseCaseTest.php`
- [x] Agregar test `it('admin can save S04 demographic without nullifying religion_id')`
  - Crear usuario admin con solo el rol `Admin` (sin `Administrador`)
  - Crear usuario target con `demographicProfile` que tiene `religion_id` no nulo
  - Cargar la página de edición y verificar que `demographic_profile.religion_id` está presente
  - Hacer `PUT /security/users/{user}/demographic-profile` con `religion_id` no nulo
  - Aserta HTTP 200
  - Aserta que `demographic_profiles.religion_id` en DB es el valor correcto (no NULL)
- [x] Ejecutar `vendor/bin/sail artisan test --compact --filter=UserEditUseCaseTest`

**Acceptance:** Test pasa en verde. Suite completa de `UserEditUseCaseTest` sigue en verde.
