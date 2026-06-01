/* global React, Icon, Iso, useTheme, ThemeToggle, useTweaks, TweaksPanel,
          TweakSection, TweakRadio, TweakToggle,
          SECTION, SESSIONS, ROSTER, ABSENCE_TOTALS, CURRENT_PERIOD,
          mainSessions, fmtDate, fmtDateLong, dowShort, isToday, isPast, isFuture, parseDate, MESES,
          SessionCard, SessionsTable, SessionsAgenda, TotalsPanel,
          RollCall, CreateSessionModal, AdminView */
const { useState: useStateMain, useMemo: useMemoMain } = React;

const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
  "listLayout": "cards",
  "rollcallStyle": "toggle",
  "density": "compact",
  "showTotals": true,
  "themeToggle": "segmented"
}/*EDITMODE-END*/;

// ---- Sidebar ----
function Sidebar({ open, onClose }) {
  const item = (icon, label, active) => (
    <button className={`up-nav-item ${active ? 'active' : ''}`}>
      <Icon name={icon} size={15} /> {label}
    </button>
  );
  return (
    <aside className={`up-sidebar ${open ? 'open' : ''}`}>
      <div className="up-sidebar-brand">
        <Iso size={22} />
        <span className="wm">CACAO</span>
      </div>
      <nav className="up-nav">
        <div className="up-nav-group">General</div>
        {item('grid', 'Dashboard')}
        <div className="up-nav-group">Académico</div>
        {item('book', 'Estudiantes')}
        {item('file', 'Inscripciones')}
        {item('calendar', 'Horarios')}
        {item('check', 'Asistencia', true)}
        <div className="up-nav-group">Seguridad</div>
        {item('star', 'Roles')}
        {item('users', 'Usuarios')}
      </nav>
      <div className="up-sidebar-user">
        <div className="avatar">{SECTION.professor.initials}</div>
        <div className="name">Prof. {SECTION.professor.name}</div>
        <button className="logout" aria-label="Cerrar sesión"><Icon name="logout" size={14} /></button>
      </div>
    </aside>
  );
}

// ---- Stat tile ----
function Stat({ tone, icon, value, label }) {
  return (
    <div className={`att-stat ${tone || ''}`}>
      <div className="att-stat-ico"><Icon name={icon} size={18} /></div>
      <div className="att-stat-info">
        <div className="att-stat-val">{value}</div>
        <div className="att-stat-lbl">{label}</div>
      </div>
    </div>
  );
}

// ---- Today highlight ----
function TodayCard({ session, onOpen }) {
  if (!session) return null;
  const dt = parseDate(session.date);
  return (
    <div className="att-today">
      <div className="att-today-date">
        <span className="dow">{dowShort(session.date)}</span>
        <span className="dnum">{dt.getDate()}</span>
        <span className="mon">{MESES[dt.getMonth()]}</span>
      </div>
      <div className="att-today-body">
        <span className="att-today-tag"><span className="pulse" /> Clase de hoy · pendiente</span>
        <h3>{session.topic}</h3>
        <div className="att-today-meta">
          <span><Icon name="clock" size={13} /> {SECTION.schedule.split('—')[1]?.trim()}</span>
          <span><Icon name="map" size={13} /> {SECTION.room}</span>
          <span><Icon name="users" size={13} /> {ROSTER.length} estudiantes</span>
        </div>
      </div>
      <div className="att-today-action">
        <div className="roster-mini">
          {ROSTER.slice(0, 4).map(s => <span className="av" key={s.enrollmentDetailId}>{s.initials}</span>)}
          <span className="av more">+{ROSTER.length - 4}</span>
        </div>
        <button className="btn btn-primary btn-lg" onClick={() => onOpen(session)}>
          <Icon name="check" size={16} /> Pasar lista
        </button>
      </div>
    </div>
  );
}

// ---- Filter chips ----
const FILTERS = [
  { key: 'all',       label: 'Todas' },
  { key: 'pending',   label: 'Pendientes',        dot: 'var(--warning)' },
  { key: 'held',      label: 'Dadas',             dot: 'var(--success)' },
  { key: 'special',   label: 'Recup. / Adelanto', dot: 'var(--info)' },
  { key: 'noprof',    label: 'Sin profesor',      dot: 'var(--danger)' },
];

function ProfessorView({ tweaks, onOpenRoll, onCreate }) {
  const [view, setView] = useStateMain('sessions');     // sessions | totals
  const [filter, setFilter] = useStateMain('all');

  const all = useMemoMain(() => mainSessions(), []);
  const todaySession = all.find(s => isToday(s.date) && s.status === 'scheduled');

  const filtered = useMemoMain(() => {
    return all.filter(s => {
      if (filter === 'pending') return s.status === 'scheduled';
      if (filter === 'held')    return s.hasRecord;
      if (filter === 'special') return s.type !== 'regular' || s.status === 'recovered' || s.status === 'advanced';
      if (filter === 'noprof')  return s.professorPresent === false;
      return true;
    });
  }, [all, filter]);

  // Stats
  const stats = useMemoMain(() => {
    const recorded = all.filter(s => s.hasRecord);
    const totalPresent = recorded.reduce((a, s) => a + s.present, 0);
    const totalSlots = recorded.reduce((a, s) => a + s.present + s.absent, 0);
    const pct = totalSlots ? Math.round((totalPresent / totalSlots) * 100) : 0;
    const totalAbsent = Object.values(ABSENCE_TOTALS).reduce((a, b) => a + b, 0);
    const pending = all.filter(s => s.status === 'scheduled').length;
    return { dadas: recorded.length, pct, totalAbsent, pending };
  }, [all]);

  const counts = useMemoMain(() => ({
    sessions: all.length,
    totals: ROSTER.length,
  }), [all]);

  return (
    <>
      <SectionBanner />
      {todaySession && <TodayCard session={todaySession} onOpen={onOpenRoll} />}

      <div className="att-stats">
        <Stat tone="ok"     icon="check"    value={stats.dadas}   label="Sesiones registradas" />
        <Stat                icon="chart"    value={`${stats.pct}%`} label="Asistencia promedio" />
        <Stat tone="danger" icon="alert"    value={stats.totalAbsent} label="Inasistencias del período" />
        <Stat tone="warn"   icon="calendar" value={stats.pending} label="Sesiones por dar" />
      </div>

      <div className="att-viewbar">
        <div className="att-tabs">
          <button className={`att-tab ${view === 'sessions' ? 'active' : ''}`} onClick={() => setView('sessions')}>
            <Icon name="calendar" size={15} /> Sesiones <span className="cnt">{counts.sessions}</span>
          </button>
          {tweaks.showTotals && (
            <button className={`att-tab ${view === 'totals' ? 'active' : ''}`} onClick={() => setView('totals')}>
              <Icon name="users" size={15} /> Inasistencias <span className="cnt">{counts.totals}</span>
            </button>
          )}
        </div>
        {view === 'sessions' && (
          <div className="att-layout-switch" role="group" aria-label="Diseño de lista">
            <button className={tweaks.listLayout === 'cards' ? 'active' : ''} onClick={() => setLayout('cards')} title="Tarjetas"><Icon name="grid" size={15} /></button>
            <button className={tweaks.listLayout === 'table' ? 'active' : ''} onClick={() => setLayout('table')} title="Tabla"><Icon name="more" size={15} /></button>
            <button className={tweaks.listLayout === 'calendar' ? 'active' : ''} onClick={() => setLayout('calendar')} title="Agenda"><Icon name="calendar" size={15} /></button>
          </div>
        )}
        <button className="btn btn-primary btn-md" onClick={onCreate}>
          <Icon name="plus" size={15} /> Nueva sesión
        </button>
      </div>

      {view === 'sessions' ? (
        <>
          <div className="att-filters">
            {FILTERS.map(f => (
              <button key={f.key} className={`att-fchip ${filter === f.key ? 'active' : ''}`} onClick={() => setFilter(f.key)}>
                {f.dot && <span className="dot" style={{ background: f.dot }} />}
                {f.label}
              </button>
            ))}
          </div>

          {filtered.length === 0 ? (
            <div className="att-empty">
              <div className="ic"><Icon name="calendar" size={20} /></div>
              <h3>Sin sesiones</h3>
              <p>No hay sesiones que coincidan con este filtro.</p>
            </div>
          ) : tweaks.listLayout === 'table' ? (
            <SessionsTable sessions={filtered} onOpen={onOpenRoll} />
          ) : tweaks.listLayout === 'calendar' ? (
            <SessionsAgenda sessions={filtered} onOpen={onOpenRoll} />
          ) : (
            <div className="att-cards">
              {filtered.map(s => <SessionCard key={s.id} session={s} onOpen={onOpenRoll} />)}
            </div>
          )}
        </>
      ) : (
        <TotalsPanel />
      )}
    </>
  );

  // setLayout proxies the tweak so the in-page switch and the Tweaks panel stay in sync
  function setLayout(v) { window.__attSetTweak && window.__attSetTweak('listLayout', v); }
}

function SectionBanner() {
  return (
    <div className="att-section-banner" style={{ '--sec-color': SECTION.careerColor }}>
      <div className="att-section-badge">{SECTION.cohort}</div>
      <div className="att-section-info">
        <h2>{SECTION.subject} <span className="sec-code">{SECTION.code}</span></h2>
        <div className="att-section-meta">
          <span><Icon name="book" size={13} /> {SECTION.career}</span>
          <span><Icon name="clock" size={13} /> {SECTION.schedule}</span>
          <span><Icon name="map" size={13} /> {SECTION.room}</span>
          <span><Icon name="users" size={13} /> {ROSTER.length} estudiantes</span>
        </div>
      </div>
      <div className="att-section-prof">
        <div className="pf-avatar">{SECTION.professor.initials}</div>
        <div className="pf-info">
          <span className="pf-name">{SECTION.professor.name}</span>
          <span className="pf-role">Profesor</span>
        </div>
      </div>
    </div>
  );
}

// ============================================================
// Root
// ============================================================
function AttendanceApp() {
  const [mode, setMode, resolved] = useTheme();
  const [tweaks, setTweak] = useTweaks(TWEAK_DEFAULTS);
  window.__attSetTweak = setTweak;

  const [role, setRole] = useStateMain('prof');     // prof | admin
  const [sidebarOpen, setSidebarOpen] = useStateMain(false);
  const [rollcall, setRollcall] = useStateMain(null); // { session, mode }
  const [createOpen, setCreateOpen] = useStateMain(false);

  const openRoll = (session, m = 'prof') => setRollcall({ session, mode: m });
  const closeRoll = () => setRollcall(null);

  return (
    <div className="att-root up-shell" data-density={tweaks.density}>
      {/* Mobile topbar */}
      <div className="up-mobile-topbar">
        <button className="up-hamburger" onClick={() => setSidebarOpen(true)} aria-label="Menú">
          <Icon name="grid" size={20} />
        </button>
        <div className="up-mobile-brand"><Iso size={20} /><span>CACAO</span></div>
        <div className="up-mobile-spacer" />
        <div className="up-mobile-user-dot">{SECTION.professor.initials}</div>
      </div>
      <div className={`up-drawer-backdrop ${sidebarOpen ? 'open' : ''}`} onClick={() => setSidebarOpen(false)} />

      <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />

      <main className="up-main">
        <div className="up-topbar">
          <span className="up-crumb">Académico</span>
          <span className="up-crumb-sep">/</span>
          <span className="up-crumb current">Asistencia</span>
          <div className="att-topbar-spacer" />
          <div className="att-role-seg">
            <button className={role === 'prof' ? 'active' : ''} onClick={() => setRole('prof')}>
              <Icon name="user" size={13} /> Profesor
            </button>
            <button className={role === 'admin' ? 'active' : ''} onClick={() => setRole('admin')}>
              <Icon name="users" size={13} /> Admin / Coord.
            </button>
          </div>
          <span className="att-period-tag"><Icon name="calendar" size={12} /> Período <strong>{CURRENT_PERIOD}</strong></span>
          <ThemeToggle variant={tweaks.themeToggle} mode={mode} setMode={setMode} resolved={resolved} />
        </div>

        <div className="up-content">
          <div className="up-header">
            <div className="up-header-left">
              <h1>{role === 'prof' ? 'Asistencia' : 'Asistencia · Coordinación'}</h1>
              <p>{role === 'prof'
                ? 'Pasá lista por sesión y seguí las inasistencias de tu sección.'
                : 'Subí la asistencia de sesiones que el profesor no registró.'}</p>
            </div>
          </div>

          {role === 'prof'
            ? <ProfessorView tweaks={tweaks} onOpenRoll={openRoll} onCreate={() => setCreateOpen(true)} />
            : <AdminView onUpload={(item) => openRoll(adminToSession(item), 'admin')} />}
        </div>
      </main>

      {rollcall && (
        <RollCall
          session={rollcall.session}
          mode={rollcall.mode}
          variant={tweaks.rollcallStyle}
          showTotals={tweaks.showTotals}
          onClose={closeRoll}
          onSave={closeRoll}
        />
      )}

      {createOpen && <CreateSessionModal onClose={() => setCreateOpen(false)} />}

      <TweaksPanel title="Tweaks · Asistencia">
        <TweakSection label="Lista de sesiones" />
        <TweakRadio label="Diseño" value={tweaks.listLayout}
          options={[{ value: 'cards', label: 'Tarjetas' }, { value: 'table', label: 'Tabla' }, { value: 'calendar', label: 'Agenda' }]}
          onChange={v => setTweak('listLayout', v)} />
        <TweakRadio label="Densidad" value={tweaks.density}
          options={[{ value: 'compact', label: 'Compacta' }, { value: 'regular', label: 'Normal' }, { value: 'comfy', label: 'Amplia' }]}
          onChange={v => setTweak('density', v)} />

        <TweakSection label="Pasar lista" />
        <TweakRadio label="Estilo" value={tweaks.rollcallStyle}
          options={[{ value: 'toggle', label: 'Toggle' }, { value: 'default', label: 'Presente' }, { value: 'grid', label: 'Cuadrícula' }]}
          onChange={v => setTweak('rollcallStyle', v)} />
        <div style={{ fontSize: 10.5, color: 'rgba(41,38,27,.55)', lineHeight: 1.45 }}>
          {tweaks.rollcallStyle === 'toggle' && 'Cada estudiante con segmento Presente/Ausente.'}
          {tweaks.rollcallStyle === 'default' && 'Todos presente; tocá la fila para marcar ausente.'}
          {tweaks.rollcallStyle === 'grid' && 'Cuadrícula de fichas; tocá para alternar estado.'}
        </div>

        <TweakSection label="Inasistencias" />
        <TweakToggle label="Mostrar totales por estudiante" value={tweaks.showTotals}
          onChange={v => setTweak('showTotals', v)} />

        <TweakSection label="Tema" />
        <TweakRadio label="Control" value={tweaks.themeToggle}
          options={[{ value: 'segmented', label: 'Seg.' }, { value: 'dropdown', label: 'Menú' }, { value: 'cycle', label: 'Ciclo' }]}
          onChange={v => setTweak('themeToggle', v)} />
      </TweaksPanel>
    </div>
  );
}

// Convierte un item de la cola admin en una "sesión" para la pantalla de pasar lista
function adminToSession(item) {
  return {
    id: 'admin-' + item.id,
    date: item.date,
    type: 'regular',
    status: 'scheduled',
    topic: item.topic + ' · ' + item.subject,
    professorPresent: false,
    present: 0, absent: 0, hasRecord: false,
  };
}

ReactDOM.createRoot(document.getElementById('root')).render(<AttendanceApp />);
