/* global React, Isotipo, Button, Badge, Icon */
const { useState: useStateF } = React;

// ================= FUNDAMENTOS =================
const Swatch = ({ name, varName, hex, light }) => (
  <div className="swatch">
    <div className={`chip ${light ? 'light' : ''}`} style={{ background: `var(${varName})` }}>{varName}</div>
    <div className="meta">
      <span className="name">{name}</span>
      <span className="hex">{hex}</span>
    </div>
  </div>
);

const FundamentosSection = () => {
  return (
    <div>
      <div className="ds-page-header">
        <h1>Fundamentos</h1>
        <p className="intro">
          Base sobre la que se apoya todo CACAO. Cada token vive como variable CSS y como clase Tailwind — <span className="mono">bg-tinta</span>, <span className="mono">text-terracota</span>, <span className="mono">bg-papel</span>. El terracota es el único acento activo de la marca.
        </p>
      </div>

      {/* Isotipo */}
      <section className="ds-section">
        <header className="ds-section-head">
          <h2>Isotipo</h2>
          <p>Cuadrícula 3×3 con el módulo terracota en posición fija [0,2] y un espacio vacío en [1,2]. Reglas inamovibles.</p>
        </header>
        <div className="spec">
          <div className="spec-head"><span className="spec-title">Escalas</span><span className="spec-meta">grid-cols-3 · rounded-[20%]</span></div>
          <div className="spec-body center" style={{ gap: 48 }}>
            {['iso-20','iso-28','iso-40','iso-72','iso-120'].map(s => (
              <div key={s} className="state-group" style={{ alignItems:'center' }}>
                <Isotipo size={s} />
                <span className="state-label" style={{ marginTop: 10 }}>{s.replace('iso-','')}px</span>
              </div>
            ))}
          </div>
        </div>

        <div className="spec" style={{ marginTop: 16 }}>
          <div className="spec-head"><span className="spec-title">Wordmark + isotipo</span><span className="spec-meta">Space Grotesk 700 · -0.01em</span></div>
          <div className="spec-body center" style={{ gap: 60, background: 'var(--bg-surface-2)' }}>
            <div className="wordmark-row"><Isotipo size="iso-28"/><span className="wm" style={{ fontSize: 22 }}>CACAO</span></div>
            <div className="wordmark-row iso-on-tinta" style={{ background:'var(--tinta)', padding:'12px 20px', borderRadius:8 }}>
              <Isotipo size="iso-28"/><span className="wm" style={{ fontSize: 22, color:'var(--papel)' }}>CACAO</span>
            </div>
            <div className="wordmark-row" style={{ background:'var(--terracota)', padding:'12px 20px', borderRadius:8 }}>
              <div className="iso iso-28" style={{ }}>
                {[...Array(9)].map((_,i)=>{
                  if (i===2) {
return <span key={i} className="cell" style={{ background:'var(--papel)' }}/>;
}

                  if (i===5) {
return <span key={i} className="cell empty"/>;
}

                  return <span key={i} className="cell" style={{ background:'var(--papel)' }}/>;
                })}
              </div>
              <span className="wm" style={{ fontSize: 22, color:'#fff' }}>CACAO</span>
            </div>
          </div>
        </div>
      </section>

      {/* Color */}
      <section className="ds-section">
        <header className="ds-section-head">
          <h2>Paleta</h2>
          <p>La marca usa pocos colores, con intención. Terracota es un acento — úsalo para CTAs, datos críticos y el item activo del sidebar.</p>
        </header>

        <div className="ds-subsection">
          <h3 className="ds-subsection-title">Marca</h3>
          <div className="token-grid">
            <Swatch name="Tinta" varName="--tinta" hex="#131110"/>
            <Swatch name="Terracota" varName="--terracota" hex="#C8521A"/>
            <Swatch name="Pizarra" varName="--pizarra" hex="#3D3A36"/>
            <Swatch name="Papel" varName="--papel" hex="#F4F2EF" light/>
            <Swatch name="Hueso" varName="--hueso" hex="#FAFAF8" light/>
            <Swatch name="Ámbar" varName="--ambar" hex="#E8895A"/>
          </div>
        </div>

        <div className="ds-subsection">
          <h3 className="ds-subsection-title">Semántica</h3>
          <div className="token-grid">
            <Swatch name="Éxito" varName="--success" hex="#4C7A1F"/>
            <Swatch name="Advertencia" varName="--warning" hex="#B87500"/>
            <Swatch name="Peligro" varName="--danger" hex="#B12A1F"/>
            <Swatch name="Info" varName="--info" hex="#1F5F8B"/>
          </div>
        </div>

        <div className="ds-subsection">
          <h3 className="ds-subsection-title">Superficies (tema activo)</h3>
          <div className="token-grid">
            <Swatch name="Page" varName="--bg-page" hex="auto" light/>
            <Swatch name="Surface" varName="--bg-surface" hex="auto" light/>
            <Swatch name="Surface 2" varName="--bg-surface-2" hex="auto" light/>
            <Swatch name="Sunken" varName="--bg-sunken" hex="auto" light/>
            <Swatch name="Border" varName="--border" hex="auto" light/>
            <Swatch name="Accent soft" varName="--accent-soft" hex="auto" light/>
          </div>
        </div>
      </section>

      {/* Tipografía */}
      <section className="ds-section">
        <header className="ds-section-head">
          <h2>Tipografía</h2>
          <p>Space Grotesk en 5 pesos — <strong>300 · 400 · 500 · 600 · 700</strong>. Nunca otra fuente en el sistema.</p>
        </header>

        <div className="spec">
          <div className="spec-head"><span className="spec-title">Jerarquía</span><span className="spec-meta">Space Grotesk</span></div>
          <div className="spec-body stack" style={{ gap: 0, padding: '0 24px' }}>
            <div className="type-sample">
              <div className="meta"><span>Display</span><span>60 · 600 · -0.02em</span></div>
              <div className="display" style={{ fontSize: 60, fontWeight: 600, lineHeight: 1 }}>Control académico.</div>
            </div>
            <div className="type-sample">
              <div className="meta"><span>H1</span><span>44 · 600</span></div>
              <div className="display" style={{ fontSize: 44, fontWeight: 600, lineHeight: 1.05 }}>Inscripciones del período</div>
            </div>
            <div className="type-sample">
              <div className="meta"><span>H2</span><span>32 · 600</span></div>
              <div className="display" style={{ fontSize: 32, fontWeight: 600 }}>Materias con prelación activa</div>
            </div>
            <div className="type-sample">
              <div className="meta"><span>H3</span><span>24 · 600</span></div>
              <div className="display" style={{ fontSize: 24, fontWeight: 600 }}>Ingeniería en Sistemas · 2026-I</div>
            </div>
            <div className="type-sample">
              <div className="meta"><span>Lead</span><span>20 · 400</span></div>
              <div className="display" style={{ fontSize: 20, fontWeight: 400, color: 'var(--text-secondary)' }}>Gestión integral del ciclo académico.</div>
            </div>
            <div className="type-sample">
              <div className="meta"><span>Body</span><span>14 · 400</span></div>
              <div className="display" style={{ fontSize: 14 }}>El estudiante puede cursar la materia una vez validadas las prelaciones y confirmada la disponibilidad de cupos.</div>
            </div>
            <div className="type-sample">
              <div className="meta"><span>Small</span><span>13 · 400</span></div>
              <div className="display" style={{ fontSize: 13, color: 'var(--text-secondary)' }}>Actualizado hace 2 horas por admin@cacao</div>
            </div>
            <div className="type-sample">
              <div className="meta"><span>Caption</span><span>11 · 600 · 0.06em</span></div>
              <div className="display mono" style={{ fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.06em', color: 'var(--text-muted)' }}>PERÍODO · 2026-I</div>
            </div>
          </div>
        </div>

        <div className="spec" style={{ marginTop: 16 }}>
          <div className="spec-head"><span className="spec-title">Pesos</span><span className="spec-meta">family-name: Space Grotesk</span></div>
          <div className="spec-body stack" style={{ gap: 12, fontSize: 22 }}>
            <div><span style={{ fontWeight: 300 }}>CACAO · Light 300</span></div>
            <div><span style={{ fontWeight: 400 }}>CACAO · Regular 400</span></div>
            <div><span style={{ fontWeight: 500 }}>CACAO · Medium 500</span></div>
            <div><span style={{ fontWeight: 600 }}>CACAO · Semibold 600</span></div>
            <div><span style={{ fontWeight: 700 }}>CACAO · Bold 700</span></div>
          </div>
        </div>
      </section>

      {/* Radius / Shadow / Spacing */}
      <section className="ds-section">
        <header className="ds-section-head">
          <h2>Radios, sombras y espacio</h2>
          <p>Radios contenidos — los elementos son más rectangulares que redondeados. Sombras sutiles; la jerarquía se construye con contraste.</p>
        </header>

        <div className="ds-subsection">
          <h3 className="ds-subsection-title">Radios</h3>
          <div className="token-grid">
            {[['xs','3px','var(--r-xs)'],['sm','4px','var(--r-sm)'],['md','6px','var(--r-md)'],['lg','10px','var(--r-lg)'],['xl','14px','var(--r-xl)'],['pill','999px','var(--r-pill)']].map(([n,v,tok])=>(
              <div key={n} className="tile" style={{ borderRadius: tok }}>
                <div className="name">r-{n}</div><div>{v}</div>
              </div>
            ))}
          </div>
        </div>

        <div className="ds-subsection">
          <h3 className="ds-subsection-title">Sombras</h3>
          <div className="token-grid">
            {[['xs','--shadow-xs'],['sm','--shadow-sm'],['md','--shadow-md'],['lg','--shadow-lg']].map(([n,tok])=>(
              <div key={n} className="tile" style={{ boxShadow: `var(${tok})`, borderRadius:10, border:'1px solid var(--border)' }}>
                <div className="name">shadow-{n}</div><div className="mono">{tok}</div>
              </div>
            ))}
          </div>
        </div>

        <div className="ds-subsection">
          <h3 className="ds-subsection-title">Espacio (múltiplos de 4)</h3>
          <div className="stack" style={{ gap: 6 }}>
            {[['sp-1',4],['sp-2',8],['sp-3',12],['sp-4',16],['sp-5',20],['sp-6',24],['sp-8',32],['sp-10',40],['sp-12',48]].map(([n,px])=>(
              <div key={n} style={{ display:'flex', alignItems:'center', gap: 12 }}>
                <span className="mono" style={{ width: 60, fontSize: 12, color:'var(--text-muted)' }}>{n}</span>
                <span style={{ width: px, height: 10, background:'var(--accent)', borderRadius: 2 }}/>
                <span className="mono" style={{ fontSize: 12, color:'var(--text-muted)' }}>{px}px</span>
              </div>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
};

Object.assign(window, { FundamentosSection });
