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
