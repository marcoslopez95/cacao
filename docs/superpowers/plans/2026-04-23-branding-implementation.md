# CACAO Branding Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aplicar la identidad visual CACAO completa al frontend: tokens de color, tipografía, shell de app, páginas de auth y landing page, en modos claro y oscuro.

**Architecture:** Reemplazar las variables CSS de shadcn/ui en `app.css` con los valores CACAO. Los 100+ componentes de shadcn heredan los colores automáticamente. Se reescriben `AppLogoIcon.vue`, `AppLogo.vue`, `AuthSimpleLayout.vue` y `Welcome.vue` para reflejar la identidad CACAO.

**Tech Stack:** Vue 3 · Inertia.js v3 · Tailwind CSS v4 · shadcn/ui · Space Grotesk

---

## Mapa de archivos

| Archivo | Acción | Responsabilidad |
|---|---|---|
| `resources/views/app.blade.php` | Modificar | Eliminar Instrument Sans de bunny.net, actualizar colores inline |
| `resources/css/app.css` | Modificar | Reemplazar bloques `:root` y `.dark` con tokens CACAO; ajustar radios en `@theme inline` |
| `resources/js/components/AppLogoIcon.vue` | Reescribir | Isotipo 3×3 en div (reemplaza SVG de Laravel), colores adaptativos por modo |
| `resources/js/components/AppLogo.vue` | Reescribir | Isotipo + wordmark CACAO para sidebar (siempre fondo oscuro) |
| `resources/js/layouts/auth/AuthSimpleLayout.vue` | Modificar | Usar nuevo AppLogoIcon + agregar wordmark CACAO centrado |
| `resources/js/pages/Welcome.vue` | Reescribir | Landing page CACAO: header + hero split + grid de módulos + footer |

---

## Task 1: Limpiar app.blade.php

**Files:**
- Modify: `resources/views/app.blade.php`

- [ ] **Step 1: Eliminar Instrument Sans y actualizar colores inline**

Reemplazar el contenido del archivo con este exacto:

```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color before CSS loads --}}
        <style>
            html { background-color: #EDEBE7; }
            html.dark { background-color: #131110; }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
```

- [ ] **Step 2: Verificar que no quedan referencias a bunny.net o Instrument Sans**

```bash
grep -n "bunny\|instrument\|inter" /var/www/cacao/resources/views/app.blade.php
```
Esperado: sin resultados.

- [ ] **Step 3: Commit**

```bash
cd /var/www/cacao && git add resources/views/app.blade.php
git commit -m "chore: remove Instrument Sans, apply CACAO inline bg colors"
```

---

## Task 2: Reemplazar tokens CSS en app.css

**Files:**
- Modify: `resources/css/app.css`

- [ ] **Step 1: Reemplazar el bloque @layer base de compatibilidad de bordes**

Cambiar:
```css
@layer base {
    *,
    ::after,
    ::before,
    ::backdrop,
    ::file-selector-button {
        border-color: var(--color-gray-200, currentColor);
    }
}
```

Por:
```css
@layer base {
    *,
    ::after,
    ::before,
    ::backdrop,
    ::file-selector-button {
        border-color: var(--border, currentColor);
    }
}
```

- [ ] **Step 2: Actualizar radios en el bloque @theme inline**

Cambiar estas tres líneas dentro del bloque `@theme inline`:
```css
    --radius-lg: var(--radius);
    --radius-md: calc(var(--radius) - 2px);
    --radius-sm: calc(var(--radius) - 4px);
```

Por:
```css
    --radius-sm: 6px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --radius-xl: 14px;
```

- [ ] **Step 3: Reemplazar el bloque :root completo**

El bloque `:root` actual (que empieza en `--background: hsl(0 0% 100%)` y termina con `--sidebar: hsl(0 0% 98%);`) debe quedar así:

```css
:root {
    --background: #EDEBE7;
    --foreground: #131110;
    --card: #FAFAF8;
    --card-foreground: #131110;
    --popover: #FAFAF8;
    --popover-foreground: #131110;
    --primary: #131110;
    --primary-foreground: #F4F2EF;
    --secondary: #F4F2EF;
    --secondary-foreground: #131110;
    --muted: #EDEBE7;
    --muted-foreground: #888780;
    --accent: #F5E8E0;
    --accent-foreground: #7A3010;
    --destructive: #791F1F;
    --destructive-foreground: #FAFAF8;
    --border: #E0DDD8;
    --input: #E0DDD8;
    --ring: #C8521A;
    --chart-1: #C8521A;
    --chart-2: #27500A;
    --chart-3: #185FA5;
    --chart-4: #633806;
    --chart-5: #888780;
    --radius: 8px;
    --sidebar-background: #131110;
    --sidebar-foreground: #C8C6C2;
    --sidebar-primary: #C8521A;
    --sidebar-primary-foreground: #ffffff;
    --sidebar-accent: #2A2826;
    --sidebar-accent-foreground: #F4F2EF;
    --sidebar-border: #2A2826;
    --sidebar-ring: #C8521A;
    --sidebar: #131110;
}
```

- [ ] **Step 4: Reemplazar el bloque .dark completo**

El bloque `.dark` actual debe quedar así:

```css
.dark {
    --background: #131110;
    --foreground: #F4F2EF;
    --card: #1E1C1A;
    --card-foreground: #F4F2EF;
    --popover: #1E1C1A;
    --popover-foreground: #F4F2EF;
    --primary: #F4F2EF;
    --primary-foreground: #131110;
    --secondary: #2A2826;
    --secondary-foreground: #F4F2EF;
    --muted: #2A2826;
    --muted-foreground: #888780;
    --accent: #3D1E0E;
    --accent-foreground: #F5E8E0;
    --destructive: #F7A0A0;
    --destructive-foreground: #131110;
    --border: #2A2826;
    --input: #2A2826;
    --ring: #E8895A;
    --chart-1: #E8895A;
    --chart-2: #7EC95A;
    --chart-3: #85B7EB;
    --chart-4: #FAC775;
    --chart-5: #888780;
    --sidebar-background: #0e0d0c;
    --sidebar-foreground: #888780;
    --sidebar-primary: #7A3010;
    --sidebar-primary-foreground: #F5E8E0;
    --sidebar-accent: #1E1C1A;
    --sidebar-accent-foreground: #C8C6C2;
    --sidebar-border: #1E1C1A;
    --sidebar-ring: #E8895A;
    --sidebar: #0e0d0c;
}
```

- [ ] **Step 5: Verificar que no quedan valores hsl() de shadcn en :root ni .dark**

```bash
grep -n "hsl(" /var/www/cacao/resources/css/app.css
```
Esperado: sin resultados.

- [ ] **Step 6: Ejecutar build para verificar que no hay errores CSS**

```bash
cd /var/www/cacao && pnpm run build 2>&1 | tail -20
```
Esperado: build exitoso sin errores.

- [ ] **Step 7: Commit**

```bash
cd /var/www/cacao && git add resources/css/app.css
git commit -m "feat: replace shadcn CSS vars with CACAO brand tokens (light + dark mode)"
```

---

## Task 3: Reescribir AppLogoIcon.vue

El SVG de Laravel se reemplaza con el isotipo CACAO 3×3. Usa `bg-foreground` para adaptarse al fondo automáticamente — en modo claro (`--foreground: #131110`) da módulos oscuros sobre papel; en modo oscuro (`--foreground: #F4F2EF`) da módulos claros sobre tinta.

**Files:**
- Modify: `resources/js/components/AppLogoIcon.vue`

- [ ] **Step 1: Reemplazar el contenido completo del archivo**

```vue
<script setup lang="ts">
defineOptions({ inheritAttrs: false });
</script>

<template>
    <div class="grid grid-cols-3 gap-[3px] w-6" v-bind="$attrs">
        <span class="rounded-[2px] aspect-square bg-foreground"></span>
        <span class="rounded-[2px] aspect-square bg-foreground"></span>
        <span class="rounded-[2px] aspect-square bg-terracota dark:bg-terra-hover"></span>
        <span class="rounded-[2px] aspect-square bg-foreground"></span>
        <span class="rounded-[2px] aspect-square bg-foreground"></span>
        <span class="invisible aspect-square"></span>
        <span class="rounded-[2px] aspect-square bg-foreground"></span>
        <span class="rounded-[2px] aspect-square bg-foreground"></span>
        <span class="rounded-[2px] aspect-square bg-foreground"></span>
    </div>
</template>
```

- [ ] **Step 2: Ejecutar pint**

```bash
cd /var/www/cacao && vendor/bin/pint --dirty --format agent
```

- [ ] **Step 3: Commit**

```bash
cd /var/www/cacao && git add resources/js/components/AppLogoIcon.vue
git commit -m "feat: replace Laravel SVG logo with CACAO 3x3 isotipo"
```

---

## Task 4: Reescribir AppLogo.vue

El componente de logo del sidebar. Sidebar siempre es oscuro (fondo `#131110`) así que los módulos del isotipo son blancos explícitos.

**Files:**
- Modify: `resources/js/components/AppLogo.vue`

- [ ] **Step 1: Reemplazar el contenido completo del archivo**

```vue
<template>
    <div class="flex items-center gap-2.5">
        <div class="grid grid-cols-3 gap-[2.5px] w-5 flex-shrink-0">
            <span class="rounded-[2px] aspect-square bg-white/90"></span>
            <span class="rounded-[2px] aspect-square bg-white/90"></span>
            <span class="rounded-[2px] aspect-square bg-terracota"></span>
            <span class="rounded-[2px] aspect-square bg-white/90"></span>
            <span class="rounded-[2px] aspect-square bg-white/90"></span>
            <span class="invisible aspect-square"></span>
            <span class="rounded-[2px] aspect-square bg-white/90"></span>
            <span class="rounded-[2px] aspect-square bg-white/90"></span>
            <span class="rounded-[2px] aspect-square bg-white/90"></span>
        </div>
        <span class="text-[13px] font-bold tracking-[4px] text-white uppercase leading-none">CACAO</span>
    </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
cd /var/www/cacao && git add resources/js/components/AppLogo.vue
git commit -m "feat: update sidebar logo to CACAO isotipo + wordmark"
```

---

## Task 5: Actualizar AuthSimpleLayout.vue

Usar el nuevo isotipo + wordmark CACAO centrado sobre el formulario de auth.

**Files:**
- Modify: `resources/js/layouts/auth/AuthSimpleLayout.vue`

- [ ] **Step 1: Reemplazar el contenido completo del archivo**

```vue
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="home()" class="flex flex-col items-center gap-2">
                        <div class="flex items-center gap-2.5">
                            <AppLogoIcon class="w-6" />
                            <span class="text-[15px] font-bold tracking-[5px] text-foreground uppercase leading-none">
                                CACAO
                            </span>
                        </div>
                    </Link>
                    <div class="space-y-1 text-center">
                        <h1 class="text-xl font-semibold text-foreground">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">{{ description }}</p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
cd /var/www/cacao && git add resources/js/layouts/auth/AuthSimpleLayout.vue
git commit -m "feat: update auth layout with CACAO isotipo + wordmark"
```

---

## Task 6: Reescribir Welcome.vue

Landing page completa con header tinta, hero split, grid de 6 módulos y footer. Dark mode incluido.

**Files:**
- Modify: `resources/js/pages/Welcome.vue`

- [ ] **Step 1: Reemplazar el contenido completo del archivo**

```vue
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="CACAO — Sistema de Gestión Académica" />

    <div class="flex min-h-screen flex-col bg-papel-dark dark:bg-tinta">

        <!-- Header -->
        <header class="h-[52px] bg-tinta flex items-center px-6 lg:px-10 justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="grid grid-cols-3 gap-[2.5px] w-5 flex-shrink-0">
                    <span class="rounded-[2px] aspect-square bg-white/90"></span>
                    <span class="rounded-[2px] aspect-square bg-white/90"></span>
                    <span class="rounded-[2px] aspect-square bg-terracota"></span>
                    <span class="rounded-[2px] aspect-square bg-white/90"></span>
                    <span class="rounded-[2px] aspect-square bg-white/90"></span>
                    <span class="invisible aspect-square"></span>
                    <span class="rounded-[2px] aspect-square bg-white/90"></span>
                    <span class="rounded-[2px] aspect-square bg-white/90"></span>
                    <span class="rounded-[2px] aspect-square bg-white/90"></span>
                </div>
                <span class="text-[13px] font-bold tracking-[4px] text-white uppercase leading-none">CACAO</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[12px] text-gris hidden lg:block">
                    Control Académico · Cursos · Operaciones
                </span>
                <template v-if="$page.props.auth.user">
                    <Link
                        href="/dashboard"
                        class="bg-terracota hover:bg-terra-hover text-white text-[12px] font-semibold px-4 py-[7px] rounded-[7px] transition-colors"
                    >
                        Dashboard
                    </Link>
                </template>
                <template v-else>
                    <Link
                        :href="login()"
                        class="text-[12px] text-gris-light hover:text-white transition-colors"
                    >
                        Ingresar
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="bg-terracota hover:bg-terra-hover text-white text-[12px] font-semibold px-4 py-[7px] rounded-[7px] transition-colors"
                    >
                        Registrarse
                    </Link>
                </template>
            </div>
        </header>

        <!-- Hero -->
        <main class="flex-1 flex flex-col lg:flex-row items-center px-6 lg:px-10 py-12 lg:py-16 gap-10 lg:gap-16 w-full max-w-[1280px] mx-auto">

            <!-- Left: copy -->
            <div class="flex-1 max-w-[420px]">
                <div class="inline-flex items-center gap-1.5 bg-terra-light dark:bg-[#3D1E0E] border border-terra-hover dark:border-[#5A2D12] rounded-full px-3 py-1 mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-terracota dark:bg-terra-hover flex-shrink-0"></span>
                    <span class="text-[11px] font-semibold text-terra-text dark:text-terra-hover">
                        Sistema de Gestión Académica
                    </span>
                </div>

                <h1 class="text-[32px] lg:text-[38px] font-bold text-tinta dark:text-papel leading-[1.15] tracking-[-0.5px] mb-4">
                    Gestión académica<br>
                    <span class="text-terracota dark:text-terra-hover">integrada</span> para<br>
                    tu institución
                </h1>

                <p class="text-[13px] text-gris leading-[1.7] mb-8">
                    Control total sobre carreras, inscripciones, evaluaciones y comunicación. Diseñado para instituciones educativas venezolanas.
                </p>

                <div class="flex items-center gap-3">
                    <Link
                        :href="login()"
                        class="bg-tinta dark:bg-papel hover:bg-tinta-soft dark:hover:bg-papel-dark text-white dark:text-tinta text-[13px] font-semibold px-5 py-2.5 rounded-[8px] transition-colors"
                    >
                        Acceder al sistema
                    </Link>
                    <a
                        href="#modulos"
                        class="border border-gris-borde dark:border-pizarra bg-hueso dark:bg-tinta-soft hover:bg-papel-dark dark:hover:bg-pizarra text-tinta dark:text-papel text-[13px] font-medium px-5 py-2.5 rounded-[8px] transition-colors"
                    >
                        Ver módulos ↓
                    </a>
                </div>
            </div>

            <!-- Right: modules grid -->
            <div class="flex-1 w-full" id="modulos">
                <div class="grid grid-cols-2 gap-3">

                    <div class="bg-hueso dark:bg-[#1E1C1A] border border-gris-borde dark:border-pizarra rounded-[12px] p-4">
                        <div class="w-7 h-7 bg-tinta dark:bg-pizarra rounded-[7px] mb-3 flex items-center justify-center">
                            <span class="w-3 h-3 border-2 border-terracota dark:border-terra-hover rounded-[3px]"></span>
                        </div>
                        <p class="text-[13px] font-semibold text-tinta dark:text-papel mb-1">Académico</p>
                        <p class="text-[11px] text-gris leading-[1.5]">Carreras, pensums, materias y prelaciones</p>
                    </div>

                    <div class="bg-hueso dark:bg-[#1E1C1A] border border-gris-borde dark:border-pizarra rounded-[12px] p-4">
                        <div class="w-7 h-7 bg-tinta dark:bg-pizarra rounded-[7px] mb-3"></div>
                        <p class="text-[13px] font-semibold text-tinta dark:text-papel mb-1">Personas</p>
                        <p class="text-[11px] text-gris leading-[1.5]">Estudiantes, profesores y representantes</p>
                    </div>

                    <div class="bg-terracota dark:bg-[#7A3010] rounded-[12px] p-4">
                        <div class="w-7 h-7 bg-white/20 rounded-[7px] mb-3"></div>
                        <p class="text-[13px] font-semibold text-white mb-1">Inscripciones</p>
                        <p class="text-[11px] text-white/75 leading-[1.5]">Validación de prelaciones y cupos</p>
                    </div>

                    <div class="bg-hueso dark:bg-[#1E1C1A] border border-gris-borde dark:border-pizarra rounded-[12px] p-4">
                        <div class="w-7 h-7 bg-tinta dark:bg-pizarra rounded-[7px] mb-3"></div>
                        <p class="text-[13px] font-semibold text-tinta dark:text-papel mb-1">Evaluaciones</p>
                        <p class="text-[11px] text-gris leading-[1.5]">Quiz, tests y entregas de archivos</p>
                    </div>

                    <div class="bg-hueso dark:bg-[#1E1C1A] border border-gris-borde dark:border-pizarra rounded-[12px] p-4">
                        <div class="w-7 h-7 bg-tinta dark:bg-pizarra rounded-[7px] mb-3"></div>
                        <p class="text-[13px] font-semibold text-tinta dark:text-papel mb-1">Infraestructura</p>
                        <p class="text-[11px] text-gris leading-[1.5]">Aulas, horarios y departamentos</p>
                    </div>

                    <div class="bg-hueso dark:bg-[#1E1C1A] border border-gris-borde dark:border-pizarra rounded-[12px] p-4">
                        <div class="w-7 h-7 bg-tinta dark:bg-pizarra rounded-[7px] mb-3"></div>
                        <p class="text-[13px] font-semibold text-tinta dark:text-papel mb-1">Recursos</p>
                        <p class="text-[11px] text-gris leading-[1.5]">Materiales de apoyo por sección</p>
                    </div>

                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="bg-tinta py-3.5 px-6 lg:px-10 flex items-center justify-between flex-shrink-0">
            <span class="text-[11px] text-gris">© 2026 CACAO · Sistema de gestión académica</span>
            <span class="text-[11px] text-gris">Venezuela</span>
        </footer>

    </div>
</template>
```

- [ ] **Step 2: Verificar que no quedan referencias a Inter, colores hardcodeados de Laravel, ni SVGs de Laravel**

```bash
grep -n "rsms\|inter\|#FDFDFC\|#1b1b18\|#f53003\|#FF4433\|laracasts\|laravel.com" /var/www/cacao/resources/js/pages/Welcome.vue
```
Esperado: sin resultados.

- [ ] **Step 3: Commit**

```bash
cd /var/www/cacao && git add resources/js/pages/Welcome.vue
git commit -m "feat: rewrite Welcome page with CACAO landing design (modules grid + dark mode)"
```

---

## Task 7: Build, lint y verificación final

**Files:** ninguno nuevo

- [ ] **Step 1: Ejecutar pint en todos los archivos PHP modificados**

```bash
cd /var/www/cacao && vendor/bin/pint --dirty --format agent
```

- [ ] **Step 2: Verificar que no quedan referencias a fuentes externas no CACAO**

```bash
grep -rn "instrument-sans\|bunny\.net\|rsms\.me\|inter\.css" /var/www/cacao/resources/
```
Esperado: sin resultados.

- [ ] **Step 3: Ejecutar build de producción**

```bash
cd /var/www/cacao && pnpm run build 2>&1 | tail -30
```
Esperado: build exitoso. Si hay errores de TypeScript por imports, verificar que `@/routes` exporta `login`, `register`, `home`.

- [ ] **Step 4: Levantar dev server y verificar visualmente**

```bash
cd /var/www/cacao && composer run dev &
```

Verificar en el navegador:
- `http://localhost:8000/` — landing page con header tinta, héroe, 6 módulos, footer
- `http://localhost:8000/login` — formulario centrado con isotipo CACAO + "CACAO" wordmark
- `http://localhost:8000/register` — igual que login
- `http://localhost:8000/dashboard` (si hay sesión) — sidebar oscuro con isotipo + "CACAO"
- Activar dark mode desde Settings > Appearance y verificar paleta CACAO oscura

- [ ] **Step 5: Commit final si hay ajustes de pint**

```bash
cd /var/www/cacao && git add -p && git commit -m "chore: apply pint formatting to branding changes"
```
