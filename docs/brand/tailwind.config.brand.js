/**
 * CACAO — Tailwind CSS Brand Config
 * Sistema de gestión académica
 *
 * Uso en tailwind.config.js de Laravel:
 *
 *   const brand = require('./docs/brand/tailwind.config.brand.js')
 *
 *   module.exports = {
 *     content: [...],
 *     theme: {
 *       extend: {
 *         ...brand,
 *       }
 *     },
 *     plugins: [],
 *   }
 */

module.exports = {

  /* ─────────────────────────────────────────
     FUENTE
  ───────────────────────────────────────── */

  fontFamily: {
    sans: ['"Space Grotesk"', 'sans-serif'],
    mono: ['"Courier New"', 'Courier', 'monospace'],
  },


  /* ─────────────────────────────────────────
     COLORES
  ───────────────────────────────────────── */

  colors: {
    /* Colores de marca */
    tinta: {
      DEFAULT: '#131110',
      soft:    '#2A2826',
    },
    terracota: {
      DEFAULT: '#C8521A',
      hover:   '#E8895A',
      light:   '#F5E8E0',
      text:    '#7A3010',
    },
    pizarra: {
      DEFAULT: '#3D3A36',
      dark:    '#1A1815',
    },
    papel: {
      DEFAULT: '#F4F2EF',
      dark:    '#EDEBE7',
    },
    hueso:   '#FAFAF8',

    /* Escala de grises */
    gris: {
      DEFAULT: '#888780',
      light:   '#C8C6C2',
      borde:   '#E0DDD8',
    },

    /* Semánticos */
    success: {
      bg:     '#EAF3DE',
      border: '#C0DD97',
      text:   '#27500A',
    },
    warning: {
      bg:     '#FAEEDA',
      border: '#FAC775',
      text:   '#633806',
    },
    danger: {
      bg:     '#FCEBEB',
      border: '#F7C1C1',
      text:   '#791F1F',
    },
    info: {
      bg:     '#E6F1FB',
      border: '#85B7EB',
      text:   '#185FA5',
    },
  },


  /* ─────────────────────────────────────────
     TIPOGRAFÍA
  ───────────────────────────────────────── */

  fontSize: {
    'wordmark': ['36px', { lineHeight: '1', letterSpacing: '7px', fontWeight: '700' }],
    'h1':       ['24px', { lineHeight: '1.3', letterSpacing: '-0.3px', fontWeight: '700' }],
    'h2':       ['18px', { lineHeight: '1.4', letterSpacing: '-0.2px', fontWeight: '600' }],
    'h3':       ['15px', { lineHeight: '1.5', fontWeight: '500' }],
    'body':     ['13px', { lineHeight: '1.7', fontWeight: '400' }],
    'label':    ['11px', { lineHeight: '1.5', letterSpacing: '0.8px', fontWeight: '500' }],
    'caption':  ['11px', { lineHeight: '1.5', fontWeight: '400' }],
    'code':     ['12px', { lineHeight: '1.6', fontWeight: '400' }],
  },

  letterSpacing: {
    wordmark: '7px',
    loose:    '2.5px',
    label:    '0.8px',
    tight:    '-0.3px',
    tighter:  '-0.5px',
  },


  /* ─────────────────────────────────────────
     ESPACIADO PERSONALIZADO
  ───────────────────────────────────────── */

  spacing: {
    px:  '1px',
    0:   '0',
    0.5: '2px',
    1:   '4px',
    2:   '8px',
    3:   '12px',
    4:   '16px',
    5:   '20px',
    6:   '24px',
    7:   '28px',
    8:   '32px',
    10:  '40px',
    12:  '48px',
    15:  '60px',
    20:  '80px',
  },


  /* ─────────────────────────────────────────
     BORDER RADIUS
  ───────────────────────────────────────── */

  borderRadius: {
    none:   '0',
    sm:     '6px',    /* chips, badges */
    DEFAULT:'8px',    /* inputs, botones */
    md:     '8px',
    lg:     '12px',   /* cards */
    xl:     '14px',   /* modales, panels */
    '2xl':  '20px',
    full:   '9999px', /* pills */
  },


  /* ─────────────────────────────────────────
     SOMBRAS
  ───────────────────────────────────────── */

  boxShadow: {
    none:   'none',
    card:   '0 2px 16px rgba(19, 17, 16, 0.06)',
    modal:  '0 8px 32px rgba(19, 17, 16, 0.12)',
    focus:  '0 0 0 3px rgba(200, 82, 26, 0.25)',
  },


  /* ─────────────────────────────────────────
     DIMENSIONES DE LAYOUT
  ───────────────────────────────────────── */

  width: {
    sidebar:  '200px',
    'sidebar-collapsed': '60px',
  },

  height: {
    topbar: '52px',
  },

  maxWidth: {
    content: '1280px',
  },


  /* ─────────────────────────────────────────
     TRANSICIONES
  ───────────────────────────────────────── */

  transitionDuration: {
    fast:   '100ms',
    normal: '150ms',
    slow:   '250ms',
  },


  /* ─────────────────────────────────────────
     Z-INDEX
  ───────────────────────────────────────── */

  zIndex: {
    base:     '0',
    raised:   '10',
    dropdown: '100',
    sticky:   '200',
    modal:    '300',
    toast:    '400',
    tooltip:  '500',
  },

}


/*
 * ─────────────────────────────────────────────────────────────────
 * CLASES DE USO FRECUENTE (referencia rápida para Blade / JSX)
 * ─────────────────────────────────────────────────────────────────
 *
 * BOTONES
 *   bg-tinta text-papel rounded hover:bg-tinta-soft          → btn-primary
 *   bg-terracota text-terracota-light rounded hover:bg-terracota-hover → btn-terra
 *   border border-gris-borde rounded hover:bg-papel-dark      → btn-outline
 *
 * BADGES
 *   bg-success-bg text-success-text rounded-full px-3 py-1 text-caption font-medium
 *   bg-warning-bg text-warning-text rounded-full ...
 *   bg-danger-bg  text-danger-text  rounded-full ...
 *   bg-info-bg    text-info-text    rounded-full ...
 *
 * CARDS
 *   bg-hueso border border-gris-borde rounded-lg p-4
 *
 * STAT CARDS
 *   bg-papel-dark rounded-md p-4
 *
 * INPUTS
 *   font-sans text-body bg-hueso border border-gris-borde rounded
 *   focus:border-terracota focus:ring-0 outline-none w-full px-3 py-2
 *
 * TOPBAR
 *   bg-tinta h-topbar flex items-center gap-4 px-6
 *
 * SIDEBAR
 *   bg-pizarra-dark w-sidebar min-h-screen flex flex-col p-3 gap-1
 *
 * SIDEBAR ITEM ACTIVO
 *   bg-terracota text-terracota-light rounded-md px-3 py-2 text-body font-medium
 *
 * SIDEBAR ITEM HOVER
 *   hover:bg-pizarra text-gris hover:text-papel rounded-md px-3 py-2 text-body
 *
 * ISOTIPO (3x3)
 *   grid grid-cols-3 gap-[3px] w-5
 *   → span: bg-tinta rounded-[2px] aspect-square (x7)
 *   → span: bg-terracota rounded-[2px] aspect-square (posición [0,2])
 *   → span: invisible aspect-square (posición [1,2])
 * ─────────────────────────────────────────────────────────────────
 */
