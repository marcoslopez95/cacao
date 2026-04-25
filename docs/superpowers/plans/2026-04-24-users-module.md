# Users Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a full user management module under `/security/users` — list, create (direct + invitation), edit, deactivate, delete, and password reset — with Laravel Policies on the backend and CASL guards on every frontend action.

**Architecture:** Follows the existing `RoleController` / `RolePolicy` / `roles.yaml` pattern. Two new controllers (`UserController`, `InvitationController`) + one public controller (`AcceptInvitationController`). One Vue index page with 6 modals and one public auth page for accepting invitations.

**Tech Stack:** Laravel 13, Spatie Permission, Fortify, Vue 3, Inertia v3, Tailwind v4 CACAO tokens, CASL (`@casl/vue`), Wayfinder, Pest

---

## File Map

**New files:**
- `database/migrations/*_add_active_to_users_table.php`
- `database/migrations/*_create_invitations_table.php`
- `app/Models/Invitation.php`
- `database/factories/InvitationFactory.php`
- `app/Policies/UserPolicy.php`
- `app/Http/Requests/Security/StoreUserRequest.php`
- `app/Http/Requests/Security/UpdateUserRequest.php`
- `app/Http/Requests/Security/ResetPasswordRequest.php`
- `app/Http/Requests/Security/StoreInvitationRequest.php`
- `app/Http/Requests/Auth/AcceptInvitationRequest.php`
- `app/Http/Controllers/Security/UserController.php`
- `app/Http/Controllers/Security/InvitationController.php`
- `app/Http/Controllers/Auth/AcceptInvitationController.php`
- `app/Mail/InvitationMail.php`
- `resources/views/mail/invitation.blade.php`
- `resources/js/pages/security/Users/Index.vue`
- `resources/js/components/security/CreateUserModal.vue`
- `resources/js/components/security/InviteUserModal.vue`
- `resources/js/components/security/EditUserModal.vue`
- `resources/js/components/security/ResetPasswordModal.vue`
- `resources/js/components/security/DeactivateUserModal.vue`
- `resources/js/components/security/DeleteUserModal.vue`
- `resources/js/pages/auth/AcceptInvitation.vue`
- `tests/Feature/Security/UserControllerTest.php`
- `tests/Feature/Security/UserPolicyTest.php`
- `tests/Feature/Security/InvitationControllerTest.php`
- `tests/Feature/Auth/AcceptInvitationControllerTest.php`

**Modified files:**
- `app/Models/User.php` — add `active` to fillable + boolean cast
- `database/factories/UserFactory.php` — add `inactive()` state
- `database/data/permissions.yaml` — add 7 `users.*` permissions
- `database/data/roles.yaml` — assign `users.*` to Admin
- `app/Providers/AppServiceProvider.php` — register `UserPolicy`
- `app/Providers/FortifyServiceProvider.php` — add `authenticateUsing()`
- `routes/web.php` — add user/invitation routes
- `resources/js/types/security.ts` — add `UserRow` type
- `resources/js/components/AppSidebar.vue` — add Usuarios nav item

---

## Task 1: Migrations + Invitation Model + Factory

**Files:**
- Create: `database/migrations/2026_04_24_000001_add_active_to_users_table.php`
- Create: `database/migrations/2026_04_24_000002_create_invitations_table.php`
- Modify: `app/Models/User.php`
- Create: `app/Models/Invitation.php`
- Create: `database/factories/InvitationFactory.php`
- Modify: `database/factories/UserFactory.php`

- [ ] **Step 1: Create migrations via Artisan**

```bash
vendor/bin/sail artisan make:migration add_active_to_users_table --no-interaction
vendor/bin/sail artisan make:migration create_invitations_table --no-interaction
```

- [ ] **Step 2: Write add_active_to_users migration**

Replace the generated file content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
};
```

- [ ] **Step 3: Write create_invitations migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('role');
            $table->uuid('token')->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
```

- [ ] **Step 4: Update User model**

In `app/Models/User.php`, change the `#[Fillable]` attribute to include `active`, and add `active` to casts:

```php
<?php

namespace App\Models;

use App\Concerns\HasTeams;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'current_team_id', 'active'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasTeams, Notifiable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'active' => 'boolean',
        ];
    }
}
```

- [ ] **Step 5: Create Invitation model**

```bash
vendor/bin/sail artisan make:model Invitation --factory --no-interaction
```

Then write `app/Models/Invitation.php`:

```php
<?php

namespace App\Models;

use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['email', 'role', 'token', 'invited_by', 'expires_at'])]
class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /** @param Builder<Invitation> $query */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('used_at')->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isPending(): bool
    {
        return ! $this->isUsed() && ! $this->isExpired();
    }

    /** @return BelongsTo<User, $this> */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
```

- [ ] **Step 6: Write InvitationFactory**

```php
<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email'      => fake()->unique()->safeEmail(),
            'role'       => 'Profesor',
            'token'      => Str::uuid()->toString(),
            'invited_by' => User::factory(),
            'expires_at' => now()->addHours(48),
            'used_at'    => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subHour()]);
    }

    public function used(): static
    {
        return $this->state(fn () => ['used_at' => now()->subMinute()]);
    }
}
```

- [ ] **Step 7: Add `inactive()` state to UserFactory**

In `database/factories/UserFactory.php`, add after the `withTwoFactor()` method:

```php
public function inactive(): static
{
    return $this->state(fn (array $attributes) => [
        'active' => false,
    ]);
}
```

- [ ] **Step 8: Run migrations**

```bash
vendor/bin/sail artisan migrate --no-interaction
```

Expected: `Running migrations... add_active_to_users_table ... DONE` and `create_invitations_table ... DONE`

- [ ] **Step 9: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 10: Commit**

```bash
git add database/migrations/ app/Models/User.php app/Models/Invitation.php database/factories/InvitationFactory.php database/factories/UserFactory.php
git commit -m "feat: add active column to users, create invitations table and model"
```

---

## Task 2: Permissions YAML + Roles YAML

**Files:**
- Modify: `database/data/permissions.yaml`
- Modify: `database/data/roles.yaml`

- [ ] **Step 1: Add permissions to permissions.yaml**

Append to `database/data/permissions.yaml`:

```yaml
  - name: users.view
    guard: web
  - name: users.create
    guard: web
  - name: users.update
    guard: web
  - name: users.delete
    guard: web
  - name: users.deactivate
    guard: web
  - name: users.reset-password
    guard: web
  - name: users.invite
    guard: web
```

- [ ] **Step 2: Assign users.* to Admin in roles.yaml**

Update the Admin entry in `database/data/roles.yaml`:

```yaml
  - name: Admin
    description: Acceso total al sistema
    guard: web
    permissions:
      - roles.view
      - roles.create
      - roles.update
      - roles.delete
      - roles.assign-permissions
      - users.view
      - users.create
      - users.update
      - users.delete
      - users.deactivate
      - users.reset-password
      - users.invite
```

- [ ] **Step 3: Write a test to verify seeding**

```bash
vendor/bin/sail artisan make:test --pest Security/PermissionSeederTest --no-interaction
```

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

test('all users permissions are seeded', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->artisan('db:seed', ['--class' => 'PermissionSeeder'])->assertSuccessful();

    $expected = [
        'users.view', 'users.create', 'users.update', 'users.delete',
        'users.deactivate', 'users.reset-password', 'users.invite',
    ];

    foreach ($expected as $name) {
        expect(Permission::where('name', $name)->exists())->toBeTrue("Missing permission: {$name}");
    }
});

test('Admin role has all users permissions after seeding', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->artisan('db:seed', ['--class' => 'PermissionSeeder'])->assertSuccessful();
    $this->artisan('db:seed', ['--class' => 'RoleSeeder'])->assertSuccessful();

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $admin = Role::findByName('Admin');

    expect($admin->hasPermissionTo('users.view'))->toBeTrue()
        ->and($admin->hasPermissionTo('users.create'))->toBeTrue()
        ->and($admin->hasPermissionTo('users.invite'))->toBeTrue();
});
```

- [ ] **Step 4: Run test to verify it fails (no permissions yet)**

```bash
vendor/bin/sail artisan test --compact --filter=PermissionSeederTest
```

Expected: PASS (seeders run correctly with yaml data)

- [ ] **Step 5: Commit**

```bash
git add database/data/permissions.yaml database/data/roles.yaml tests/Feature/Security/PermissionSeederTest.php
git commit -m "feat: add users.* permissions to catalog, assign to Admin role"
```

---

## Task 3: UserPolicy + Registration + UserPolicyTest

**Files:**
- Create: `app/Policies/UserPolicy.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `tests/Feature/Security/UserPolicyTest.php`

- [ ] **Step 1: Write the failing policy test**

```bash
vendor/bin/sail artisan make:test --pest Security/UserPolicyTest --no-interaction
```

```php
<?php

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'users.view', 'users.create', 'users.update', 'users.delete',
        'users.deactivate', 'users.reset-password', 'users.invite',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWithPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);
    return $user;
}

test('viewAny requires users.view', function () {
    $policy = new UserPolicy();

    expect($policy->viewAny(userWithPerm('users.view')))->toBeTrue()
        ->and($policy->viewAny(User::factory()->create()))->toBeFalse();
});

test('create requires users.create', function () {
    $policy = new UserPolicy();

    expect($policy->create(userWithPerm('users.create')))->toBeTrue()
        ->and($policy->create(User::factory()->create()))->toBeFalse();
});

test('update requires users.update and cannot edit self', function () {
    $policy = new UserPolicy();
    $actor = userWithPerm('users.update');
    $other = User::factory()->create();

    expect($policy->update($actor, $other))->toBeTrue()
        ->and($policy->update($actor, $actor))->toBeFalse();
});

test('delete requires users.delete', function () {
    $policy = new UserPolicy();
    $actor = userWithPerm('users.delete');
    $other = User::factory()->create();

    expect($policy->delete($actor, $other))->toBeTrue()
        ->and($policy->delete(User::factory()->create(), $other))->toBeFalse();
});

test('deactivate requires users.deactivate and cannot deactivate self', function () {
    $policy = new UserPolicy();
    $actor = userWithPerm('users.deactivate');
    $other = User::factory()->create();

    expect($policy->deactivate($actor, $other))->toBeTrue()
        ->and($policy->deactivate($actor, $actor))->toBeFalse();
});

test('resetPassword requires users.reset-password', function () {
    $policy = new UserPolicy();

    expect($policy->resetPassword(userWithPerm('users.reset-password')))->toBeTrue()
        ->and($policy->resetPassword(User::factory()->create()))->toBeFalse();
});

test('invite requires users.invite', function () {
    $policy = new UserPolicy();

    expect($policy->invite(userWithPerm('users.invite')))->toBeTrue()
        ->and($policy->invite(User::factory()->create()))->toBeFalse();
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
vendor/bin/sail artisan test --compact --filter=UserPolicyTest
```

Expected: FAIL — `App\Policies\UserPolicy` not found.

- [ ] **Step 3: Create UserPolicy**

```php
<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        return $user->can('users.update');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can('users.delete');
    }

    public function deactivate(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        return $user->can('users.deactivate');
    }

    public function resetPassword(User $user): bool
    {
        return $user->can('users.reset-password');
    }

    public function invite(User $user): bool
    {
        return $user->can('users.invite');
    }
}
```

- [ ] **Step 4: Register in AppServiceProvider**

In `app/Providers/AppServiceProvider.php`, add `UserPolicy` import and registration:

```php
use App\Policies\UserPolicy;
```

In `configureAuthorization()`:

```php
protected function configureAuthorization(): void
{
    Gate::before(fn (User $user) => $user->hasRole('Admin') ? true : null);

    Gate::policy(Role::class, RolePolicy::class);
    Gate::policy(User::class, UserPolicy::class);
}
```

- [ ] **Step 5: Run tests**

```bash
vendor/bin/sail artisan test --compact --filter=UserPolicyTest
```

Expected: PASS — 7 tests.

- [ ] **Step 6: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Policies/UserPolicy.php app/Providers/AppServiceProvider.php tests/Feature/Security/UserPolicyTest.php
git commit -m "feat: add UserPolicy with 7 permission checks, register in AppServiceProvider"
```

---

## Task 4: Form Requests

**Files:**
- Create: `app/Http/Requests/Security/StoreUserRequest.php`
- Create: `app/Http/Requests/Security/UpdateUserRequest.php`
- Create: `app/Http/Requests/Security/ResetPasswordRequest.php`
- Create: `app/Http/Requests/Security/StoreInvitationRequest.php`
- Create: `app/Http/Requests/Auth/AcceptInvitationRequest.php`

- [ ] **Step 1: Create all form requests via Artisan**

```bash
vendor/bin/sail artisan make:request Security/StoreUserRequest --no-interaction
vendor/bin/sail artisan make:request Security/UpdateUserRequest --no-interaction
vendor/bin/sail artisan make:request Security/ResetPasswordRequest --no-interaction
vendor/bin/sail artisan make:request Security/StoreInvitationRequest --no-interaction
vendor/bin/sail artisan make:request Auth/AcceptInvitationRequest --no-interaction
```

- [ ] **Step 2: Write StoreUserRequest**

```php
<?php

namespace App\Http\Requests\Security;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'min:2', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'role'          => ['required', 'string', Rule::exists('roles', 'name')],
            'password_mode' => ['required', Rule::in(['link', 'manual', 'random'])],
            'password'      => [
                Rule::requiredIf(fn () => $this->input('password_mode') === 'manual'),
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }
}
```

- [ ] **Step 3: Write UpdateUserRequest**

```php
<?php

namespace App\Http\Requests\Security;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        if (! $target instanceof User) {
            return false;
        }

        return $this->user()?->can('update', $target) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return [
            'name'    => ['required', 'string', 'min:2', 'max:255'],
            'email'   => ['required', 'email', Rule::unique('users', 'email')->ignore($target->id)],
            'roles'   => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }
}
```

- [ ] **Step 4: Write ResetPasswordRequest**

```php
<?php

namespace App\Http\Requests\Security;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('resetPassword', \App\Models\User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password_mode' => ['required', Rule::in(['link', 'manual', 'random'])],
            'password'      => [
                Rule::requiredIf(fn () => $this->input('password_mode') === 'manual'),
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }
}
```

- [ ] **Step 5: Write StoreInvitationRequest**

```php
<?php

namespace App\Http\Requests\Security;

use App\Models\Invitation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('invite', \App\Models\User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::exists('roles', 'name'),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (Invitation::pending()->where('email', $value)->exists()) {
                        // Allowed — existing pending invite will be cancelled in controller
                    }
                },
            ],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rulesFixed(): array
    {
        return [
            'email' => ['required', 'email'],
            'role'  => ['required', 'string', Rule::exists('roles', 'name')],
        ];
    }
}
```

Wait — `StoreInvitationRequest` should NOT block duplicate emails (spec says cancel previous and create new). Remove that custom closure:

```php
<?php

namespace App\Http\Requests\Security;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('invite', \App\Models\User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'role'  => ['required', 'string', Rule::exists('roles', 'name')],
        ];
    }
}
```

- [ ] **Step 6: Write AcceptInvitationRequest**

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AcceptInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
```

- [ ] **Step 7: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add app/Http/Requests/
git commit -m "feat: add form requests for user/invitation CRUD and invitation acceptance"
```

---

## Task 5: UserController + Routes + UserControllerTest

**Files:**
- Create: `app/Http/Controllers/Security/UserController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Security/UserControllerTest.php`

- [ ] **Step 1: Write the failing tests**

```bash
vendor/bin/sail artisan make:test --pest Security/UserControllerTest --no-interaction
```

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'users.view', 'users.create', 'users.update', 'users.delete',
        'users.deactivate', 'users.reset-password', 'users.invite',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);
});

function userWithUserPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);
    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('unauthenticated redirects to login', function () {
    $this->get('/security/users')->assertRedirect('/login');
});

test('user without users.view gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get('/security/users')
        ->assertForbidden();
});

test('user with users.view sees users index', function () {
    $this->actingAs(userWithUserPerm('users.view'))
        ->get('/security/users')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('security/Users/Index', false)->has('users')->has('roles'));
});

test('index filters by search', function () {
    User::factory()->create(['name' => 'Ana García', 'email' => 'ana@test.com']);
    User::factory()->create(['name' => 'Pedro López', 'email' => 'pedro@test.com']);

    $this->actingAs(userWithUserPerm('users.view'))
        ->get('/security/users?search=Ana')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('users.total', 1));
});

test('index filters by status active', function () {
    User::factory()->create(['active' => true]);
    User::factory()->inactive()->create();

    $this->actingAs(userWithUserPerm('users.view'))
        ->get('/security/users?status=active')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('users.total', 2)); // includes the actor
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('user without users.create gets 403 on store', function () {
    $this->actingAs(User::factory()->create())
        ->post('/security/users', [])
        ->assertForbidden();
});

test('store with password_mode link creates user and sends reset', function () {
    Password::shouldReceive('sendResetLink')->once()->andReturn(Password::RESET_LINK_SENT);

    $actor = userWithUserPerm('users.create');

    $this->actingAs($actor)
        ->post('/security/users', [
            'name'          => 'Carlos Pérez',
            'email'         => 'carlos@test.com',
            'role'          => 'Profesor',
            'password_mode' => 'link',
        ])
        ->assertRedirect(route('security.users.index'));

    expect(User::where('email', 'carlos@test.com')->exists())->toBeTrue();
});

test('store with password_mode manual creates user with given password', function () {
    $actor = userWithUserPerm('users.create');

    $this->actingAs($actor)
        ->post('/security/users', [
            'name'                  => 'Laura Torres',
            'email'                 => 'laura@test.com',
            'role'                  => 'Profesor',
            'password_mode'         => 'manual',
            'password'              => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])
        ->assertRedirect(route('security.users.index'));

    $user = User::where('email', 'laura@test.com')->firstOrFail();
    expect(Hash::check('secret12345', $user->password))->toBeTrue();
});

test('store with password_mode random creates user and flashes password', function () {
    $actor = userWithUserPerm('users.create');

    $this->actingAs($actor)
        ->post('/security/users', [
            'name'          => 'Marta Díaz',
            'email'         => 'marta@test.com',
            'role'          => 'Profesor',
            'password_mode' => 'random',
        ])
        ->assertRedirect(route('security.users.index'));

    expect(User::where('email', 'marta@test.com')->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('update changes name, email and roles', function () {
    $actor  = userWithUserPerm('users.update');
    $target = User::factory()->create();

    $this->actingAs($actor)
        ->patch("/security/users/{$target->id}", [
            'name'  => 'Nombre Nuevo',
            'email' => 'nuevo@test.com',
            'roles' => ['Profesor'],
        ])
        ->assertRedirect(route('security.users.index'));

    $target->refresh();
    expect($target->name)->toBe('Nombre Nuevo')
        ->and($target->email)->toBe('nuevo@test.com')
        ->and($target->hasRole('Profesor'))->toBeTrue();
});

test('user cannot update self via this endpoint', function () {
    $actor = userWithUserPerm('users.update');

    $this->actingAs($actor)
        ->patch("/security/users/{$actor->id}", [
            'name'  => 'Self Edit',
            'email' => $actor->email,
            'roles' => [],
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('user without users.delete gets 403 on destroy', function () {
    $target = User::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete("/security/users/{$target->id}")
        ->assertForbidden();
});

test('destroy deletes user with no history', function () {
    $actor  = userWithUserPerm('users.delete');
    $target = User::factory()->create();

    $this->actingAs($actor)
        ->delete("/security/users/{$target->id}")
        ->assertRedirect(route('security.users.index'));

    expect(User::find($target->id))->toBeNull();
});

// ---------------------------------------------------------------------------
// deactivate
// ---------------------------------------------------------------------------

test('deactivate toggles active state', function () {
    $actor  = userWithUserPerm('users.deactivate');
    $target = User::factory()->create(['active' => true]);

    $this->actingAs($actor)
        ->patch("/security/users/{$target->id}/deactivate")
        ->assertRedirect(route('security.users.index'));

    expect($target->fresh()->active)->toBeFalse();
});

test('user cannot deactivate self', function () {
    $actor = userWithUserPerm('users.deactivate');

    $this->actingAs($actor)
        ->patch("/security/users/{$actor->id}/deactivate")
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// resetPassword
// ---------------------------------------------------------------------------

test('resetPassword with link mode sends reset email', function () {
    Password::shouldReceive('sendResetLink')->once()->andReturn(Password::RESET_LINK_SENT);

    $actor  = userWithUserPerm('users.reset-password');
    $target = User::factory()->create();

    $this->actingAs($actor)
        ->post("/security/users/{$target->id}/reset-password", [
            'password_mode' => 'link',
        ])
        ->assertRedirect(route('security.users.index'));
});

test('resetPassword with manual mode updates password', function () {
    $actor  = userWithUserPerm('users.reset-password');
    $target = User::factory()->create();

    $this->actingAs($actor)
        ->post("/security/users/{$target->id}/reset-password", [
            'password_mode'         => 'manual',
            'password'              => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])
        ->assertRedirect(route('security.users.index'));

    expect(Hash::check('newpassword1', $target->fresh()->password))->toBeTrue();
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
vendor/bin/sail artisan test --compact --filter=UserControllerTest
```

Expected: FAIL — route not found / controller not found.

- [ ] **Step 3: Add routes to routes/web.php**

In `routes/web.php`, add user routes inside the existing `security` middleware group and add the new user-specific routes:

```php
use App\Http\Controllers\Security\InvitationController;
use App\Http\Controllers\Security\UserController;
```

Then add inside the existing security group (after the roles routes):

```php
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
```

Also add public invitation routes at the end of `routes/web.php` (before `require __DIR__.'/settings.php'`):

```php
use App\Http\Controllers\Auth\AcceptInvitationController;

Route::get('invitation/{token}', [AcceptInvitationController::class, 'show'])->name('invitation.show');
Route::post('invitation/{token}', [AcceptInvitationController::class, 'store'])->name('invitation.store');
```

- [ ] **Step 4: Create UserController**

```bash
vendor/bin/sail artisan make:controller Security/UserController --no-interaction
```

```php
<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\ResetPasswordRequest;
use App\Http\Requests\Security\StoreUserRequest;
use App\Http\Requests\Security\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $actor = $request->user();

        $query = User::query()->with('roles:id,name');

        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q->where('name', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%"));
        }

        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        if ($status = $request->input('status')) {
            match ($status) {
                'active'   => $query->where('active', true),
                'inactive' => $query->where('active', false),
                default    => null,
            };
        }

        $users = $query->orderBy('name')->paginate(20)->through(fn (User $user) => [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'active'     => $user->active,
            'roles'      => $user->roles->pluck('name')->values(),
            'created_at' => $user->created_at?->toDateString(),
        ]);

        return Inertia::render('security/Users/Index', [
            'users'   => $users,
            'roles'   => Role::orderBy('name')->pluck('name')->values(),
            'filters' => $request->only('search', 'role', 'status'),
            'can'     => [
                'create' => $actor->can('create', User::class),
                'invite' => $actor->can('invite', User::class),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $password = match ($data['password_mode']) {
            'manual' => $data['password'],
            'random' => Str::random(16),
            default  => null,
        };

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $password ? Hash::make($password) : Hash::make(Str::random(32)),
            'active'   => true,
        ]);

        $user->syncRoles([$data['role']]);

        match ($data['password_mode']) {
            'link'   => Password::sendResetLink(['email' => $user->email]),
            'random' => Inertia::flash('toast', [
                'type'     => 'password',
                'message'  => "Usuario creado. Contraseña generada: {$password}",
                'password' => $password,
            ]),
            default  => null,
        };

        if ($data['password_mode'] !== 'random') {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario creado.']);
        }

        return to_route('security.users.index');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->update([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        $user->syncRoles($data['roles'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario actualizado.']);

        return to_route('security.users.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario eliminado.']);

        return to_route('security.users.index');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        $user->update(['active' => ! $user->active]);

        $msg = $user->active ? 'Usuario reactivado.' : 'Usuario desactivado.';
        Inertia::flash('toast', ['type' => 'success', 'message' => $msg]);

        return to_route('security.users.index');
    }

    public function resetPassword(ResetPasswordRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $password = match ($data['password_mode']) {
            'manual' => $data['password'],
            'random' => Str::random(16),
            default  => null,
        };

        match ($data['password_mode']) {
            'link' => Password::sendResetLink(['email' => $user->email]),
            default => $user->update(['password' => Hash::make($password)]),
        };

        if ($data['password_mode'] === 'random') {
            Inertia::flash('toast', [
                'type'     => 'password',
                'message'  => "Contraseña cambiada. Nueva contraseña: {$password}",
                'password' => $password,
            ]);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Contraseña actualizada.']);
        }

        return to_route('security.users.index');
    }
}
```

- [ ] **Step 5: Run tests**

```bash
vendor/bin/sail artisan test --compact --filter=UserControllerTest
```

Expected: PASS — all tests green.

- [ ] **Step 6: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Security/UserController.php routes/web.php tests/Feature/Security/UserControllerTest.php
git commit -m "feat: UserController with index/store/update/destroy/deactivate/resetPassword"
```

---

## Task 6: InvitationController + InvitationMail + InvitationControllerTest

**Files:**
- Create: `app/Http/Controllers/Security/InvitationController.php`
- Create: `app/Mail/InvitationMail.php`
- Create: `resources/views/mail/invitation.blade.php`
- Create: `tests/Feature/Security/InvitationControllerTest.php`

- [ ] **Step 1: Write the failing tests**

```bash
vendor/bin/sail artisan make:test --pest Security/InvitationControllerTest --no-interaction
```

```php
<?php

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'users.invite', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);
});

function userWithInvitePerm(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('users.invite');
    return $user;
}

test('unauthenticated cannot send invitation', function () {
    $this->post('/security/invitations', [])->assertRedirect('/login');
});

test('user without users.invite gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->post('/security/invitations', ['email' => 'x@test.com', 'role' => 'Profesor'])
        ->assertForbidden();
});

test('store creates invitation and sends mail', function () {
    Mail::fake();

    $actor = userWithInvitePerm();

    $this->actingAs($actor)
        ->post('/security/invitations', ['email' => 'invite@test.com', 'role' => 'Profesor'])
        ->assertRedirect(route('security.users.index'));

    expect(Invitation::where('email', 'invite@test.com')->pending()->exists())->toBeTrue();

    Mail::assertSent(InvitationMail::class, fn ($mail) => $mail->hasTo('invite@test.com'));
});

test('store cancels existing pending invitation for same email', function () {
    Mail::fake();

    $actor = userWithInvitePerm();
    $old   = Invitation::factory()->create(['email' => 'dup@test.com']);

    $this->actingAs($actor)
        ->post('/security/invitations', ['email' => 'dup@test.com', 'role' => 'Profesor'])
        ->assertRedirect(route('security.users.index'));

    expect(Invitation::find($old->id))->toBeNull()
        ->and(Invitation::where('email', 'dup@test.com')->pending()->count())->toBe(1);
});

test('destroy deletes a pending invitation', function () {
    Permission::firstOrCreate(['name' => 'users.invite', 'guard_name' => 'web']);

    $actor      = userWithInvitePerm();
    $invitation = Invitation::factory()->create();

    $this->actingAs($actor)
        ->delete("/security/invitations/{$invitation->id}")
        ->assertRedirect(route('security.users.index'));

    expect(Invitation::find($invitation->id))->toBeNull();
});

test('destroy cannot delete a used invitation', function () {
    $actor      = userWithInvitePerm();
    $invitation = Invitation::factory()->used()->create();

    $this->actingAs($actor)
        ->delete("/security/invitations/{$invitation->id}")
        ->assertForbidden();
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
vendor/bin/sail artisan test --compact --filter=InvitationControllerTest
```

Expected: FAIL — mail class and controller not found.

- [ ] **Step 3: Create InvitationMail**

```bash
vendor/bin/sail artisan make:mail InvitationMail --markdown=mail.invitation --no-interaction
```

Then write `app/Mail/InvitationMail.php`:

```php
<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Invitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to:      $this->invitation->email,
            subject: 'Te invitaron a CACAO',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.invitation',
            with: [
                'acceptUrl'  => route('invitation.show', $this->invitation->token),
                'expiresAt'  => $this->invitation->expires_at->format('d/m/Y H:i'),
                'inviterName' => $this->invitation->invitedBy?->name ?? 'El equipo CACAO',
            ],
        );
    }
}
```

- [ ] **Step 4: Create mail blade view**

Create `resources/views/mail/invitation.blade.php`:

```html
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Invitación a CACAO</title>
</head>
<body style="font-family:sans-serif;background:#F4F2EF;margin:0;padding:32px;">
<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:8px;padding:40px;">
    <h1 style="font-size:24px;color:#131110;margin:0 0 8px;">Te invitaron a CACAO</h1>
    <p style="color:#888780;margin:0 0 24px;">{{ $inviterName }} te invitó a acceder a la plataforma académica CACAO.</p>
    <a href="{{ $acceptUrl }}"
       style="display:inline-block;background:#C8521A;color:#fff;text-decoration:none;padding:12px 24px;border-radius:6px;font-weight:600;">
        Aceptar invitación
    </a>
    <p style="color:#888780;font-size:13px;margin:24px 0 0;">
        Este enlace expira el {{ $expiresAt }}.<br>
        Si no esperabas esta invitación, podés ignorar este correo.
    </p>
</div>
</body>
</html>
```

- [ ] **Step 5: Create InvitationController**

```bash
vendor/bin/sail artisan make:controller Security/InvitationController --no-interaction
```

```php
<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreInvitationRequest;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Invitation::pending()->where('email', $data['email'])->delete();

        $invitation = Invitation::create([
            'email'      => $data['email'],
            'role'       => $data['role'],
            'token'      => Str::uuid()->toString(),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addHours(48),
        ]);

        Mail::send(new InvitationMail($invitation));

        Inertia::flash('toast', [
            'type'    => 'success',
            'message' => "Invitación enviada a {$data['email']}. Expira en 48 horas.",
        ]);

        return to_route('security.users.index');
    }

    public function destroy(Request $request, Invitation $invitation): RedirectResponse
    {
        Gate::authorize('invite', \App\Models\User::class);

        abort_if($invitation->isUsed(), 403);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invitación cancelada.']);

        return to_route('security.users.index');
    }
}
```

- [ ] **Step 6: Run tests**

```bash
vendor/bin/sail artisan test --compact --filter=InvitationControllerTest
```

Expected: PASS — all tests green.

- [ ] **Step 7: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Security/InvitationController.php app/Mail/InvitationMail.php resources/views/mail/ tests/Feature/Security/InvitationControllerTest.php
git commit -m "feat: InvitationController, InvitationMail, cancel-and-resend logic"
```

---

## Task 7: AcceptInvitationController + AcceptInvitationControllerTest

**Files:**
- Create: `app/Http/Controllers/Auth/AcceptInvitationController.php`
- Create: `tests/Feature/Auth/AcceptInvitationControllerTest.php`

- [ ] **Step 1: Write the failing tests**

```bash
vendor/bin/sail artisan make:test --pest Auth/AcceptInvitationControllerTest --no-interaction
```

```php
<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);
});

test('show with valid token renders accept invitation page', function () {
    $invitation = Invitation::factory()->create(['role' => 'Profesor']);

    $this->get(route('invitation.show', $invitation->token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/AcceptInvitation', false)
            ->where('inviteEmail', $invitation->email)
            ->where('expired', false)
        );
});

test('show with expired token renders expired page', function () {
    $invitation = Invitation::factory()->expired()->create();

    $this->get(route('invitation.show', $invitation->token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('expired', true));
});

test('show with used token renders expired page', function () {
    $invitation = Invitation::factory()->used()->create();

    $this->get(route('invitation.show', $invitation->token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('expired', true));
});

test('show with unknown token returns 404', function () {
    $this->get(route('invitation.show', 'invalid-token'))->assertNotFound();
});

test('store creates user, assigns role, marks invitation used, and logs in', function () {
    $invitation = Invitation::factory()->create(['email' => 'nuevo@test.com', 'role' => 'Profesor']);

    $this->post(route('invitation.store', $invitation->token), [
        'name'                  => 'Nuevo Usuario',
        'password'              => 'password12',
        'password_confirmation' => 'password12',
    ])->assertRedirect();

    $user = User::where('email', 'nuevo@test.com')->firstOrFail();
    expect($user->hasRole('Profesor'))->toBeTrue()
        ->and($user->active)->toBeTrue();

    $invitation->refresh();
    expect($invitation->used_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('store on expired invitation returns 422', function () {
    $invitation = Invitation::factory()->expired()->create();

    $this->post(route('invitation.store', $invitation->token), [
        'name'                  => 'Test User',
        'password'              => 'password12',
        'password_confirmation' => 'password12',
    ])->assertStatus(422);
});

test('store on used invitation returns 422', function () {
    $invitation = Invitation::factory()->used()->create();

    $this->post(route('invitation.store', $invitation->token), [
        'name'                  => 'Test User',
        'password'              => 'password12',
        'password_confirmation' => 'password12',
    ])->assertStatus(422);
});

test('invitation token is single use', function () {
    $invitation = Invitation::factory()->create(['email' => 'once@test.com', 'role' => 'Profesor']);

    $this->post(route('invitation.store', $invitation->token), [
        'name'                  => 'First',
        'password'              => 'password12',
        'password_confirmation' => 'password12',
    ]);

    $this->post(route('invitation.store', $invitation->token), [
        'name'                  => 'Second',
        'password'              => 'password12',
        'password_confirmation' => 'password12',
    ])->assertStatus(422);
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
vendor/bin/sail artisan test --compact --filter=AcceptInvitationControllerTest
```

Expected: FAIL — controller/route not found.

- [ ] **Step 3: Create AcceptInvitationController**

```bash
vendor/bin/sail artisan make:controller Auth/AcceptInvitationController --no-interaction
```

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcceptInvitationController extends Controller
{
    public function show(string $token): Response
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            return Inertia::render('auth/AcceptInvitation', [
                'expired'     => true,
                'inviteEmail' => $invitation->email,
                'inviteRole'  => $invitation->role,
                'token'       => $token,
            ]);
        }

        return Inertia::render('auth/AcceptInvitation', [
            'expired'       => false,
            'inviteEmail'   => $invitation->email,
            'inviteRole'    => $invitation->role,
            'inviteExpiresIn' => $invitation->expires_at->diffForHumans(),
            'token'         => $token,
        ]);
    }

    public function store(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'token' => ['Esta invitación ya fue usada o ha expirado.'],
            ]);
        }

        $data = $request->validated();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $invitation->email,
            'password' => Hash::make($data['password']),
            'active'   => true,
        ]);

        $user->syncRoles([$invitation->role]);

        $invitation->update(['used_at' => now()]);

        Auth::login($user);

        return redirect()->intended(route('dashboard', $user->currentTeam ?? [], false));
    }
}
```

Wait — the dashboard route requires a team slug. Since users created via invitation don't have a team yet (the `UserFactory` `afterCreating` creates a team, but this controller creates users directly), we need to either create a personal team here or redirect to a simpler URL. Looking at the existing `RegisterResponse`, let me check what it does.

Actually, looking at the `CreateNewUser` action pattern, it also creates a team. For the `AcceptInvitationController`, I should do the same. Let me check if there's a `HasTeams` concern that creates teams.

For now, redirect to `/` (home) after login — the dashboard middleware will handle routing to the correct team. This simplifies the controller:

```php
        Auth::login($user);

        return redirect('/');
    }
```

The full controller becomes:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcceptInvitationController extends Controller
{
    public function show(string $token): Response
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            return Inertia::render('auth/AcceptInvitation', [
                'expired'     => true,
                'inviteEmail' => $invitation->email,
                'inviteRole'  => $invitation->role,
                'token'       => $token,
            ]);
        }

        return Inertia::render('auth/AcceptInvitation', [
            'expired'         => false,
            'inviteEmail'     => $invitation->email,
            'inviteRole'      => $invitation->role,
            'inviteExpiresIn' => $invitation->expires_at->diffForHumans(),
            'token'           => $token,
        ]);
    }

    public function store(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'token' => ['Esta invitación ya fue usada o ha expirado.'],
            ]);
        }

        $data = $request->validated();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $invitation->email,
            'password' => Hash::make($data['password']),
            'active'   => true,
        ]);

        $user->syncRoles([$invitation->role]);

        $invitation->update(['used_at' => now()]);

        Auth::login($user);

        return redirect('/');
    }
}
```

- [ ] **Step 4: Run tests**

```bash
vendor/bin/sail artisan test --compact --filter=AcceptInvitationControllerTest
```

Expected: PASS — all tests green. Note: the `store` creates a User directly without team; if `HasTeams` `afterCreating` logic causes issues, add a personal team creation inside the controller matching the `CreateNewUser` action pattern.

- [ ] **Step 5: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Auth/AcceptInvitationController.php tests/Feature/Auth/AcceptInvitationControllerTest.php
git commit -m "feat: AcceptInvitationController — public invitation acceptance flow"
```

---

## Task 8: Fortify inactive-user block

**Files:**
- Modify: `app/Providers/FortifyServiceProvider.php`

- [ ] **Step 1: Write the failing test**

```bash
vendor/bin/sail artisan make:test --pest Auth/InactiveUserLoginTest --no-interaction
```

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

test('inactive user cannot log in and gets error message', function () {
    $user = User::factory()->inactive()->create();

    $this->post('/login', [
        'email'    => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('active user can log in normally', function () {
    $user = User::factory()->create(['active' => true]);

    $this->post('/login', [
        'email'    => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($user);
});
```

- [ ] **Step 2: Run test to confirm inactive-user test fails**

```bash
vendor/bin/sail artisan test --compact --filter=InactiveUserLoginTest
```

Expected: inactive user test FAILS (currently logs in without issue).

- [ ] **Step 3: Add authenticateUsing to FortifyServiceProvider**

In `app/Providers/FortifyServiceProvider.php`, add to the imports:

```php
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
```

In `configureActions()`:

```php
private function configureActions(): void
{
    Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    Fortify::createUsersUsing(CreateNewUser::class);

    Fortify::authenticateUsing(function (Request $request) {
        $user = \App\Models\User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return null;
        }

        if (! $user->active) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta está desactivada. Contactá al administrador.'],
            ]);
        }

        return $user;
    });
}
```

- [ ] **Step 4: Run tests**

```bash
vendor/bin/sail artisan test --compact --filter=InactiveUserLoginTest
```

Expected: PASS — both tests green.

- [ ] **Step 5: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Providers/FortifyServiceProvider.php tests/Feature/Auth/InactiveUserLoginTest.php
git commit -m "feat: block inactive users from logging in via Fortify::authenticateUsing"
```

---

## Task 9: Wayfinder + Types + Users/Index.vue (table + filters)

**Files:**
- Modify: `resources/js/types/security.ts`
- Create: `resources/js/pages/security/Users/Index.vue`
- Wayfinder auto-generates: `resources/js/routes/security/users/index.ts` and `resources/js/actions/App/Http/Controllers/Security/UserController.ts`

- [ ] **Step 1: Regenerate Wayfinder**

```bash
vendor/bin/sail artisan wayfinder:generate --no-interaction
```

Expected: generates `resources/js/routes/security/users/index.ts` and action files for `UserController` and `InvitationController`.

- [ ] **Step 2: Add UserRow type to security.ts**

Append to `resources/js/types/security.ts`:

```typescript
export type UserRow = {
    id: number;
    name: string;
    email: string;
    active: boolean;
    roles: string[];
    created_at: string;
};

export type UserPaginator = {
    data: UserRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};
```

- [ ] **Step 3: Create Users/Index.vue**

Create `resources/js/pages/security/Users/Index.vue`:

```vue
<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import Badge from '@/components/base/Badge.vue'
import Button from '@/components/base/Button.vue'
import CreateUserModal from '@/components/security/CreateUserModal.vue'
import DeactivateUserModal from '@/components/security/DeactivateUserModal.vue'
import DeleteUserModal from '@/components/security/DeleteUserModal.vue'
import EditUserModal from '@/components/security/EditUserModal.vue'
import InviteUserModal from '@/components/security/InviteUserModal.vue'
import ResetPasswordModal from '@/components/security/ResetPasswordModal.vue'
import { usePermission } from '@/composables/usePermission'
import { index } from '@/routes/security/users'
import type { UserPaginator, UserRow } from '@/types'

type Props = {
    users: UserPaginator
    roles: string[]
    filters: { search?: string; role?: string; status?: string }
    can: { create: boolean; invite: boolean }
}

const props = defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Seguridad', href: '#' },
            { title: 'Usuarios', href: index.url() },
        ],
    },
})

const { can } = usePermission()

const search = ref(props.filters.search ?? '')
const roleFilter = ref(props.filters.role ?? '')
const statusFilter = ref(props.filters.status ?? '')

let debounceTimer: ReturnType<typeof setTimeout>

function applyFilters(): void {
    router.get(index.url(), {
        search:  search.value || undefined,
        role:    roleFilter.value || undefined,
        status:  statusFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

function onSearchInput(): void {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(applyFilters, 350)
}

const editingUser    = ref<UserRow | null>(null)
const resetUser      = ref<UserRow | null>(null)
const deactivateUser = ref<UserRow | null>(null)
const deleteUser     = ref<UserRow | null>(null)
const showCreate     = ref(false)
const showInvite     = ref(false)

const authId = window.__page?.props?.auth?.user?.id
</script>

<template>
    <Head title="Usuarios" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <!-- Header -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Usuarios
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Gestiona los usuarios y sus accesos al sistema
                </p>
            </div>
            <div style="display:flex;gap:8px;">
                <Button
                    v-if="props.can.invite"
                    variant="ghost"
                    icon="mail"
                    @click="showInvite = true"
                >
                    Invitar por correo
                </Button>
                <Button
                    v-if="props.can.create"
                    variant="primary"
                    icon="plus"
                    @click="showCreate = true"
                >
                    Nuevo usuario
                </Button>
            </div>
        </div>

        <!-- Filters -->
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <input
                v-model="search"
                type="search"
                placeholder="Buscar por nombre o correo..."
                class="input"
                style="flex:1;min-width:200px;max-width:320px;"
                @input="onSearchInput"
            />
            <select v-model="roleFilter" class="input" style="width:160px;" @change="applyFilters">
                <option value="">Todos los roles</option>
                <option v-for="r in props.roles" :key="r" :value="r">{{ r }}</option>
            </select>
            <select v-model="statusFilter" class="input" style="width:160px;" @change="applyFilters">
                <option value="">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </select>
        </div>

        <!-- Table -->
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Roles</th>
                        <th>Estado</th>
                        <th>Creado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in props.users.data" :key="user.id">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--accent-light);color:var(--accent);display:grid;place-items:center;font-weight:600;font-size:13px;flex-shrink:0;">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div style="font-weight:500;color:var(--text-primary);">{{ user.name }}</div>
                                    <div style="font-size:var(--text-xs);color:var(--text-muted);">{{ user.email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                <Badge v-for="role in user.roles" :key="role" variant="default">{{ role }}</Badge>
                                <span v-if="!user.roles.length" style="color:var(--text-muted);font-style:italic;font-size:var(--text-sm);">Sin rol</span>
                            </div>
                        </td>
                        <td>
                            <Badge :variant="user.active ? 'success' : 'error'">
                                {{ user.active ? 'Activo' : 'Inactivo' }}
                            </Badge>
                        </td>
                        <td style="color:var(--text-muted);font-size:var(--text-sm);">
                            {{ user.created_at }}
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    v-if="can('users.update') && user.id !== authId"
                                    variant="ghost" size="sm" icon-only icon="edit"
                                    :aria-label="`Editar ${user.name}`"
                                    @click="editingUser = user"
                                />
                                <Button
                                    v-if="can('users.reset-password')"
                                    variant="ghost" size="sm" icon-only icon="key"
                                    :aria-label="`Cambiar contraseña de ${user.name}`"
                                    @click="resetUser = user"
                                />
                                <Button
                                    v-if="can('users.deactivate') && user.id !== authId"
                                    variant="ghost" size="sm" icon-only
                                    :icon="user.active ? 'toggle-right' : 'toggle-left'"
                                    :aria-label="user.active ? `Desactivar ${user.name}` : `Reactivar ${user.name}`"
                                    @click="deactivateUser = user"
                                />
                                <Button
                                    v-if="can('users.delete')"
                                    variant="ghost" size="sm" icon-only icon="trash"
                                    :aria-label="`Eliminar ${user.name}`"
                                    @click="deleteUser = user"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!props.users.data.length">
                        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay usuarios que coincidan con los filtros
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="props.users.last_page > 1" style="display:flex;justify-content:center;gap:4px;">
            <template v-for="link in props.users.links" :key="link.label">
                <button
                    v-if="link.url"
                    :disabled="link.active"
                    style="padding:4px 10px;border-radius:4px;border:1px solid var(--border);background:var(--bg-soft);font-size:var(--text-sm);cursor:pointer;"
                    :style="link.active ? 'background:var(--accent);color:#fff;border-color:var(--accent);' : ''"
                    @click="router.get(link.url)"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>

    <CreateUserModal
        :open="showCreate"
        :roles="props.roles"
        @update:open="showCreate = $event"
    />

    <InviteUserModal
        :open="showInvite"
        :roles="props.roles"
        @update:open="showInvite = $event"
    />

    <EditUserModal
        v-if="editingUser"
        :open="!!editingUser"
        :user="editingUser"
        :roles="props.roles"
        @update:open="v => { if (!v) editingUser = null }"
    />

    <ResetPasswordModal
        v-if="resetUser"
        :open="!!resetUser"
        :user="resetUser"
        @update:open="v => { if (!v) resetUser = null }"
    />

    <DeactivateUserModal
        v-if="deactivateUser"
        :open="!!deactivateUser"
        :user="deactivateUser"
        @update:open="v => { if (!v) deactivateUser = null }"
    />

    <DeleteUserModal
        v-if="deleteUser"
        :open="!!deleteUser"
        :user="deleteUser"
        @update:open="v => { if (!v) deleteUser = null }"
    />
</template>
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/security/Users/ resources/js/types/security.ts resources/js/routes/ resources/js/actions/
git commit -m "feat: Users/Index.vue with table, filters, pagination, and modal slots"
```

---

## Task 10: CreateUserModal + InviteUserModal

**Files:**
- Create: `resources/js/components/security/CreateUserModal.vue`
- Create: `resources/js/components/security/InviteUserModal.vue`

- [ ] **Step 1: Create CreateUserModal.vue**

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { store } from '@/routes/security/users'

defineProps<{
    open: boolean
    roles: string[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()
const formKey = ref(0)
const passwordMode = ref<'link' | 'manual' | 'random'>('link')

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) {
        formKey.value++
        passwordMode.value = 'link'
    }
}
</script>

<template>
    <Modal
        :open="open"
        title="Nuevo usuario"
        description="Crea una cuenta directamente. El usuario podrá cambiar sus datos después."
        size="md"
        @update:open="close"
    >
        <Form
            :key="formKey"
            v-bind="store.form()"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <input type="hidden" name="password_mode" :value="passwordMode" />

            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cu-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Nombre completo</label>
                    <input id="cu-name" name="name" class="input" placeholder="María González" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cu-email" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Correo institucional</label>
                    <input id="cu-email" name="email" type="email" class="input" placeholder="maria@institucion.edu.ve" required />
                    <InputError :message="errors.email" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cu-role" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Rol</label>
                    <select id="cu-role" name="role" class="input" required>
                        <option value="">Seleccionar rol...</option>
                        <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                    </select>
                    <InputError :message="errors.role" />
                </div>

                <div style="display:grid;gap:8px;">
                    <span style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Contraseña inicial</span>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <button
                            v-for="mode in ([['link','Enviar link'],['manual','Escribir'],['random','Generar']] as const)"
                            :key="mode[0]"
                            type="button"
                            style="flex:1;padding:6px 10px;border-radius:6px;font-size:var(--text-sm);border:1px solid var(--border);cursor:pointer;transition:all 0.15s;"
                            :style="passwordMode === mode[0] ? 'background:var(--accent);color:#fff;border-color:var(--accent);' : 'background:var(--bg-soft);color:var(--text-secondary);'"
                            @click="passwordMode = mode[0]"
                        >
                            {{ mode[1] }}
                        </button>
                    </div>

                    <p v-if="passwordMode === 'random'" style="font-size:var(--text-xs);color:var(--text-muted);margin:0;">
                        La contraseña se mostrará una sola vez al confirmar.
                    </p>

                    <template v-if="passwordMode === 'manual'">
                        <input name="password" type="password" class="input" placeholder="Contraseña (mín. 8 caracteres)" minlength="8" required />
                        <input name="password_confirmation" type="password" class="input" placeholder="Confirmar contraseña" required />
                        <InputError :message="errors.password" />
                    </template>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Crear usuario</Button>
            </div>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 2: Create InviteUserModal.vue**

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { store } from '@/routes/security/invitations'

defineProps<{
    open: boolean
    roles: string[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()
const formKey = ref(0)

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) formKey.value++
}
</script>

<template>
    <Modal
        :open="open"
        title="Invitar por correo"
        description="El usuario recibirá un enlace para crear su contraseña. Expira en 48 horas."
        size="sm"
        @update:open="close"
    >
        <Form
            :key="formKey"
            v-bind="store.form()"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="inv-email" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Correo institucional</label>
                    <input id="inv-email" name="email" type="email" class="input" placeholder="correo@institucion.edu.ve" required />
                    <InputError :message="errors.email" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="inv-role" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Rol</label>
                    <select id="inv-role" name="role" class="input" required>
                        <option value="">Seleccionar rol...</option>
                        <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                    </select>
                    <InputError :message="errors.role" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Enviar invitación</Button>
            </div>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/security/CreateUserModal.vue resources/js/components/security/InviteUserModal.vue
git commit -m "feat: CreateUserModal (3 password modes) and InviteUserModal"
```

---

## Task 11: EditUserModal + ResetPasswordModal

**Files:**
- Create: `resources/js/components/security/EditUserModal.vue`
- Create: `resources/js/components/security/ResetPasswordModal.vue`

- [ ] **Step 1: Create EditUserModal.vue**

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { update } from '@/routes/security/users'
import type { UserRow } from '@/types'

const props = defineProps<{
    open: boolean
    user: UserRow
    roles: string[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()
const formKey = ref(0)

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) formKey.value++
}
</script>

<template>
    <Modal
        :open="open"
        title="Editar usuario"
        :description="`Modifica el nombre, correo o roles de ${user.name}.`"
        size="md"
        @update:open="close"
    >
        <Form
            :key="formKey"
            v-bind="update.form(user)"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="eu-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Nombre completo</label>
                    <input id="eu-name" name="name" class="input" :value="user.name" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="eu-email" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Correo institucional</label>
                    <input id="eu-email" name="email" type="email" class="input" :value="user.email" required />
                    <InputError :message="errors.email" />
                </div>

                <div style="display:grid;gap:8px;">
                    <span style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Roles</span>
                    <div style="display:grid;gap:6px;padding-left:4px;">
                        <label
                            v-for="r in roles"
                            :key="r"
                            style="display:flex;align-items:center;gap:8px;font-size:var(--text-sm);cursor:pointer;"
                        >
                            <input
                                type="checkbox"
                                name="roles[]"
                                :value="r"
                                :checked="user.roles.includes(r)"
                                style="width:14px;height:14px;accent-color:var(--accent);"
                            />
                            {{ r }}
                        </label>
                    </div>
                    <InputError :message="errors.roles" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Guardar cambios</Button>
            </div>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 2: Create ResetPasswordModal.vue**

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { resetPassword } from '@/routes/security/users'
import type { UserRow } from '@/types'

const props = defineProps<{
    open: boolean
    user: UserRow
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()
const formKey = ref(0)
const passwordMode = ref<'link' | 'manual' | 'random'>('link')

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) {
        formKey.value++
        passwordMode.value = 'link'
    }
}
</script>

<template>
    <Modal
        :open="open"
        title="Cambiar contraseña"
        size="sm"
        @update:open="close"
    >
        <div style="padding:12px 0 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--border);margin-bottom:16px;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--accent-light);color:var(--accent);display:grid;place-items:center;font-weight:600;flex-shrink:0;">
                {{ user.name.charAt(0).toUpperCase() }}
            </div>
            <div>
                <div style="font-weight:500;color:var(--text-primary);">{{ user.name }}</div>
                <div style="font-size:var(--text-xs);color:var(--text-muted);">{{ user.email }}</div>
            </div>
        </div>

        <Form
            :key="formKey"
            v-bind="resetPassword.form(user)"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <input type="hidden" name="password_mode" :value="passwordMode" />

            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:8px;">
                    <span style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Método</span>
                    <div style="display:flex;gap:6px;">
                        <button
                            v-for="mode in ([['link','Enviar link'],['manual','Escribir'],['random','Generar']] as const)"
                            :key="mode[0]"
                            type="button"
                            style="flex:1;padding:6px 10px;border-radius:6px;font-size:var(--text-sm);border:1px solid var(--border);cursor:pointer;"
                            :style="passwordMode === mode[0] ? 'background:var(--accent);color:#fff;border-color:var(--accent);' : 'background:var(--bg-soft);color:var(--text-secondary);'"
                            @click="passwordMode = mode[0]"
                        >
                            {{ mode[1] }}
                        </button>
                    </div>
                </div>

                <template v-if="passwordMode === 'manual'">
                    <input name="password" type="password" class="input" placeholder="Nueva contraseña" minlength="8" required />
                    <input name="password_confirmation" type="password" class="input" placeholder="Confirmar" required />
                    <InputError :message="errors.password" />
                </template>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Aplicar</Button>
            </div>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/security/EditUserModal.vue resources/js/components/security/ResetPasswordModal.vue
git commit -m "feat: EditUserModal and ResetPasswordModal with 3 password modes"
```

---

## Task 12: DeactivateUserModal + DeleteUserModal

**Files:**
- Create: `resources/js/components/security/DeactivateUserModal.vue`
- Create: `resources/js/components/security/DeleteUserModal.vue`

- [ ] **Step 1: Create DeactivateUserModal.vue**

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import { deactivate } from '@/routes/security/users'
import type { UserRow } from '@/types'

const props = defineProps<{
    open: boolean
    user: UserRow
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    useForm({}).patch(deactivate.url(props.user), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal
        :open="open"
        :title="user.active ? 'Desactivar usuario' : 'Reactivar usuario'"
        size="sm"
        @update:open="close"
    >
        <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
            <template v-if="user.active">
                ¿Desactivar a <strong>{{ user.name }}</strong>? No podrá iniciar sesión hasta que reactives su cuenta.
            </template>
            <template v-else>
                ¿Reactivar acceso de <strong>{{ user.name }}</strong>? Podrá iniciar sesión nuevamente.
            </template>
        </p>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button :variant="user.active ? 'danger' : 'primary'" @click="submit">
                {{ user.active ? 'Desactivar' : 'Reactivar' }}
            </Button>
        </div>
    </Modal>
</template>
```

- [ ] **Step 2: Create DeleteUserModal.vue**

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/security/users'
import type { UserRow } from '@/types'

const props = defineProps<{
    open: boolean
    user: UserRow
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    useForm({}).delete(destroy.url(props.user), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal
        :open="open"
        title="Eliminar usuario"
        size="sm"
        @update:open="close"
    >
        <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
            ¿Eliminar a <strong>{{ user.name }}</strong> ({{ user.email }})? Esta acción no se puede deshacer.
        </p>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button variant="danger" @click="submit">Eliminar</Button>
        </div>
    </Modal>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/security/DeactivateUserModal.vue resources/js/components/security/DeleteUserModal.vue
git commit -m "feat: DeactivateUserModal and DeleteUserModal"
```

---

## Task 13: AcceptInvitation.vue (auth page)

**Files:**
- Create: `resources/js/pages/auth/AcceptInvitation.vue`

- [ ] **Step 1: Create AcceptInvitation.vue**

```vue
<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3'
import Button from '@/components/base/Button.vue'
import InputError from '@/components/InputError.vue'
import PasswordInput from '@/components/PasswordInput.vue'

const props = defineProps<{
    expired: boolean
    inviteEmail: string
    inviteRole?: string
    inviteExpiresIn?: string
    token: string
}>()

setLayoutProps({
    title:          props.expired ? 'Invitación expirada' : 'Aceptar invitación',
    description:    props.expired
        ? 'Este enlace ya fue utilizado o ha expirado.'
        : 'Completá tus datos para activar tu acceso a CACAO.',
    panelQuote:     'Tu acceso a CACAO comienza con',
    panelHighlight: 'una invitación institucional.',
    panelRole:      'Invitación institucional',
    panelContext:   'Acceso seguro',
})

const acceptUrl = `/invitation/${props.token}`
</script>

<template>
    <Head :title="expired ? 'Invitación expirada' : 'Aceptar invitación'" />

    <div v-if="expired" style="display:flex;flex-direction:column;align-items:center;gap:16px;padding:24px 0;text-align:center;">
        <div style="width:56px;height:56px;border-radius:50%;background:#fee2e2;display:grid;place-items:center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
        </div>
        <h2 style="font-size:18px;font-weight:600;color:var(--text-primary);margin:0;">Invitación no válida</h2>
        <p style="color:var(--text-muted);font-size:14px;margin:0;">
            Este enlace ya fue utilizado o ha expirado. Pedile a un administrador que te envíe una nueva invitación.
        </p>
    </div>

    <Form
        v-else
        :action="acceptUrl"
        method="post"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <!-- Invite banner -->
        <div class="flex items-center gap-3.5 rounded-md border border-terracota bg-terra-light dark:bg-[#3D1E0E] dark:border-terra-hover px-4 py-3">
            <div class="shrink-0 w-9 h-9 rounded-full bg-terracota text-white grid place-items-center">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
            </div>
            <div class="text-[12px] leading-snug">
                <div class="text-[13px] font-semibold text-terra-text dark:text-terra-hover">Invitación válida</div>
                <div class="text-gris dark:text-gris-light">
                    Invitado como <strong class="text-tinta dark:text-papel">{{ inviteEmail }}</strong>
                    <template v-if="inviteExpiresIn"> · expira {{ inviteExpiresIn }}</template>
                </div>
            </div>
        </div>

        <!-- Name -->
        <div class="grid gap-1.5">
            <label for="name" class="text-[13px] font-medium text-tinta dark:text-papel">Nombre completo</label>
            <input
                id="name"
                name="name"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="María González"
                class="h-11 px-3.5 text-[14px] rounded-md border border-gris-borde dark:border-pizarra bg-hueso dark:bg-tinta-soft text-tinta dark:text-papel placeholder:text-gris focus:border-terracota focus:ring-2 focus:ring-terracota/20 focus:outline-none transition-colors"
            />
            <InputError :message="errors.name" />
        </div>

        <!-- Password -->
        <div class="grid gap-1.5">
            <label for="password" class="text-[13px] font-medium text-tinta dark:text-papel">Contraseña</label>
            <PasswordInput id="password" name="password" autocomplete="new-password" required />
            <InputError :message="errors.password" />
        </div>

        <!-- Confirm -->
        <div class="grid gap-1.5">
            <label for="password_confirmation" class="text-[13px] font-medium text-tinta dark:text-papel">Confirmar contraseña</label>
            <PasswordInput id="password_confirmation" name="password_confirmation" autocomplete="new-password" required />
        </div>

        <Button type="submit" variant="primary" class="w-full" :loading="processing">
            Activar cuenta
        </Button>
    </Form>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/pages/auth/AcceptInvitation.vue
git commit -m "feat: AcceptInvitation auth page with expired state and registration form"
```

---

## Task 14: Sidebar navigation entry

**Files:**
- Modify: `resources/js/components/AppSidebar.vue`

- [ ] **Step 1: Add import for users route**

In `AppSidebar.vue`, add the import for the users index route alongside the existing roles import:

```typescript
import { index as usersIndex } from '@/routes/security/users'
```

- [ ] **Step 2: Add Usuarios to the Security navGroup**

Find the Security group block that checks `page.props.auth?.permissions?.includes('roles.view')` and update it:

```typescript
if (
    page.props.auth?.permissions?.includes('roles.view') ||
    page.props.auth?.permissions?.includes('users.view') ||
    page.props.auth?.roles?.includes('Admin')
) {
    const securityItems: { icon: string; label: string; href: string }[] = []

    if (
        page.props.auth?.permissions?.includes('roles.view') ||
        page.props.auth?.roles?.includes('Admin')
    ) {
        securityItems.push({ icon: 'shield', label: 'Roles', href: rolesIndex.url() })
    }

    if (
        page.props.auth?.permissions?.includes('users.view') ||
        page.props.auth?.roles?.includes('Admin')
    ) {
        securityItems.push({ icon: 'users', label: 'Usuarios', href: usersIndex.url() })
    }

    if (securityItems.length) {
        groups.push({ label: 'Seguridad', items: securityItems })
    }
}
```

- [ ] **Step 3: Run Pint (backend) and TypeScript check (frontend)**

```bash
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail npm run build 2>&1 | tail -20
```

Expected: build succeeds with no TypeScript errors.

- [ ] **Step 4: Run full test suite**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all tests pass.

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/AppSidebar.vue
git commit -m "feat: add Usuarios to Security sidebar, guarded by users.view permission"
```

---

## Self-Review Checklist

**Spec coverage:**
- [x] `add_active_to_users_table` migration → Task 1
- [x] `create_invitations_table` migration → Task 1
- [x] `Invitation` model with scopes + methods → Task 1
- [x] `User` model `active` fillable + cast → Task 1
- [x] Fortify `authenticateUsing()` block → Task 8
- [x] 7 permissions in YAML → Task 2
- [x] Admin gets `users.*` → Task 2
- [x] `UserPolicy` (7 methods, self-block on update/deactivate) → Task 3
- [x] `Gate::before` Admin bypass (already global, Task 3 registers policy) → Task 3
- [x] All 5 Form Requests → Task 4
- [x] `UserController` (index+filters, store 3 modes, update, destroy, deactivate, resetPassword) → Task 5
- [x] `InvitationController` (store w/ cancel-and-create, destroy) → Task 6
- [x] `InvitationMail` → Task 6
- [x] `AcceptInvitationController` (show expired/valid, store creates+logs-in) → Task 7
- [x] Routes (security prefix + public invitation) → Task 5
- [x] `Users/Index.vue` (table, filters, pagination, action buttons with CASL guards) → Task 9
- [x] `CreateUserModal` (3 password modes) → Task 10
- [x] `InviteUserModal` → Task 10
- [x] `EditUserModal` (roles checkboxes, cannot edit self) → Task 11
- [x] `ResetPasswordModal` (3 password modes) → Task 11
- [x] `DeactivateUserModal` (active/inactive state messaging) → Task 12
- [x] `DeleteUserModal` → Task 12
- [x] `AcceptInvitation.vue` (expired state + form) → Task 13
- [x] Sidebar "Usuarios" entry guarded by `users.view` → Task 14

**Type consistency:** `UserRow` defined in Task 9, used in Tasks 9-12. `UserPaginator` defined in Task 9, used in `Index.vue`. Wayfinder route functions (`index`, `store`, `update`, `destroy`, `deactivate`, `resetPassword`, `resetPassword`) auto-generated from controller method names — verify after Task 9 step 1 that the generated names match.

**Note:** `resetPassword` Wayfinder name must match exactly. Run `cat resources/js/routes/security/users/index.ts` after wayfinder:generate to verify exported function names.
