# Design — user-edit-auth-fix

**Feature ID:** `user-edit-auth-fix`

---

## Decisión de diseño

### Reemplazar `hasAnyRole()` por `Gate::authorize()`

El patrón canónico del proyecto para FormRequests de Admin es:

```php
public function authorize(): bool
{
    Gate::authorize('create', ModelClass::class);
    return true;
}
```

`Gate::authorize()` dispara la Policy del modelo. El `Gate::before` en `AppServiceProvider` hace bypass para `Admin` antes de que llegue a la Policy. Esto garantiza que usuarios Admin siempre tengan acceso, y la Policy puede definir reglas adicionales por rol (Coordinador, propietario del recurso, etc.).

### Policies implicadas

| FormRequest | Policy a usar | Modelo |
|---|---|---|
| `StoreStudentLanguageRequest` | Policy de `StudentLanguage` o acceso via `Student` | `StudentLanguage::class` |
| `StoreStudentBenefitRequest` | Policy de `StudentBenefit` o acceso via `Student` | equivalente |
| `StoreGuardianProfileRequest` | Policy de `GuardianProfile` | `GuardianProfile::class` |

**Nota:** Para `StoreStudentLanguageRequest` y `StoreStudentBenefitRequest`, la acción equivalente es `create` (agregar un item al pivote). El método de Policy debe ser `create`. Como `Gate::before` hace el bypass para Admin antes de evaluar la Policy, solo importa que la llamada sea `Gate::authorize('create', Model::class)`.

Para `StoreGuardianProfileRequest` el caso es más complejo: un guardian puede editar su propio perfil (no solo el admin). El diseño correcto es:
1. Usar `Gate::authorize('update', $guardian)` o `Gate::authorize('create', GuardianProfile::class)`
2. La Policy de `GuardianProfile` ya maneja acceso del propio guardian

### Corrección del helper de test

`adminForEditUseCase()` debe asignar **solo** el rol `Admin`. El rol `Administrador` en los tests era un parche para ocultar el bug. Eliminarlo hace que los tests sean representativos de producción.

### Tests nuevos

Agregar en `UserEditUseCaseTest.php`:
- `S10 languages: Admin (solo rol Admin) puede guardar idiomas`
- `S13 benefits: Admin (solo rol Admin) puede guardar beneficios`
- `S16 guardian profile: Admin (solo rol Admin) puede guardar perfil de representante`

---

## Archivos a tocar

| Archivo | Cambio |
|---|---|
| `app/Http/Requests/Admin/StoreStudentLanguageRequest.php` | `authorize()`: reemplazar `hasAnyRole()` por `Gate::authorize('create', ...)` |
| `app/Http/Requests/Admin/StoreStudentBenefitRequest.php` | `authorize()`: reemplazar `hasAnyRole()` por `Gate::authorize('create', ...)` |
| `app/Http/Requests/Admin/StoreGuardianProfileRequest.php` | `authorize()`: reemplazar `hasAnyRole(['Administrador', 'Coordinador'])` por `Gate::authorize()` + check de propio guardian |
| `tests/Feature/Security/UserEditUseCaseTest.php` | Eliminar `$user->assignRole('Administrador')` en `adminForEditUseCase()` + agregar tests de regresión |

---

## Patrón de referencia

`StoreDemographicProfileRequest`:

```php
use App\Models\DemographicProfile;
use Illuminate\Support\Facades\Gate;

public function authorize(): bool
{
    Gate::authorize('create', DemographicProfile::class);
    return true;
}
```

Para `StoreGuardianProfileRequest` (preservar acceso del propio guardian):

```php
public function authorize(): bool
{
    $guardian = $this->route('guardian');
    $user = $this->user();

    // El propio guardian puede editar su perfil
    if ($user->guardian?->id === $guardian->id) {
        return true;
    }

    // Admins y coordinadores pasan por Gate (Gate::before hace bypass para Admin)
    Gate::authorize('update', $guardian);
    return true;
}
```

---

## Impacto

- Sin migraciones, sin cambios de schema
- Sin cambios de rutas ni wayfinder
- Solo backend PHP + tests
- Pint sobre los tres FormRequests modificados
