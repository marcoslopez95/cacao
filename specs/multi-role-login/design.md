# Design — Multi-Role Login

**Feature:** `05-multi-role-login`

---

## Decisiones de arquitectura

### 1. Lógica de redirección — trait `ResolvesLoginRedirect`

`LoginResponse` y `TwoFactorLoginResponse` comparten la misma lógica de redirección.
Se extrae a un trait para evitar duplicación:

```php
// app/Http/Responses/Concerns/ResolvesLoginRedirect.php
trait ResolvesLoginRedirect
{
    private function resolveRedirect(Request $request): string
    {
        $user = $request->user();
        $team = $user?->currentTeam ?? $user?->personalTeam();

        if ($team) {
            URL::defaults(['current_team' => $team->slug]);
            return "/{$team->slug}" . Fortify::redirects('login');
        }

        return match (true) {
            $user?->hasAnyRole(['Profesor', 'Coordinador de Area']) => route('professor.dashboard'),
            $user?->hasRole('Estudiante')                          => route('student.dashboard'),
            $user?->hasRole('Representante')                       => route('guardian.dashboard'),
            default                                                 => route('home'),
        };
    }
}
```

Ambos `LoginResponse` y `TwoFactorLoginResponse` usan el trait y llaman `$this->resolveRedirect($request)`.

### 2. Middleware `EnsureRole`

```php
// app/Http/Middleware/EnsureRole.php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    abort_if(! $request->user()?->hasAnyRole($roles), 403);
    return $next($request);
}
```

Registrado en `bootstrap/app.php` como alias `role`.
Uso en rutas: `->middleware(['auth', 'verified', 'role:Profesor,Coordinador de Area'])`

### 3. Grupos de rutas

```php
// routes/web.php

Route::middleware(['auth', 'verified', 'role:Profesor,Coordinador de Area'])
    ->prefix('professor')->name('professor.')
    ->group(function () {
        Route::get('dashboard', [Professor\DashboardController::class, 'index'])
            ->name('dashboard');
    });

Route::middleware(['auth', 'verified', 'role:Estudiante'])
    ->prefix('student')->name('student.')
    ->group(function () {
        Route::get('dashboard', [Student\DashboardController::class, 'index'])
            ->name('dashboard');
    });

Route::middleware(['auth', 'verified', 'role:Representante'])
    ->prefix('guardian')->name('guardian.')
    ->group(function () {
        Route::get('dashboard', [Guardian\DashboardController::class, 'index'])
            ->name('dashboard');
    });
```

### 4. Controladores de dashboard

Tres controladores simples, uno por portal:

```
app/Http/Controllers/Professor/DashboardController.php
app/Http/Controllers/Student/DashboardController.php
app/Http/Controllers/Guardian/DashboardController.php
```

Cada uno retorna `Inertia::render('{portal}/Dashboard')` sin props adicionales.
El nombre del usuario llega vía el `auth.user` ya compartido en `HandleInertiaRequests`.

### 5. Páginas Vue de dashboard

```
resources/js/pages/professor/Dashboard.vue
resources/js/pages/student/Dashboard.vue
resources/js/pages/guardian/Dashboard.vue
```

Estructura común:
- Usa `AppLayout`
- Saludo: "Bienvenido/a, {nombre}"
- Mensaje descriptivo del portal
- Sin widgets ni estadísticas por ahora

### 6. `AppSidebar.vue` — navegación por rol

El computed `navGroups` se refactoriza para detectar el rol del usuario desde `auth.roles`:

```typescript
const role = computed(() =>
    page.props.auth?.roles?.includes('Admin') ? 'admin'
    : page.props.auth?.roles?.some(r => ['Profesor', 'Coordinador de Area'].includes(r)) ? 'professor'
    : page.props.auth?.roles?.includes('Estudiante') ? 'student'
    : page.props.auth?.roles?.includes('Representante') ? 'guardian'
    : 'unknown'
)
```

**Grupos por rol:**

| Rol | Grupos visibles |
|---|---|
| `admin` | General, Seguridad (perms), Académico (perms), Infraestructura (perms), Horarios (perms), Inscripciones, Mi cuenta |
| `professor` | General (`/professor/dashboard`), Mi cuenta |
| `student` | General (`/student/dashboard`), Inscripciones → Mi inscripción, Mi cuenta |
| `guardian` | General (`/guardian/dashboard`), Mi cuenta |

El enlace al dashboard del sidebar usa el Wayfinder generado para la ruta `professor.dashboard`, `student.dashboard`, etc.

---

## Flujo de login post-fix

```
POST /login
  → FortifyServiceProvider::authenticateUsing()
      validar credenciales + verificar activo
  → LoginResponse::toResponse()
      → ResolvesLoginRedirect::resolveRedirect()
          ¿tiene team? → /{team}/dashboard    (Admin)
          rol Profesor/Coordinador? → /professor/dashboard
          rol Estudiante? → /student/dashboard
          rol Representante? → /guardian/dashboard
          sin rol? → /
```

---

## Archivos involucrados

**Nuevos:**
- `app/Http/Responses/Concerns/ResolvesLoginRedirect.php`
- `app/Http/Middleware/EnsureRole.php`
- `app/Http/Controllers/Professor/DashboardController.php`
- `app/Http/Controllers/Student/DashboardController.php`
- `app/Http/Controllers/Guardian/DashboardController.php`
- `resources/js/pages/professor/Dashboard.vue`
- `resources/js/pages/student/Dashboard.vue`
- `resources/js/pages/guardian/Dashboard.vue`
- `tests/Feature/Auth/MultiRoleLoginTest.php`

**Modificados:**
- `app/Http/Responses/LoginResponse.php`
- `app/Http/Responses/TwoFactorLoginResponse.php`
- `bootstrap/app.php` (registro del alias `role`)
- `routes/web.php` (grupos de portales)
- `resources/js/components/AppSidebar.vue`
