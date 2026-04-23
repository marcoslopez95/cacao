# CACAO — Aplicación de identidad visual al frontend

**Fecha:** 2026-04-19  
**Alcance:** Sistema visual completo (Opción C) — tokens, shell, auth, landing, dark mode

---

## Resumen

Reemplazar el sistema de colores genérico de shadcn/ui con la identidad visual CACAO completa. El proyecto ya tiene la estructura de componentes y layouts construida; este trabajo es puramente de aplicación visual: colores, tipografía, espaciado y composición según el brandbook.

---

## Decisiones de diseño

### 1. Estrategia de tokens (Opción 2 — CACAO reemplaza shadcn vars)

El bloque `:root` y `.dark` en `app.css` actualmente tiene variables de shadcn genéricas (`--background`, `--primary`, `--foreground`, etc.). Se reemplaza íntegro con valores CACAO. Los 100+ componentes shadcn reciben los colores correctos automáticamente sin capa de indirección.

**No** se crea un sistema paralelo ni se agregan nuevas variables intermedias. Un solo set de variables, limpio y mantenible.

### 2. Dark mode — ambos modos con paleta CACAO adaptada

La marca no define dark mode oficialmente, pero el sistema lo soporta. Decisiones específicas:

- **Terracota en dark**: el acento principal sube de `#C8521A` → `#E8895A` (más luminoso, mismo carácter, mejor contraste sobre oscuro)
- **Item activo sidebar en dark**: usa `#7A3010` (terracota-text) como fondo — el texto encima es `#F5E8E0`, mantiene contraste WCAG
- **Sidebar**: casi no cambia entre modos — ya era oscuro en light. En dark solo se vuelve un tono más profundo (`#0e0d0c`)
- **Semánticos invertidos**: los fondos claros (`#EAF3DE`, `#FAEEDA`, etc.) se invierten a sus equivalentes oscuros (`#0D2810`, `#2A1A05`, etc.)
- **Botón principal en dark**: invierte a papel (`#F4F2EF`) sobre tinta — máximo contraste

### 3. Tipografía

Space Grotesk ya está configurada en `app.css` como `--font-sans`. Se elimina cualquier referencia residual a Instrument Sans o Inter. La variable `--font-sans` en `@theme inline` ya apunta a Space Grotesk.

---

## Paleta de tokens (`:root` y `.dark`)

### Modo claro

| Variable shadcn | Valor CACAO | Uso |
|---|---|---|
| `--background` | `#EDEBE7` (papel-dark) | Fondo de página |
| `--foreground` | `#131110` (tinta) | Texto principal |
| `--card` | `#FAFAF8` (hueso) | Superficies card |
| `--card-foreground` | `#131110` | Texto en cards |
| `--popover` | `#FAFAF8` | Popovers |
| `--popover-foreground` | `#131110` | Texto popovers |
| `--primary` | `#131110` (tinta) | Botón primario bg |
| `--primary-foreground` | `#F4F2EF` | Texto botón primario |
| `--secondary` | `#F4F2EF` (papel) | Botón secundario bg |
| `--secondary-foreground` | `#131110` | Texto secundario |
| `--muted` | `#EDEBE7` (papel-dark) | Fondos atenuados |
| `--muted-foreground` | `#888780` (gris) | Texto atenuado |
| `--accent` | `#F5E8E0` (terra-light) | Acento hover bg |
| `--accent-foreground` | `#7A3010` (terra-text) | Texto sobre acento |
| `--destructive` | `#791F1F` | Color destructivo |
| `--destructive-foreground` | `#FAFAF8` | Texto destructivo |
| `--border` | `#E0DDD8` (gris-borde) | Bordes |
| `--input` | `#E0DDD8` | Bordes de inputs |
| `--ring` | `#C8521A` (terracota) | Focus ring |
| `--radius` | `8px` | Radio base |
| `--sidebar-background` | `#131110` (tinta) | Fondo sidebar |
| `--sidebar-foreground` | `#C8C6C2` (gris-light) | Texto sidebar |
| `--sidebar-primary` | `#C8521A` (terracota) | Item activo sidebar |
| `--sidebar-primary-foreground` | `#fff` | Texto item activo |
| `--sidebar-accent` | `#2A2826` (tinta-soft) | Hover items sidebar |
| `--sidebar-accent-foreground` | `#F4F2EF` | Texto hover sidebar |
| `--sidebar-border` | `#2A2826` | Bordes sidebar |
| `--sidebar-ring` | `#C8521A` | Focus sidebar |
| `--sidebar` | `#131110` | Alias de sidebar-background (usado por shadcn internamente) |

### Modo oscuro (`.dark`)

| Variable | Valor | Notas |
|---|---|---|
| `--background` | `#131110` | tinta como fondo |
| `--foreground` | `#F4F2EF` | papel como texto |
| `--card` | `#1E1C1A` | superficie card |
| `--card-foreground` | `#F4F2EF` | |
| `--popover` | `#1E1C1A` | |
| `--popover-foreground` | `#F4F2EF` | |
| `--primary` | `#F4F2EF` | papel como botón primario |
| `--primary-foreground` | `#131110` | tinta como texto |
| `--secondary` | `#2A2826` | tinta-soft |
| `--secondary-foreground` | `#F4F2EF` | |
| `--muted` | `#2A2826` | |
| `--muted-foreground` | `#888780` | |
| `--accent` | `#3D1E0E` | terra-light oscuro |
| `--accent-foreground` | `#F5E8E0` | |
| `--destructive` | `#F7A0A0` | error claro sobre oscuro |
| `--destructive-foreground` | `#131110` | |
| `--border` | `#2A2826` | |
| `--input` | `#2A2826` | |
| `--ring` | `#E8895A` | terra-hover como focus ring |
| `--sidebar-background` | `#0e0d0c` | tono más profundo |
| `--sidebar-foreground` | `#888780` | |
| `--sidebar-primary` | `#7A3010` | terra-text como activo dark |
| `--sidebar-primary-foreground` | `#F5E8E0` | |
| `--sidebar-accent` | `#1E1C1A` | |
| `--sidebar-accent-foreground` | `#C8C6C2` | |
| `--sidebar-border` | `#1E1C1A` | |
| `--sidebar-ring` | `#E8895A` | |
| `--sidebar` | `#0e0d0c` | Alias de sidebar-background |

---

## Archivos a modificar

### `resources/css/app.css`
- Reemplazar el bloque `:root { }` completo con los tokens CACAO light
- Reemplazar el bloque `.dark { }` completo con los tokens CACAO dark
- Eliminar el bloque `@layer utilities` residual con Instrument Sans (ya eliminado)
- Ajustar `--radius` a `8px` (de `0.5rem`) — consistente con brandbook

### `resources/js/components/AppLogo.vue`
- Reemplazar el texto "Laravel Starter Kit" por "CACAO"
- Wordmark: `font-weight: 700`, `letter-spacing: 4px`, mayúsculas
- Isotipo: ya tiene la estructura 3×3 correcta — verificar que el módulo terracota esté en posición `[0,2]`

### `resources/js/components/AppLogoIcon.vue`
- Mismo ajuste: verificar posición del módulo terracota `[0,2]`

### `resources/js/pages/Welcome.vue`
- Reescritura completa con el diseño de landing aprobado:
  - Header tinta (height 52px): isotipo + wordmark + subtítulo + botón "Ingresar" terracota
  - Hero split: copy a la izquierda (badge + heading con acento terracota + descripción + 2 CTAs), grid 2×3 de módulos a la derecha
  - Card de Inscripciones en terracota (módulo central del sistema)
  - Footer strip tinta
  - Dark mode: fondo tinta, cards tinta-soft, terracota → terra-hover

### `resources/views/app.blade.php`
- Reemplazar la importación de Space Grotesk desde bunny.net — ya está en `app.css` desde Google Fonts. Eliminar duplicado.
- Ajustar los colores inline del dark mode background a `#131110` (ya era `oklch(0.145 0 0)`, verificar equivalencia)

---

## Componentes que NO se tocan

Los 100+ componentes de `resources/js/components/ui/` heredan los colores correctamente a través de las variables CSS shadcn. No requieren cambios individuales.

Los layouts (`AppLayout`, `AuthLayout`, `AppSidebarLayout`, `AuthSimpleLayout`) ya usan las clases semánticas de Tailwind (`bg-background`, `text-foreground`, etc.) — recibirán los colores CACAO automáticamente.

Las páginas de settings (`Profile`, `Security`, `Appearance`) no requieren cambios — usan los componentes shadcn que se actualizan solos.

---

## Semánticos dark mode

| Estado | Light bg | Dark bg | Texto dark |
|---|---|---|---|
| Éxito | `#EAF3DE` | `#0D2810` | `#7EC95A` |
| Alerta | `#FAEEDA` | `#2A1A05` | `#FAC775` |
| Error | `#FCEBEB` | `#2A0808` | `#F7A0A0` |
| Info | `#E6F1FB` | `#0A1E35` | `#85B7EB` |

Estos se aplican en `app.css` bajo `.dark` (junto al resto de las overrides de dark mode) — no en `tokens.css`. El sistema usa la clase `.dark` para el toggle manual (detectado en `app.blade.php`), no `@media (prefers-color-scheme: dark)`. Poner las variables en `tokens.css` con media query no funcionaría para el toggle manual.

---

## Lo que queda fuera de este spec

- Rediseño de páginas de contenido específicas de cada módulo (dashboard de admin, vistas de estudiante, etc.) — esas se harán cuando se implemente cada módulo
- Íconos de navegación del sidebar — se definen cuando se construya la navegación real de CACAO
- Componentes de equipos (`teams/`) — son scaffold del starter kit, se eliminarán cuando se construya el módulo de roles CACAO

---

## Criterios de éxito

- [ ] El sistema corre en modo claro y se ve con paleta CACAO completa
- [ ] El toggle de dark mode en Settings > Appearance aplica la paleta CACAO oscura
- [ ] El sidebar siempre tiene fondo tinta con item activo terracota (claro) o terra-text (oscuro)
- [ ] La landing page muestra los 6 módulos del sistema
- [ ] Space Grotesk es la única fuente en toda la UI
- [ ] No queda ninguna referencia a Instrument Sans, Inter, ni a colores genéricos shadcn hardcodeados
