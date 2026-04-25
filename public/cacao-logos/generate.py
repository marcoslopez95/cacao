#!/usr/bin/env python3
"""
Genera PNG en múltiples tamaños e ICO para CACAO
a partir de los SVG fuente.
"""
import cairosvg
from PIL import Image
import io
import os

SVG_DIR  = '/home/claude/cacao-logos/svg'
PNG_DIR  = '/home/claude/cacao-logos/png'
ICO_DIR  = '/home/claude/cacao-logos/ico'

os.makedirs(PNG_DIR, exist_ok=True)
os.makedirs(ICO_DIR, exist_ok=True)

def svg_to_png(svg_path, output_path, width, height=None):
    if height is None:
        height = width
    cairosvg.svg2png(
        url=svg_path,
        write_to=output_path,
        output_width=width,
        output_height=height,
    )
    print(f'  ✓ {os.path.basename(output_path)} ({width}px)')

def svg_to_png_bytes(svg_path, width, height=None):
    if height is None:
        height = width
    return cairosvg.svg2png(
        url=svg_path,
        output_width=width,
        output_height=height,
    )


# ── 1. ISOTIPO (cuadrado) ──────────────────────────────────────
print('\n[Isotipo — fondo transparente]')
iso_svg = f'{SVG_DIR}/isotipo.svg'
for size in [16, 24, 32, 48, 64, 96, 128, 192, 256, 512]:
    svg_to_png(iso_svg, f'{PNG_DIR}/isotipo-{size}.png', size)

print('\n[Isotipo — variante oscura]')
iso_dark_svg = f'{SVG_DIR}/isotipo-dark.svg'
for size in [64, 128, 256]:
    svg_to_png(iso_dark_svg, f'{PNG_DIR}/isotipo-dark-{size}.png', size)

print('\n[Isotipo — variante terracota]')
iso_terra_svg = f'{SVG_DIR}/isotipo-terra.svg'
for size in [64, 128, 256]:
    svg_to_png(iso_terra_svg, f'{PNG_DIR}/isotipo-terra-{size}.png', size)


# ── 2. FAVICON (cuadrado con fondo tinta) ─────────────────────
print('\n[Favicon — con fondo tinta]')
fav_svg = f'{SVG_DIR}/favicon.svg'
for size in [16, 32, 48, 64, 128, 180, 192, 512]:
    out = f'{PNG_DIR}/favicon-{size}.png'
    svg_to_png(fav_svg, out, size)


# ── 3. LOGO HORIZONTAL ────────────────────────────────────────
print('\n[Logo horizontal — fondo transparente]')
logo_svg = f'{SVG_DIR}/logo-horizontal.svg'
for w, h in [(320,80),(640,160),(960,240),(1280,320)]:
    svg_to_png(logo_svg, f'{PNG_DIR}/logo-horizontal-{w}.png', w, h)

print('\n[Logo horizontal — variante oscura]')
logo_dark_svg = f'{SVG_DIR}/logo-horizontal-dark.svg'
for w, h in [(320,80),(640,160)]:
    svg_to_png(logo_dark_svg, f'{PNG_DIR}/logo-horizontal-dark-{w}.png', w, h)


# ── 4. APPLE TOUCH ICON (180x180 con fondo tinta) ─────────────
print('\n[Apple Touch Icon]')
# Generar versión 180px del favicon con padding para Apple
fav_bytes = svg_to_png_bytes(fav_svg, 150, 150)
apple_img = Image.new('RGB', (180, 180), color=(19, 17, 16))  # #131110
overlay   = Image.open(io.BytesIO(fav_bytes)).convert('RGBA')
apple_img.paste(overlay, (15, 15), overlay)
apple_img.save(f'{PNG_DIR}/apple-touch-icon.png', 'PNG')
print(f'  ✓ apple-touch-icon.png (180px)')


# ── 5. OG IMAGE ───────────────────────────────────────────────
print('\n[OG Image — 1200x630]')
svg_to_png(f'{SVG_DIR}/og-image.svg', f'{PNG_DIR}/og-image.png', 1200, 630)


# ── 6. ICO — favicon.ico multi-tamaño ─────────────────────────
print('\n[ICO — favicon.ico]')
ico_sizes = [16, 32, 48]
frames = []

for size in ico_sizes:
    png_bytes = svg_to_png_bytes(fav_svg, size, size)
    img = Image.open(io.BytesIO(png_bytes)).convert('RGBA')
    frames.append(img)

# Guardar como ICO multi-resolución
ico_path = f'{ICO_DIR}/favicon.ico'
frames[0].save(
    ico_path,
    format='ICO',
    sizes=[(s, s) for s in ico_sizes],
    append_images=frames[1:],
)
print(f'  ✓ favicon.ico (16, 32, 48px)')

# ICO solo 32px (más compatible)
png_32 = svg_to_png_bytes(fav_svg, 32, 32)
img_32 = Image.open(io.BytesIO(png_32)).convert('RGBA')
img_32.save(f'{ICO_DIR}/favicon-32.ico', format='ICO', sizes=[(32,32)])
print(f'  ✓ favicon-32.ico (32px)')


# ── 7. PWA ICONS (manifest.json compatible) ───────────────────
print('\n[PWA Icons — 192 y 512]')
for size in [192, 512]:
    src = f'{PNG_DIR}/favicon-{size}.png'
    dst = f'{PNG_DIR}/pwa-icon-{size}.png'
    os.rename(src, dst)
    print(f'  ✓ pwa-icon-{size}.png')


# ── RESUMEN ───────────────────────────────────────────────────
print('\n' + '='*50)
print('Kit de logos CACAO generado:')
print()

all_svgs = sorted(os.listdir(f'{SVG_DIR}'))
all_pngs = sorted(os.listdir(f'{PNG_DIR}'))
all_icos = sorted(os.listdir(f'{ICO_DIR}'))

print(f'SVG ({len(all_svgs)} archivos):')
for f in all_svgs:
    size = os.path.getsize(f'{SVG_DIR}/{f}')
    print(f'  {f} ({size:,} bytes)')

print(f'\nPNG ({len(all_pngs)} archivos):')
for f in all_pngs:
    size = os.path.getsize(f'{PNG_DIR}/{f}')
    print(f'  {f} ({size:,} bytes)')

print(f'\nICO ({len(all_icos)} archivos):')
for f in all_icos:
    size = os.path.getsize(f'{ICO_DIR}/{f}')
    print(f'  {f} ({size:,} bytes)')

print('='*50)
