# Design — user-edit-admin-fix

**Feature ID:** `user-edit-admin-fix`

---

## El cambio

**Archivo:** `app/Http/Resources/Admin/DemographicProfileResource.php`

**Línea 15 (actual):**
```php
$isPrivileged = $request->user()->hasAnyRole(['Administrador', 'Coordinador']);
```

**Línea 15 (corregida):**
```php
$isPrivileged = $request->user()->hasAnyRole(['Admin', 'Administrador', 'Coordinador']);
```

**Justificación:**
- `Admin` es el nombre real del rol en producción (Spatie role name)
- `Administrador` se mantiene por compatibilidad hacia atrás (no rompe nada)
- `Coordinador` se mantiene sin cambio

El mismo fix se aplica a la línea que construye el objeto `religion` anidado (línea 54 circa):
```php
'religion' => $this->when(
    $isPrivileged,  // ← usa la misma variable, no requiere cambio adicional
    $this->whenLoaded('religion', ...)
),
```

---

## Por qué no usar Gate::check()

El patrón `Gate::before()` en `AppServiceProvider` registra un bypass para el rol `Admin`:
```php
Gate::before(fn (User $user) => $user->hasRole('Admin') ? true : null);
```

Esto aplica a calls `Gate::authorize()` y `$user->can()`. Sin embargo, en un Resource, usar `$request->user()->can('viewReligion', $this->resource)` requeriría crear una Policy nueva para DemographicProfile con un método `viewReligion()`. Esto sería un cambio mayor sin beneficio real sobre el fix simple.

El fix `hasAnyRole(['Admin', ...])` es suficiente, seguro y alineado con el patrón ya existente en el Resource.

---

## Impacto

| Archivo | Cambio |
|---------|--------|
| `DemographicProfileResource.php` | 1 string cambiado en la lista hasAnyRole |
| `UserEditUseCaseTest.php` | nuevo test |

Sin migraciones. Sin cambios en frontend. Sin cambios en otros archivos PHP.
