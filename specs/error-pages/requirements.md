# Requirements — Error Pages

**Feature:** `06-error-pages`

---

## Problema

El sistema no tiene páginas de error personalizadas. Cuando ocurre un 404, 401, 403 o 500, Laravel muestra una respuesta HTTP genérica que rompe la experiencia CACAO — no usa el sistema visual, no tiene navegación, y no respeta el modo oscuro.

---

## Páginas a implementar

| Código | Componente Inertia | Condición |
|---|---|---|
| 404 | `errors/NotFound` | Cualquier ruta no registrada |
| 401 | `errors/AccessDenied` (prop `status: 401`) | Sesión no iniciada en ruta protegida |
| 403 | `errors/AccessDenied` (prop `status: 403`) | Sesión activa pero sin permisos |
| 500 | `errors/ServerError` | Excepción no capturada en servidor |

---

## Requisitos funcionales

### RF-01 Manejo de excepciones
- `bootstrap/app.php` registra un responder en `withExceptions()` que intercepta los códigos 404, 401, 403, 500 y retorna un `Inertia::render()` con el componente y status code correcto
- La respuesta conserva el HTTP status code original
- Funciona tanto para peticiones Inertia (AJAX) como para peticiones directas del navegador

### RF-02 Layout independiente
- Las páginas de error NO usan `AppLayout` — se renderizan sin barra lateral ni topbar
- `app.ts` retorna `null` para rutas cuyo nombre comienza con `errors/`

### RF-03 CSS compartido de páginas de error
- `resources/css/error-pages.css` con el layout y animaciones compartidas entre las tres páginas
- Las animaciones page-specific (lupa, candado, sigil) viven en el `<style>` interno de cada componente Vue

### RF-04 Página 404 — Not Found
- **Arte:** SVG 300×300 con isotipo 3×3. 8 celdas caen escalonadas. Celda terracota en posición [0,2]. Celda [1,2] vacía con lupa orbital animada que hace ping terracota periódico
- **Copy:** Status pill `● 404 · Página no encontrada`. H1: *"La buscamos por todas partes, pero **no aparece**."*. Lead explicativo. CTAs: `Ir al inicio` (primary terracota) + `Reportar enlace roto →` (link)

### RF-05 Página 401/403 — Access Denied
- Recibe prop `status: 401 | 403`
- **Arte:** SVG con isotipo. Candado terracota en [0,2]: shackle cae y hace click al cerrar, luego vibra cada ~6s
- **Copy 401:** Status pill `● 401 · Necesitas iniciar sesión`. H1: *"Esta página está **bajo llave**."*. CTAs: `Iniciar sesión` (primary) + `Ir al inicio` (ghost)
- **Copy 403:** Status pill `● 403 · Sin permisos para esta sección`. H1: *"Aquí **no puedes pasar**."*. CTAs: `Contactar admin` (primary) + `Ir al inicio` (ghost)

### RF-06 Página 500 — Server Error
- **Arte:** SVG con isotipo. Las 7 celdas aterrizan levemente torcidas (±3–8°). Sigil (círculo+cruz+arco) en [0,2]: se dibuja, luego rota continuamente. Punto terracota central respira
- **Copy:** Status pill `● 500 · Error interno del servidor`. H1: *"Algo se **rompió** de nuestro lado."*. CTAs: `Reintentar` (primary, con spinner al click) + `Ir al inicio` (ghost)
- **Bloque de incidente:** ID generado client-side `CAC-XXXXXX` (hex aleatorio, estable por sesión con `sessionStorage`). Botón `Copiar` con feedback visual al copiar al clipboard
- **Footer:** estado `Incidente activo` en rojo (usar `--danger`) en lugar de `Servicio operativo`

### RF-07 Comportamiento compartido
- Layout dos columnas en ≥820px (arte izquierda, copy derecha). Stack en mobile (<820px) con copy centrado
- Dark mode: respeta `[data-theme="dark"]` del sistema existente vía CSS variables
- Respeta `prefers-reduced-motion`: todas las animaciones se desactivan
- Footer minimalista: isotipo 3×3 mini + wordmark + indicador de servicio

---

## Requisitos no funcionales

- Vitest: tests de lógica de componente (generación de ID, selección de copy por status, botón de copia)
- Pest: feature tests que verifican cada código → componente Inertia correcto
- Dusk: browser tests que visitan las páginas y verifican elementos visuales clave
- `vendor/bin/sail npm run build` sin errores TypeScript
- Pint sin errores en cada archivo PHP modificado

---

## Fuera de scope

- Página de mantenimiento (503) — future
- Animaciones de debug en desarrollo (stack trace overlay)
- Integración con servicio externo de error tracking (Sentry, etc.)
