/* global React, Icon, Button, Badge */
const { useState: useStateC, useRef: useRefC, useEffect: useEffectC } = React;

// ====== BUTTONS ======
const ButtonsSection = () => (
  <section className="ds-section">
    <header className="ds-section-head">
      <h2>Botones</h2>
      <p>Un primario terracota por pantalla. Tinta sobre fondo claro para acciones principales no-destructivas. Fantasma para acciones terciarias.</p>
    </header>

    <div className="ds-subsection">
      <h3 className="ds-subsection-title">Variantes</h3>
      <div className="spec">
        <div className="spec-body">
          <div className="state-group"><span className="state-label">Primary (accent)</span><Button variant="primary">Inscribir materia</Button></div>
          <div className="state-group"><span className="state-label">Tinta</span><Button variant="tinta">Guardar cambios</Button></div>
          <div className="state-group"><span className="state-label">Secondary</span><Button variant="secondary">Cancelar</Button></div>
          <div className="state-group"><span className="state-label">Ghost</span><Button variant="ghost">Descartar</Button></div>
          <div className="state-group"><span className="state-label">Danger</span><Button variant="danger" icon="trash">Eliminar sección</Button></div>
          <div className="state-group"><span className="state-label">Link</span><Button variant="link">Ver todas las inscripciones</Button></div>
        </div>
      </div>
    </div>

    <div className="ds-subsection">
      <h3 className="ds-subsection-title">Tamaños</h3>
      <div className="spec">
        <div className="spec-body" style={{ alignItems:'center' }}>
          <div className="state-group"><span className="state-label">Small · 30px</span><Button variant="primary" size="sm">Aprobar</Button></div>
          <div className="state-group"><span className="state-label">Medium · 36px</span><Button variant="primary" size="md">Aprobar inscripción</Button></div>
          <div className="state-group"><span className="state-label">Large · 44px</span><Button variant="primary" size="lg">Aprobar inscripción</Button></div>
        </div>
      </div>
    </div>

    <div className="ds-subsection">
      <h3 className="ds-subsection-title">Con icono</h3>
      <div className="spec">
        <div className="spec-body">
          <div className="state-group"><span className="state-label">Icono izquierda</span><Button variant="primary" icon="plus">Nueva carrera</Button></div>
          <div className="state-group"><span className="state-label">Icono derecha</span><Button variant="secondary" iconRight="arrowRight">Continuar</Button></div>
          <div className="state-group"><span className="state-label">Solo icono</span>
            <div className="row">
              <Button variant="secondary" icon="edit" iconOnly aria-label="Editar"/>
              <Button variant="ghost" icon="more" iconOnly aria-label="Más"/>
              <Button variant="danger" icon="trash" iconOnly aria-label="Eliminar"/>
            </div>
          </div>
          <div className="state-group"><span className="state-label">Descarga</span><Button variant="tinta" icon="download">Exportar CSV</Button></div>
        </div>
      </div>
    </div>

    <div className="ds-subsection">
      <h3 className="ds-subsection-title">Estados</h3>
      <div className="spec">
        <div className="spec-body">
          <div className="state-group"><span className="state-label">Default</span><Button variant="primary">Inscribir</Button></div>
          <div className="state-group"><span className="state-label">Hover (simulado)</span><Button variant="primary" style={{ background:'var(--accent-hover)', borderColor:'var(--accent-hover)' }}>Inscribir</Button></div>
          <div className="state-group"><span className="state-label">Focus</span><Button variant="primary" className="focus" style={{ boxShadow:'0 0 0 3px color-mix(in oklab, var(--accent) 30%, transparent)' }}>Inscribir</Button></div>
          <div className="state-group"><span className="state-label">Loading</span><Button variant="primary" loading>Procesando…</Button></div>
          <div className="state-group"><span className="state-label">Disabled</span><Button variant="primary" disabled>Sin cupo</Button></div>
        </div>
      </div>
    </div>
  </section>
);

// ====== BADGES ======
const BadgesSection = () => (
  <section className="ds-section">
    <header className="ds-section-head">
      <h2>Badges y chips</h2>
      <p>Usa badges para estados — <em>pendiente, aprobada, rechazada</em> — y chips para filtros o tags removibles.</p>
    </header>

    <div className="ds-subsection">
      <h3 className="ds-subsection-title">Estados de inscripción</h3>
      <div className="spec"><div className="spec-body">
        <Badge variant="warning" dot>Pendiente</Badge>
        <Badge variant="success" dot>Aprobada</Badge>
        <Badge variant="danger" dot>Rechazada</Badge>
        <Badge variant="info" dot>En revisión</Badge>
        <Badge variant="neutral" dot>Retirada</Badge>
      </div></div>
    </div>

    <div className="ds-subsection">
      <h3 className="ds-subsection-title">Variantes</h3>
      <div className="spec"><div className="spec-body">
        <Badge variant="neutral">Neutral</Badge>
        <Badge variant="accent">Acento</Badge>
        <Badge variant="success">Éxito</Badge>
        <Badge variant="warning">Advertencia</Badge>
        <Badge variant="danger">Peligro</Badge>
        <Badge variant="info">Info</Badge>
        <Badge variant="outline">Outline</Badge>
        <Badge variant="solid-tinta">Tinta sólida</Badge>
      </div></div>
    </div>

    <div className="ds-subsection">
      <h3 className="ds-subsection-title">Chips (filtros activos)</h3>
      <div className="spec"><div className="spec-body">
        <ChipsDemo />
      </div></div>
    </div>
  </section>
);

const ChipsDemo = () => {
  const [chips, setChips] = useStateC(['Ingeniería en Sistemas', 'Período 2026-I', 'Semestre 5', 'Estado: aprobada']);

  return (
    <div className="row" style={{ width: '100%' }}>
      {chips.map(c => (
        <span key={c} className="chip">
          {c}
          <button className="x" onClick={() => setChips(chips.filter(x => x !== c))} aria-label={`Quitar ${c}`}>
            <Icon name="x" size={12}/>
          </button>
        </span>
      ))}
      {chips.length === 0 && <span className="muted" style={{ fontSize: 13 }}>Sin filtros activos</span>}
      {chips.length > 0 && <button className="btn btn-link" onClick={() => setChips([])}>Limpiar todo</button>}
    </div>
  );
};

// ====== FORMS ======
const FormsSection = () => {
  const [focused, setFocused] = useStateC(false);
  const [check, setCheck] = useStateC({ a: true, b: false, c: true });
  const [radio, setRadio] = useStateC('quiz');
  const [sw, setSw] = useStateC(true);
  const [drop, setDrop] = useStateC(false);

  return (
    <section className="ds-section">
      <header className="ds-section-head">
        <h2>Formularios</h2>
        <p>Inputs sobrios con alto contraste de borde. Focus siempre terracota. Labels en <span className="mono">fs-sm · 500</span>.</p>
      </header>

      <div className="ds-subsection">
        <h3 className="ds-subsection-title">Text input — estados</h3>
        <div className="spec"><div className="spec-body">
          <div className="field">
            <label className="label">Cédula <span className="req">*</span></label>
            <input className="input" placeholder="V-12345678" />
            <span className="help">Sin puntos ni guiones</span>
          </div>
          <div className="field">
            <label className="label">Email</label>
            <input className="input" value="maria.rodriguez@cacao.edu.ve" onChange={()=>{}} />
          </div>
          <div className="field">
            <label className="label">Focus</label>
            <input className="input" defaultValue="Focus state" autoFocus={focused} onFocus={()=>setFocused(true)} />
          </div>
          <div className="field">
            <label className="label">Con error</label>
            <input className="input error" defaultValue="ma.rodriguez"/>
            <span className="err"><Icon name="alert" size={12}/> Debe incluir @ y dominio</span>
          </div>
          <div className="field">
            <label className="label">Válido</label>
            <input className="input success" defaultValue="V-23456789"/>
          </div>
          <div className="field">
            <label className="label">Deshabilitado</label>
            <input className="input" defaultValue="Auto-generado" disabled/>
          </div>
        </div></div>
      </div>

      <div className="ds-subsection">
        <h3 className="ds-subsection-title">Input con icono / affix</h3>
        <div className="spec"><div className="spec-body">
          <div className="field">
            <label className="label">Buscar estudiante</label>
            <div className="input-group has-left">
              <span className="ig-icon"><Icon name="search" size={14}/></span>
              <input className="input" placeholder="Nombre, apellido o cédula"/>
            </div>
          </div>
          <div className="field">
            <label className="label">Período</label>
            <div className="input-group">
              <span className="affix left">Año</span>
              <input className="input with-affix" defaultValue="2026"/>
            </div>
          </div>
          <div className="field">
            <label className="label">Promedio previo</label>
            <div className="input-group">
              <input className="input with-affix right" defaultValue="17.50"/>
              <span className="affix right">/ 20</span>
            </div>
          </div>
        </div></div>
      </div>

      <div className="ds-subsection">
        <h3 className="ds-subsection-title">Select, textarea, file</h3>
        <div className="spec"><div className="spec-body">
          <div className="field">
            <label className="label">Carrera</label>
            <select className="select" defaultValue="sis">
              <option value="sis">Ingeniería en Sistemas</option>
              <option value="civ">Ingeniería Civil</option>
              <option value="adm">Administración</option>
              <option value="com">Comunicación Social</option>
            </select>
          </div>
          <div className="field">
            <label className="label">Tipo de bachillerato</label>
            <select className="select" defaultValue="">
              <option value="" disabled>Selecciona una opción</option>
              <option>Ciencias</option><option>Humanidades</option><option>Técnico</option>
            </select>
          </div>
          <div className="field full">
            <label className="label">Observación del profesor</label>
            <textarea className="textarea" defaultValue="El estudiante demostró dominio sólido en estructuras de datos, aunque debe reforzar complejidad algorítmica."/>
            <span className="help">Opcional · máx 500 caracteres</span>
          </div>
        </div></div>
      </div>

      <div className="ds-subsection">
        <h3 className="ds-subsection-title">Dropzone</h3>
        <div className="spec"><div className="spec-body stack">
          <div className={`dropzone ${drop ? 'active' : ''}`}
               onMouseEnter={()=>setDrop(true)} onMouseLeave={()=>setDrop(false)}>
            <span className="icon"><Icon name="upload" size={22}/></span>
            <div><strong>Arrastra tu entrega aquí</strong> o haz clic para seleccionar</div>
            <div className="hint">PDF, DOCX o ZIP · máx 20 MB</div>
          </div>
        </div></div>
      </div>

      <div className="ds-subsection">
        <h3 className="ds-subsection-title">Checkbox, radio y switch</h3>
        <div className="spec"><div className="spec-body grid2">
          <div className="stack">
            <span className="state-label">Checkbox</span>
            <label className="check"><input type="checkbox" checked={check.a} onChange={e=>setCheck({...check, a: e.target.checked})}/><span className="box"/> Permitir inscripciones fuera del período regular</label>
            <label className="check"><input type="checkbox" checked={check.b} onChange={e=>setCheck({...check, b: e.target.checked})}/><span className="box"/> Enviar recordatorio al profesor 48h antes</label>
            <label className="check"><input type="checkbox" checked={check.c} onChange={e=>setCheck({...check, c: e.target.checked})}/><span className="box"/> Corrección automática al enviar el quiz</label>
            <label className="check"><input type="checkbox" disabled/><span className="box"/> <span style={{ opacity: 0.6 }}>Exigir evaluación del profesor (bloqueado)</span></label>
          </div>
          <div className="stack">
            <span className="state-label">Radio · tipo de actividad</span>
            {[['quiz','Quiz — opción múltiple, una respuesta'],['test','Test — varias respuestas'],['file','Entrega de archivo — revisión manual']].map(([v,l])=>(
              <label key={v} className="radio"><input type="radio" name="r1" checked={radio===v} onChange={()=>setRadio(v)}/><span className="box"/> {l}</label>
            ))}
            <div style={{ height: 12 }}/>
            <span className="state-label">Switch</span>
            <label className="switch">
              <input type="checkbox" checked={sw} onChange={e=>setSw(e.target.checked)}/>
              <span className="track"/>
              <span style={{ fontSize: 13 }}>Activar período de inscripciones</span>
            </label>
          </div>
        </div></div>
      </div>
    </section>
  );
};

Object.assign(window, { ButtonsSection, BadgesSection, FormsSection });
