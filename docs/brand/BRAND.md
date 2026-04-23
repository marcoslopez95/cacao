# CACAO — Guía de marca

## Nombre

**CACAO** — Control Académico, Cursos y Administración de Operaciones

Acrónimo de origen venezolano. El cacao venezolano es reconocido mundialmente como símbolo de calidad y origen. La marca refleja esto: un sistema de calidad, con identidad propia.

---

## Personalidad de marca

| Atributo | Descripción |
|----------|-------------|
| Moderno | Interfaces limpias, tecnología actual, sin nostalgia |
| Ágil | Flujos cortos, feedback inmediato, sin fricciones |
| Confiable | Consistencia visual, jerarquía clara, sin sorpresas |
| Venezolano | Nombre, color terracota cálido, contexto local |

---

## Tipografía

**Fuente única: Space Grotesk**
- Google Fonts: `https://fonts.google.com/specimen/Space+Grotesk`
- Import: `@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap');`
- `font-family: 'Space Grotesk', sans-serif;`

### Escala tipográfica

| Nombre | Peso | Tamaño | Letter-spacing | Uso |
|--------|------|--------|---------------|-----|
| Wordmark | 700 | 36px+ | 7px | Solo logo |
| Heading 1 | 700 | 24px | -0.3px | Títulos de página |
| Heading 2 | 600 | 18px | -0.2px | Títulos de sección |
| Heading 3 | 500 | 15px | 0 | Subtítulos, panel titles |
| Body | 400 | 13px | 0 | Texto general, line-height 1.7 |
| Label | 500 | 11px | 0.8px | Etiquetas, uppercase |
| Caption | 400 | 11px | 0 | Texto secundario, ayuda |
| Código | 400 | 12px | 0 | Courier New, datos técnicos |

---

## Paleta de colores

### Colores de marca

| Token | Hex | Uso |
|-------|-----|-----|
| `tinta` | `#131110` | Color primario. Fondo sidebar, topbar, botones principales |
| `terracota` | `#C8521A` | Acento. CTAs, isotipo, enlaces activos, datos críticos |
| `pizarra` | `#3D3A36` | Secundario. Sidebar activo fondo, paneles oscuros |
| `papel` | `#F4F2EF` | Fondo principal de interfaces claras |
| `papel-dark` | `#EDEBE7` | Fondo de página, áreas de contenido |
| `hueso` | `#FAFAF8` | Cards, superficies elevadas |
| `ambar` | `#E8895A` | Hover de terracota, estados activos |
| `gris` | `#888780` | Texto secundario, subtítulos |
| `gris-light` | `#C8C6C2` | Bordes enfatizados, placeholders |
| `borde` | `#E0DDD8` | Bordes de cards y dividers |

### Colores semánticos

| Estado | Fondo | Texto | Uso |
|--------|-------|-------|-----|
| Éxito | `#EAF3DE` | `#27500A` | Aprobado, inscripción aprobada, acción completada |
| Alerta | `#FAEEDA` | `#633806` | Pendiente, materia en riesgo, acción requerida |
| Error | `#FCEBEB` | `#791F1F` | Reprobado, rechazado, prelación no cumplida |
| Info | `#E6F1FB` | `#185FA5` | Información neutral, conteos, datos |

---

## Isotipo

### Construcción
- Cuadrícula 3×3 de módulos cuadrados
- 7 módulos en `tinta` (#131110)
- 1 módulo en `terracota` (#C8521A) — **posición fija: fila 0, columna 2** (esquina superior derecha)
- 1 espacio vacío (invisible) — **posición fija: fila 1, columna 2**
- Radio de esquina: 20% del lado del módulo
- Gap entre módulos: 25% del lado del módulo

### Mapa de posiciones
```
[tinta]    [tinta]    [TERRACOTA]
[tinta]    [tinta]    [VACÍO]
[tinta]    [tinta]    [tinta]
```

### Variantes permitidas

| Fondo | Módulos base | Módulo acento |
|-------|-------------|---------------|
| Papel (#F4F2EF) | tinta (#131110) | terracota (#C8521A) |
| Tinta (#131110) | papel (#F4F2EF) | terracota (#C8521A) |
| Terracota (#C8521A) | papel (#F4F2EF) | tinta (#131110) |

### Tamaños mínimos
- Con wordmark: 120px de ancho mínimo
- Solo isotipo: 16px de ancho mínimo (favicon)
- Espacio de protección: equivalente al lado de un módulo en todos los lados

---

## Wordmark

- Siempre en **mayúsculas**: `CACAO`
- Fuente: Space Grotesk 700
- Letter-spacing: mínimo 4px, recomendado 6-7px en display
- Subtítulo opcional: `Plataforma académica` en Space Grotesk 400, letter-spacing 2.5px, uppercase, color gris

---

## Componentes UI

### Bordes
- Default: `0.5px solid #E0DDD8`
- Hover: `0.5px solid #C8C6C2`
- Focus/active: `1px solid #C8521A`
- Error: `1px solid #E24B4A`

### Border radius
- Módulos/chips pequeños: `6px`
- Inputs, botones: `8px`
- Cards: `12px`
- Modales, panels grandes: `14px`

### Espaciado base
- `4px` — micro (gaps internos)
- `8px` — xs
- `12px` — sm
- `16px` — md (padding interno de cards)
- `20px` — lg
- `24px` — xl
- `28px` — 2xl (padding de secciones)
- `32px` — 3xl
- `40px` — 4xl (padding de páginas)
- `60px` — 5xl (padding de portada/cover)

### Sombras
- Cards: ninguna en estado normal
- Cards hover: `0 2px 16px rgba(19,17,16,0.06)`
- Modales: `0 8px 32px rgba(19,17,16,0.12)`

---

## Reglas de uso

### Hacer
- Usar Space Grotesk como única tipografía
- Mantener terracota siempre en posición [0,2] del isotipo
- Usar terracota solo como acento (máximo 10% del área visual)
- Aplicar colores semánticos solo para su significado asignado
- Usar el wordmark siempre en mayúsculas con tracking

### No hacer
- Rotar, inclinar o deformar el isotipo
- Mover el módulo terracota a otra posición
- Usar terracota como fondo dominante en interfaces largas
- Reemplazar Space Grotesk por otra fuente
- Colocar el logo sobre fondos con poco contraste
- Usar el wordmark en minúsculas
