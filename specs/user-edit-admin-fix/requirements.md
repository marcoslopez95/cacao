# Requirements — user-edit-admin-fix

**Feature ID:** `user-edit-admin-fix`
**QA Hallazgos:** HLZ-14
**Prioridad:** ALTA — cada save de S04 por un admin destruye `religion_id` silenciosamente

---

## Problema

`DemographicProfileResource::toArray()` contiene un guard de privilegio:

```php
$isPrivileged = $request->user()->hasAnyRole(['Administrador', 'Coordinador']);
// ...
'religion_id' => $this->when($isPrivileged, $this->religion_id),
```

El rol de administrador en producción se llama `Admin` (no `Administrador`). La tabla de roles no tiene ningún rol llamado `Administrador` en producción. Resultado para un usuario con rol `Admin`:
- `hasAnyRole(['Administrador', 'Coordinador'])` → `false`
- `religion_id` → ausente del array resolve (el `when()` lo elimina del output)
- `buildInitialFormData()`: `d.religionId = p.religion_id ?? undefined` → `undefined`
- `saveDemographic()`: envía `religion_id: formData.religionId ?? null` → `null`
- `StoreDemographicProfileRequest`: `religion_id` es `nullable` → acepta `null` sin error
- **Resultado: cualquier save de S04 por un admin pone `religion_id = NULL` en DB**, sin ningún error visible

Este es el mismo patrón de bug que HLZ-06 (que fue resuelto en `user-edit-auth-fix` para FormRequests), pero aquí el bug está en un **Resource** (resultado: corrupción silenciosa de datos, no 403).

---

## Análisis del guard de privilegio

La intención del guard es mostrar `religion_id` solo para usuarios con acceso privilegiado. En el contexto de edición (`/security/users/{user}/edit`), los admins y coordinadores editan a los usuarios, y necesitan ver y actualizar `religion_id`.

El fix mínimo y correcto: incluir `Admin` en la lista. El fix estructural correcto: usar `Gate::check()` o alinear con el patrón de `AppServiceProvider` que registra `Gate::before()` para el rol `Admin`.

**Fix elegido:** Agregar `'Admin'` a la lista `hasAnyRole()`. Mantener `'Administrador'` y `'Coordinador'` por compatibilidad con posibles roles adicionales. Esto es el fix más simple y sin riesgo de regresión.

---

## Precondiciones

- `DemographicProfileResource` está en `app/Http/Resources/Admin/DemographicProfileResource.php`
- El rol `Admin` existe en la tabla `roles` (Spatie)
- `buildInitialFormData()` ya lee `p.religion_id` correctamente (fue corregido en `user-edit-catalog-ids`)
- `saveDemographic()` ya envía `religion_id: formData.religionId ?? null` correctamente

---

## Criterios de aceptación

1. **RF-01** — Un usuario con rol `Admin` carga `/security/users/{user}/edit` y `props.demographicProfile.religion_id` está presente en el response (no ausente).
2. **RF-02** — Un usuario con rol `Admin` guarda S04 sin cambiar el campo de religión → `demographic_profiles.religion_id` permanece con su valor previo en DB (no se nullifica).
3. **RF-03** — Un usuario con rol `Admin` guarda S04 con `religion_id` seleccionado → `demographic_profiles.religion_id` se actualiza al nuevo valor en DB.
4. **RF-04** — Test: "Admin puede guardar S04 sin que religion_id sea nullificado".

---

## Archivos afectados

**Backend (PHP):**
- `app/Http/Resources/Admin/DemographicProfileResource.php` — línea 15: agregar `'Admin'` a la lista `hasAnyRole()`

**Tests:**
- `tests/Feature/Security/UserEditUseCaseTest.php` — agregar test que verifica RF-02 y RF-03
