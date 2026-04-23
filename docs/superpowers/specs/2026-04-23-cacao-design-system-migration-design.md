# CACAO Design System — Migración completa

**Fecha:** 2026-04-23
**Stack:** Vue 3 + Inertia.js v3 + Tailwind CSS v4 + Laravel 13

---

## Objetivo

Migrar el proyecto de shadcn/ui a la identidad visual CACAO documentada en `docs/design_handoff_cacao/`. El resultado es un sistema de diseño coherente, sin dependencias shadcn, con componentes Vue SFC propios.

**Fuente de verdad:** `docs/design_handoff_cacao/reference/` — especialmente `tokens.css`, `components.css`, `primitives.jsx` y `sections-dashboard.jsx`.

---

## Decisiones de arquitectura

### 1. Enfoque: bottom-up
Orden de implementación: CSS tokens → primitives → layout shell → Dashboard → feedback → data → páginas existentes.
Cada capa es compilable y funcional antes de avanzar a la siguiente.

### 2. Dark mode: `data-theme="dark"`
Se abandona la clase `.dark` de Tailwind. El toggle se controla con `data-theme="dark"` en `<html>`.
- `app.blade.php` usa `data-theme` en vez de `class="dark"` en el elemento `<html>`.
- El script de detección del sistema cambia a: si `prefers-color-scheme: dark`, setear `document.documentElement.setAttribute('data-theme','dark')`.
- Las CSS variables del design system ya usan `[data-theme="dark"]` — no hay que añadir nada.

### 3. Eliminar shadcn/ui
`resources/js/components/ui/` se elimina por completo. Sus importaciones en layouts y páginas se reemplazan por los nuevos componentes.

---

## Arquitectura CSS (`resources/css/app.css`)

```css
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
@import 'tailwindcss';
@import 'tw-animate-css';

@theme inline {
  /* Marca fija */
  --color-tinta: #131110;
  --color-terracota: #C8521A;
  --color-pizarra: #3D3A36;
  --color-papel: #F4F2EF;
  --color-papel-dark: #EDEBE7;
  --color-hueso: #FAFAF8;
  --color-ambar: #E8895A;

  /* Tipografía */
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

  /* Radius */
  --radius-xs: 3px;
  --radius-sm: 4px;
  --radius-md: 6px;
  --radius-lg: 10px;
  --radius-xl: 14px;
  --radius-pill: 999px;

  /* Tokens dinámicos expuestos como utilidades Tailwind */
  --color-bg-page:       var(--bg-page);
  --color-bg-surface:    var(--bg-surface);
  --color-bg-surface-2:  var(--bg-surface-2);
  --color-bg-sunken:     var(--bg-sunken);
  --color-accent:        var(--accent);
  --color-accent-hover:  var(--accent-hover);
  --color-accent-soft:   var(--accent-soft);
  --color-accent-fg:     var(--accent-fg);
  --color-text-primary:  var(--text-primary);
  --color-text-secondary:var(--text-secondary);
  --color-text-muted:    var(--text-muted);
  --color-text-inverse:  var(--text-inverse);
  --color-border-base:   var(--border);
  --color-border-strong: var(--border-strong);
  --color-sidebar-bg:    var(--sidebar-bg);
  --color-sidebar-fg:    var(--sidebar-fg);
  --color-topbar-bg:     var(--topbar-bg);
  --color-topbar-fg:     var(--topbar-fg);
  --color-success:       var(--success);
  --color-success-bg:    var(--success-bg);
  --color-success-fg:    var(--success-fg);
  --color-warning:       var(--warning);
  --color-warning-bg:    var(--warning-bg);
  --color-warning-fg:    var(--warning-fg);
  --color-danger:        var(--danger);
  --color-danger-bg:     var(--danger-bg);
  --color-danger-fg:     var(--danger-fg);
  --color-info:          var(--info);
  --color-info-bg:       var(--info-bg);
  --color-info-fg:       var(--info-fg);
}

/* Tokens dinámicos — redefinibles en [data-theme="dark"] */
:root {
  --bg-page: #EDEBE7;
  --bg-surface: #FAFAF8;
  --bg-surface-2: #F4F2EF;
  --bg-sunken: #E8E4DE;
  --accent: #C8521A;
  --accent-hover: #A9441A;
  --accent-soft: #F7E4D7;
  --accent-fg: #FFFFFF;
  --text-primary: #131110;
  --text-secondary: #55504A;
  --text-muted: #8A8278;
  --text-inverse: #F4F2EF;
  --border: #E0DDD8;
  --border-strong: #C9C5BE;
  --sidebar-bg: #3D3A36;
  --sidebar-fg: #E8E4DF;
  --sidebar-muted: #9C9690;
  --topbar-bg: #131110;
  --topbar-fg: #F4F2EF;
  --success: #4C7A1F;  --success-bg: #EAF3DE;  --success-fg: #2E4B12;
  --warning: #B87500;  --warning-bg: #FBEFD3;  --warning-fg: #6B4500;
  --danger:  #B12A1F;  --danger-bg:  #F8DCD7;  --danger-fg:  #6E1812;
  --info:    #1F5F8B;  --info-bg:    #DCE8F2;  --info-fg:    #133C58;
  --shadow-xs: 0 1px 0 rgba(19,17,16,0.04);
  --shadow-sm: 0 1px 2px rgba(19,17,16,0.06), 0 1px 0 rgba(19,17,16,0.03);
  --shadow-md: 0 4px 12px rgba(19,17,16,0.08), 0 1px 2px rgba(19,17,16,0.04);
  --shadow-lg: 0 16px 40px rgba(19,17,16,0.14), 0 2px 6px rgba(19,17,16,0.06);
}

[data-theme="dark"] {
  --bg-page: #1C1A18;
  --bg-surface: #242220;
  --bg-surface-2: #2A2826;
  --bg-sunken: #181614;
  --accent: #E8895A;
  --accent-hover: #F0A078;
  --accent-soft: #3A251C;
  --accent-fg: #131110;
  --text-primary: #E8E4DF;
  --text-secondary: #B5AFA8;
  --text-muted: #7A7570;
  --text-inverse: #131110;
  --border: #2E2C2A;
  --border-strong: #3E3B38;
  --sidebar-bg: #161412;
  --sidebar-fg: #D8D3CD;
  --sidebar-muted: #7F7A74;
  --topbar-bg: #0E0C0B;
  --topbar-fg: #E8E4DF;
  --success: #89B95A;  --success-bg: #0D2810;  --success-fg: #B6D891;
  --warning: #E2A645;  --warning-bg: #2B1F0A;  --warning-fg: #EFC07B;
  --danger:  #E77465;  --danger-bg:  #2E1411;  --danger-fg:  #F2A89B;
  --info:    #6FA8CF;  --info-bg:    #0F2230;  --info-fg:    #A8C8E0;
  --shadow-sm: 0 1px 2px rgba(0,0,0,0.4);
  --shadow-md: 0 4px 12px rgba(0,0,0,0.4), 0 1px 2px rgba(0,0,0,0.3);
  --shadow-lg: 0 16px 40px rgba(0,0,0,0.5), 0 2px 6px rgba(0,0,0,0.3);
}

@layer base {
  *, ::after, ::before { border-color: var(--border); }
  body {
    font-family: var(--font-sans);
    font-size: var(--text-base);
    line-height: 1.5;
    -webkit-font-smoothing: antialiased;
  }
  *:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
    border-radius: var(--radius-sm);
  }
}

/* Override tokens.css body rule (unlayered → wins over @layer base) */
body {
  color: var(--text-primary);
  background-color: var(--bg-page);
}
```

Utilidades resultantes (ejemplos):
- `bg-bg-page`, `bg-bg-surface`, `bg-bg-sunken`
- `text-text-primary`, `text-text-muted`, `text-accent`
- `border-border-base`, `border-border-strong`
- `bg-accent`, `text-accent-fg`
- `bg-success-bg`, `text-success-fg`

---

## `app.blade.php` — cambios

```php
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="{{ ($appearance ?? 'system') === 'dark' ? 'dark' : 'light' }}">
```

Script inline de detección del sistema:
```js
(function() {
  const stored = localStorage.getItem('cacao-theme');
  if (stored) {
    document.documentElement.setAttribute('data-theme', stored);
  } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    document.documentElement.setAttribute('data-theme', 'dark');
  }
})();
```

---

## Estructura de archivos

```
resources/js/
  components/
    base/
      Isotipo.vue       ← grid 3×3, props: size (xs/sm/md/lg/xl), variant
      Icon.vue          ← SVG inline, props: name, size, stroke
      Button.vue        ← 6 variantes × 3 tamaños, icon, loading, disabled
      Badge.vue         ← 6 variantes, dot, outline
      Avatar.vue        ← 5 tamaños, initials, color presets 1-5
      Card.vue          ← slots: #header, default, #footer
      StatCard.vue      ← label, value, delta, footer, variant accent
    feedback/
      Toast.vue         ← stack bottom-right, auto-dismiss 4s
      useToast.ts       ← composable imperativo
      Modal.vue         ← trap focus, esc, overlay click, sizes sm/md/lg
      Alert.vue         ← banner semántico dismissible
    data/
      DataTable.vue     ← sort, selección, búsqueda, paginación, row-actions
      TodaySchedule.vue ← barra horaria 07-19 con marcador "ahora"
    AppSidebar.vue      ← reescrito con tokens CACAO
    AppTopbar.vue       ← nuevo, reemplaza AppSidebarHeader.vue
    AppContent.vue      ← sin cambios (estructura)
    AppShell.vue        ← sin cambios (estructura)
  layouts/
    AppLayout.vue       ← actualizado: AppSidebar + AppTopbar + slot
  pages/
    Dashboard.vue       ← nueva, basada en sections-dashboard.jsx
    security/Roles/Index.vue  ← migrar a nuevos componentes
    auth/Login.vue etc. ← migrar inputs y botones
```

---

## Componentes: especificaciones

### `Isotipo.vue`
- Grid 3×3 de spans
- Prop `size`: `xs`=20px, `sm`=28px, `md`=40px, `lg`=72px, `xl`=120px
- Prop `variant`: `'on-dark'` (cells en `--sidebar-fg`) | `'on-light'` (cells en `--text-primary`) | `'on-topbar'` (cells en `--topbar-fg`). Default: `'on-light'`
- Celda [0,2] siempre `--accent`, celda [1,2] siempre vacía (`invisible`)

### `Icon.vue`
- Set de 38 iconos extraídos de `primitives.jsx`: `search`, `plus`, `check`, `x`, `chevronDown/Right/Left/Up`, `arrowRight`, `filter`, `download`, `upload`, `file`, `folder`, `user`, `users`, `book`, `building`, `calendar`, `clock`, `edit`, `trash`, `eye`, `more`, `moreV`, `bell`, `info`, `alert`, `home`, `grid`, `chart`, `logout`, `sun`, `moon`, `code`, `mail`, `phone`, `map`, `star`
- Props: `name: string`, `size: number = 16`, `stroke: number = 1.75`
- SVG con `viewBox="0 0 24 24"`, `fill="none"`, `stroke="currentColor"`

### `Button.vue`
- Variantes: `primary | secondary | ghost | danger | link | tonal`
- Tamaños: `sm` (h-7 = 28px), `md` (h-8 = 32px, default), `lg` (h-10 = 40px)
- Props: `icon?`, `iconRight?`, `iconOnly?` (square), `loading?`, `disabled?`
- `primary`: bg `--accent`, text `--accent-fg`, hover `--accent-hover`
- `secondary`: bg `--bg-surface-2`, text `--text-primary`, border `--border`
- `ghost`: bg transparent, hover bg `--bg-surface-2`
- `danger`: bg `--danger-bg`, text `--danger-fg`, hover `--danger`+white
- `link`: no bg/border, text `--accent`, underline on hover
- `tonal`: bg `--accent-soft`, text `--accent`
- Loading: spinner SVG reemplaza icono, texto se mantiene
- Disabled: opacity 0.5, cursor not-allowed
- Focus-visible: outline 2px `--accent`, offset 2px

### `Badge.vue`
- Variantes: `neutral | success | warning | danger | info | accent`
- Props: `dot?` (bolita de color antes del texto), `outline?` (borde sin fill)
- Height 20px, font-size 11px, font-weight 600, radius `--radius-pill`
- Colores: usa pares `--{variant}-bg` / `--{variant}-fg` de los tokens

### `Avatar.vue`
- Tamaños: `xs`=20px, `sm`=28px, `md`=36px, `lg`=48px, `xl`=64px
- Props: `src?`, `initials?`, `size?`, `colorPreset?: 1|2|3|4|5`
- Presets de color (backgrounds derivados): 1=terracota, 2=azul, 3=verde, 4=morado, 5=ambar
- Radius 50%, font-weight 600, font-size proporcional

### `Card.vue`
- bg `--bg-surface`, border `--border`, radius `--radius-lg`, padding 20px, shadow `--shadow-xs`
- Slots: `#header` (con clase `card-head`), `default` (`card-body`), `#footer` (`card-foot`)
- Hover: `translateY(-1px)` + `--shadow-sm` (transición 150ms)

### `StatCard.vue`
- Props: `label`, `value`, `delta?`, `deltaDirection?: 'up'|'down'`, `footer?`, `accent?: boolean`
- label: 11px uppercase font-weight 600 `--text-muted`
- value: 44px font-weight 700 tabular-nums `--text-primary`
- delta: 12px, `up`=`--success`, `down`=`--danger`
- `accent`: barra superior 3px color `--accent`

---

## Layout shell

```
AppLayout.vue
├── AppSidebar.vue (240px fijo, bg --sidebar-bg)
│   ├── Brand: Isotipo sm + wordmark "CACAO"
│   ├── NavGroup × 3 (General, Académico, Análisis)
│   │   └── NavItem (icon + label + badge opcional)
│   └── Footer: Avatar + nombre + logout icon
└── main.app-main (flex-1, flex-col)
    ├── AppTopbar.vue (56px, bg --topbar-bg)
    │   ├── Breadcrumbs
    │   ├── SearchBar (⌘K)
    │   ├── NotifButton
    │   └── UserMenu
    └── div.app-content (padding 24px/32px, overflow-y-auto)
        └── <slot /> (router-view vía Inertia)
```

`AppSidebar.vue` recibe props: `navItems: NavItem[]`, no tiene estado de ruta propio — usa `usePage().url` de Inertia para determinar el ítem activo.

---

## Dashboard.vue (`pages/Dashboard.vue`)

Página nueva que implementa `sections-dashboard.jsx` con datos mock TypeScript.
Secciones:
1. Header con saludo + `SegmentedControl` (Semana/Mes/Período) + botones
2. `TodaySchedule` — barra horaria 07:00–19:00
3. `StatsGrid` — 4 `StatCard` en grid responsive
4. `DashGrid` — tabla inscripciones recientes + panel "Requieren atención" + top profesores
5. `DashGrid3` — ocupación aulas (progress bars) + mapa prelaciones + próximas evaluaciones

---

## TodaySchedule.vue

```ts
interface ScheduleSlot {
  start: number   // decimal, ej. 8.5 = 08:30
  end: number
  title: string
  room: string
  section: string
  status: 'done' | 'now' | 'next'
}
```

- Rango: 07:00–19:00 (12 horas)
- Posición: `left: ((start-7)/12)*100%`, `width: ((end-start)/12)*100%`
- Marcador "ahora": calculado con `new Date()` en tiempo real (ref reactiva que se actualiza cada minuto)
- Estados: `done`=bg-sunken text-muted line-through, `now`=bg-accent text-accent-fg, `next`=bg-surface-2 border-dashed

---

## DataTable.vue

Props:
```ts
interface Column<T> {
  key: keyof T
  label: string
  sortable?: boolean
  render?: (value: T[keyof T], row: T) => VNode
}
interface DataTableProps<T> {
  columns: Column<T>[]
  rows: T[]
  searchable?: boolean
  selectable?: boolean
  pagination?: boolean
  pageSize?: number
}
```

Características:
- Header: 11px uppercase font-weight 600 `--text-muted`, height 40px, border-bottom
- Row: height 52px, hover bg `--bg-surface-2`
- Row actions: botones ghost icon-only visibles en hover de la fila
- Selección: checkbox en header (toggle all) + por fila
- Sort: icono ▲/▼ en header clickable, `aria-sort`
- Search: input en toolbar con debounce 300ms
- Pagination: `< 1 2 3 >` con page size configurable

---

## Toast + useToast

```ts
// composable
const { toast } = useToast()
toast({ message: 'Rol creado', variant: 'success', duration: 4000 })
toast({ message: 'Error', variant: 'danger', action: { label: 'Deshacer', onClick: fn } })
```

- Stack bottom-right, offset 16px, gap 8px entre toasts
- Animación entrada: slide desde derecha + fade, 200ms
- Animación salida: fade + slide, 150ms
- `role="status"` para success/info/warning, `role="alert"` para danger
- `prefers-reduced-motion`: desactivar animaciones de movimiento

---

## Modal.vue

```ts
// Usage
<Modal v-model:open="showModal" title="Nuevo rol" size="md">
  <!-- content -->
  <template #footer>
    <Button variant="ghost" @click="showModal=false">Cancelar</Button>
    <Button variant="primary" @click="save">Guardar</Button>
  </template>
</Modal>
```

- Overlay: `rgba(19,17,16,0.5)` + backdrop-blur-sm
- Panel: bg-surface, radius `--radius-xl`, max-width: sm=400/md=520/lg=720
- Animación: fade overlay + scale 0.96→1 panel, 180ms
- Trap focus con `useFocusTrap` o implementación manual
- Esc to close, click overlay to close (configurable)

---

## Migración de páginas existentes

### `security/Roles/Index.vue`
Reemplazar:
- `Button` de shadcn → `Button.vue` base
- `Badge` de shadcn → `Badge.vue` base
- `table` nativo con clases shadcn → `DataTable.vue`
- `DeleteRoleModal`, `EditRoleModal`, `CreateRoleModal` → adaptar para usar `Modal.vue`

### Páginas de auth
- Inputs → inputs nativos `<input>` estilizados directamente con clases tokens (no se crea TextField.vue en esta fase — fuera de scope del Dashboard MVP)
- Botones → `Button.vue` base
- Wrappers de card → `Card.vue`

---

## Migración de AppearanceTabs / dark mode toggle

El componente `AppearanceTabs.vue` actual usa `document.documentElement.classList`. Cambiar a:
```ts
document.documentElement.setAttribute('data-theme', 'dark')   // activar
document.documentElement.setAttribute('data-theme', 'light')  // desactivar
localStorage.setItem('cacao-theme', theme)
```

---

## Orden de implementación (plan detallado)

1. CSS tokens en `app.css` + actualizar `app.blade.php`
2. `Icon.vue` + `Isotipo.vue` (sin deps externas)
3. `Button.vue` + `Badge.vue` + `Avatar.vue`
4. `Card.vue` + `StatCard.vue`
5. `AppSidebar.vue` + `AppTopbar.vue` + `AppLayout.vue`
6. `Dashboard.vue` (verifica visualmente todo lo anterior)
7. `Toast.vue` + `useToast.ts`
8. `Modal.vue`
9. `Alert.vue`
10. `DataTable.vue` + `TodaySchedule.vue`
11. Migrar `Roles/Index.vue`
12. Migrar `AppearanceTabs.vue` (dark mode toggle)
13. Migrar páginas de auth
14. Eliminar `components/ui/` y limpiar imports huérfanos
