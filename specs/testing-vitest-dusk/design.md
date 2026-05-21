# Diseño — Testing: Vitest + Laravel Dusk

**Feature:** `04-testing-vitest-dusk`
**Spec source:** `docs/superpowers/specs/2026-05-21-testing-vitest-dusk-design.md`

---

## Capa 1: Vitest

### Instalación

```bash
vendor/bin/sail npm install -D vitest @vue/test-utils jsdom @vitest/coverage-v8
```

### Configuración

Bloque `test` en `vite.config.ts` (sin archivo separado):

```ts
test: {
  environment: 'jsdom',
  setupFiles: ['tests/js/setup.ts'],
  include: ['tests/js/**/*.test.ts'],
}
```

### Setup global (`tests/js/setup.ts`)

Mocks necesarios para todos los tests:
- `@inertiajs/vue3`: `useForm`, `usePage` (devuelve auth con roles de prueba), `router`
- CASL ability con roles de prueba
- `vue-i18n`

### Estructura de archivos

```
tests/js/
├── setup.ts
├── composables/
│   ├── enrollment/
│   │   ├── useEnrollmentForm.test.ts
│   │   └── useEnrollmentPermissions.test.ts
│   ├── forms/
│   └── permissions/
└── utils/
    └── *.test.ts
```

### Scripts en `package.json`

```json
"test":          "vitest",
"test:run":      "vitest run",
"test:coverage": "vitest run --coverage"
```

---

## Capa 2: Laravel Dusk

### Instalación

```bash
vendor/bin/sail composer require laravel/dusk --dev
vendor/bin/sail artisan dusk:install
```

### Integración con Pest

En `tests/Pest.php`:

```php
pest()->extend(DuskTestCase::class)->in('Browser');
```

### Conexión Selenium

`tests/DuskTestCase.php` — apuntar al container existente:

```php
protected function driver(): RemoteWebDriver
{
    return RemoteWebDriver::create(
        'http://selenium:4444',
        DesiredCapabilities::chrome()
    );
}
```

No se crea nueva infraestructura — el container `selenium/standalone-chromium` ya está en `compose.yaml`.

### Estructura de archivos

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

### Convención de tests (Pest syntax)

```php
test('admin can view careers list', function () {
    $this->browse(function (Browser $browser) {
        $browser->loginAs(User::factory()->admin()->create())
                ->visit(route('academic.careers.index'))
                ->assertSee('Carreras');
    });
});
```

### Comandos de ejecución

```bash
vendor/bin/sail dusk                          # todos los browser tests
vendor/bin/sail dusk --filter LoginTest       # test específico
```

---

## Cobertura detallada

### Vitest

| Archivo | Qué testear |
|---------|------------|
| `composables/enrollment/useEnrollmentForm.ts` | Estado inicial; submit llama router correcto; reset limpia form |
| `composables/enrollment/useEnrollmentPermissions.ts` | `canCreate/canEdit/canDelete` por rol |
| `composables/usePermission.ts` | Chequeos CASL generales por rol |
| `composables/filters/*.ts` | Estado de filtros; debounce; reset |
| `utils/*.ts` | Cada helper: casos normales + edge cases |

### Dusk

| Área | Flujos |
|------|--------|
| **Auth** | Login correcto → dashboard; credenciales incorrectas → error; logout |
| **Dashboard** | Admin ve panel admin; estudiante ve panel estudiante |
| **Enrollment** | Lista de inscripciones carga; detalle de inscripción renderiza |
| **Academic** | Lista de carreras; pensum de carrera; lista de materias; lista de secciones |
| **Admin** | Panel de usuarios carga; panel de roles carga |
