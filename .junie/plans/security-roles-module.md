---
sessionId: session-260423-165158-ywk2
isActive: true
---

# Requirements

### Overview & Goals
Agregar un módulo global de **Seguridad → Roles** que permita a usuarios autorizados gestionar roles y la asignación de permisos a esos roles. Los **permisos** son un catálogo fijo del sistema (sembrado desde YAML), no se crean ni editan desde la UI — solo se asignan. El propio módulo introduce permisos granulares (uno por acción/botón) para habilitar autorización fina tanto en backend como en frontend.

### Scope

**In Scope**
- Catálogo de permisos del sistema en `database/data/permissions.yaml` + `PermissionSeeder`.
- Permisos iniciales del módulo: `roles.view`, `roles.create`, `roles.update`, `roles.delete`, `roles.assign-permissions`.
- Asignación por defecto de permisos a roles existentes en `database/data/roles.yaml` (ver Technical Design).
- CRUD completo de roles (listar, crear, editar nombre, eliminar, sincronizar permisos).
- Restricción: un rol con usuarios asignados no puede eliminarse.
- Protección del rol `Admin`: no editable, no eliminable, con todos los permisos vía `Gate::before`.
- Página `resources/js/pages/security/Roles/Index.vue` con tabla y modales de crear/editar/eliminar.
- Entrada en el sidebar visible solo si el usuario tiene `roles.view`.
- Migración del sistema de autorización frontend: CASL pasa a construirse desde `auth.permissions` (no desde `auth.roles` hardcodeados).
- Cada botón/acción en la UI se muestra condicionalmente según su permiso específico.
- Tests Pest de feature para el flujo backend y policy.

**Out of Scope**
- CRUD de usuarios / asignación de roles a usuarios (será módulo aparte).
- Scoping de roles por Team (el usuario confirmó que no se maneja).
- Creación de permisos desde UI.
- Traducciones i18n del módulo (se usan strings en español directos; la infra `@/i18n` ya existe y puede adoptarse después).

### User Stories
- **Como** administrador, **quiero** ver la lista de roles con su cantidad de usuarios y permisos, **para** entender el estado de autorización del sistema.
- **Como** administrador, **quiero** crear un nuevo rol con un nombre único y un set de permisos, **para** modelar una nueva responsabilidad.
- **Como** administrador, **quiero** editar el nombre de un rol y sincronizar sus permisos, **para** ajustarlo a cambios del negocio.
- **Como** administrador, **quiero** eliminar un rol sin usuarios asignados, **para** mantener el catálogo limpio; si tiene usuarios, el sistema debe impedirlo con un mensaje claro.
- **Como** usuario sin permiso `roles.view`, **no debo ver** la entrada "Seguridad" en el sidebar ni poder acceder a la ruta.
- **Como** usuario con `roles.view` pero sin `roles.create`, **debo ver** el listado pero **no el botón** de crear.

### Functional Requirements
- Listado muestra: nombre, descripción (si aplica), número de usuarios, número de permisos asignados.
- Crear rol: nombre único (trim, 2–60 chars), `guard_name='web'`, lista de permisos marcables agrupados por módulo (prefijo antes del `.`).
- Editar rol: mismos campos; el rol `Admin` no es editable (bloqueado por policy y UI deshabilitada).
- Eliminar rol: confirmación en modal; el servidor valida que `users_count === 0` y que no sea `Admin`.
- Flash toasts (ya existe `Inertia::flash('toast', ...)`) para feedback: "Rol creado", "Rol actualizado", "Rol eliminado".
- 403 si falta el permiso en backend; el frontend oculta o deshabilita el botón correspondiente (`$can('roles.create')`, etc.).

### Non-Functional Requirements
- **Seguridad**: toda acción pasa por `RolePolicy` + middleware `auth`; el `Admin` siempre autorizado por `Gate::before`.
- **Consistencia**: catálogo de permisos versionado en YAML; el seeder es idempotente (`firstOrCreate`).
- **Performance**: al listar, usar `withCount('users')` y `with('permissions:id,name')` para evitar N+1; Spatie ya cachea permisos.
- **Mantenibilidad**: reglas CASL dejan de estar hardcodeadas por rol y se derivan de `auth.permissions`.

# Technical Design

### Current Implementation
- **Spatie Permission v7** instalado (`composer.json`), migraciones publicadas en `database/migrations/2026_04_23_200006_create_permission_tables.php`. `config/permission.php` presente con `teams=false` en la config por defecto.
- `App\Models\User` ya usa `Spatie\Permission\Traits\HasRoles`.
- Seeder existente: `database/seeders/RoleSeeder.php` lee `database/data/roles.yaml` y hace `Role::firstOrCreate(...)` — pero **no asigna permisos** (no existen aún). `UserSeeder` hace `syncRoles` por email.
- No existen controladores de roles ni policy. `routes/web.php` y `routes/settings.php` no tienen rutas de seguridad.
- `app/Http/Middleware/HandleInertiaRequests.php` ya comparte `auth.user` y `auth.roles` (`$user?->getRoleNames()`), pero **no** permisos.
- Frontend: `resources/js/casl/ability.ts` define `AppActions = 'manage'|'view'|'create'|'edit'|'delete'` y `AppSubjects` con reglas hardcodeadas por rol en inglés (`admin`, `professor`, ...), desincronizado con los roles reales en español. `resources/js/app.ts` llama `ability.update(buildRules(roles))` en cada `router.on('success')`.
- Sidebar: `resources/js/components/AppSidebar.vue` construye `mainNavItems` manualmente con un solo link a Dashboard.
- Patrones existentes a reusar:
  - Controlador tipo `app/Http/Controllers/Teams/TeamController.php` (Inertia::render + flash toast + `Gate::authorize`).
  - Policy tipo `app/Policies/TeamPolicy.php` registrada automáticamente por convención Laravel 13.
  - Form Requests en `app/Http/Requests/Teams/*`.
  - Wayfinder ya activo (`@laravel/vite-plugin-wayfinder`): los imports `@/routes/*` y `@/actions/*` son generados.
  - UI: modales tipo `resources/js/components/CreateTeamModal.vue`, `DeleteTeamModal.vue` sobre `components/ui/dialog`.

### Key Decisions

1. **Ubicación**: Top-level `/security/roles` como módulo administrativo nuevo (no bajo `/settings/*` que está reservado para ajustes personales y teams).
2. **Catálogo de permisos**: `database/data/permissions.yaml` + `PermissionSeeder` (idem convención de `roles.yaml`).
3. **Permisos del módulo**: `roles.view`, `roles.create`, `roles.update`, `roles.delete`, `roles.assign-permissions` (un permiso por botón de UI).
4. **Rol Admin protegido** vía `Gate::before` en `AppServiceProvider::boot()`: retorna `true` si `$user->hasRole('Admin')`. Policy adicionalmente bloquea `update`/`delete`/`assignPermissions` contra el rol `Admin` para que ni siquiera un Admin pueda destruirlo.
5. **Frontend CASL-from-permissions**: se agrega `auth.permissions` al share de Inertia y `buildRules(permissions)` crea una regla `can(permissionName, 'all')` por cada string. Los componentes usarán `$can('roles.create', 'all')` o un helper `can('roles.create')`. Se deja la estructura CASL para no romper el ecosistema existente.
6. **UX**: `Index.vue` con tabla shadcn y 3 modales (`CreateRoleModal`, `EditRoleModal`, `DeleteRoleModal`) — mismo patrón que `teams/Index.vue`.
7. **Validación de eliminación**: controller chequea `Role::withCount('users')` y responde con error 422/redirect con flash toast si `users_count > 0`.

### Proposed Changes

#### Backend

**Nuevos archivos**
- `database/data/permissions.yaml` — catálogo inicial:
  ```yaml
  permissions:
    - { name: roles.view,                guard: web }
    - { name: roles.create,              guard: web }
    - { name: roles.update,              guard: web }
    - { name: roles.delete,              guard: web }
    - { name: roles.assign-permissions,  guard: web }
  ```
- `database/seeders/PermissionSeeder.php` — `Permission::firstOrCreate(['name'=>..., 'guard_name'=>...])` iterando el YAML.
- `app/Http/Controllers/Security/RoleController.php` con acciones `index`, `store`, `update`, `destroy`.
- `app/Http/Requests/Security/StoreRoleRequest.php` y `UpdateRoleRequest.php` (ambos `authorize()` delega a `Gate::allows` via policy) con reglas de nombre único y `permissions.*` existentes en tabla `permissions`.
- `app/Policies/RolePolicy.php`: `viewAny`, `create`, `update(Role)`, `delete(Role)`, `assignPermissions(Role)`. `update`/`delete`/`assignPermissions` retornan `false` si `$role->name === 'Admin'`.

**Modificados**
- `database/data/roles.yaml` — agregar `permissions:` por rol (Admin implicit, Profesor/Estudiante/Coordinador vacíos por ahora).
- `database/seeders/RoleSeeder.php` — tras `firstOrCreate`, hacer `syncPermissions($roleData['permissions'] ?? [])`.
- `database/seeders/DatabaseSeeder.php` — ejecutar `PermissionSeeder` **antes** que `RoleSeeder`.
- `app/Providers/AppServiceProvider.php::boot()` — agregar `Gate::before(fn ($user) => $user->hasRole('Admin') ? true : null);`.
- `app/Http/Middleware/HandleInertiaRequests.php::share()` — agregar `'permissions' => $user?->getAllPermissions()->pluck('name') ?? []` dentro de `auth`.
- `routes/web.php` — agregar grupo:
  ```php
  Route::middleware(['auth','verified'])->prefix('security')->name('security.')->group(function () {
      Route::get('roles',               [RoleController::class, 'index'])->name('roles.index');
      Route::post('roles',              [RoleController::class, 'store'])->name('roles.store');
      Route::patch('roles/{role}',      [RoleController::class, 'update'])->name('roles.update');
      Route::delete('roles/{role}',     [RoleController::class, 'destroy'])->name('roles.destroy');
  });
  ```

#### Frontend

**Nuevos archivos**
- `resources/js/pages/security/Roles/Index.vue` — tabla shadcn con columnas `Nombre`, `Usuarios`, `Permisos`, `Acciones`; botón "Nuevo rol" oculto si `!$can('roles.create')`.
- `resources/js/components/security/CreateRoleModal.vue` — form con `useForm`, checklist de permisos agrupados por prefijo (`roles.*`, `users.*`, ...).
- `resources/js/components/security/EditRoleModal.vue` — recibe `role` como prop; deshabilita todo si `role.name === 'Admin'`.
- `resources/js/components/security/DeleteRoleModal.vue` — confirmación; deshabilitado si `role.usersCount > 0` con mensaje "No se puede eliminar: tiene N usuarios asignados".
- `resources/js/composables/usePermission.ts` — pequeño helper opcional para uso fuera de template.

**Modificados**
- `resources/js/types/index.d.ts` — extender `SharedData.auth` con `permissions: string[]` y añadir tipos `Role`, `Permission`.
- `resources/js/casl/ability.ts` — reescribir `buildRules(permissions: string[])` para que `can(permission, 'all')` por cada string; mantener el fallback `manage all` para Admin (detectable vía permiso marcador o presencia de todos). Alternativamente: `roles.includes('Admin') ? can('manage','all') : permissions.forEach(p => can(p,'all'))`.
- `resources/js/app.ts` — en lugar de `auth.roles`, extraer `auth.permissions` (+ `auth.roles` para caso Admin).
- `resources/js/components/AppSidebar.vue` — agregar entrada "Seguridad → Roles" usando `@/routes/security/roles.index`, visible con `v-if="$can('roles.view','all')"`.

### Data Models / Contracts

**`Inertia::render('security/Roles/Index', [...])` payload**
```php
[
  'roles' => Role::query()
      ->withCount('users')
      ->with('permissions:id,name')
      ->orderBy('name')
      ->get()
      ->map(fn (Role $r) => [
          'id' => $r->id,
          'name' => $r->name,
          'isAdmin' => $r->name === 'Admin',
          'usersCount' => $r->users_count,
          'permissions' => $r->permissions->pluck('name'),
      ]),
  'permissions' => Permission::orderBy('name')->pluck('name'),
  'can' => [
      'create' => $user->can('roles.create'),
      'update' => $user->can('roles.update'),
      'delete' => $user->can('roles.delete'),
      'assignPermissions' => $user->can('roles.assign-permissions'),
  ],
]
```

**`StoreRoleRequest`**
```php
return [
  'name'          => ['required','string','min:2','max:60','unique:roles,name'],
  'permissions'   => ['array'],
  'permissions.*' => ['string','exists:permissions,name'],
];
```

**`UpdateRoleRequest`** — idem, pero `unique:roles,name,{id}` y `authorize()` falla si `$this->route('role')->name === 'Admin'`.

### Components
- **Nuevos**: `RoleController`, `RolePolicy`, `StoreRoleRequest`, `UpdateRoleRequest`, `PermissionSeeder`, `security/Roles/Index.vue`, `CreateRoleModal.vue`, `EditRoleModal.vue`, `DeleteRoleModal.vue`, `usePermission.ts`.
- **Afectados**: `HandleInertiaRequests`, `AppServiceProvider`, `RoleSeeder`, `DatabaseSeeder`, `roles.yaml`, `web.php`, `casl/ability.ts`, `app.ts`, `AppSidebar.vue`, `types/index.d.ts`.

### File Structure
```
app/
  Http/Controllers/Security/RoleController.php      (new)
  Http/Requests/Security/StoreRoleRequest.php       (new)
  Http/Requests/Security/UpdateRoleRequest.php      (new)
  Policies/RolePolicy.php                           (new)
  Providers/AppServiceProvider.php                  (mod: Gate::before)
  Http/Middleware/HandleInertiaRequests.php         (mod: share permissions)
database/
  data/permissions.yaml                             (new)
  data/roles.yaml                                   (mod: permissions por rol)
  seeders/PermissionSeeder.php                      (new)
  seeders/RoleSeeder.php                            (mod: syncPermissions)
  seeders/DatabaseSeeder.php                        (mod: orden)
routes/web.php                                      (mod: grupo security)
resources/js/
  pages/security/Roles/Index.vue                    (new)
  components/security/CreateRoleModal.vue           (new)
  components/security/EditRoleModal.vue             (new)
  components/security/DeleteRoleModal.vue           (new)
  composables/usePermission.ts                      (new)
  casl/ability.ts                                   (mod: from permissions)
  app.ts                                            (mod: pass permissions)
  components/AppSidebar.vue                         (mod: nav entry)
  types/index.d.ts                                  (mod: auth.permissions, Role)
tests/Feature/Security/
  RoleControllerTest.php                            (new)
  RolePolicyTest.php                                (new)
```

### Architecture Diagram
```mermaid
graph LR
    A[Browser] -->|GET /security/roles| B[RoleController@index]
    B --> P[RolePolicy::viewAny]
    B --> DB[(roles / permissions)]
    B -->|Inertia::render| V[security/Roles/Index.vue]
    V -->|$can roles.create| M1[CreateRoleModal]
    V -->|$can roles.update| M2[EditRoleModal]
    V -->|$can roles.delete| M3[DeleteRoleModal]
    M1 -->|POST| C[RoleController@store]
    M2 -->|PATCH| U[RoleController@update]
    M3 -->|DELETE| D[RoleController@destroy]
    C & U & D --> P
    HIR[HandleInertiaRequests] -->|auth.permissions| V
    APP[app.ts] -->|buildRules| CASL[CASL ability]
    CASL -->|$can| V
    GB[AppServiceProvider::Gate::before] -.Admin bypass.-> P
```

### Risks
- **Desalineación CASL**: reglas actuales están en inglés y se usan en código existente. Mitigación: mantener compat en `buildRules` aceptando tanto `permissions` como el fallback por rol `Admin`; revisar grep de `$can(` en la app.
- **Caché de permisos Spatie**: al modificar roles/permisos hay que invalidar cache. Mitigación: Spatie lo hace automáticamente al `syncPermissions`, pero el seeder debe correr `php artisan cache:clear` o el seeder ya llama al forget cache del config.
- **Rol Admin sin permisos explícitos**: si alguien elimina `Gate::before`, Admin perdería permisos. Mitigación: documentar en el PR y cubrir con test.
- **Botón visible pero acción denegada**: si el share de `auth.permissions` falla, la UI podría ocultar todo. El backend sigue siendo la fuente de verdad (Policy), por lo que no hay riesgo de seguridad, solo UX.

# Testing

### Validation Approach
Todos los cambios se validan con Pest feature tests (siguen el estilo de `tests/Feature/Teams/*`). No se usan tests unitarios salvo para la policy aislada.

### Key Scenarios
- `GET /security/roles` con usuario sin `roles.view` → 403.
- `GET /security/roles` con usuario con `roles.view` → Inertia component `security/Roles/Index`, props `roles` y `permissions`.
- `POST /security/roles` con permiso + payload válido → crea rol, sincroniza permisos, redirect con flash `toast.type=success`.
- `POST /security/roles` sin permiso → 403.
- `POST /security/roles` con nombre duplicado → 422 con error de validación.
- `PATCH /security/roles/{role}` sobre rol `Admin` → 403 (policy).
- `PATCH /security/roles/{role}` sobre rol normal → actualiza nombre y `syncPermissions`.
- `DELETE /security/roles/{role}` con `users_count > 0` → redirect con flash `toast.type=error` y rol intacto.
- `DELETE /security/roles/{role}` sobre `Admin` → 403.
- `DELETE /security/roles/{role}` sin usuarios → eliminado.
- Usuario con rol `Admin` siempre pasa todas las policies (`Gate::before`).
- `HandleInertiaRequests`: `auth.permissions` contiene los nombres de permisos del usuario.

### Edge Cases
- Payload con `permissions` incluyendo uno inexistente → 422.
- Usuario sin autenticar → redirect a login.
- `Role::firstOrCreate` en seeder es idempotente al reejecutar `db:seed`.
- Seeder ejecuta `PermissionSeeder` antes que `RoleSeeder` (si no, `syncPermissions` fallaría).

### Test Changes
- Nuevos: `tests/Feature/Security/RoleControllerTest.php`, `tests/Feature/Security/RolePolicyTest.php`.
- Actualizar: agregar caso en test relacionado a Inertia share para cubrir `auth.permissions` (puede ir en `RoleControllerTest`).
- No se modifican tests de Teams existentes.

# Delivery Steps

### ✓ Step 1: Catálogo de permisos y protección del rol Admin
El sistema tiene un catálogo de permisos sembrado desde YAML y el rol `Admin` pasa automáticamente todas las autorizaciones.

- Crear `database/data/permissions.yaml` con los 5 permisos iniciales del módulo (`roles.view`, `roles.create`, `roles.update`, `roles.delete`, `roles.assign-permissions`).
- Crear `database/seeders/PermissionSeeder.php` que lee el YAML y hace `Permission::firstOrCreate` (idempotente, estilo `RoleSeeder`).
- Extender `database/data/roles.yaml` agregando campo `permissions: []` por rol (vacío para no-Admin inicialmente).
- Modificar `RoleSeeder` para llamar `syncPermissions($roleData['permissions'] ?? [])` tras crear el rol.
- Actualizar `DatabaseSeeder` para ejecutar `PermissionSeeder` antes que `RoleSeeder`.
- Agregar `Gate::before(fn ($user) => $user->hasRole('Admin') ? true : null)` en `AppServiceProvider::boot()`.
- Cubrir con tests: seeder idempotente, `Gate::before` concede cualquier habilidad al Admin.

### * Step 2: Backend CRUD de Roles (controlador, requests, policy, rutas)
Los endpoints `/security/roles` permiten listar, crear, actualizar y eliminar roles con autorización granular.

- Crear `app/Policies/RolePolicy.php` con `viewAny`, `create`, `update`, `delete`, `assignPermissions`; bloquear mutaciones sobre el rol `Admin`.
- Crear `app/Http/Requests/Security/StoreRoleRequest.php` y `UpdateRoleRequest.php` con validación de `name` único y `permissions.*` `exists:permissions,name`.
- Crear `app/Http/Controllers/Security/RoleController.php` con `index` (Inertia::render con `withCount('users')` y permisos), `store` (crea + `syncPermissions`), `update` (actualiza + `syncPermissions`), `destroy` (bloquea si `users_count > 0`).
- Registrar el grupo `Route::middleware(['auth','verified'])->prefix('security')` en `routes/web.php` con las 4 rutas nombradas `security.roles.*`.
- Usar `Inertia::flash('toast', ...)` en store/update/destroy para feedback UX.
- Tests Pest en `tests/Feature/Security/RoleControllerTest.php` y `RolePolicyTest.php` cubriendo permisos, validación, nombre único, protección Admin y bloqueo por usuarios asignados.

###   Step 3: Cableado de permisos en Inertia + CASL frontend
El frontend recibe `auth.permissions` desde el backend y CASL deriva sus reglas de esos permisos.

- Modificar `app/Http/Middleware/HandleInertiaRequests.php::share()` para incluir `permissions` => `$user?->getAllPermissions()->pluck('name') ?? []` dentro de `auth`.
- Reescribir `resources/js/casl/ability.ts::buildRules(permissions, roles)` para: si el usuario tiene rol `Admin`, `can('manage','all')`; en caso contrario, `permissions.forEach(p => can(p,'all'))`.
- Actualizar `resources/js/app.ts` para extraer y pasar `auth.permissions` (+ `auth.roles` para el caso Admin) a `buildRules` en `setup()` y en el handler de `router.on('success')`.
- Extender `resources/js/types/index.d.ts`: añadir `permissions: string[]` a `SharedData.auth` y tipos `Role` y `Permission`.
- Agregar composable `resources/js/composables/usePermission.ts` como helper para usar fuera de templates (`can('roles.create')` → `ability.can('roles.create','all')`).
- Validar con un test de Inertia feature que el share contiene `permissions` esperados.

###   Step 4: UI del módulo de Roles (Index + modales + navegación)
Existe una página `/security/roles` con tabla y modales de CRUD, y una entrada de sidebar visible según permiso.

- Crear `resources/js/pages/security/Roles/Index.vue` con tabla shadcn (columnas Nombre, Usuarios, Permisos, Acciones), siguiendo el patrón de `teams/Index.vue` y usando `@/routes/security/roles.index` de Wayfinder.
- Crear `resources/js/components/security/CreateRoleModal.vue` con `useForm`, input de nombre y checklist de permisos agrupados por prefijo (`roles.*`, etc.).
- Crear `resources/js/components/security/EditRoleModal.vue` con prefill de datos del rol; desactivar el formulario completo si `role.isAdmin === true`.
- Crear `resources/js/components/security/DeleteRoleModal.vue` con confirmación; botón deshabilitado y mensaje explicativo si `role.usersCount > 0`.
- Cada botón envuelto con `v-if="$can('roles.create','all')"`, `$can('roles.update','all')`, `$can('roles.delete','all')`, `$can('roles.assign-permissions','all')`.
- Actualizar `resources/js/components/AppSidebar.vue` agregando item "Seguridad → Roles" con `v-if="$can('roles.view','all')"` apuntando a `@/routes/security/roles.index()`.
- Verificar manualmente el flujo con `vendor/bin/sail npm run dev` (o solicitarlo al usuario).