/* global React, Icon, ROSTER, ABSENCE_TOTALS, SESSIONS_COUNTED, fmtDateLong, SECTION, dowShort */
// ============================================================
// CACAO · Asistencia — Pasar lista (pantalla completa)
// 3 variantes vía prop `variant`:
//   'toggle'  → lista con segmento Presente/Ausente por estudiante
//   'default' → todos presente; tap en la fila marca ausente
//   'grid'    → cuadrícula de fichas, tap alterna presente/ausente
// Modo admin → professor_present = false (aviso visible)
// ============================================================
const { useState: useStateR, useMemo: useMemoR } = React;

function priorClass(n) {
  if (n >= 6) {
return 'danger';
}

  if (n >= 3) {
return 'warn';
}

  return '';
}

function RollCall({ session, variant = 'toggle', mode = 'prof', showTotals = true, onClose, onSave }) {
  // Estado inicial: todos presente (regla — pasar lista parte de presente)
  const [marks, setMarks] = useStateR(() => {
    const m = {};

    for (const st of ROSTER) {
m[st.enrollmentDetailId] = 'present';
}

    return m;
  });
  const [query, setQuery] = useStateR('');
  const [saved, setSaved] = useStateR(false);

  const set = (eid, status) => setMarks(prev => ({ ...prev, [eid]: status }));
  const toggle = (eid) => setMarks(prev => ({ ...prev, [eid]: prev[eid] === 'absent' ? 'present' : 'absent' }));
  const allPresent = () => {
 const m = {};

 for (const st of ROSTER) {
m[st.enrollmentDetailId] = 'present';
}

 setMarks(m); 
};
  const allAbsent  = () => {
 const m = {};

 for (const st of ROSTER) {
m[st.enrollmentDetailId] = 'absent';
}

  setMarks(m); 
};

  const present = Object.values(marks).filter(v => v === 'present').length;
  const absent  = ROSTER.length - present;

  const filtered = useMemoR(() => {
    const q = query.trim().toLowerCase();

    if (!q) {
return ROSTER;
}

    return ROSTER.filter(s => s.name.toLowerCase().includes(q) || s.code.toLowerCase().includes(q));
  }, [query]);

  const handleSave = () => {
    setSaved(true);
    setTimeout(() => {
 onSave && onSave(marks, mode); 
}, 900);
  };

  const isAdmin = mode === 'admin';

  return (
    <div className="att-rc" role="dialog" aria-modal="true">
      {/* Top bar */}
      <div className="att-rc-bar">
        <button className="att-rc-back" onClick={onClose}>
          <Icon name="chevronLeft" size={15} /> Volver
        </button>
        <div className="att-rc-titles">
          <h2>
            {session.topic}
            {isAdmin && (
              <span className="att-rc-admin-flag">
                <Icon name="alert" size={11} /> Subida administrativa
              </span>
            )}
          </h2>
          <div className="sub">
            <span><Icon name="calendar" size={12} /> {fmtDateLong(session.date)}</span>
            <span><Icon name="users" size={12} /> {SECTION.subject} · {SECTION.cohort}</span>
            <span><Icon name="clock" size={12} /> {SECTION.schedule.split('—')[1]?.trim() || '09:00 a 11:00'}</span>
          </div>
        </div>
        <div className="att-rc-counts">
          <div className="att-rc-count present"><span className="n">{present}</span><span className="l">Presente</span></div>
          <div className="att-rc-count absent"><span className="n">{absent}</span><span className="l">Ausente</span></div>
        </div>
      </div>

      {/* Body */}
      <div className="att-rc-body">
        <div className="att-rc-inner">
          {isAdmin && (
            <div className="att-admin-banner" style={{ marginBottom: 18 }}>
              <Icon name="info" size={16} />
              <div className="ab-body">
                Estás registrando esta asistencia como <strong>Coordinación / Admin</strong> porque el profesor no la pasó.
                La sesión quedará marcada con <strong>professor_present = false</strong>.
              </div>
            </div>
          )}

          <div className="att-rc-progress">
            <span><strong style={{ color: 'var(--text-primary)' }}>{ROSTER.length}</strong> estudiantes</span>
            <div className="track"><div className="fill" style={{ width: `${(present / ROSTER.length) * 100}%` }} /></div>
            <div className="att-rc-bulk">
              <button onClick={allPresent}>Todos presente</button>
              <button onClick={allAbsent}>Todos ausente</button>
            </div>
          </div>

          <div className="att-rc-search">
            <Icon name="search" size={15} />
            <input placeholder="Buscar estudiante por nombre o código…" value={query} onChange={e => setQuery(e.target.value)} />
          </div>

          {variant === 'toggle'  && <ToggleList  roster={filtered} marks={marks} set={set} showTotals={showTotals} />}
          {variant === 'default' && <FastList     roster={filtered} marks={marks} toggle={toggle} showTotals={showTotals} />}
          {variant === 'grid'    && <TileGrid     roster={filtered} marks={marks} toggle={toggle} showTotals={showTotals} />}
        </div>
      </div>

      {/* Sticky footer */}
      <div className="att-rc-foot">
        <div className="summary">
          <strong>{present}</strong> presente · <strong>{absent}</strong> ausente
          {absent > 0 && <span style={{ color: 'var(--text-muted)' }}> · {Math.round((present / ROSTER.length) * 100)}% asistencia</span>}
        </div>
        <div className="actions">
          <button className="btn btn-secondary btn-md" onClick={onClose}>Cancelar</button>
          <button className="btn btn-primary btn-md" onClick={handleSave}>
            <Icon name="check" size={15} /> Guardar asistencia
          </button>
        </div>
      </div>

      {saved && (
        <div className="att-toast">
          <Icon name="check" size={16} className="ok-ico" />
          Asistencia guardada · sesión marcada como dada
        </div>
      )}
    </div>
  );
}

// ---- Chip de inasistencias previas ----
function PriorChip({ eid }) {
  const n = ABSENCE_TOTALS[eid] || 0;

  if (n === 0) {
return <span className="rc-prior">sin faltas</span>;
}

  return (
    <span className={`rc-prior ${priorClass(n)}`}>
      <Icon name="alert" size={9} /> {n} falta{n !== 1 ? 's' : ''} previas
    </span>
  );
}

// ---- Variant A: toggle ----
function ToggleList({ roster, marks, set, showTotals }) {
  return (
    <div className="att-rc-list">
      {roster.map((st, i) => (
        <div className="att-rc-item" key={st.enrollmentDetailId}>
          <span className="rc-num">{i + 1}</span>
          <div className="rc-av">{st.initials}</div>
          <div className="rc-info">
            <div className="rc-name">{st.name}</div>
            <div className="rc-sub">
              {st.code}
              {showTotals && <PriorChip eid={st.enrollmentDetailId} />}
            </div>
          </div>
          <div className="rc-seg">
            <button className={`present ${marks[st.enrollmentDetailId] === 'present' ? 'on' : ''}`}
                    onClick={() => set(st.enrollmentDetailId, 'present')}>
              <Icon name="check" size={13} /> Presente
            </button>
            <button className={`absent ${marks[st.enrollmentDetailId] === 'absent' ? 'on' : ''}`}
                    onClick={() => set(st.enrollmentDetailId, 'absent')}>
              <Icon name="x" size={13} /> Ausente
            </button>
          </div>
        </div>
      ))}
    </div>
  );
}

// ---- Variant B: default presente (tap = ausente) ----
function FastList({ roster, marks, toggle, showTotals }) {
  return (
    <div className="att-rc-fastlist">
      {roster.map((st, i) => {
        const absent = marks[st.enrollmentDetailId] === 'absent';

        return (
          <div className={`att-rc-fastrow ${absent ? 'absent' : ''}`} key={st.enrollmentDetailId}
               onClick={() => toggle(st.enrollmentDetailId)}>
            <span className="rc-num">{i + 1}</span>
            <div className="rc-check">
              {absent ? <Icon name="x" size={15} /> : <Icon name="check" size={15} />}
            </div>
            <div className="rc-info">
              <div className="rc-name">{st.name}</div>
              <div className="rc-sub">
                {st.code}
                {showTotals && <PriorChip eid={st.enrollmentDetailId} />}
              </div>
            </div>
            <span className="rc-state-lbl">{absent ? 'Ausente' : 'Presente'}</span>
          </div>
        );
      })}
    </div>
  );
}

// ---- Variant C: cuadrícula ----
function TileGrid({ roster, marks, toggle, showTotals }) {
  return (
    <div className="att-rc-grid">
      {roster.map((st) => {
        const absent = marks[st.enrollmentDetailId] === 'absent';
        const prior = ABSENCE_TOTALS[st.enrollmentDetailId] || 0;

        return (
          <div className={`att-rc-tile ${absent ? 'absent' : ''}`} key={st.enrollmentDetailId}
               onClick={() => toggle(st.enrollmentDetailId)}>
            <div className="tile-av">
              {absent ? <Icon name="x" size={18} /> : st.initials}
            </div>
            <div className="tile-name">{st.name}</div>
            <div className="tile-state">{absent ? 'Ausente' : 'Presente'}</div>
            {showTotals && prior > 0 && <div className="tile-abs">{prior} falta{prior !== 1 ? 's' : ''}</div>}
          </div>
        );
      })}
    </div>
  );
}

Object.assign(window, { RollCall });
