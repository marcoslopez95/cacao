# CACAO — Contexto del proyecto

> Este archivo es la fuente de verdad para Claude Code.
> Léelo completo antes de escribir cualquier línea de código.

---

## ¿Qué es CACAO?

**CACAO** = Control Académico, Cursos y Administración de Operaciones.

Sistema de gestión académica integral para instituciones educativas venezolanas (todos los niveles: primaria, secundaria, universitario). Nombre con identidad venezolana — el cacao venezolano es reconocido mundialmente como símbolo de calidad.

---

## Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.3 · Laravel 13 |
| Frontend | Vue 3 + Inertia.js v3 |
| Base de datos | PostgreSQL |
| Autenticación | Laravel Fortify v1 |
| CSS | Tailwind CSS v4 con tokens CACAO |
| Tipografía | Space Grotesk (Google Fonts) |
| Package manager JS | pnpm |
| Rutas frontend | Laravel Wayfinder v0 |
| Testing | Pest v4 |

---

## Arquitectura general

- **Patrón**: Modular monolito Laravel con separación por dominios
- **Frontend**: Vue 3 + Inertia.js v3 (SPA sin complejidad de SPA)
- **Vistas**: `resources/js/pages/` — nunca Blade para páginas, solo para layouts shell
- **Multi-nivel educativo**: soporta primaria/secundaria y universitario
- **Representantes**: estudiantes de nivel no universitario tienen representantes legales con acceso al sistema

---

## Módulos del sistema

### 1. Académico
- Categorías de carrera (Ingeniería, Humanidades, Economía, etc.)
- Carreras → Pensums (una carrera tiene N pensums, uno activo a la vez)
- Materias → pertenecen a un pensum, gestionadas por un departamento
- Prelaciones: auto-referencial en materia (`prerequisites` tabla pivote)
- Secciones: cada materia tiene N secciones con cupo máximo por sección

### 2. Personas
- **Estudiantes**: datos personales + geográficos + socioeconómicos + educativos
- **Profesores**: adscritos a un departamento, pueden dictar N materias/secciones
- **Representantes**: vinculados a estudiantes (nivel no universitario)
- **Users**: modelo único de auth con rol (admin, profesor, estudiante, representante)

### 3. Inscripciones
- Validación automática de prelaciones antes de aprobar (`PrerequisiteValidator` service)
- Control de cupos por sección
- Estados: `pending` → `approved` / `rejected`
- **`Enrollment` es la entidad pivote central** — notas, asistencia y entregas cuelgan de ella

### 4. Infraestructura
- Aulas: `type` enum (`theory` / `laboratory`), capacidad, edificio
- Una sección puede tener aula teórica + aula laboratorio (ambas FK en `sections`)
- Horarios por sección: día, hora inicio/fin, tipo
- Departamentos: agrupan profesores por área

### 5. Evaluaciones y notas
- Actividades: `quiz`, `test` (opción múltiple con corrección automática), `file_upload`
- Entregas de archivo: revisadas por el profesor con calificación + comentario opcional
- Asistencia: cargada manualmente por el profesor por sección
- Notas finales: cargadas por el profesor con observación opcional

### 6. Recursos y comunicación
- Materiales de apoyo por sección (profesor sube, estudiante descarga)
- Evaluación de profesores por parte de los estudiantes (una por período/sección)

---

## Modelo de datos — entidades principales

```
career_categories → careers → curriculums → subjects
subjects → prerequisites (auto-referencial, tabla pivote)
subjects → sections → schedules
sections → classrooms (theory_classroom_id + lab_classroom_id, ambos nullable)
departments → professors → sections
departments → subjects

students → geographic_data (1:1)
students → socioeconomic_data (1:1)
students → educational_data (1:1)
students → guardians (1:N, para nivel no universitario)

students + sections → enrollments  ← PIVOTE CENTRAL
enrollments → grades
enrollments → attendances
enrollments → submissions

sections → activities → quiz_questions → answer_options
submissions → student_answers

sections → learning_materials
professors → professor_evaluations (emitidas por estudiantes)

users → professors | students | guardians (morphable / FK directa)
```

---

## Roles y permisos

| Rol | Acceso |
|-----|--------|
| `admin` | Todo el sistema. Gestión de carreras, pensums, materias, aulas, usuarios, reportes |
| `professor` | Sus secciones: notas, asistencia, materiales, evaluaciones, estudiantes inscritos |
| `student` | Sus inscripciones, notas, horario, materiales, evaluaciones, evaluar profesores |
| `guardian` | Ver notas, asistencia e inscripciones del estudiante vinculado (solo lectura) |

Implementar con **Laravel Policies** por modelo. Nunca `if ($user->role === 'admin')` directo en controladores.

---

## Convenciones de código

### Idioma
- **Código (PHP/JS)**: inglés — variables, métodos, clases, migraciones, rutas
- **UI y comunicaciones**: español latinoamericano (Venezuela) — nunca España, nunca "vosotros/os"

### Estructura de carpetas Laravel
```
app/
  Http/
    Controllers/
      Admin/          ← panel administrativo
      Professor/      ← portal del profesor
      Student/        ← portal del estudiante
      Guardian/       ← portal del representante
    Requests/         ← Form Requests por módulo
    Resources/        ← API Resources si aplica
  Models/
  Services/           ← lógica de negocio (nunca en controladores)
    EnrollmentService.php
    GradeService.php
    PrerequisiteValidator.php
  Policies/
```

### Rutas Laravel
```
/admin/*              ← panel administrativo
/professor/*          ← portal del profesor
/student/*            ← portal del estudiante
/guardian/*           ← portal del representante
```

### Convenciones Eloquent
- Modelos: singular PascalCase — `Student`, `Subject`, `Section`, `Enrollment`
- Tablas: plural snake_case — `students`, `subjects`, `sections`, `enrollments`
- Migraciones: descriptivas — `create_students_table`, `add_geographic_data_to_students`
- Usar **Form Requests** para toda validación de entrada
- Usar **Policies** para toda autorización

---

## Identidad visual CACAO

### Tipografía
- **Fuente única**: Space Grotesk — `@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap')`
- Nunca usar otra fuente en ningún elemento del sistema

### Paleta (tokens Tailwind en `tailwind.config.js`)
```js
colors: {
  tinta:     { DEFAULT: '#131110', soft: '#2A2826' },
  terracota: { DEFAULT: '#C8521A', hover: '#E8895A', light: '#F5E8E0', text: '#7A3010' },
  pizarra:   { DEFAULT: '#3D3A36', dark: '#1A1815' },
  papel:     { DEFAULT: '#F4F2EF', dark: '#EDEBE7' },
  hueso:     '#FAFAF8',
  gris:      { DEFAULT: '#888780', light: '#C8C6C2', borde: '#E0DDD8' },
}
```

### Isotipo en Vue/Blade
```html
<!-- Cuadrícula 3×3 — módulo terracota SIEMPRE en posición [0,2] -->
<div class="grid grid-cols-3 gap-[3px] w-5">
  <span class="bg-tinta     rounded-[2px] aspect-square"></span>
  <span class="bg-tinta     rounded-[2px] aspect-square"></span>
  <span class="bg-terracota rounded-[2px] aspect-square"></span>
  <span class="bg-tinta     rounded-[2px] aspect-square"></span>
  <span class="bg-tinta     rounded-[2px] aspect-square"></span>
  <span class="invisible              aspect-square"></span>
  <span class="bg-tinta     rounded-[2px] aspect-square"></span>
  <span class="bg-tinta     rounded-[2px] aspect-square"></span>
  <span class="bg-tinta     rounded-[2px] aspect-square"></span>
</div>
```

### Reglas de marca inamovibles
- Space Grotesk es la única fuente — no agregar otras
- El módulo terracota siempre en posición [0,2] del isotipo
- Terracota solo como acento (CTAs, activos, alertas críticas) — nunca fondo dominante
- Wordmark siempre en mayúsculas con tracking mínimo de 4px

---

## Documentación relacionada (`/docs/brand/`)

| Archivo | Contenido |
|---------|-----------|
| `brandbook.html` | Brand book visual completo |
| `BRAND.md` | Guía de marca en texto plano |
| `tokens.css` | Variables CSS — importar en `resources/css/app.css` |
| `tailwind.config.brand.js` | Tokens Tailwind — extender en `tailwind.config.js` |

---

## Estado actual del proyecto

- [x] Requerimientos completos definidos
- [x] Diagrama entidad-relación (25 entidades)
- [x] Identidad visual completa (brand book, tokens, Tailwind config)
- [ ] Migraciones y modelos Eloquent
- [ ] Factories y seeders
- [ ] Autenticación y roles (Fortify + Policies)
- [ ] Módulo académico (carreras, pensums, materias, prelaciones)
- [ ] Módulo personas (estudiantes, profesores, representantes)
- [ ] Módulo inscripciones con validación de prelaciones
- [ ] Módulo infraestructura (aulas, horarios, departamentos)
- [ ] Módulo evaluaciones (quiz, test, entregas)
- [ ] Módulo recursos (materiales de apoyo)
- [ ] Interfaces Vue por rol (admin, profesor, estudiante, representante)

---

## Notas críticas para Claude Code

1. **`Enrollment` es el pivote central** — todo historial académico (notas, asistencia, entregas) cuelga de enrollments, nunca directamente de students o sections.
2. **Prelaciones siempre en `PrerequisiteValidator`** — nunca validar en controladores directamente.
3. **Un aula es siempre una fila en `classrooms`** — el tipo (teórica/lab) es un campo enum, no tablas separadas. Una sección tiene `theory_classroom_id` y `lab_classroom_id` como FKs separadas.
4. **Wayfinder para todas las rutas frontend** — nunca URLs hardcodeadas en Vue.
5. **Pest para todos los tests** — feature tests primero, unit tests para lógica aislada (services, validators).
6. **`pint --dirty` después de cada cambio PHP** — antes de dar por finalizado cualquier cambio.

---
---

# Laravel Boost Guidelines

> Reglas del framework — generadas automáticamente por Laravel Boost.
> No modificar esta sección manualmente.

=== foundation rules ===

## Foundational Context

- php: 8.3
- inertiajs/inertia-laravel (INERTIA_LARAVEL): v3
- laravel/fortify (FORTIFY): v1
- laravel/framework (LARAVEL): v13
- laravel/prompts (PROMPTS): v0
- laravel/wayfinder (WAYFINDER): v0
- laravel/boost (BOOST): v2
- laravel/mcp (MCP): v0
- laravel/pail (PAIL): v1
- laravel/pint (PINT): v1
- laravel/sail (SAIL): v1
- pestphp/pest (PEST): v4
- phpunit/phpunit (PHPUNIT): v12
- @inertiajs/vue3 (INERTIA_VUE): v3
- tailwindcss (TAILWINDCSS): v4
- vue (VUE): v3
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE): v0
- eslint (ESLINT): v9
- prettier (PRETTIER): v3

## Skills Activation

- `fortify-development` — autenticación, login, registro, 2FA, reset password, Fortify
- `laravel-best-practices` — cualquier código PHP Laravel: controllers, models, migrations, policies, jobs, queries
- `wayfinder-development` — frontend conectando a rutas/controllers Laravel; importar de `@/actions` o `@/routes`
- `pest-testing` — cualquier test: escribir, editar, corregir, refactorizar
- `inertia-vue-development` — páginas Vue, forms, navegación, `<Link>`, `useForm`, `useHttp`, deferred props
- `tailwindcss-development` — clases Tailwind en cualquier template, layouts, componentes UI, dark mode

## Conventions

- Seguir convenciones existentes del proyecto al crear o editar archivos
- Nombres descriptivos: `isRegisteredForDiscounts`, no `discount()`
- Revisar componentes existentes antes de crear nuevos

## Application Structure

- No crear carpetas base nuevas sin aprobación
- No cambiar dependencias sin aprobación

## Frontend Bundling

- Si un cambio frontend no se refleja: `npm run build`, `npm run dev`, o `composer run dev`

## Replies

- Respuestas concisas — enfocarse en lo importante

=== boost rules ===

## Laravel Boost Tools

- `database-query` — consultas read-only en lugar de tinker SQL
- `database-schema` — inspeccionar tablas antes de migraciones/modelos
- `get-absolute-url` — resolver URL correcta antes de compartirla
- `browser-logs` — leer errores del navegador

## search-docs (IMPORTANTE)

- **Siempre usar `search-docs` antes de hacer cambios de código**
- Pasar `packages` array para acotar resultados
- Múltiples queries amplias: `['rate limiting', 'routing rate limiting', 'routing']`
- No incluir nombre del paquete en la query (ya está en `packages`)

## Artisan

- Comandos directamente por CLI: `php artisan route:list`, `php artisan list`
- Config: `php artisan config:show app.name`
- `.env` leerlo directamente

## Tinker

- Siempre comillas simples: `php artisan tinker --execute 'Your::code();'`
- No crear modelos sin aprobación del usuario

=== php rules ===

## PHP

- Siempre llaves en estructuras de control, incluso de una línea
- Constructor property promotion de PHP 8: `public function __construct(public GitHub $github) { }`
- Return types explícitos y type hints en todos los parámetros
- Enum keys en TitleCase: `FavoritePerson`, `Monthly`
- PHPDoc blocks, no inline comments (salvo lógica excepcionalmente compleja)
- Array shape types en PHPDoc

=== tests rules ===

## Tests

- Todo cambio debe tener test. Escribir o actualizar test y ejecutarlo
- `php artisan test --compact` con filename o filter específico

=== inertia-laravel/core rules ===

## Inertia

- Componentes en `resources/js/pages/`
- Usar `Inertia::render()` para routing server-side
- **Activar `inertia-vue-development` para patrones Vue client-side**

## Inertia v3

- Nuevas features v3: `useHttp`, optimistic updates, `useLayoutProps`, instant visits, SSR simplificado
- De v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data
- `Inertia::lazy()` eliminado → usar `Inertia::optional()`
- `router.cancel()` → `router.cancelAll()`
- Axios eliminado → usar XHR client built-in o instalar Axios

=== laravel/core rules ===

## Laravel Way

- `php artisan make:` para crear archivos
- Nuevos modelos: crear factories y seeders también
- APIs: Eloquent API Resources + versionado
- Links: rutas nombradas con `route()`
- Tests: factories para modelos; `php artisan make:test --pest {name}`

=== wayfinder/core rules ===

## Wayfinder

Importar de `@/actions/` (controllers) o `@/routes/` (named routes). Nunca URLs hardcodeadas.

=== pint/core rules ===

## Pint

- Después de modificar PHP: `vendor/bin/pint --dirty --format agent`

=== pest/core rules ===

## Pest

- Crear: `php artisan make:test --pest {name}`
- Ejecutar: `php artisan test --compact` o `--filter=testName`
- No eliminar tests sin aprobación

=== inertia-vue/core rules ===

## Inertia + Vue

- Componentes Vue con un solo elemento raíz
- Activar `inertia-vue-development` para patrones client-side

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA_VUE) - v3
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `fortify-development` — ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.
- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `wayfinder-development` — Use this skill for Laravel Wayfinder which auto-generates typed functions for Laravel controllers and routes. ALWAYS use this skill when frontend code needs to call backend routes or controller actions. Trigger when: connecting any React/Vue/Svelte/Inertia frontend to Laravel controllers, routes, building end-to-end features with both frontend and backend, wiring up forms or links to backend endpoints, fixing route-related TypeScript errors, importing from @/actions or @/routes, or running wayfinder:generate. Use Wayfinder route functions instead of hardcoded URLs. Covers: wayfinder() vite plugin, .url()/.get()/.post()/.form(), query params, route model binding, tree-shaking. Do not use for backend-only task
- `pest-testing` — Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code.
- `inertia-vue-development` — Develops Inertia.js v3 Vue client-side applications. Activates when creating Vue pages, forms, or navigation; using <Link>, <Form>, useForm, useHttp, setLayoutProps, or router; working with deferred props, prefetching, optimistic updates, instant visits, or polling; or when user mentions Vue with Inertia, Vue pages, Vue forms, or Vue navigation.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `vendor/bin/sail npm run build`, `vendor/bin/sail npm run dev`, or `vendor/bin/sail composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `vendor/bin/sail artisan route:list`). Use `vendor/bin/sail artisan list` to discover available commands and `vendor/bin/sail artisan [command] --help` to check parameters.
- Inspect routes with `vendor/bin/sail artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `vendor/bin/sail artisan config:show app.name`, `vendor/bin/sail artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `vendor/bin/sail artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `vendor/bin/sail artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== sail rules ===

# Laravel Sail

- This project runs inside Laravel Sail's Docker containers. You MUST execute all commands through Sail.
- Start services using `vendor/bin/sail up -d` and stop them with `vendor/bin/sail stop`.
- Open the application in the browser by running `vendor/bin/sail open`.
- Always prefix PHP, Artisan, Composer, and Node commands with `vendor/bin/sail`. Examples:
    - Run Artisan Commands: `vendor/bin/sail artisan migrate`
    - Install Composer packages: `vendor/bin/sail composer install`
    - Execute Node commands: `vendor/bin/sail npm run dev`
    - Execute PHP scripts: `vendor/bin/sail php [script]`
- View all available Sail commands by running `vendor/bin/sail` without arguments.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `vendor/bin/sail artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `vendor/bin/sail artisan list` and check their parameters with `vendor/bin/sail artisan [command] --help`.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `vendor/bin/sail artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev` or `vendor/bin/sail composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/sail bin pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/sail bin pint --test --format agent`, simply run `vendor/bin/sail bin pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `vendor/bin/sail artisan make:test --pest {name}`.
- Run tests: `vendor/bin/sail artisan test --compact` or filter: `vendor/bin/sail artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
