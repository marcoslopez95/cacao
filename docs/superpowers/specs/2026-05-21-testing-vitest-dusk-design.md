# Testing Infrastructure: Vitest + Laravel Dusk

**Date:** 2026-05-21
**Status:** Approved

## Overview

Add a two-layer testing infrastructure to cover frontend logic and browser-level flows:

- **Vitest** — unit tests for Vue composables and utility functions
- **Laravel Dusk** — browser E2E tests via the existing Selenium container

Existing Pest backend tests are unaffected. This spec only covers new testing layers.

---

## Layer 1: Vitest (Frontend Unit Tests)

### Dependencies

```bash
vendor/bin/sail npm install -D vitest @vue/test-utils jsdom @vitest/coverage-v8
```

### Configuration

Add a `test` block to the existing `vite.config.ts` — no separate config file needed:

```ts
test: {
  environment: 'jsdom',
  setupFiles: ['tests/js/setup.ts'],
  include: ['tests/js/**/*.test.ts'],
}
```

### Global Setup (`tests/js/setup.ts`)

Mocks required for all tests:
- `@inertiajs/vue3`: `useForm`, `usePage`, `router`
- CASL ability with test roles (admin, professor, student, guardian)
- `vue-i18n`

### File Structure

```
tests/js/
├── setup.ts
├── composables/
│   ├── enrollment/
│   │   ├── useEnrollmentForm.test.ts
│   │   └── useEnrollmentPermissions.test.ts
│   ├── forms/          (one file per existing form composable)
│   └── permissions/    (one file per existing permission composable)
└── utils/
    └── *.test.ts       (one file per util module)
```

### Coverage Targets

| File | What to test |
|------|-------------|
| `composables/enrollment/useEnrollmentForm.ts` | Initial state; submit calls correct router method; reset clears form |
| `composables/enrollment/useEnrollmentPermissions.ts` | `canCreate/canEdit/canDelete` return correct value per role |
| `composables/usePermission.ts` | General CASL ability checks per role |
| `composables/filters/*.ts` | Filter state; debounce behavior; reset |
| `utils/*.ts` | Each helper: normal cases + edge cases |

### npm Scripts

```json
"test":          "vitest",
"test:run":      "vitest run",
"test:coverage": "vitest run --coverage"
```

---

## Layer 2: Laravel Dusk (Browser E2E Tests)

### Dependencies

```bash
vendor/bin/sail composer require laravel/dusk --dev
vendor/bin/sail artisan dusk:install
```

### Pest Integration

Add to `tests/Pest.php`:

```php
pest()->extend(DuskTestCase::class)->in('Browser');
```

### Selenium Connection

The `selenium/standalone-chromium` container is already defined in `compose.yaml`. Configure `tests/DuskTestCase.php`:

```php
protected function driver(): RemoteWebDriver
{
    return RemoteWebDriver::create(
        'http://selenium:4444',
        DesiredCapabilities::chrome()
    );
}
```

No new infrastructure required.

### File Structure

```
tests/Browser/
├── Auth/
│   ├── LoginTest.php
│   └── LogoutTest.php
├── Dashboard/
│   └── DashboardTest.php
├── Enrollment/
│   └── EnrollmentFlowTest.php
└── Academic/
    ├── CareersTest.php
    ├── SubjectsTest.php
    └── SectionsTest.php
```

### Coverage Targets

| Area | Flows |
|------|-------|
| **Auth** | Correct login redirects to dashboard; wrong credentials show error; logout clears session |
| **Dashboard** | Admin sees admin panel; student sees student view |
| **Enrollment** | Enrollment list loads; enrollment detail renders |
| **Academic** | Career list loads; pensum detail renders; subject list loads; section list loads |
| **Admin** | User management panel loads; role management panel loads |

### Test Convention (Pest syntax)

```php
test('admin can view careers list', function () {
    $this->browse(function (Browser $browser) {
        $browser->loginAs(User::factory()->admin()->create())
                ->visit(route('academic.careers.index'))
                ->assertSee('Carreras');
    });
});
```

### Run Commands

```bash
vendor/bin/sail dusk                          # all browser tests
vendor/bin/sail dusk --filter LoginTest       # specific test
vendor/bin/sail artisan test --compact        # pest tests (unchanged)
```

---

## Testing Pyramid

```
[Dusk]   E2E: Auth, Dashboard, Enrollment, Academic, Admin
[Pest]   Feature: HTTP, controllers, policies, DB (existing)
[Vitest] Unit: composables, utils, pure logic
```

Each layer tests what the others cannot:
- Vitest covers pure logic Dusk cannot easily introspect
- Dusk covers real browser rendering Pest cannot test
- Pest covers backend rules and DB state Dusk is too slow to verify exhaustively

---

## Out of Scope

- Component tests (`@vue/test-utils` mounting) — covered by Dusk at E2E level
- Visual regression tests
- Performance/load tests
- Playwright (Selenium container already exists)
