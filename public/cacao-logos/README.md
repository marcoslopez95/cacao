# CACAO — Kit de logos

## Estructura de archivos

```
cacao-logos/
├── svg/
│   ├── isotipo.svg               ← Isotipo solo, fondo transparente (fondo claro)
│   ├── isotipo-dark.svg          ← Isotipo solo, módulos en papel (para fondos oscuros)
│   ├── isotipo-terra.svg         ← Isotipo solo, módulos en papel (para fondo terracota)
│   ├── favicon.svg               ← Favicon cuadrado con fondo tinta
│   ├── logo-horizontal.svg       ← Logo completo: isotipo + wordmark (fondo claro)
│   ├── logo-horizontal-dark.svg  ← Logo completo: variante oscura
│   ├── wordmark.svg              ← Solo el texto CACAO, sin isotipo
│   └── og-image.svg              ← Open Graph image 1200×630
├── png/
│   ├── isotipo-{16..512}.png     ← Isotipo transparente en 10 tamaños
│   ├── isotipo-dark-{64,128,256}.png
│   ├── isotipo-terra-{64,128,256}.png
│   ├── favicon-{16,32,48,64,128,180}.png ← Favicon con fondo tinta
│   ├── logo-horizontal-{320,640,960,1280}.png
│   ├── logo-horizontal-dark-{320,640}.png
│   ├── apple-touch-icon.png      ← 180×180 para iOS
│   ├── pwa-icon-192.png          ← PWA icon
│   ├── pwa-icon-512.png          ← PWA icon grande
│   └── og-image.png              ← Open Graph 1200×630
└── ico/
    ├── favicon.ico               ← Multi-tamaño: 16, 32, 48px
    └── favicon-32.ico            ← Solo 32px (más compatible)
```

---

## Dónde copiar en Laravel

```
public/
├── favicon.ico                   ← ico/favicon.ico
└── img/
    └── brand/
        ├── isotipo.svg
        ├── isotipo-dark.svg
        ├── isotipo-terra.svg
        ├── favicon.svg
        ├── logo-horizontal.svg
        ├── logo-horizontal-dark.svg
        ├── wordmark.svg
        ├── og-image.svg
        ├── apple-touch-icon.png
        ├── pwa-icon-192.png
        ├── pwa-icon-512.png
        └── [demás PNG según necesidad]
site.webmanifest                  ← en public/
```

---

## HTML — meta tags para Blade layout

Pega esto en `resources/views/app.blade.php` dentro del `<head>`:

```html
<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/svg+xml" href="/img/brand/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/img/brand/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/img/brand/favicon-16.png">

<!-- Apple Touch Icon -->
<link rel="apple-touch-icon" sizes="180x180" href="/img/brand/apple-touch-icon.png">

<!-- PWA Manifest -->
<link rel="manifest" href="/site.webmanifest">
<meta name="theme-color" content="#C8521A">

<!-- Open Graph -->
<meta property="og:image" content="{{ asset('img/brand/og-image.png') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:site_name" content="CACAO">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="{{ asset('img/brand/og-image.png') }}">
```

---

## Vue — componente isotipo

```vue
<!-- resources/js/components/Isotipo.vue -->
<template>
  <div class="grid grid-cols-3 w-5" style="gap: 3px;">
    <span class="rounded-[2px] aspect-square" :class="baseColor"></span>
    <span class="rounded-[2px] aspect-square" :class="baseColor"></span>
    <span class="rounded-[2px] aspect-square bg-terracota"></span>
    <span class="rounded-[2px] aspect-square" :class="baseColor"></span>
    <span class="rounded-[2px] aspect-square" :class="baseColor"></span>
    <span class="invisible aspect-square"></span>
    <span class="rounded-[2px] aspect-square" :class="baseColor"></span>
    <span class="rounded-[2px] aspect-square" :class="baseColor"></span>
    <span class="rounded-[2px] aspect-square" :class="baseColor"></span>
  </div>
</template>

<script setup>
defineProps({
  variant: {
    type: String,
    default: 'dark',    // 'dark' | 'light'
  }
})

const baseColor = computed(() =>
  props.variant === 'light' ? 'bg-papel' : 'bg-tinta'
)
</script>
```

---

## Guía de uso rápido

| Contexto | Archivo |
|----------|---------|
| Navbar/topbar oscuro | `isotipo-dark.svg` + texto blanco |
| Navbar/topbar claro | `isotipo.svg` + texto tinta |
| Fondo terracota | `isotipo-terra.svg` + texto papel |
| Favicon browser | `favicon.ico` |
| iOS home screen | `apple-touch-icon.png` |
| PWA / Android | `pwa-icon-192.png` + `pwa-icon-512.png` |
| Compartir en redes | `og-image.png` (1200×630) |
| Documentos / impresión | `logo-horizontal-1280.png` |
| Código Vue/Blade | `isotipo.svg` inline o componente `<Isotipo>` |

---

## Reglas inamovibles

- El módulo **terracota siempre en posición [0,2]** — nunca moverlo
- El espacio vacío es **siempre posición [1,2]**
- En fondos oscuros usar **isotipo-dark** (módulos en papel)
- En fondo terracota usar **isotipo-terra** (módulo acento en tinta)
- Tamaño mínimo del isotipo solo: **16px**
- Tamaño mínimo del logo horizontal: **120px de ancho**
