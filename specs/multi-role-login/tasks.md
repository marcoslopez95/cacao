# Tasks — Multi-Role Login

**Feature:** `05-multi-role-login`

---

## Progreso general

- [ ] Task 1 — Middleware `EnsureRole`
- [ ] Task 2 — Trait `ResolvesLoginRedirect` + fix `LoginResponse` y `TwoFactorLoginResponse`
- [ ] Task 3 — Rutas de portales por rol
- [ ] Task 4 — Controladores de dashboard por rol
- [ ] Task 5 — Feature tests de login multi-rol y acceso a portales
- [ ] Task 6 — Páginas Vue de dashboard por rol
- [ ] Task 7 — `AppSidebar.vue` — navegación contextual por rol

---

## Detalle de cada task

---

### Task 1 — Middleware `EnsureRole`

**Archivos involucrados:**
- `app/Http/Middleware/EnsureRole.php` (nuevo)
- `bootstrap/app.php` (registro del alias)

**Implementación:**

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_if(! $request->user()?->hasAnyRole($roles), 403);
        return $next($request);
    }
}
```

Registrar en `bootstrap/app.php` dentro de `withMiddleware()`:
```php
$middleware->alias(['role' => \App\Http\Middleware\EnsureRole::class]);
```

**Criterio de done:**
- Middleware creado y registrado con alias `role`
- `php artisan route:list` no muestra errores
- Pint pasa sin errores

---

### Task 2 — Trait `ResolvesLoginRedirect` + fix de responses

**Archivos involucrados:**
- `app/Http/Responses/Concerns/ResolvesLoginRedirect.php` (nuevo)
- `app/Http/Responses/LoginResponse.php` (modificar)
- `app/Http/Responses/TwoFactorLoginResponse.php` (modificar)

**Trait:**

```php
namespace App\Http\Responses\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Fortify;

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

**`LoginResponse` modificado:**

```php
use App\Http\Responses\Concerns\ResolvesLoginRedirect;

class LoginResponse implements LoginResponseContract
{
    use ResolvesLoginRedirect;

    public function toResponse($request): Response
    {
        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended($this->resolveRedirect($request));
    }
}
```

**`TwoFactorLoginResponse` modificado — misma estructura.**

**Criterio de done:**
- Admin con team → redirige a `/{team}/dashboard`
- Profesor → redirige a `/professor/dashboard`
- Estudiante → redirige a `/student/dashboard`
- Representante → redirige a `/guardian/dashboard`
- Usuario sin rol ni team → redirige a `/`
- No hay llamada a `abort(403)` en ninguna de las dos responses
- Pint pasa sin errores

---

### Task 3 — Rutas de portales

**Archivos involucrados:**
- `routes/web.php`

**Añadir al final de `web.php`** (antes del `require settings.php`):

```php
use App\Http\Controllers\Professor;
use App\Http\Controllers\Student;
use App\Http\Controllers\Guardian;

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

Ejecutar después: `vendor/bin/sail artisan wayfinder:generate`

**Criterio de done:**
- `php artisan route:list --path=professor` muestra la ruta `professor.dashboard`
- `php artisan route:list --path=student` muestra la ruta `student.dashboard`
- `php artisan route:list --path=guardian` muestra la ruta `guardian.dashboard`
- Wayfinder generó los archivos de rutas correspondientes

---

### Task 4 — Controladores de dashboard

**Archivos involucrados:**
- `app/Http/Controllers/Professor/DashboardController.php` (nuevo)
- `app/Http/Controllers/Student/DashboardController.php` (nuevo)
- `app/Http/Controllers/Guardian/DashboardController.php` (nuevo)

**Estructura común (ejemplo para Profesor):**

```php
namespace App\Http\Controllers\Professor;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function index(Request $request): Response
    {
        return Inertia::render('professor/Dashboard');
    }
}
```

Los tres controladores tienen la misma estructura, cambiando el componente Vue:
- Profesor → `'professor/Dashboard'`
- Estudiante → `'student/Dashboard'`
- Representante → `'guardian/Dashboard'`

**Criterio de done:**
- Los tres controladores creados
- `GET /professor/dashboard` retorna 200 para un usuario Profesor autenticado
- `GET /student/dashboard` retorna 200 para un usuario Estudiante autenticado
- `GET /guardian/dashboard` retorna 200 para un usuario Representante autenticado
- Pint pasa sin errores

---

### Task 5 — Feature tests de login multi-rol

**Archivos involucrados:**
- `tests/Feature/Auth/MultiRoleLoginTest.php` (nuevo)

**Tests a incluir:**

```
test('admin with team is redirected to team dashboard after login')
test('professor is redirected to professor dashboard after login')
test('student is redirected to student dashboard after login')
test('guardian is redirected to guardian dashboard after login')
test('coordinador de area is redirected to professor dashboard after login')
test('inactive user cannot login')
test('professor cannot access student portal')
test('student cannot access professor portal')
test('guardian cannot access student portal')
test('unauthenticated user is redirected to login from professor portal')
test('unauthenticated user is redirected to login from student portal')
test('unauthenticated user is redirected to login from guardian portal')
```

**Notas de implementación:**
- Usar `$user->assignRole('Profesor')` de Spatie para asignar roles en tests
- Para admin con team: usar factory de User + Team + Membership
- `actingAs($user)` + `get('/professor/dashboard')` → assertStatus(200)
- `actingAs($studentUser)` + `get('/professor/dashboard')` → assertStatus(403)
- Para tests de login: `post('/login', ['email' => ..., 'password' => ...])` → `assertRedirect('/professor/dashboard')`

**Criterio de done:**
- Todos los tests pasan con `vendor/bin/sail artisan test --compact --filter=MultiRoleLogin`
- RefreshDatabase en todos los tests
- Sin fixtures hardcodeados — usar factories

---

### Task 6 — Páginas Vue de dashboard por rol

**Archivos involucrados:**
- `resources/js/pages/professor/Dashboard.vue` (nuevo)
- `resources/js/pages/student/Dashboard.vue` (nuevo)
- `resources/js/pages/guardian/Dashboard.vue` (nuevo)

**Estructura común:**

```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const userName = computed(() => page.props.auth?.user?.name ?? '')
</script>

<template>
    <AppLayout>
        <div>
            <h1>Bienvenido/a, {{ userName }}</h1>
            <p><!-- mensaje descriptivo del portal --></p>
        </div>
    </AppLayout>
</template>
```

**Mensajes descriptivos por rol:**
- Profesor: "Tu portal académico está siendo preparado. Pronto podrás gestionar tus secciones, horarios y notas desde aquí."
- Estudiante: "Tu portal estudiantil está siendo preparado. Desde aquí podrás gestionar tu inscripción, consultar tus notas y mucho más."
- Representante: "Tu portal de representante está siendo preparado. Desde aquí podrás consultar el progreso académico de tus representados."

**Criterio de done:**
- `vendor/bin/sail npm run build` pasa sin errores TypeScript
- Las tres páginas renderizan correctamente en el navegador
- El nombre del usuario se muestra correctamente

---

### Task 7 — `AppSidebar.vue` navegación contextual

**Archivos involucrados:**
- `resources/js/components/AppSidebar.vue`

**Cambios al computed `navGroups`:**

1. Agregar computed `portalRole`:
```typescript
const portalRole = computed(() => {
    const roles = page.props.auth?.roles ?? []
    if (roles.includes('Admin')) return 'admin'
    if (roles.some((r: string) => ['Profesor', 'Coordinador de Area'].includes(r))) return 'professor'
    if (roles.includes('Estudiante')) return 'student'
    if (roles.includes('Representante')) return 'guardian'
    return 'unknown'
})
```

2. Actualizar `dashboardUrl` para considerar el rol:
```typescript
const dashboardUrl = computed(() => {
    if (portalRole.value === 'admin' && page.props.currentTeam) {
        return dashboard(page.props.currentTeam.slug).url
    }
    if (portalRole.value === 'professor') return professorDashboard.url()
    if (portalRole.value === 'student') return studentDashboard.url()
    if (portalRole.value === 'guardian') return guardianDashboard.url()
    return '/'
})
```

3. Refactorizar `navGroups` con switch/condición por `portalRole`:

**Admin:** grupos actuales sin cambios (Seguridad, Académico, Infraestructura, Horarios, Inscripciones — se mueve de "Estudiante" a "Inscripciones" solo visible para Admin)

**Professor:**
```typescript
[
    { label: 'General', items: [{ icon: 'grid', label: 'Dashboard', href: dashboardUrl.value }] },
    { label: 'Mi cuenta', items: [{ icon: 'settings', label: 'Configuración', href: profileEdit.url() }] },
]
```

**Student:**
```typescript
[
    { label: 'General', items: [{ icon: 'grid', label: 'Dashboard', href: dashboardUrl.value }] },
    { label: 'Inscripciones', items: [{ icon: 'edit', label: 'Mi inscripción', href: enrollmentIndex.url() }] },
    { label: 'Mi cuenta', items: [{ icon: 'settings', label: 'Configuración', href: profileEdit.url() }] },
]
```

**Guardian:**
```typescript
[
    { label: 'General', items: [{ icon: 'grid', label: 'Dashboard', href: dashboardUrl.value }] },
    { label: 'Mi cuenta', items: [{ icon: 'settings', label: 'Configuración', href: profileEdit.url() }] },
]
```

4. Importar los nuevos Wayfinder routes:
```typescript
import { index as professorDashboard } from '@/routes/professor/dashboard'
import { index as studentDashboard } from '@/routes/student/dashboard'
import { index as guardianDashboard } from '@/routes/guardian/dashboard'
```

Nota: los nombres exactos de los imports Wayfinder dependerán de lo generado en Task 3.

**Criterio de done:**
- Admin ve todos los grupos actuales
- Profesor autenticado solo ve General + Mi cuenta
- Estudiante autenticado ve General + Inscripciones + Mi cuenta
- Representante autenticado solo ve General + Mi cuenta
- El grupo "Estudiante" (enrollment para todos) desaparece del sidebar de admin — reemplazado por grupo "Inscripciones" solo para admin y estudiante
- `vendor/bin/sail npm run build` pasa sin errores TypeScript

---

## Notas de ejecución

- Tasks 1, 2, 3, 4 son backend — ejecutar en orden
- Task 5 (tests) puede ejecutarse en paralelo con Task 3/4 si hay dos agentes, pero requiere que Task 1 y 2 estén completos
- Tasks 6 y 7 son frontend — requieren que Task 3 esté completo (Wayfinder genera las rutas)
- Después de cada archivo PHP modificado: `vendor/bin/sail bin pint --dirty --format agent`
- Después de Task 3: ejecutar `vendor/bin/sail artisan wayfinder:generate`
- Después de cada tarea frontend: `vendor/bin/sail npm run build` para verificar TypeScript
