# Users Module — Implementation Spec

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a full user management module under `/security/users` — list, create (direct + invitation), edit, deactivate, delete, and password reset — with Laravel Policies on the backend and CASL guards on every frontend action.

**Architecture:** Follows the existing `RoleController` / `RolePolicy` / `roles.yaml` pattern exactly. Two new controllers (`UserController`, `InvitationController`) + one public controller (`AcceptInvitationController`). One new Vue page with 6 modals and one public auth page for accepting invitations.

**Tech Stack:** Laravel 13, Spatie Permission, Fortify, Vue 3, Inertia v3, Tailwind v4 CACAO tokens, CASL (`@casl/vue`), Wayfinder, Pest

---

## Data Model

### Migration 1 — `add_active_to_users_table`
Adds `active boolean default true not null` to the `users` table.
Inactive users are blocked at the authentication layer via a Fortify pipeline action.

### Migration 2 — `create_invitations_table`
```
invitations
  id                bigint PK
  email             string (unique among pending: where used_at is null)
  role              string (role name to assign on acceptance)
  token             string unique (UUID v4)
  invited_by        FK → users.id (set null on delete)
  expires_at        timestamp (now + 48 hours)
  used_at           timestamp nullable
  created_at        timestamp
  updated_at        timestamp
```

### `Invitation` Model
- `$fillable`: email, role, token, invited_by, expires_at
- Scopes: `pending()` → where used_at is null and expires_at > now
- Methods: `isExpired()`, `isUsed()`, `isPending()`
- Relationship: `invitedBy()` → belongsTo User

### `User` Model changes
- Add `active` to `$fillable`
- Add `active` cast to boolean

### Fortify Authentication
Override `Fortify::authenticateUsing()` in `FortifyServiceProvider` (or `AppServiceProvider`). After verifying credentials, check `$user->active`: if false, throw `ValidationException` with message "Tu cuenta está desactivada. Contactá al administrador." on the `email` field.

```php
Fortify::authenticateUsing(function (Request $request) {
    $user = User::where('email', $request->email)->first();
    if ($user && Hash::check($request->password, $user->password)) {
        if (! $user->active) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta está desactivada. Contactá al administrador.'],
            ]);
        }
        return $user;
    }
});
```

---

## Permissions

Add to `database/data/permissions.yaml`:
```yaml
- name: users.view
  description: Ver lista de usuarios
- name: users.create
  description: Crear usuarios directamente
- name: users.update
  description: Editar nombre, correo y roles de un usuario
- name: users.delete
  description: Eliminar usuarios sin historial académico
- name: users.deactivate
  description: Activar o desactivar usuarios
- name: users.reset-password
  description: Enviar link de contraseña o asignar una nueva
- name: users.invite
  description: Enviar invitaciones por correo
```

Assign `users.*` permissions to the **Admin** role in `database/data/roles.yaml`.

---

## Backend

### `UserPolicy` — `app/Policies/UserPolicy.php`
Registered in `AppServiceProvider` alongside `RolePolicy`.

| method | permission check | extra constraints |
|--------|-----------------|-------------------|
| `viewAny` | `users.view` | — |
| `create` | `users.create` | — |
| `update(auth, target)` | `users.update` | cannot edit self via this module |
| `delete(auth, target)` | `users.delete` | only if `$target->enrollments()->count() === 0` |
| `deactivate(auth, target)` | `users.deactivate` | cannot deactivate self |
| `resetPassword` | `users.reset-password` | — |
| `invite` | `users.invite` | — |

Admin `Gate::before` bypass applies (already configured globally).

### `UserController` — `app/Http/Controllers/Security/UserController.php`

**`index`** — Returns paginated users (20/page) with filters:
- `search` param: name or email LIKE
- `role` param: filter by role name
- `status` param: `active`, `inactive`, `all` (default: `all`)
- Eager loads: `roles`
- Passes `roles` list (for filter dropdown) and `permissions` list as Inertia props

**`store`** — Creates user directly. Password options (mutually exclusive):
- `password_mode: 'link'` → creates user without password, sends Fortify reset link
- `password_mode: 'manual'` → uses provided `password` field
- `password_mode: 'random'` → generates random 16-char password, stores it, flashes it once in toast

**`update`** — Updates name, email, and roles (`syncRoles()`).

**`destroy`** — Deletes only if no enrolled courses. Returns 422 with message if user has history.

**`deactivate`** — Toggles `active` boolean. Returns updated user state.

**`resetPassword`** — Same three modes as `store`: link, manual, random.

### `InvitationController` — `app/Http/Controllers/Security/InvitationController.php`

**`store`** — Creates `Invitation` record and sends `InvitationMail` (Mailable). Cancels any existing pending invitation for the same email before creating a new one.

**`destroy`** — Deletes a pending invitation (cannot delete used ones).

### `AcceptInvitationController` — `app/Http/Controllers/Auth/AcceptInvitationController.php`
Public routes (no auth middleware).

**`show`** — Finds invitation by token. If expired → renders error page. If valid → renders `auth/AcceptInvitation` Inertia page passing `inviteEmail`, `inviteRole`, `inviteExpiresIn`, `token`.

**`store`** — Validates name + password + password_confirmation. Creates `User` record, assigns role, marks `invitation.used_at = now()`, logs the user in, redirects to dashboard.

### Form Requests
- `StoreUserRequest` — validates name, email (unique), role (exists), password_mode + conditional password
- `UpdateUserRequest` — validates name, email (unique ignoring self), roles array
- `ResetPasswordRequest` — validates password_mode + conditional password
- `StoreInvitationRequest` — validates email (unique among pending invitations), role
- `AcceptInvitationRequest` — validates name (required), password (min 8, confirmed)

### Routes — `routes/security.php` (or add to existing security routes file)
```php
Route::middleware(['auth', 'verified'])->prefix('security')->name('security.')->group(function () {
    // Users
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    // Invitations
    Route::post('invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
});

// Public — accept invitation (no auth)
Route::get('invitation/{token}', [AcceptInvitationController::class, 'show'])->name('invitation.show');
Route::post('invitation/{token}', [AcceptInvitationController::class, 'store'])->name('invitation.store');
```

### `InvitationMail` — `app/Mail/InvitationMail.php`
Mailable with subject "Te invitaron a CACAO". Body includes the accept URL and expiry. Uses existing Mailpit for dev.

### Sidebar navigation
Add "Usuarios" link under the Security group in the existing sidebar, guarded by `users.view`.

---

## Frontend

### `resources/js/pages/security/Users/Index.vue`
Inertia page at route `security.users.index`.

**Props from controller:**
```typescript
{
  users: Paginator<UserRow>,  // paginated
  roles: string[],            // for filter dropdown
  filters: { search: string, role: string, status: string },
  can: { create: boolean, invite: boolean }
}
```

**Table columns:** Avatar (initial letter) · Name + email · Roles (badges) · Status badge (Activo/Inactivo) · Created at · Actions

**Actions per row** (each guarded by CASL `can()`):
- Edit (pencil) → `can('update', 'User')`
- Reset password (key) → `can('reset-password', 'User')`
- Deactivate toggle → `can('deactivate', 'User')`, disabled for self
- Delete (trash, only if no history) → `can('delete', 'User')`

**Header actions:**
- "Nuevo usuario" button → `can('create', 'User')`
- "Invitar por correo" button → `can('invite', 'User')`

**Filters:** Search input + role `<select>` + status `<select>` — all trigger Inertia `router.get()` with debounce on search.

### Modals

#### `CreateUserModal.vue`
Fields: Nombre completo, Correo institucional, Rol (select with existing roles).
Password section — radio/segmented control with 3 options:
- "Enviar link de contraseña" (default) — no extra field
- "Escribir contraseña" — reveals password + confirm fields
- "Generar aleatoria" — no extra field; shows warning "la contraseña se mostrará una sola vez"

On success: if random password → toast shows password prominently with copy button. Otherwise standard success toast.

#### `InviteUserModal.vue`
Fields: Correo institucional, Rol.
On success: toast "Invitación enviada a {email}. Expira en 48 horas."

#### `EditUserModal.vue`
Fields: Nombre completo, Correo institucional, Roles (multi-select or checkboxes).
Cannot edit own account via this modal (button hidden for self).

#### `ResetPasswordModal.vue`
Shows user name and email at top.
Same 3-option password section as CreateUserModal.

#### `DeactivateUserModal.vue`
Simple confirm dialog. Message changes based on current state:
- Activating: "¿Reactivar acceso de {name}? Podrá iniciar sesión nuevamente."
- Deactivating: "¿Desactivar a {name}? No podrá iniciar sesión hasta que reactives su cuenta."

#### `DeleteUserModal.vue`
Only shown for users with no academic history (controller enforces this, frontend hides button otherwise).
Confirm by typing the user's name (same pattern as critical deletions in other systems) — or simpler: just a confirm button with user name displayed prominently.

### `resources/js/pages/auth/AcceptInvitation.vue`
Public page, uses `AuthSplitLayout` via `setLayoutProps()`.
Panel props: "Tu acceso a CACAO comienza con una invitación institucional."

Fields: Nombre completo, Contraseña, Confirmar contraseña.
Email shown as readonly banner (same `inviteEmail` banner from Register.vue).
If token expired: shows error state with message instead of form.

### CASL integration
No new subjects needed — `'User'` already exists in `AppSubjects`.
Permission strings (`users.view`, `users.create`, etc.) are automatically included in the shared `auth.permissions` array from `HandleInertiaRequests`. CASL rules engine maps them via `can(permission, 'all')`.

In components: use `const { can } = usePermission()` composable already available.

---

## Error handling

| scenario | response |
|----------|----------|
| Delete user with history | 422 JSON `{ message: "Este usuario tiene inscripciones activas y no puede eliminarse." }` |
| Deactivate self | 403 via Policy |
| Edit self via this module | 403 via Policy (profile settings is the correct place) |
| Expired invitation token | Render `AcceptInvitation` page with `expired: true` prop |
| Used invitation token | Same expired handling |
| Duplicate pending invite | Cancel previous, create new. Inform admin via toast. |

---

## Testing

### Feature tests (Pest)
- `UserControllerTest` — index (with filters), store (3 password modes), update, destroy (with/without history), deactivate (self-block), resetPassword
- `InvitationControllerTest` — store, destroy, duplicate handling
- `AcceptInvitationControllerTest` — show (valid/expired/used), store (creates user, assigns role, logs in)
- `UserPolicyTest` — all 7 policy methods, Admin bypass

### Key assertions
- Inactive user cannot authenticate (login returns 422)
- Delete blocked when user has enrollments
- Admin cannot deactivate self
- Invitation token is single-use (second accept returns expired)
- Random password is hashed in DB, shown once in response flash

---

## Sidebar navigation entry

In the existing sidebar component, under the Security group, add:
```
Usuarios  →  /security/users  (icon: users, guard: can('view', 'User'))
```
