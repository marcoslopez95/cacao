# Tasks — Testing: Vitest + Laravel Dusk

**Feature:** `04-testing-vitest-dusk`
**Spec source:** `specs/testing-vitest-dusk/design.md`

---

## Progreso general

- [ ] Task 1 — Vitest: Instalación y configuración base
- [ ] Task 2 — Vitest: Tests de composables de inscripción
- [ ] Task 3 — Vitest: Tests de otros composables y utils
- [ ] Task 4 — Dusk: Instalación y configuración con Selenium
- [ ] Task 5 — Dusk: Tests de autenticación
- [ ] Task 6 — Dusk: Tests de Dashboard
- [ ] Task 7 — Dusk: Tests de Enrollment
- [ ] Task 8 — Dusk: Tests de Academic y Admin

---

## Detalle de cada task

---

### Task 1 — Vitest: Instalación y configuración base

**Archivos involucrados:**
- `package.json`
- `vite.config.ts`
- `tests/js/setup.ts` (nuevo)

**Pasos:**
1. `vendor/bin/sail npm install -D vitest @vue/test-utils jsdom @vitest/coverage-v8`
2. Agregar bloque `test` a `vite.config.ts`:
   ```ts
   test: {
     environment: 'jsdom',
     setupFiles: ['tests/js/setup.ts'],
     include: ['tests/js/**/*.test.ts'],
   }
   ```
3. Crear `tests/js/setup.ts` con mocks de:
   - `@inertiajs/vue3`: `useForm`, `usePage`, `router`
   - CASL ability con roles de prueba
   - `vue-i18n`
4. Agregar a `package.json`:
   ```json
   "test":          "vitest",
   "test:run":      "vitest run",
   "test:coverage": "vitest run --coverage"
   ```
5. Crear `tests/js/composables/.gitkeep` para verificar que el directorio existe
6. Ejecutar `vendor/bin/sail npm run test:run` — debe pasar (0 tests, 0 failures)

**Criterio de done:**
- `vendor/bin/sail npm run test:run` ejecuta sin errores
- `vendor/bin/sail npm run test:coverage` genera `coverage/` en la raíz
- No hay warnings de TypeScript en el setup

---

### Task 2 — Vitest: Tests de composables de inscripción

**Archivos involucrados:**
- `tests/js/composables/enrollment/useEnrollmentForm.test.ts` (nuevo)
- `tests/js/composables/enrollment/useEnrollmentPermissions.test.ts` (nuevo)

**Pasos:**
1. Leer `resources/js/composables/enrollment/useEnrollmentForm.ts` para entender la API
2. Escribir `useEnrollmentForm.test.ts`:
   - Estado inicial del form (campos vacíos/default)
   - Submit llama `router.post` con la URL correcta (Wayfinder)
   - Reset limpia todos los campos
3. Leer `resources/js/composables/enrollment/useEnrollmentPermissions.ts`
4. Escribir `useEnrollmentPermissions.test.ts`:
   - Admin puede crear, editar y eliminar
   - Estudiante solo puede crear y ver (no editar)
   - Representante puede crear en nombre de su estudiante
   - Invitado no puede hacer nada
5. Ejecutar `vendor/bin/sail npm run test:run` — todos los tests deben pasar

**Criterio de done:**
- Mínimo 6 tests en `useEnrollmentForm.test.ts`
- Mínimo 8 tests en `useEnrollmentPermissions.test.ts` (2 por rol × 4 roles)
- `vendor/bin/sail npm run test:run` pasa sin errores

---

### Task 3 — Vitest: Tests de otros composables y utils

**Archivos involucrados:**
- `tests/js/composables/permissions/*.test.ts` (uno por composable existente)
- `tests/js/composables/filters/*.test.ts` (uno por composable existente)
- `tests/js/utils/*.test.ts` (uno por módulo en `resources/js/utils/`)

**Pasos:**
1. Listar `resources/js/composables/permissions/`, `resources/js/composables/filters/`, `resources/js/utils/`
2. Por cada archivo en esos directorios:
   - Crear el `.test.ts` correspondiente en `tests/js/`
   - Testear comportamiento público: estado inicial, mutaciones, valores devueltos
3. Ejecutar `vendor/bin/sail npm run test:run` — todos los tests deben pasar
4. Ejecutar `vendor/bin/sail npm run test:coverage` — revisar cobertura

**Criterio de done:**
- Existe al menos un test por cada composable en `permissions/` y `filters/`
- Existe al menos un test por cada módulo en `utils/`
- `vendor/bin/sail npm run test:run` pasa
- Cobertura de composables/utils ≥ 70%

---

### Task 4 — Dusk: Instalación y configuración con Selenium

**Archivos involucrados:**
- `composer.json` / `composer.lock`
- `tests/DuskTestCase.php` (generado por artisan)
- `tests/Browser/` (creado por artisan)
- `tests/Pest.php`
- `.env.dusk.local` (nuevo)

**Pasos:**
1. `vendor/bin/sail composer require laravel/dusk --dev`
2. `vendor/bin/sail artisan dusk:install`
3. Editar `tests/DuskTestCase.php` — configurar driver para Selenium container:
   ```php
   protected function driver(): RemoteWebDriver
   {
       return RemoteWebDriver::create(
           'http://selenium:4444',
           DesiredCapabilities::chrome()
       );
   }
   ```
4. Agregar en `tests/Pest.php`:
   ```php
   pest()->extend(DuskTestCase::class)->in('Browser');
   ```
5. Crear `.env.dusk.local` con `APP_URL=http://laravel.test` (o la URL interna del container)
6. Agregar `.env.dusk.local` a `.gitignore`
7. Escribir un test de smoke mínimo en `tests/Browser/SmokeTest.php` que visite `/` y no falle
8. `vendor/bin/sail dusk tests/Browser/SmokeTest.php` — debe pasar

**Criterio de done:**
- `vendor/bin/sail dusk tests/Browser/SmokeTest.php` pasa sin errores de conexión
- El container Selenium responde en `http://selenium:4444`
- `.env.dusk.local` no está commiteado

---

### Task 5 — Dusk: Tests de autenticación

**Archivos involucrados:**
- `tests/Browser/Auth/LoginTest.php` (nuevo)
- `tests/Browser/Auth/LogoutTest.php` (nuevo)

**Pasos:**
1. Crear `tests/Browser/Auth/LoginTest.php`:
   - Login con credenciales correctas → redirige a dashboard
   - Login con password incorrecto → muestra mensaje de error en pantalla
   - Login con email inexistente → muestra mensaje de error
   - Guest que visita ruta protegida → redirige a `/login`
2. Crear `tests/Browser/Auth/LogoutTest.php`:
   - Usuario autenticado hace logout → sesión cerrada, redirige a login
3. `vendor/bin/sail dusk tests/Browser/Auth/` — todos los tests deben pasar

**Criterio de done:**
- 4 tests en `LoginTest.php`, 1 en `LogoutTest.php`
- `vendor/bin/sail dusk tests/Browser/Auth/` pasa completamente

---

### Task 6 — Dusk: Tests de Dashboard

**Archivos involucrados:**
- `tests/Browser/Dashboard/DashboardTest.php` (nuevo)

**Pasos:**
1. Crear `tests/Browser/Dashboard/DashboardTest.php`:
   - Admin autenticado ve el panel de admin (verifica elemento/texto específico)
   - Estudiante autenticado ve el panel de estudiante (verifica elemento/texto específico)
   - Guest que visita dashboard → redirige a login
2. `vendor/bin/sail dusk tests/Browser/Dashboard/` — todos los tests deben pasar

**Criterio de done:**
- 3 tests que pasan
- Los asserts usan elementos/textos reales del markup (no genéricos)

---

### Task 7 — Dusk: Tests de Enrollment

**Archivos involucrados:**
- `tests/Browser/Enrollment/EnrollmentFlowTest.php` (nuevo)

**Pasos:**
1. Verificar que existen las rutas `enrollment.index` y `enrollment.show` (o equivalentes)
2. Crear `tests/Browser/Enrollment/EnrollmentFlowTest.php`:
   - Estudiante ve su lista de inscripciones (`enrollment.index`)
   - Estudiante ve el detalle de una inscripción (`enrollment.show`)
   - Admin ve la lista completa de inscripciones
   - Estudiante no puede ver inscripciones de otro estudiante → 403 o redirect
3. `vendor/bin/sail dusk tests/Browser/Enrollment/` — todos los tests deben pasar

**Criterio de done:**
- 4 tests que pasan
- Se usan factories con `RefreshDatabase` para datos de prueba

---

### Task 8 — Dusk: Tests de Academic y Admin

**Archivos involucrados:**
- `tests/Browser/Academic/CareersTest.php` (nuevo)
- `tests/Browser/Academic/SubjectsTest.php` (nuevo)
- `tests/Browser/Academic/SectionsTest.php` (nuevo)

**Pasos:**
1. Para cada módulo, verificar que la ruta existe antes de escribir el test. Si la ruta no existe, marcar el test como `->skip('ruta no implementada aún')` con nota
2. Crear `CareersTest.php`:
   - Admin ve lista de carreras
   - Admin ve el detalle/pensum de una carrera
3. Crear `SubjectsTest.php`:
   - Admin ve lista de materias
4. Crear `SectionsTest.php`:
   - Admin ve lista de secciones
5. `vendor/bin/sail dusk tests/Browser/Academic/` — tests implementados deben pasar (skipped está bien)

**Criterio de done:**
- Archivos creados para los tres módulos
- Tests implementados pasan; los de rutas no implementadas están marcados con `->skip()`
- Cero tests fallando (skip no es fallo)
