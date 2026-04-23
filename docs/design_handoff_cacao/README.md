# Handoff: CACAO — Sistema de Diseño

**Control Académico, Curricular y de Actividades Organizadas**
Sistema de información académica construido en Vue 3 + Tailwind v4.

---

## Overview

Este paquete documenta el sistema de diseño completo de **CACAO**: paleta, tipografía, tokens, y la biblioteca de componentes UI (botones, forms, tablas, navegación, feedback, cards, etc.), más una pantalla de ejemplo en contexto (Dashboard admin).

El objetivo es que un desarrollador trabajando con **Claude Code** pueda implementar todos estos componentes en un proyecto **Vue 3 + Tailwind v4** manteniendo fidelidad pixel-perfect con el diseño.

## About the Design Files

Los archivos en la carpeta `reference/` son el **prototipo de diseño** construido en HTML + React inline (Babel) como herramienta de design review. **No son código de producción**. El trabajo consiste en **recrear estos diseños en un proyecto Vue 3 + Tailwind v4** usando componentes SFC (`.vue`), Composition API, y las convenciones del stack objetivo.

La traducción directa recomendada es:

| Referencia (`.jsx`) | Implementación (Vue 3) |
|---|---|
| `primitives.jsx` · Isotipo, Icon, Button, Badge | `src/components/base/{Isotipo,Icon,Button,Badge}.vue` |
| `sections-components.jsx` · Forms, Avatars | `src/components/form/*.vue`, `src/components/base/Avatar.vue` |
| `sections-patterns.jsx` · Tables, Lists, Cards, Navigation, Feedback | `src/components/{table,list,card,nav,feedback}/*.vue` |
| `sections-dashboard.jsx` · Layout + KPIs + Horario | `src/layouts/AppLayout.vue`, `src/views/Dashboard.vue` |
| `tokens.css` · variables CSS | `src/assets/tokens.css` (importar en `main.ts` o `app.css`) |
| `components.css` · estilos componentes | Distribuir por SFC con `<style scoped>`, o usar clases Tailwind |

## Fidelity

**Hi-fi.** Todos los colores, tipografía, espaciado, radios, sombras, estados y microinteracciones están definidos al pixel. Usar los valores exactos de `tokens.css`.

---

## Stack objetivo

- **Vue 3.5+** con `<script setup>` + TypeScript recomendado
- **Tailwind CSS v4** (configuración via `@theme` en CSS)
- **vue-router** para navegación entre módulos
- **Pinia** para estado (sugerido)
- **@vueuse/core** para composables utilitarios (sugerido: `useDark`, `useLocalStorage`)
- Fuentes: **Space Grotesk** (300,400,500,600,700) + **JetBrains Mono** (400,500) desde Google Fonts

---

## Design Tokens (Tailwind v4 — `@theme`)

Todos los tokens viven como custom properties y como tokens de Tailwind v4. Copiar `reference/tokens.css` a `src/assets/tokens.css` e importar en el entry. Convertir a `@theme` así:

```css
/* src/assets/tokens.css */
@import "tailwindcss";

@theme {
  /* Brand */
  --color-tinta: #131110;
  --color-terracota: #C8521A;
  --color-pizarra: #3D3A36;
  --color-papel: #F4F2EF;
  --color-papel-dark: #EDEBE7;
  --color-hueso: #FAFAF8;
  --color-ambar: #E8895A;

  /* Semantic (light defaults) */
  --color-accent: #C8521A;
  --color-accent-hover: #A9441A;
  --color-accent-soft: #F7E4D7;
  --color-success: #4C7A1F;
  --color-warning: #B87500;
  --color-danger:  #B12A1F;
  --color-info:    #1F5F8B;

  /* Type scale */
  --font-sans: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
  --font-mono: 'JetBrains Mono', ui-monospace, Menlo, monospace;
  --text-xs: 11px;
  --text-sm: 13px;
  --text-base: 14px;
  --text-md: 15px;
  --text-lg: 17px;
  --text-xl: 20px;
  --text-2xl: 24px;
  --text-3xl: 32px;
  --text-4xl: 44px;
  --text-5xl: 60px;

  /* Radius */
  --radius-xs: 3px;
  --radius-sm: 4px;
  --radius-md: 6px;
  --radius-lg: 10px;
  --radius-xl: 14px;

  /* Spacing (agregar solo si Tailwind default no cubre) */
}
```

Para los **tokens de superficie** (que cambian en dark mode) mantener las CSS custom properties **fuera** del bloque `@theme` para que sigan siendo redefinibles por `[data-theme="dark"]`:

```css
:root {
  --bg-page: #EDEBE7;
  --bg-surface: #FAFAF8;
  --bg-surface-2: #F4F2EF;
  --text-primary: #131110;
  --text-secondary: #55504A;
  --text-muted: #8A8278;
  --border: #E0DDD8;
  --border-strong: #C9C5BE;
  --sidebar-bg: #3D3A36;
  --sidebar-fg: #E8E4DF;
  --topbar-bg: #131110;
  /* …ver reference/tokens.css para la lista completa */
}
[data-theme="dark"] {
  --bg-page: #1C1A18;
  --bg-surface: #242220;
  --accent: #E8895A;
  /* …etc */
}
```

Y en Tailwind v4 se exponen como utilidades con:

```css
@theme {
  --color-bg-page: var(--bg-page);
  --color-bg-surface: var(--bg-surface);
  --color-text-primary: var(--text-primary);
  --color-text-secondary: var(--text-secondary);
  --color-text-muted: var(--text-muted);
  --color-border-base: var(--border);
  --color-border-strong: var(--border-strong);
}
```

Así obtienes `bg-bg-page`, `text-text-primary`, `border-border-base` etc. Usa el prefijo que prefieras.

### Shadows

```css
--shadow-xs: 0 1px 0 rgba(19,17,16,0.04);
--shadow-sm: 0 1px 2px rgba(19,17,16,0.06), 0 1px 0 rgba(19,17,16,0.03);
--shadow-md: 0 4px 12px rgba(19,17,16,0.08), 0 1px 2px rgba(19,17,16,0.04);
--shadow-lg: 0 16px 40px rgba(19,17,16,0.14), 0 2px 6px rgba(19,17,16,0.06);
```

### Espaciado

Escala en múltiplos de 4: `4, 8, 12, 16, 20, 24, 32, 40, 48, 64`. Compatible con Tailwind default (`p-1`, `p-2`, …).

### Dark mode

Toggle con atributo `data-theme="dark"` en `<html>`. Usa `@vueuse/core#useDark({ selector: 'html', attribute: 'data-theme', valueDark: 'dark', valueLight: 'light' })`.

---

## Reglas de marca (inamovibles)

### Isotipo
Cuadrícula 3×3 con dos reglas fijas:
- Posición **[0,2]** (esquina superior derecha) = **Terracota** — siempre.
- Posición **[1,2]** (borde derecho, centro) = **vacío** — siempre.

Las otras 7 celdas se pintan del color de foreground del contexto (tinta sobre papel, papel sobre tinta).

Tamaños disponibles: `20px`, `28px`, `40px`, `72px`, `120px`. Gap proporcional (~10% del tamaño).

### Wordmark
- Fuente: **Space Grotesk** 700
- `letter-spacing: -0.01em`
- Todo en mayúsculas para el logo: **CACAO**
- Nunca rotar, nunca distorsionar, nunca aplicar gradientes ni sombras

### Uso del acento (Terracota #C8521A)
Reservado para:
- CTAs primarios
- Datos críticos (KPIs con cambio significativo)
- Item activo del sidebar
- Focus rings
- Isotipo celda [0,2]

**No usar** como fondo de secciones grandes ni en textos de párrafo.

---

## Componentes a implementar

### 1. `Isotipo.vue`
Props: `size: 'xs' | 'sm' | 'md' | 'lg' | 'xl'` (mapea a 20/28/40/72/120 px).
Render: 9 spans en grid 3×3. El 3º con color acento, el 6º vacío, el resto color `--text-primary` (o recibido vía prop `variant`).

### 2. `Icon.vue`
Inline SVG set. Ver `reference/primitives.jsx` para el set de paths. Props: `name`, `size` (default 14).

### 3. `Button.vue`
Variantes: `primary | secondary | ghost | danger | link | tonal`
Tamaños: `sm | md | lg` (altura 28 / 32 / 40 px)
Props: `icon?`, `iconRight?`, `iconOnly?`, `loading?`, `disabled?`
Estados: default / hover / active / focus-visible (ring acento) / disabled / loading

**Specs primary:**
- bg `var(--accent)`, text `var(--accent-fg)`, radius `--radius-md`
- hover: bg `var(--accent-hover)`
- disabled: opacity 0.5, cursor not-allowed
- focus-visible: outline 2px `var(--accent)` + offset 2px

### 4. `Badge.vue` y `Chip.vue`
Variantes semánticas: `neutral | success | warning | danger | info | accent`
Props: `dot?` (bolita de color), `outline?` (borde sin fill)
Altura 20px, `font-size: 11px`, `font-weight: 600`, radius pill.
Chip = Badge con close button opcional (`removable?`).

### 5. Forms (`TextField.vue`, `Select.vue`, `Textarea.vue`, `Checkbox.vue`, `Radio.vue`, `Switch.vue`, `Dropzone.vue`)
- Input height: 36px (sm) / 40px (md, default) / 44px (lg)
- Border: 1px `var(--border)`, radius `--radius-md`
- Focus: border `var(--accent)` + ring 3px `var(--accent-soft)`
- Error: border `var(--danger)`, mensaje debajo en `text-danger text-xs`
- Label encima, hint/error debajo
- Affixes (icono o texto al inicio/final del input)
- Dropzone: borde dashed `--border-strong`, hover `--accent`

### 6. `Avatar.vue`
Tamaños: `xs (20) | sm (28) | md (36) | lg (48) | xl (64)`
Props: `src?`, `initials?`, `variant?` (1-5 color presets). Background derivado de hash del nombre si no se provee. Radius 50%.

### 7. `Card.vue` (+ `StatCard.vue`)
Card base: `bg-surface`, border `--border`, radius `--radius-lg`, padding 20px, shadow-xs.
Slots: `#header`, default, `#footer`.

**StatCard** (las del dashboard):
- `label` (uppercase 11px muted)
- `value` (44px semibold tabular-nums)
- `delta` (12px con flecha ▲/▼ y color semántico)
- `footer` (13px muted, texto de contexto)
- Variante `accent`: acento en la barra superior

### 8. `Table.vue` (+ `TableCell.vue`, `TableHead.vue`)
- Header: `font-size: 11px`, uppercase, `letter-spacing: 0.06em`, `color: var(--text-muted)`, height 40px, border-bottom `--border`
- Row height: 52px, hover bg `--bg-surface-2`
- Sort indicator en headers clickables
- Selección múltiple con checkbox (header toggles all)
- Features: búsqueda, filtros chip, paginación, row-actions (icon buttons visibles on hover)

### 9. `Toast.vue` + `useToast()` composable
- Posición: bottom-right con offset 16px, stack vertical
- Auto-dismiss 4s (configurable)
- Variantes: success / warning / danger / info / neutral
- Con action opcional ("Deshacer")
- Shadow-lg, radius-lg

### 10. `Modal.vue`
- Overlay: `rgba(19,17,16,0.5)` + backdrop-blur sutil
- Panel: bg-surface, radius `--radius-xl`, max-width según size (sm 400 / md 520 / lg 720)
- Header con título + close, body scrollable, footer con acciones alineadas a la derecha
- Trap focus, esc to close, click overlay to close (configurable)
- Variante `confirm` con icono semántico

### 11. `Alert.vue` / `EmptyState.vue` / `Skeleton.vue`
- Alert: banner con icono + mensaje + acción opcional, variantes semánticas, dismissible
- EmptyState: icono grande + título + descripción + CTA; usar para tablas vacías
- Skeleton: `bg-bg-sunken` con shimmer via `@keyframes`

### 12. Navegación
- **Topbar** (`--topbar-bg: #131110` / fg papel): breadcrumbs, search global con `⌘K`, notifs, botón Nuevo, user menu
- **Sidebar** (`--sidebar-bg: #3D3A36` / fg `#E8E4DF`): logo CACAO arriba, grupos (General / Académico / Análisis), item activo con bg `--accent`, footer con user card
- **Tabs**: línea bajo el activo con color acento, hover muted
- **Breadcrumbs**: separador `/` en muted, último item en primary bold
- **Segmented control**: para filtros Semana/Mes/Período (usado en dashboard)

### 13. `TodaySchedule.vue` (widget del dashboard)
Barra horaria 07:00 → 19:00 con:
- Escala de horas con marcadores dashed
- Slots posicionados por `left: X%, width: Y%` (absolute)
- Tres estados visuales:
  - `done`: `bg-bg-sunken`, texto muted, title con line-through gris
  - `now`: `bg-accent text-accent-fg`, shadow acento, **halo pulse opcional**
  - `next`: `bg-bg-surface-2`, borde dashed `--border-strong`
- Marcador "ahora" vertical con pill mostrando la hora actual
- Leyenda con dots de los tres estados

Props esperadas:
```ts
interface ScheduleSlot {
  start: number  // hora 0-23 (decimal ok: 8.5 = 08:30)
  end: number
  title: string
  room: string
  section: string
  status: 'done' | 'now' | 'next'
}
```

---

## Layout del shell

```
┌─────────────────────────────────────────────────────┐
│  [Sidebar 240px]   [Main: Topbar 56px + Content]    │
│   pizarra           tinta                            │
│   ├ Brand           ├ Breadcrumbs  ░  Search ⌘K      │
│   ├ Nav groups      └────────────────────────────┘   │
│   └ User card       ┌────────────────────────────┐   │
│                     │  Page content (scroll)     │   │
│                     │  padding 24/32 px          │   │
│                     └────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

Archivo sugerido: `src/layouts/AppLayout.vue` con:
```vue
<template>
  <div class="app-shell">
    <AppSidebar :items="navItems" v-model:active="activeRoute"/>
    <main class="app-main">
      <AppTopbar :breadcrumbs="breadcrumbs"/>
      <div class="app-content"><router-view/></div>
    </main>
  </div>
</template>
```

---

## Tipografía — jerarquía (Space Grotesk)

| Token | Size | Weight | Line-height | Uso |
|---|---|---|---|---|
| `display` | 60px | 700 | 1.05 | Hero del login o landing |
| `h1` | 44px | 700 | 1.1 | Título de página |
| `h2` | 32px | 600 | 1.2 | Sección |
| `h3` | 24px | 600 | 1.3 | Subsección / title card |
| `h4` | 20px | 600 | 1.35 | Card heading |
| `body-lg` | 17px | 400 | 1.5 | Párrafo destacado |
| `body` | 14px | 400 | 1.5 | Por defecto |
| `body-sm` | 13px | 400 | 1.5 | UI secundario |
| `caption` | 11px | 600 | 1.4, 0.06em | Labels uppercase |
| `mono` | 11-13px | 500 | 1.4 | Números, IDs, códigos |

Tabular-nums en toda la UI numérica (KPIs, tablas, horas).

---

## Interacciones y microanimaciones

- **Transiciones globales**: 150ms, `ease-out` para hover; 200ms para cambios de estado
- **Focus ring**: outline 2px acento, offset 2px, radius sm — SIEMPRE visible para teclado (`focus-visible`)
- **Hover en tablas**: bg `--bg-surface-2` toda la fila
- **Hover en cards**: `translateY(-1px)` + shadow-sm
- **Loading buttons**: spinner reemplaza icono, texto se mantiene
- **Toast entry**: slide-in desde derecha, exit fade + slide
- **Modal entry**: fade overlay + scale 0.96→1 panel, 180ms

---

## Accesibilidad

- Contraste mínimo AA en todas las combinaciones (validado en paleta)
- Focus visible en todos los interactivos
- `aria-label` en icon-only buttons
- `role="status"` / `role="alert"` en toasts
- `aria-sort` en headers de tabla ordenables
- Trap focus en modales, return focus al trigger al cerrar
- Reduce motion: respetar `prefers-reduced-motion`

---

## State / Routing sugerido

```
/login
/dashboard                       ← la pantalla del reference
/inscripciones
/estudiantes
/profesores
/carreras
/materias
/secciones
/aulas
/reportes
/prelaciones
```

Composables sugeridos:
- `useTheme()` — toggle light/dark con persistencia
- `useToast()` — api imperativa para toasts
- `useModal()` — api imperativa para modales
- `useAuth()` — estado de sesión
- `usePermissions()` — RBAC por módulo

---

## Assets

- **Fuentes**: Google Fonts (Space Grotesk, JetBrains Mono). Cargar en `index.html` con `<link preconnect>`.
- **Iconos**: inline SVG en `Icon.vue` (ver set en `reference/primitives.jsx`). Si necesitas más, añadir lucide-vue-next o heroicons manteniendo stroke 1.5-2.
- **Isotipo**: no es imagen — es el componente `Isotipo.vue` que genera la cuadrícula programáticamente.

---

## Archivos de referencia

En `reference/`:

| Archivo | Contenido |
|---|---|
| `CACAO Design System.html` | Entry point del prototipo — navegable |
| `tokens.css` | **Todos los tokens** (colores, tipo, spacing, radios, shadows, light+dark) |
| `components.css` | Estilos completos de todos los componentes |
| `primitives.jsx` | Isotipo, Icon, Button, Badge |
| `sections-fundamentals.jsx` | Showcase de paleta, tipo, radios, shadows |
| `sections-components.jsx` | Forms, Avatars |
| `sections-patterns.jsx` | Tables, Lists, Cards, Navigation, Feedback |
| `sections-dashboard.jsx` | Pantalla completa del Dashboard admin + TodaySchedule |
| `tweaks-panel.jsx`, `tweaks.jsx` | Panel de Tweaks (no se implementa en prod — es herramienta de design review) |
| `app.jsx` | Composición del storybook (solo referencia de layout) |

Para ver el prototipo en vivo: abrir `CACAO Design System.html` en un navegador.

---

## Prompt sugerido para Claude Code

> "Implementa el sistema de diseño CACAO en este proyecto Vue 3 + Tailwind v4, siguiendo el README de `design_handoff_cacao/` y usando los archivos en `design_handoff_cacao/reference/` como fuente de verdad para tokens, markup y comportamiento. Empezá por:
> 1. Copiar y adaptar `tokens.css` a `src/assets/tokens.css` con la sintaxis `@theme` de Tailwind v4.
> 2. Crear los primitives en `src/components/base/`: `Isotipo.vue`, `Icon.vue`, `Button.vue`, `Badge.vue`.
> 3. Crear `AppLayout.vue`, `AppSidebar.vue`, `AppTopbar.vue`.
> 4. Implementar la vista `Dashboard.vue` reproduciendo `sections-dashboard.jsx` con datos mock.
>
> Respetá los valores exactos de los tokens. Usá `<script setup lang="ts">` y Composition API."
