/* global React, Icon, SECTION, SESSIONS, ROSTER, SESSION_TYPE, CURRENT_PERIOD,
          fmtDate, fmtDateLong, dowShort, parseDate, MESES, StatusPill, TypePill */
// ============================================================
// CACAO · Asistencia — Crear sesión (modal) + Vista admin
// ============================================================
const { useState: useStateA, useMemo: useMemoA } = React;

// ============================================================
// Crear sesión — modal
// ============================================================
function CreateSessionModal({ onClose }) {
  const [type, setType] = useStateA('regular');
  const cancellable = SESSIONS.filter(s => s.status === 'held' || s.status === 'scheduled');

  const TYPE_OPTS = [
    { key: 'regular', name: 'Regular', icon: 'calendar', desc: 'Clase del horario habitual.' },
    { key: 'makeup',  name: 'Recuperación', icon: 'arrowRight', desc: 'Repone una sesión cancelada.' },
    { key: 'advance', name: 'Adelanto', icon: 'clock', desc: 'Dicta antes una clase futura.' },
  ];

  return (
    <div className="att-modal-scrim" onClick={onClose}>
      <div className="att-modal" onClick={e => e.stopPropagation()}>
        <div className="att-modal-head">
          <h3>Nueva sesión de clase</h3>
          <button className="x" onClick={onClose} aria-label="Cerrar"><Icon name="x" size={16} /></button>
        </div>
        <div className="att-modal-body">
          <div className="att-field full" style={{ marginBottom: 16 }}>
            <label>Tipo de sesión</label>
            <div className="att-type-pick">
              {TYPE_OPTS.map(o => (
                <button key={o.key} className={`att-type-opt ${type === o.key ? 'sel' : ''}`} onClick={() => setType(o.key)}>
                  <span className="to-name"><Icon name={o.icon} size={14} /> {o.name}</span>
                  <span className="to-desc">{o.desc}</span>
                </button>
              ))}
            </div>
          </div>

          <div className="att-form-grid">
            <div className="att-field">
              <label>Sección</label>
              <select defaultValue="sec"><option value="sec">{SECTION.subject} · {SECTION.cohort} · {SECTION.career}</option></select>
            </div>
            <div className="att-field">
              <label>Fecha</label>
              <input type="date" defaultValue={type === 'regular' ? '2026-06-10' : '2026-05-30'} />
            </div>
            <div className="att-field">
              <label>Inicio</label>
              <input type="time" defaultValue="09:00" />
            </div>
            <div className="att-field">
              <label>Fin</label>
              <input type="time" defaultValue="11:00" />
            </div>

            {type !== 'regular' && (
              <div className="att-field full">
                <label>
                  {type === 'makeup' ? 'Sesión que recupera (cancelada)' : 'Sesión futura que adelanta'}
                </label>
                <select defaultValue="">
                  <option value="" disabled>Seleccionar sesión vinculada…</option>
                  {cancellable.map(s => (
                    <option key={s.id} value={s.id}>{fmtDate(s.date)} · {s.topic}</option>
                  ))}
                </select>
              </div>
            )}

            <div className="att-field full">
              <label>Tema <span className="opt">(opcional)</span></label>
              <input type="text" placeholder="Ej. Archivos — práctica guiada" />
            </div>
          </div>

          {type === 'makeup' && (
            <div className="att-modal-hint">
              <Icon name="info" size={14} />
              <span>La sesión cancelada vinculada pasará a estado <strong>recuperada</strong>. Pasás la lista en esta sesión de recuperación.</span>
            </div>
          )}
          {type === 'advance' && (
            <div className="att-modal-hint">
              <Icon name="info" size={14} />
              <span>Al registrar la asistencia de este adelanto, se <strong>copiará automáticamente</strong> a la clase futura vinculada, que quedará en estado <strong>adelantada</strong>.</span>
            </div>
          )}
        </div>
        <div className="att-modal-foot">
          <button className="btn btn-secondary btn-md" onClick={onClose}>Cancelar</button>
          <button className="btn btn-primary btn-md" onClick={onClose}>
            <Icon name="plus" size={15} /> Crear sesión
          </button>
        </div>
      </div>
    </div>
  );
}

// ============================================================
// Vista Admin / Coordinador — subir asistencia manual
// ============================================================
const ADMIN_QUEUE = [
  { id: 'a1', subject: 'Programación I',   code: 'SIS-1204', cohort: '4° A', career: 'Ing. en Sistemas', careerColor: '#C8521A', prof: 'Marco Salas',  date: '2026-05-22', topic: 'Punteros — práctica', reason: 'Profesor de reposo médico', students: 26 },
  { id: 'a2', subject: 'Cálculo II',       code: 'SIS-0902', cohort: '2° B', career: 'Ing. en Sistemas', careerColor: '#C8521A', prof: 'Ana Pérez',    date: '2026-05-25', topic: 'Integrales impropias', reason: 'No registrada por el profesor', students: 31 },
  { id: 'a3', subject: 'Estática',         code: 'IND-0710', cohort: '2° A', career: 'Ing. Industrial',  careerColor: '#7C5A3A', prof: 'Hugo Torres',  date: '2026-05-26', topic: 'Equilibrio de cuerpos', reason: 'Falta del profesor', students: 28 },
  { id: 'a4', subject: 'Bases de Datos',   code: 'SIS-1408', cohort: '4° A', career: 'Ing. en Sistemas', careerColor: '#C8521A', prof: 'Luis Méndez',  date: '2026-05-26', topic: 'Normalización 3FN', reason: 'Comisión académica', students: 24 },
];

function AdminCard({ item, onUpload }) {
  const dt = parseDate(item.date);

  return (
    <div className="att-card clickable" onClick={() => onUpload(item)} style={{ '--sec-color': item.careerColor }}>
      <div className="att-card-top">
        <div className="att-card-date" style={{ borderColor: 'color-mix(in srgb, ' + item.careerColor + ' 30%, var(--border))' }}>
          <div className="d-dow">{dowShort(item.date)}</div>
          <div className="d-num">{dt.getDate()}</div>
          <div className="d-mon">{MESES[dt.getMonth()]}</div>
        </div>
        <div className="att-card-head">
          <h4 className="att-card-topic">{item.subject} · {item.cohort}</h4>
          <div className="att-card-tags">
            <span className="att-pill warn"><span className="pdot" />Sin registrar</span>
            <span className="att-pill type" style={{ fontFamily: 'var(--font-mono)' }}>{item.code}</span>
          </div>
        </div>
      </div>
      <div className="att-card-body">
        <div style={{ fontSize: 12.5, color: 'var(--text-secondary)' }}>{item.topic}</div>
        <div className="att-link-note">
          <Icon name="user" size={13} />
          <span>Profesor <strong>{item.prof}</strong> · {item.reason}</span>
        </div>
        <div className="att-upload-note"><Icon name="alert" size={11} /> Se marcará professor_present = false</div>
      </div>
      <div className="att-card-foot">
        <span className="foot-meta">{item.students} estudiantes</span>
        <button className="att-card-cta" onClick={(e) => {
 e.stopPropagation(); onUpload(item); 
}}>
          <Icon name="upload" size={13} /> Subir asistencia
        </button>
      </div>
    </div>
  );
}

function AdminView({ onUpload }) {
  const [query, setQuery] = useStateA('');
  const filtered = useMemoA(() => {
    const q = query.trim().toLowerCase();

    if (!q) {
return ADMIN_QUEUE;
}

    return ADMIN_QUEUE.filter(i => (i.subject + i.prof + i.code + i.cohort).toLowerCase().includes(q));
  }, [query]);

  return (
    <div>
      <div className="att-admin-banner">
        <Icon name="info" size={16} />
        <div className="ab-body">
          <strong>Subida administrativa de asistencia.</strong> Estas sesiones no fueron registradas por el profesor.
          Como Coordinación / Admin podés subir la lista; cada sesión quedará marcada con <strong>professor_present = false</strong>.
          <br />Esta es la vía del coordinador mientras el portal propio no exista.
        </div>
      </div>

      <div className="att-viewbar">
        <div className="att-tabs">
          <button className="att-tab active">Pendientes de registrar <span className="cnt">{ADMIN_QUEUE.length}</span></button>
        </div>
      </div>

      <div className="att-rc-search" style={{ maxWidth: 380, marginBottom: 18 }}>
        <Icon name="search" size={15} />
        <input placeholder="Buscar sección, profesor o código…" value={query} onChange={e => setQuery(e.target.value)} />
      </div>

      {filtered.length === 0 ? (
        <div className="att-empty">
          <div className="ic"><Icon name="check" size={20} /></div>
          <h3>Todo al día</h3>
          <p>No hay sesiones pendientes de subir.</p>
        </div>
      ) : (
        <div className="att-cards">
          {filtered.map(item => <AdminCard key={item.id} item={item} onUpload={onUpload} />)}
        </div>
      )}
    </div>
  );
}

Object.assign(window, { CreateSessionModal, AdminView, ADMIN_QUEUE });
