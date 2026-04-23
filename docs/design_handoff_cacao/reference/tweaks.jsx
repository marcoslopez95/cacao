/* global React, useTweaks, TweaksPanel, TweakSection, TweakRadio, TweakToggle */

const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
  "acento": "terracota",
  "temperamento": "oficina",
  "tejido": false
}/*EDITMODE-END*/;

// Preset tables — values are applied by overriding CSS vars on :root via a
// <style data-tweaks> tag. This keeps every component reactive (buttons,
// cards, chart bars, focus rings, isotipo cell, the works).

const ACENTOS = {
  terracota: {
    label: 'Terracota',
    light: {
      accent: '#C8521A', hover: '#A9441A', soft: '#F7E4D7', fg: '#FFFFFF',
    },
    dark: {
      accent: '#E8895A', hover: '#F0A078', soft: '#3A251C', fg: '#131110',
    },
  },
  ambar: {
    label: 'Ámbar',
    light: {
      accent: '#B8720D', hover: '#955B08', soft: '#FAEBCD', fg: '#FFFFFF',
    },
    dark: {
      accent: '#E8B05A', hover: '#F0C078', soft: '#3A2D1C', fg: '#131110',
    },
  },
  cacao: {
    label: 'Cacao',
    light: {
      accent: '#5A2F1A', hover: '#421F10', soft: '#EADCCE', fg: '#F4F2EF',
    },
    dark: {
      accent: '#C89A7A', hover: '#DBB196', soft: '#2E2018', fg: '#131110',
    },
  },
};

const TEMPERAMENTOS = {
  editorial: {
    label: 'Editorial',
    font: "'Space Grotesk', ui-sans-serif, system-ui, sans-serif",
    weightBase: 300, weightEmph: 500, weightBold: 600,
    letter: '-0.015em',
    fs: { xs: '11px', sm: '13px', base: '14px', md: '15px', lg: '18px', xl: '22px', '2xl': '28px', '3xl': '38px', '4xl': '52px', '5xl': '72px' },
    r: { xs: '2px', sm: '3px', md: '4px', lg: '6px', xl: '8px' },
    sp: { '4': '18px', '6': '28px', '8': '40px', '10': '52px', '12': '64px' },
    bodyLh: '1.6',
  },
  oficina: {
    label: 'Oficina',
    font: "'Space Grotesk', ui-sans-serif, system-ui, sans-serif",
    weightBase: 400, weightEmph: 500, weightBold: 600,
    letter: '-0.005em',
    fs: { xs: '11px', sm: '13px', base: '14px', md: '15px', lg: '17px', xl: '20px', '2xl': '24px', '3xl': '32px', '4xl': '44px', '5xl': '60px' },
    r: { xs: '3px', sm: '4px', md: '6px', lg: '10px', xl: '14px' },
    sp: { '4': '16px', '6': '24px', '8': '32px', '10': '40px', '12': '48px' },
    bodyLh: '1.5',
  },
  plaza: {
    label: 'Plaza',
    font: "'Space Grotesk', ui-sans-serif, system-ui, sans-serif",
    weightBase: 500, weightEmph: 600, weightBold: 700,
    letter: '0',
    fs: { xs: '12px', sm: '14px', base: '15px', md: '16px', lg: '18px', xl: '21px', '2xl': '25px', '3xl': '34px', '4xl': '46px', '5xl': '62px' },
    r: { xs: '6px', sm: '8px', md: '12px', lg: '18px', xl: '22px' },
    sp: { '4': '14px', '6': '20px', '8': '28px', '10': '36px', '12': '44px' },
    bodyLh: '1.45',
  },
};

function applyTweaks(t, theme) {
  const ac = ACENTOS[t.acento] || ACENTOS.terracota;
  const tm = TEMPERAMENTOS[t.temperamento] || TEMPERAMENTOS.oficina;
  const pal = theme === 'dark' ? ac.dark : ac.light;

  const rules = [];

  // Accent — light + dark scopes so both themes react live.
  rules.push(`:root{
    --accent:${ac.light.accent};
    --accent-hover:${ac.light.hover};
    --accent-soft:${ac.light.soft};
    --accent-fg:${ac.light.fg};
  }`);
  rules.push(`[data-theme="dark"]{
    --accent:${ac.dark.accent};
    --accent-hover:${ac.dark.hover};
    --accent-soft:${ac.dark.soft};
    --accent-fg:${ac.dark.fg};
  }`);

  // Temperament — type scale, radii, spacing, weight baseline.
  const f = tm.fs, r = tm.r, s = tm.sp;
  rules.push(`:root{
    --font-sans:${tm.font};
    --fs-xs:${f.xs}; --fs-sm:${f.sm}; --fs-base:${f.base}; --fs-md:${f.md};
    --fs-lg:${f.lg}; --fs-xl:${f.xl}; --fs-2xl:${f['2xl']}; --fs-3xl:${f['3xl']};
    --fs-4xl:${f['4xl']}; --fs-5xl:${f['5xl']};
    --r-xs:${r.xs}; --r-sm:${r.sm}; --r-md:${r.md}; --r-lg:${r.lg}; --r-xl:${r.xl};
    --sp-4:${s['4']}; --sp-6:${s['6']}; --sp-8:${s['8']}; --sp-10:${s['10']}; --sp-12:${s['12']};
    --tw-letter:${tm.letter};
    --tw-weight-base:${tm.weightBase};
    --tw-weight-emph:${tm.weightEmph};
    --tw-weight-bold:${tm.weightBold};
  }
  body{ font-weight: var(--tw-weight-base); letter-spacing: var(--tw-letter); line-height:${tm.bodyLh}; }
  h1,h2,h3,h4,.wm,.wordmark{ font-weight: var(--tw-weight-bold) !important; letter-spacing: var(--tw-letter); }
  .btn,.badge,.ds-nav-item,.demo-nav-item,.chip{ font-weight: var(--tw-weight-emph); }
  `);

  // Tejido — subtle textile texture on page + surfaces. Woven diagonals at
  // very low opacity; no decorative overlay, just character.
  if (t.tejido) {
    rules.push(`:root{
      --tw-tejido:
        repeating-linear-gradient(45deg, rgba(19,17,16,0.025) 0 1px, transparent 1px 4px),
        repeating-linear-gradient(-45deg, rgba(19,17,16,0.018) 0 1px, transparent 1px 6px);
    }
    [data-theme="dark"]{
      --tw-tejido:
        repeating-linear-gradient(45deg, rgba(255,245,230,0.025) 0 1px, transparent 1px 4px),
        repeating-linear-gradient(-45deg, rgba(255,245,230,0.018) 0 1px, transparent 1px 6px);
    }
    body{ background-image: var(--tw-tejido); }
    .card, .spec, .ds-sidebar, .demo-body, .ds-content{ background-image: var(--tw-tejido); }
    `);
  }

  return rules.join('\n');
}

function CacaoTweaks({ theme }) {
  const [t, setTweak] = useTweaks(TWEAK_DEFAULTS);

  React.useEffect(() => {
    let el = document.getElementById('__cacao_tweaks');
    if (!el) {
      el = document.createElement('style');
      el.id = '__cacao_tweaks';
      document.head.appendChild(el);
    }
    el.textContent = applyTweaks(t, theme);
  }, [t.acento, t.temperamento, t.tejido, theme]);

  return (
    <TweaksPanel title="Tweaks · CACAO">
      <TweakSection label="Acento" />
      <TweakRadio
        label="Color de marca"
        value={t.acento}
        options={[
          { value: 'terracota', label: 'Terracota' },
          { value: 'ambar', label: 'Ámbar' },
          { value: 'cacao', label: 'Cacao' },
        ]}
        onChange={(v) => setTweak('acento', v)}
      />

      <TweakSection label="Temperamento" />
      <TweakRadio
        label="Tono del sistema"
        value={t.temperamento}
        options={[
          { value: 'editorial', label: 'Editorial' },
          { value: 'oficina', label: 'Oficina' },
          { value: 'plaza', label: 'Plaza' },
        ]}
        onChange={(v) => setTweak('temperamento', v)}
      />
      <div style={{ fontSize: 10.5, color: 'rgba(41,38,27,.55)', lineHeight: 1.45, paddingTop: 2 }}>
        {t.temperamento === 'editorial' && 'Delgado, aireado, radios pequeños. Ritmo de revista.'}
        {t.temperamento === 'oficina' && 'Balanceado. La línea base del Plan Maestro.'}
        {t.temperamento === 'plaza' && 'Más peso, más redondez, más densidad. Tono consumer.'}
      </div>

      <TweakSection label="Material" />
      <TweakToggle
        label="Tejido artesanal"
        value={t.tejido}
        onChange={(v) => setTweak('tejido', v)}
      />
      <div style={{ fontSize: 10.5, color: 'rgba(41,38,27,.55)', lineHeight: 1.45 }}>
        Textura sutil en el fondo y las superficies — carácter de papel cacao.
      </div>
    </TweaksPanel>
  );
}

Object.assign(window, { CacaoTweaks, TWEAK_DEFAULTS });
