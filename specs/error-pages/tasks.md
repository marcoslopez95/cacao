# Tasks — Error Pages

**Feature:** `06-error-pages`

---

## Progreso general

- [x] Task 1 — Wiring Laravel: exception handler + layout exclusion en `app.ts`
- [x] Task 2 — CSS compartido `resources/css/error-pages.css`
- [x] Task 3 — Vue page `errors/NotFound` (404)
- [x] Task 4 — Vue page `errors/AccessDenied` (401/403)
- [x] Task 5 — Vue page `errors/ServerError` (500)
- [x] Task 6 — Pest feature tests
- [x] Task 7 — Vitest component tests
- [x] Task 8 — Dusk browser tests

---

## Detalle de cada task

---

### Task 1 — Wiring Laravel: exception handler + layout exclusion

**Archivos involucrados:**
- `bootstrap/app.php`
- `resources/js/app.ts`

**`bootstrap/app.php`** — añadir dentro de `withExceptions()`:

```php
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
        return match ($response->getStatusCode()) {
            401 => Inertia::render('errors/AccessDenied', ['status' => 401])
                ->toResponse($request)->setStatusCode(401),
            403 => Inertia::render('errors/AccessDenied', ['status' => 403])
                ->toResponse($request)->setStatusCode(403),
            404 => Inertia::render('errors/NotFound')
                ->toResponse($request)->setStatusCode(404),
            500 => Inertia::render('errors/ServerError')
                ->toResponse($request)->setStatusCode(500),
            default => $response,
        };
    });
})
```

**`resources/js/app.ts`** — en la función `layout`:

```typescript
case name === 'Welcome':
case name.startsWith('errors/'):
    return null;
```

**Criterio de done:**
- `php artisan route:list` sin errores
- Pint sin errores (`vendor/bin/sail bin pint --dirty --format agent`)
- `vendor/bin/sail npm run build` sin errores TypeScript

---

### Task 2 — CSS compartido de páginas de error

**Archivos involucrados:**
- `resources/css/error-pages.css` (nuevo)

**Contenido:** layout de dos columnas, status pill, botones EP, footer. Ver `design.md` para valores exactos.

Estructura de clases `ep-*`:
- `.ep-root` — wrapper raíz (grid 1fr/auto, min-height 100dvh, background: var(--bg-surface-2))
- `.ep-shell` — main (grid, place-items center, padding clamp)
- `.ep-error` — contenedor 2-col (max-width 1080px)
- `.ep-art` — columna de arte (aspect-ratio 1/1)
- `.ep-paper` — fill y stroke del rect SVG de fondo
- `.ep-copy` — columna de texto
- `.ep-status` — pill de estado (mono, pill-radius, borde)
- `.ep-dot` / `.ep-code` / `.ep-sep` — partes del pill
- `.ep-title` — h1 (clamp 34–56px, tracking -0.025em)
- `.ep-title em` — color: var(--accent), no italic
- `.ep-lead` — párrafo (clamp 16–19px)
- `.ep-actions` — flex row, gap 12px
- `.ep-btn` / `.ep-btn--primary` / `.ep-btn--ghost` / `.ep-btn--link`
- `.ep-footer` — footer minimalista
- `.ep-brand` / `.ep-mark` / `.ep-m-accent` / `.ep-m-empty`
- `.ep-service` / `.ep-live`

Keyframes compartidos: `ep-fadeInUp`, `ep-pulse`, `ep-live`

Responsive: `@media (min-width: 820px)` para 2 columnas; `@media (max-width: 819px)` copy centrado.

`@media (prefers-reduced-motion: reduce)` — todas las animaciones con `animation: none !important; opacity: 1 !important`.

**Criterio de done:**
- Archivo creado en `resources/css/`
- `vendor/bin/sail npm run build` sin errores

---

### Task 3 — Vue page `errors/NotFound`

**Archivos involucrados:**
- `resources/js/pages/errors/NotFound.vue` (nuevo)

**Estructura:**

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
</script>
```

No recibe props. Importa `error-pages.css` con `@import '@/../css/error-pages.css'` o vía `<style>@import ...</style>`.

**Template:** ver `design.md §404`. SVG con 8 celdas + lupa. 7 celdas tinta (`ep-cell ep-cN`), celda [0,2] terracota (`ep-cell ep-cell-accent ep-c3`), celda [1,2] vacía con lupa anclada a translate(222,158).

**`<style>` interno** (keyframes específicos 404):
- `ep-cellDrop` con delays `ep-c1`–`ep-c8`
- `ep-cell-accent` override: cellDrop 0.16s + accentGlow
- `ep-loupeIn`, `ep-loupeOrbit`, `ep-drawRing`, `ep-ping`
- Loupe classes: `.ep-loupe`, `.ep-loupe-ring`, `.ep-loupe-handle`, `.ep-ping`

**Criterio de done:**
- `vendor/bin/sail npm run build` sin errores TypeScript
- El componente renderiza en navegador vía ruta inexistente (`/pagina-que-no-existe`)
- Animaciones visibles: celdas caen, lupa orbita, ping aparece periódicamente
- Dark mode correcto
- Mobile: stack, copy centrado

---

### Task 4 — Vue page `errors/AccessDenied`

**Archivos involucrados:**
- `resources/js/pages/errors/AccessDenied.vue` (nuevo)

**Props:**
```typescript
defineProps<{ status: 401 | 403 }>()
```

**Template:** ver `design.md §401/403`. SVG con 7 celdas tinta + candado terracota en [0,2]. Copy y CTAs se renderizan desde un computed basado en `props.status`.

```typescript
const config = computed(() => ({
    401: {
        label: 'Necesitas iniciar sesión',
        title: 'Esta página está <em>bajo llave</em>.',
        lead: 'Para continuar necesitas iniciar sesión...',
        primary: { label: 'Iniciar sesión', href: route('login') },
        secondary: { label: 'Ir al inicio', href: '/' },
    },
    403: {
        label: 'Sin permisos para esta sección',
        title: 'Aquí <em>no puedes pasar</em>.',
        lead: 'Tu cuenta no tiene permiso para ver esta sección...',
        primary: { label: 'Contactar admin', href: 'mailto:admin@cacao.edu.ve?subject=403' },
        secondary: { label: 'Ir al inicio', href: '/' },
    },
}[props.status]))
```

Para el `href` del CTA de 401 usar la función Wayfinder de la ruta de login.

**`<style>` interno** (keyframes específicos 401/403):
- `ep-lockIn`, `ep-shackleClick`, `ep-lockJiggle`
- `.ep-lock` con ambas animaciones, `.ep-lock-body`, `.ep-lock-shackle`, `.ep-lock-keyhole`, `.ep-lock-keystem`

**Criterio de done:**
- `vendor/bin/sail npm run build` sin errores TypeScript
- 401: CTA muestra "Iniciar sesión", candado visible
- 403: CTA muestra "Contactar admin"
- Probado accediendo a ruta protegida con sesión activa de rol incorrecto

---

### Task 5 — Vue page `errors/ServerError`

**Archivos involucrados:**
- `resources/js/pages/errors/ServerError.vue` (nuevo)

**Lógica client-side:**
- `incidentId`: generado al mount con `sessionStorage`. Si `sessionStorage.getItem('ep_incident')` existe, usarlo; si no, generar `'CAC-' + Math.floor(Math.random()*0xFFFFFF).toString(16).toUpperCase().padStart(6,'0')` y guardarlo en `sessionStorage`.
- `handleRetry()`: añade clase spinner al botón, tras 600ms hace `window.location.reload()`
- `handleCopy()`: `navigator.clipboard.writeText(incidentId)` → cambiar label a "Copiado" por 1600ms

**Template:** ver `design.md §500`. Celdas torcidas, sigil rotatorio en [0,2], bloque de incidente bajo las actions.

**Footer:** `data-incident="true"` o clase especial para mostrar `Incidente activo` con `.ep-live` en `var(--danger)`.

**`<style>` interno** (keyframes específicos 500):
- `ep-drop-1` a `ep-drop-8` con rotaciones finales distintas
- `ep-sigilIn`, `ep-rotorSpin`, `ep-drawSigil`, `ep-dotBreath`
- `.ep-sigil-wrap`, `.ep-sigil-rotor`, `.ep-sigil-stroke`, `.ep-sigil-dot`

**Criterio de done:**
- `vendor/bin/sail npm run build` sin errores TypeScript
- Incident ID aparece y es copiable
- Botón Reintentar tiene spinner antes de reload
- Footer muestra "Incidente activo" en rojo

---

### Task 6 — Pest feature tests

**Archivos involucrados:**
- `tests/Feature/Errors/ErrorPagesTest.php` (nuevo)

**Crear con:** `vendor/bin/sail artisan make:test --pest Errors/ErrorPagesTest`

**Tests a incluir:**

```
test('unknown route returns 404 with NotFound inertia page')
test('404 preserves http status code')
test('unauthenticated access to protected route returns 401 with AccessDenied page')
test('unauthorized role access returns 403 with AccessDenied page with status prop')
test('401 page receives status prop 401')
test('403 page receives status prop 403')
```

**Notas:**
- Para 404: `$this->get('/ruta-que-no-existe')->assertStatus(404)->assertInertia(fn($p) => $p->component('errors/NotFound'))`
- Para 401: crear ruta de test protegida con `auth` middleware y acceder sin autenticar → assert 401 o verificar redirect a login (depende del middleware de auth)
- Para 403: `actingAs($user)` con rol incorrecto + `get('/enrollment')` → assertStatus(403) + assertInertia component `errors/AccessDenied`
- Para prop status: `->assertInertia(fn($p) => $p->component('errors/AccessDenied')->where('status', 403))`

**Criterio de done:**
- Todos los tests pasan: `vendor/bin/sail artisan test --compact --filter=ErrorPagesTest`
- RefreshDatabase en todos los tests
- Sin fixtures hardcodeados — usar factories

---

### Task 7 — Vitest component tests

**Archivos involucrados:**
- `tests/js/pages/errors/NotFound.test.ts` (nuevo)
- `tests/js/pages/errors/AccessDenied.test.ts` (nuevo)
- `tests/js/pages/errors/ServerError.test.ts` (nuevo)

**NotFound.test.ts:**
```typescript
// Verifica que el componente renderiza con el texto correcto
test('renders 404 copy', () => {
    // mount NotFound, assertText 'no aparece', assertText 'Ir al inicio'
})
test('has link to home', () => {
    // verify href="/" en el CTA primario
})
```

**AccessDenied.test.ts:**
```typescript
test('renders 401 copy when status is 401', () => {
    // mount con { status: 401 }, assertText 'bajo llave', assertText 'Iniciar sesión'
})
test('renders 403 copy when status is 403', () => {
    // mount con { status: 403 }, assertText 'no puedes pasar', assertText 'Contactar admin'
})
```

**ServerError.test.ts:**
```typescript
test('generates incident id on mount', () => {
    // mount, verificar que incidentId comienza con 'CAC-'
})
test('reuses incident id from sessionStorage', () => {
    // setItem en sessionStorage, mount, verificar mismo id
})
test('copy button copies to clipboard', async () => {
    // mock navigator.clipboard.writeText, click botón, verify fue llamado
})
```

**Notas de implementación:**
- Usar `@vue/test-utils` con `mount`
- Para clipboard: `vi.stubGlobal('navigator', { clipboard: { writeText: vi.fn() } })`
- Para sessionStorage: `sessionStorage.clear()` en `beforeEach`

**Criterio de done:**
- Todos los tests pasan: `vendor/bin/sail npm run -- vitest run tests/js/pages/errors/`
- Sin snapshot tests — solo assertions de comportamiento

---

### Task 8 — Dusk browser tests

**Archivos involucrados:**
- `tests/Browser/Errors/ErrorPagesTest.php` (nuevo)

**Crear con:** `vendor/bin/sail artisan make:test --pest Browser/Errors/ErrorPagesTest`

**Tests a incluir:**

```php
test('404 page is shown for unknown route', function () {
    $this->browse(function ($browser) {
        $browser->visit('/esta-ruta-no-existe-en-ninguna-parte')
            ->waitForText('no aparece', 10)
            ->assertSee('no aparece')
            ->assertSee('Ir al inicio')
            ->assertSee('CACAO');
    });
});

test('404 page has working home link', function () {
    $this->browse(function ($browser) {
        $browser->visit('/ruta-inexistente-xyz')
            ->waitForText('Ir al inicio', 5)
            ->clickLink('Ir al inicio')
            ->pause(1000)
            ->assertPathIsNot('/ruta-inexistente-xyz');
    });
});

test('403 page is shown when user lacks permission', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $this->browse(function ($browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/enrollment')
            ->waitForText('no puedes pasar', 10)
            ->assertSee('no puedes pasar')
            ->assertSee('Contactar admin');
    });
});

test('500 page shows incident id', function () {
    // Requiere ruta de test que lanza excepción
    // Crear en routes/web.php solo en APP_ENV=testing:
    //   Route::get('/_test/trigger-500', fn() => abort(500))->name('test.trigger500');
    $this->browse(function ($browser) {
        $browser->visit('/_test/trigger-500')
            ->waitForText('rompió', 10)
            ->assertSee('rompió')
            ->assertSee('CAC-')
            ->assertSee('Reintentar');
    });
});

test('dark mode: 404 page respects dark theme', function () {
    $this->browse(function ($browser) {
        $browser->script("document.documentElement.setAttribute('data-theme','dark')");
        $browser->visit('/ruta-no-existe-dark')
            ->waitForText('no aparece', 10)
            ->assertSee('no aparece');
        // Verificar que el fondo sea oscuro via script JS
    });
});
```

**Notas:**
- Agregar ruta de test solo en `APP_ENV=testing` dentro de `routes/web.php` con `if (app()->environment('testing'))`
- Los tests Dusk no usan RefreshDatabase — ver patrón en tests existentes
- Ejecutar: `vendor/bin/sail artisan dusk --filter=ErrorPagesTest`
- Roles deben existir: `php artisan db:seed --class=RoleSeeder` antes de los tests

**Criterio de done:**
- Todos los tests pasan en el navegador headless
- Screenshots generadas en `tests/Browser/screenshots/`
- No dejar la ruta `/_test/trigger-500` activa en producción (verificar con env check)

---

## Notas de ejecución

- Tasks 1 y 2 son prerequisitos de 3, 4 y 5
- Tasks 3, 4, 5 pueden ejecutarse en paralelo si hay varios agentes
- Task 6 requiere Task 1 completa (el responder de excepciones debe existir)
- Tasks 7 y 8 requieren Tasks 3, 4, 5 completas
- Después de cada archivo PHP: `vendor/bin/sail bin pint --dirty --format agent`
- Después de cada Vue/TS: `vendor/bin/sail npm run build` para verificar TypeScript
- Para run full de tests al finalizar: `vendor/bin/sail artisan test --compact --filter=ErrorPages` y `vendor/bin/sail npm run -- vitest run tests/js/pages/errors/` y `vendor/bin/sail artisan dusk --filter=ErrorPagesTest`
