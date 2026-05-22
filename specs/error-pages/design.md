# Design — Error Pages

**Feature:** `06-error-pages`

---

## Sistema visual compartido

Todas las páginas de error comparten el mismo lenguaje visual: el **isotipo CACAO 3×3** como arte central, con la celda [0,2] sustituida por un elemento especial que narra el error. El layout es de dos columnas en desktop (arte | copy) y stack en mobile.

---

## Tokens CSS disponibles (de `app.css`)

```
--bg-page, --bg-surface, --bg-surface-2, --bg-sunken
--text-primary, --text-secondary, --text-muted, --text-inverse
--accent, --accent-hover, --accent-fg, --accent-soft
--border, --border-strong
--success, --danger
--font-sans: 'Space Grotesk'
--font-mono: 'JetBrains Mono'
```

Dark mode: `[data-theme="dark"]` en elemento raíz — ya definido en `app.css`.

---

## Layout compartido (`resources/css/error-pages.css`)

```
.ep-root
  min-height: 100vh/100dvh
  display: grid; grid-template-rows: 1fr auto
  background: var(--bg-surface-2)

.ep-shell (main)
  display: grid; place-items: center
  padding: clamp(20px, 5vw, 56px)

.ep-error (contenedor 2-col)
  max-width: 1080px
  grid-template-columns: minmax(0,1.05fr) minmax(0,1fr)   [≥820px]
  grid-template-columns: 1fr                               [<820px]
  gap: clamp(48px,6vw,96px) / clamp(28px,5vw,64px)

.ep-art
  aspect-ratio: 1/1; max-width: 460px mobile / stretch desktop

.ep-copy
  flex-col; gap: 20px
  text-align: left (desktop) / center (mobile)

.ep-footer
  padding: 16px clamp(20px,5vw,56px)
  flex; justify-between; border-top: 1px solid var(--border)
  font: mono 11px uppercase tracking-[0.14em]
```

**Papel SVG** (fondo del isotipo):
```
fill: var(--bg-surface); stroke: var(--border); stroke-width: 1; rx: 14
```

---

## Animaciones compartidas (`error-pages.css`)

```css
/* status pill, título, lead, actions */
@keyframes fadeInUp {
  0%   { opacity:0; transform: translateY(8px); }
  100% { opacity:1; transform: translateY(0); }
}
.ep-status  { animation: fadeInUp 0.55s ease 0.08s both; }
.ep-title   { animation: fadeInUp 0.65s cubic-bezier(.2,.8,.2,1) 0.20s both; }
.ep-lead    { animation: fadeInUp 0.65s ease 0.34s both; }
.ep-actions { animation: fadeInUp 0.65s ease 0.48s both; }

/* dot pulsante en status pill */
@keyframes pulse {
  0%,100% { box-shadow: 0 0 0 0 color-mix(in oklab, var(--accent) 50%, transparent); }
  50%     { box-shadow: 0 0 0 7px color-mix(in oklab, var(--accent) 0%, transparent); }
}

/* indicador live en footer */
@keyframes live {
  0%,100% { opacity: 0.45; }
  50%     { opacity: 1; }
}
```

**Keyframe de celda (compartido entre las 3 páginas):**
```css
@keyframes ep-cellDrop {
  0%   { opacity:0; transform: translateY(-14px) scale(0.7); }
  65%  { opacity:1; transform: translateY(1px) scale(1.03); }
  100% { opacity:1; transform: translateY(0) scale(1); }
}
```

---

## SVG Isotipo — geometría exacta

```
viewBox: 0 0 300 300
Papel: rect x=10 y=10 w=280 h=280 rx=14
Celdas: 56×56, rx=11, gap=8
Posiciones (x, y):
  [0,0]=( 66, 66)  [0,1]=(130, 66)  [0,2]=(194, 66)  ← celda especial por página
  [1,0]=( 66,130)  [1,1]=(130,130)  [1,2]=(194,130) vacía siempre
  [2,0]=( 66,194)  [2,1]=(130,194)  [2,2]=(194,194)
```

Las celdas [1,2] siempre está vacía (regla de marca). La celda [0,2] es la que varía por página.

---

## Página 404 — Not Found

### Elemento especial [0,2]: lupa + [0,2] terracota

```
Celda [0,2]: fill var(--accent)  → animación cellDrop + accentGlow
Lupa: anclada al centro de [1,2] = translate(222, 158)
  ring: circle r=18, stroke var(--text-muted), stroke-width 2.4
  handle: line (13,13)→(22,22), mismo estilo
  ping: circle r=3, fill var(--accent)
```

**Animaciones específicas 404:**
```css
/* Glow periódico en celda terracota */
@keyframes ep-accentGlow {
  0%,100% { filter: none; }
  50%     { filter: drop-shadow(0 0 6px color-mix(in oklab, var(--accent) 65%, transparent)); }
}
/* Lupa */
@keyframes ep-loupeIn  { 0%{opacity:0;transform:scale(0.4)} 100%{opacity:1;transform:scale(1)} }
@keyframes ep-loupeOrbit {
  0%   { transform: translate(0,0) rotate(0); }
  25%  { transform: translate(-4px,-3px) rotate(-4deg); }
  50%  { transform: translate(3px,-5px) rotate(3deg); }
  75%  { transform: translate(5px,2px) rotate(5deg); }
  100% { transform: translate(0,0) rotate(0); }
}
@keyframes ep-ping {
  0%,18%,100% { opacity:0; transform:scale(0.4); }
  22%         { opacity:1; transform:scale(1.4); }
  32%         { opacity:0; transform:scale(2.4); }
}
```

**Copy:**
```
Status:  ● 404 · Página no encontrada
H1:      La buscamos por todas partes, pero <em>no aparece</em>.
Lead:    Es posible que el enlace haya cambiado, el archivo se haya movido
         de carpeta, o nunca haya existido. Volvamos a terreno conocido.
CTA 1:   [🏠 Ir al inicio]     href="/"           btn primary (terracota)
CTA 2:   Reportar enlace roto → href="mailto:soporte@…"  btn link
```

---

## Página 401/403 — Access Denied

### Prop: `status: 401 | 403`

### Elemento especial [0,2]: candado terracota

```
Posición del grupo: translate(194, 66)
Shackle: path d="M16,34 V22 a12,12 0 0 1 24,0 V34"
         stroke var(--accent), stroke-width 5, fill none
Body:    rect x=6 y=26 w=44 h=30 rx=6  fill var(--accent)
Keyhole: circle cx=28 cy=38 r=3.2  fill var(--bg-surface)
Keystem: rect x=26.8 y=39.4 w=2.4 h=9 rx=0.8  fill var(--bg-surface)
```

**Animaciones específicas 401/403:**
```css
@keyframes ep-lockIn {
  0%   { opacity:0; transform:translateY(-12px) scale(0.5); }
  60%  { opacity:1; transform:translateY(2px) scale(1.05); }
  100% { opacity:1; transform:translateY(0) scale(1); }
}
@keyframes ep-shackleClick {
  0%,50% { transform: translateY(-10px); }
  80%    { transform: translateY(2px); }
  100%   { transform: translateY(0); }
}
@keyframes ep-lockJiggle {
  0%,92%,100% { transform: translate(0,0) rotate(0); }
  94%         { transform: translate(-1.5px,0) rotate(-3deg); }
  96%         { transform: translate(1.5px,0) rotate(3deg); }
  98%         { transform: translate(0,0) rotate(0); }
}
```

**Copy diferenciado por prop `status`:**

| Campo | 401 | 403 |
|---|---|---|
| Status label | Necesitas iniciar sesión | Sin permisos para esta sección |
| H1 | Esta página está `<em>bajo llave</em>`. | Aquí `<em>no puedes pasar</em>`. |
| Lead | Para continuar necesitas iniciar sesión con una cuenta CACAO. | Tu cuenta no tiene permiso para ver esta sección. Contacta a un administrador. |
| CTA primario | 🔑 Iniciar sesión → `route('login')` | ✉️ Contactar admin → `mailto:admin@…` |
| CTA secundario | 🏠 Ir al inicio → `"/"` | 🏠 Ir al inicio → `"/"` |

CTA primario: `btn--primary`. CTA secundario: `btn--ghost`.

---

## Página 500 — Server Error

### Celdas: ligeramente torcidas al aterrizar

Cada celda tiene su propio `@keyframes drop-N` que termina con una rotación residual de ±2–8°:
```
drop-1: finaliza translate(-2px,1px) rotate(-4deg)
drop-2: finaliza translate(2px,-1px) rotate(3deg)
drop-4: finaliza translate(-3px,1px) rotate(-3deg)
drop-5: finaliza translate(2px,-1px) rotate(5deg)
drop-6: finaliza translate(-1px,2px) rotate(-5deg)
drop-7: finaliza translate(1px,1px)  rotate(2deg)
drop-8: finaliza translate(-1px,-1px) rotate(-7deg)
```

### Elemento especial [0,2]: sigil de malfunción

```
Posición del grupo: translate(194, 66)
Rotor (gira): circle cx=28 cy=28 r=22  stroke var(--text-muted), stroke-width 2.2
              path "M28,6 A22,22 0 0 1 50,28"
              line horizontal M6,28 L50,28 y vertical M28,6 L28,50
Punto central: circle cx=28 cy=28 r=4.2  fill var(--accent)  → respiración suave
```

**Animaciones específicas 500:**
```css
@keyframes ep-sigilIn   { 0%{opacity:0;transform:scale(0.5)} 100%{opacity:1;transform:scale(1)} }
@keyframes ep-rotorSpin { to { transform: rotate(360deg); } }
@keyframes ep-drawSigil { to { stroke-dashoffset: 0; } }  /* dasharray/dashoffset: 120 */
@keyframes ep-dotBreath {
  0%,100% { opacity:0.6; transform:scale(1); }
  50%     { opacity:1;   transform:scale(1.15); }
}
```

**Copy:**
```
Status:  ● 500 · Error interno del servidor
H1:      Algo se <em>rompió</em> de nuestro lado.
Lead:    Nuestro equipo ya recibió el aviso. Por lo general se resuelve con
         un reintento — si vuelve a fallar, comparte el código del incidente.
CTA 1:   [↺ Reintentar]  button#retryBtn — spinner icon, onClick → reload
CTA 2:   [🏠 Ir al inicio]  href="/"  btn--ghost
```

**Bloque de incidente (debajo de las acciones):**
```
"Incidente  CAC-XXXXXX  [Copiar]"
font-mono, 11px, color text-muted
CAC-XXXXXX: generado con sessionStorage + Math.random hex si no existe
Botón Copiar: navigator.clipboard.writeText, cambiar label a "Copiado" por 1.6s
```

**Footer 500:** `data-state="incident"` → `.ep-live { background: var(--danger); }` + texto "Incidente activo"

---

## Componentes de copia (`ep-*`)

**Status pill:**
```html
<span class="ep-status">
  <span class="ep-dot"></span>     <!-- terracota pulsante -->
  <span class="ep-code">404</span> <!-- mono bold -->
  <span class="ep-sep"></span>     <!-- divisor vertical 1px -->
  <span>Página no encontrada</span>
</span>
```

**Botones:**
```css
.ep-btn { height:46px; padding:0 20px; border-radius:6px; font:sans 15px/500 }
.ep-btn--primary { background:var(--accent); color:var(--accent-fg) }
.ep-btn--ghost   { background:transparent; border: 1px solid var(--border-strong); color:var(--text-primary) }
.ep-btn--link    { height:auto; padding:0 4px; border:0; color:var(--text-secondary) }
```

**Footer marca mini:**
```html
<span class="ep-mark">        <!-- inline-grid 3×3 de 4px, gap 1px -->
  <span></span><span></span><span class="ep-m-accent"></span>
  <span></span><span></span><span class="ep-m-empty"></span>
  <span></span><span></span><span></span>
</span>
CACAO · Sistema de Inscripción
```
