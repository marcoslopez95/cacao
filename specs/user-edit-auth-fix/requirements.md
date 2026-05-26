# Requirements — user-edit-auth-fix

**Feature ID:** `user-edit-auth-fix`
**QA Hallazgo:** HLZ-06
**Prioridad:** ALTA — bloquea guardado de S10, S13 y S16 para todos los usuarios Admin reales

---

## Problema

Tres FormRequests en `app/Http/Requests/Admin/` usan `hasAnyRole(['Administrador', 'Coordinador'])` directamente en `authorize()`:

- `StoreStudentLanguageRequest` — guarda S10 (idiomas)
- `StoreStudentBenefitRequest` — guarda S13 (beneficios)
- `StoreGuardianProfileRequest` — guarda S16 (perfil representante)

El rol de administrador en producción se llama **`Admin`** (no `Administrador`). El role `Administrador` no existe en producción. Como resultado:

- Un usuario con rol `Admin` que intente guardar S10 recibe **403 Forbidden**
- Un usuario con rol `Admin` que intente guardar S13 recibe **403 Forbidden**
- Un usuario con rol `Admin` que intente guardar S16 recibe **403 Forbidden**

### Por qué los tests no detectan el bug

`adminForEditUseCase()` en `UserEditUseCaseTest.php` asigna doble rol al admin de prueba:

```php
$user->assignRole('Admin');
$user->assignRole('Administrador'); // ← máscara el bug
```

El admin de test tiene ambos roles, así `hasAnyRole(['Administrador', ...])` devuelve `true`. En producción el admin solo tiene `Admin`, no `Administrador`.

### Patrón correcto existente

El resto de FormRequests del dominio Admin usan `Gate::authorize()`:

```php
// StoreDemographicProfileRequest (correcto)
public function authorize(): bool
{
    Gate::authorize('create', DemographicProfile::class);
    return true;
}
```

`AppServiceProvider` tiene un `Gate::before` que devuelve `true` para usuarios con rol `Admin`:

```php
Gate::before(fn (User $user) => $user->hasRole('Admin') ? true : null);
```

Este bypass solo aplica a llamadas `Gate::authorize()` — **no aplica** a `hasAnyRole()`.

---

## Precondiciones

- Rol `Admin` existe en la tabla `roles`
- `AppServiceProvider` tiene `Gate::before` configurado para `Admin`
- Policies de `StudentLanguage`, `StudentBenefit`, `GuardianProfile` permiten acceso a admins

---

## Criterios de aceptación

1. Un usuario con solo el rol `Admin` (sin `Administrador`) puede guardar S10, S13 y S16 (status 200, no 403)
2. Un usuario con rol `Coordinador` puede guardar S16 (preservar acceso existente)
3. Un guardian puede editar su propio perfil S16 (preservar acceso existente)
4. `adminForEditUseCase()` en los tests solo asigna `Admin` (un solo rol)
5. Tests específicos confirman el bug no regresa

---

## Archivos afectados

- `app/Http/Requests/Admin/StoreStudentLanguageRequest.php`
- `app/Http/Requests/Admin/StoreStudentBenefitRequest.php`
- `app/Http/Requests/Admin/StoreGuardianProfileRequest.php`
- `tests/Feature/Security/UserEditUseCaseTest.php`
