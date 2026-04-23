/* global React, Icon, Button, Badge, Isotipo */
const { useState: useStateD, useMemo: useMemoD } = React;

const TodaySchedule = () => {
  // Horario del día: 7:00 → 19:00 en bloques de 1h = 12 columnas.
  const HOURS = Array.from({ length: 13 }, (_, i) => 7 + i); // 7..19 marks
  const SLOTS = [
    { start: 7,  end: 9,  title: 'Algoritmos II',        room: 'A-203', status: 'done',    section: '01' },
    { start: 9,  end: 11, title: 'Bases de Datos',       room: 'Lab-3', status: 'done',    section: '02' },
    { start: 11, end: 13, title: 'Microeconomía',        room: 'B-115', status: 'now',     section: '04' },
    { start: 14, end: 16, title: 'Redacción Period.',    room: 'A-110', status: 'next',    section: '01' },
    { start: 16, end: 18, title: 'Mecánica de Mat.',     room: 'Lab-1', status: 'next',    section: '03' },
  ];

  const range = 19 - 7; // 12 hours
  const pct = (h) => ((h - 7) / range) * 100;

  // "Ahora" — marcador en vivo; para demo, fijo a 12:20
  const nowHour = 12 + 20/60;

  return (
    <div className="tsched">
      <div className="tsched-head">
        <div className="row" style={{ gap: 10, alignItems:'baseline' }}>
          <h3 style={{ margin: 0 }}>Horario de hoy</h3>
          <span className="sub mono" style={{ fontSize: 12 }}>JUE · 23 ABR · 2026-I</span>
        </div>
        <div className="tsched-legend">
          <span><i className="dot done"/> Concluida</span>
          <span><i className="dot now"/> En curso</span>
          <span><i className="dot next"/> Próxima</span>
          <Button variant="link">Ver semana →</Button>
        </div>
      </div>

      <div className="tsched-track">
        {/* hour grid */}
        <div className="tsched-hours">
          {HOURS.map(h => (
            <span key={h} className="tsched-hour" style={{ left: `${pct(h)}%` }}>
              <i/>
              <b>{String(h).padStart(2,'0')}</b>
            </span>
          ))}
        </div>
        {/* slots */}
        <div className="tsched-slots">
          {SLOTS.map((s, i) => (
            <div key={i} className={`tsched-slot ${s.status}`} style={{ left: `${pct(s.start)}%`, width: `${((s.end - s.start)/range)*100}%` }}>
              <span className="tsched-slot-time">{String(s.start).padStart(2,'0')}:00 — {String(s.end).padStart(2,'0')}:00</span>
              <span className="tsched-slot-title">{s.title}</span>
              <span className="tsched-slot-meta">{s.room} · sec {s.section}</span>
            </div>
          ))}
          {/* Now marker */}
          <div className="tsched-now" style={{ left: `${pct(nowHour)}%` }}>
            <span className="tsched-now-pill">12:20</span>
          </div>
        </div>
      </div>
    </div>
  );
};

const DashboardDemo = () => {
  const [nav, setNav] = useStateD('dashboard');
  const [range, setRange] = useStateD('periodo');

  const ENR = [
    { s:'María Rodríguez',  car:'Ing. Sistemas',    sub:'Algoritmos II',       st:'pending' },
    { s:'José Pérez',       car:'Ing. Civil',       sub:'Mecánica de Mat.',    st:'approved' },
    { s:'Andrea Quintero',  car:'Administración',   sub:'Microeconomía',       st:'approved' },
    { s:'Luis Hernández',   car:'Ing. Sistemas',    sub:'Bases de Datos',      st:'rejected' },
    { s:'Valentina Gómez',  car:'Comunicación',     sub:'Redacción Period.',   st:'pending' },
  ];
  const STB = {
    pending:{v:'warning',l:'Pendiente'},
    approved:{v:'success',l:'Aprobada'},
    rejected:{v:'danger',l:'Rechazada'},
  };

  return (
    <div className="demo-shell">
      {/* Sidebar */}
      <aside className="demo-sidebar">
        <div className="demo-sidebar-brand">
          <Isotipo size="iso-28"/>
          <div>
            <div className="wm">CACAO</div>
            <div style={{ fontSize: 10, color:'var(--sidebar-muted)', fontFamily:'var(--font-mono)', textTransform:'uppercase', letterSpacing:'0.06em' }}>admin · 2026-I</div>
          </div>
        </div>
        <nav className="demo-nav">
          <div className="demo-nav-group">General</div>
          {[['dashboard','Dashboard','home'],['inscripciones','Inscripciones','file','98'],['estudiantes','Estudiantes','users'],['profesores','Profesores','user']].map(([k,l,i,ct])=>(
            <button key={k} className={`demo-nav-item ${nav===k?'active':''}`} onClick={()=>setNav(k)}>
              <span className="ico"><Icon name={i} size={15}/></span> {l}
              {ct && <span className="ct">{ct}</span>}
            </button>
          ))}
          <div className="demo-nav-group">Académico</div>
          {[['carreras','Carreras','book'],['materias','Materias','grid'],['secciones','Secciones','calendar'],['aulas','Aulas','building']].map(([k,l,i])=>(
            <button key={k} className={`demo-nav-item ${nav===k?'active':''}`} onClick={()=>setNav(k)}>
              <span className="ico"><Icon name={i} size={15}/></span> {l}
            </button>
          ))}
          <div className="demo-nav-group">Análisis</div>
          {[['reportes','Reportes','chart'],['prelaciones','Mapa de prelaciones','map']].map(([k,l,i])=>(
            <button key={k} className={`demo-nav-item ${nav===k?'active':''}`} onClick={()=>setNav(k)}>
              <span className="ico"><Icon name={i} size={15}/></span> {l}
            </button>
          ))}
        </nav>
        <div className="demo-sidebar-foot">
          <span className="avatar sm avatar-1">AM</span>
          <div style={{ flex:1, color:'var(--sidebar-fg)', fontSize: 12 }}>
            <div style={{ fontWeight: 600 }}>Alicia Márquez</div>
            <div style={{ color:'var(--sidebar-muted)', fontSize: 11 }}>admin@cacao</div>
          </div>
          <Icon name="logout" size={14}/>
        </div>
      </aside>

      {/* Main */}
      <div className="demo-main">
        <div className="demo-top">
          <nav className="crumbs" style={{ color:'rgba(255,255,255,0.7)' }}>
            <span style={{ color:'rgba(255,255,255,0.5)' }}>Admin</span>
            <span className="sep" style={{ color:'rgba(255,255,255,0.3)' }}>/</span>
            <span style={{ color:'#fff', fontWeight: 600 }}>Dashboard</span>
          </nav>
          <span className="spacer"/>
          <div className="search">
            <Icon name="search" size={14}/>
            <input placeholder="Buscar estudiantes, secciones, materias…"/>
            <span className="kbd">⌘K</span>
          </div>
          <button className="top-action" aria-label="Notificaciones">
            <Icon name="bell" size={14}/>
            <span className="notif-dot"/>
          </button>
          <button className="top-action">
            <Icon name="plus" size={14}/> Nuevo
          </button>
        </div>

        <div className="demo-body">
          <div style={{ display:'flex', justifyContent:'space-between', alignItems:'flex-start', gap: 24 }}>
            <div>
              <h1>Bienvenida, Alicia</h1>
              <div className="sub">Resumen del período <strong style={{ color:'var(--accent)' }}>2026-I</strong> · <span className="mono">{new Date().toLocaleDateString('es-VE', { day:'2-digit', month:'long', year:'numeric' })}</span></div>
            </div>
            <div className="row" style={{ gap: 8 }}>
              <div className="segmented">
                {['Semana','Mes','Período'].map(v => (
                  <button key={v} className={range===v.toLowerCase()?'active':''} onClick={()=>setRange(v.toLowerCase())}>{v}</button>
                ))}
              </div>
              <Button variant="secondary" icon="download" size="sm">Exportar</Button>
              <Button variant="primary" icon="plus" size="sm">Nueva inscripción</Button>
            </div>
          </div>

          {/* Tira de horario del día */}
          <TodaySchedule/>

          <div className="stats-grid">
            <div className="stat accent">
              <span className="stat-label">Inscripciones activas</span>
              <span className="stat-value">1.248</span>
              <span className="stat-delta up">▲ 12.4%</span>
              <span className="stat-footer">98 pendientes de aprobación</span>
            </div>
            <div className="stat">
              <span className="stat-label">Estudiantes totales</span>
              <span className="stat-value">3.412</span>
              <span className="stat-delta up">▲ 4.1% este mes</span>
              <span className="stat-footer">312 nuevos ingresos</span>
            </div>
            <div className="stat">
              <span className="stat-label">Índice de aprobación</span>
              <span className="stat-value">84,2<span style={{ fontSize: 18, color:'var(--text-muted)' }}> %</span></span>
              <span className="stat-delta down">▼ 2.3%</span>
              <span className="stat-footer">7 materias bajo 60%</span>
            </div>
            <div className="stat">
              <span className="stat-label">Ocupación de aulas</span>
              <span className="stat-value">76<span style={{ fontSize: 18, color:'var(--text-muted)' }}> %</span></span>
              <span className="stat-delta up">▲ 3 aulas en pico</span>
              <span className="stat-footer">9 laboratorios libres</span>
            </div>
          </div>

          <div className="dash-grid">
            {/* Inscripciones recientes */}
            <div className="card">
              <div className="card-head">
                <div><h3>Inscripciones recientes</h3><div className="sub">Últimas 24 horas</div></div>
                <Button variant="link">Ver todas →</Button>
              </div>
              <table className="table">
                <thead>
                  <tr><th>Estudiante</th><th>Carrera</th><th>Materia</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody>
                  {ENR.map((r,i) => (
                    <tr key={i}>
                      <td>
                        <div style={{ display:'flex', alignItems:'center', gap: 10 }}>
                          <span className={`avatar sm avatar-${(i%5)+1}`}>{r.s.split(' ').map(x=>x[0]).slice(0,2).join('')}</span>
                          <span style={{ fontWeight: 500 }}>{r.s}</span>
                        </div>
                      </td>
                      <td>{r.car}</td>
                      <td>{r.sub}</td>
                      <td><Badge variant={STB[r.st].v} dot>{STB[r.st].l}</Badge></td>
                      <td style={{ textAlign:'right' }}>
                        {r.st==='pending' ? (
                          <div className="row-actions">
                            <Button variant="ghost" size="sm" icon="check" iconOnly aria-label="Aprobar"/>
                            <Button variant="ghost" size="sm" icon="x" iconOnly aria-label="Rechazar"/>
                          </div>
                        ) : <Button variant="ghost" size="sm" icon="chevronRight" iconOnly aria-label="Ver"/>}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Pendientes / acciones */}
            <div className="stack">
              <div className="card">
                <div className="card-head"><h3>Requieren tu atención</h3></div>
                <div className="list" style={{ border:'none', borderRadius:0 }}>
                  <div className="list-item">
                    <span className="avatar sm" style={{ background:'var(--warning-bg)', color:'var(--warning-fg)' }}><Icon name="alert" size={14}/></span>
                    <div className="grow">
                      <div className="li-title">98 inscripciones pendientes</div>
                      <div className="li-meta">Validación manual requerida</div>
                    </div>
                    <Button variant="secondary" size="sm">Revisar</Button>
                  </div>
                  <div className="list-item">
                    <span className="avatar sm" style={{ background:'var(--danger-bg)', color:'var(--danger-fg)' }}><Icon name="x" size={14}/></span>
                    <div className="grow">
                      <div className="li-title">Cupo agotado en 3 secciones</div>
                      <div className="li-meta">Bases de Datos · Algoritmos II · Física II</div>
                    </div>
                    <Button variant="secondary" size="sm">Ver</Button>
                  </div>
                  <div className="list-item">
                    <span className="avatar sm" style={{ background:'var(--info-bg)', color:'var(--info-fg)' }}><Icon name="calendar" size={14}/></span>
                    <div className="grow">
                      <div className="li-title">Cierre de período en 12 días</div>
                      <div className="li-meta">Carga de notas finales pendiente en 24 secciones</div>
                    </div>
                    <Button variant="secondary" size="sm">Ir</Button>
                  </div>
                </div>
              </div>

              <div className="card">
                <div className="card-head"><h3>Top profesores evaluados</h3><Button variant="link">Todos →</Button></div>
                <div className="list" style={{ border:'none' }}>
                  {[
                    { n:'Prof. Martín Álvarez', d:'Dep. Computación · 4 secciones', s:4.8 },
                    { n:'Prof. Lucía Campos',   d:'Dep. Matemáticas · 3 secciones', s:4.7 },
                    { n:'Prof. Héctor Rojas',   d:'Dep. Civil · 2 secciones', s:4.5 },
                  ].map((p,i) => (
                    <div key={i} className="list-item">
                      <span className={`avatar sm avatar-${i+2}`}>{p.n.replace('Prof. ','').split(' ').map(x=>x[0]).slice(0,2).join('')}</span>
                      <div className="grow">
                        <div className="li-title">{p.n}</div>
                        <div className="li-meta">{p.d}</div>
                      </div>
                      <div style={{ display:'flex', alignItems:'center', gap: 4, fontWeight: 600, color:'var(--accent)', fontSize: 13 }}>
                        <Icon name="star" size={12}/> {p.s.toFixed(1)}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>

          <div className="dash-grid-3">
            {/* Ocupación de aulas */}
            <div className="card">
              <div className="card-head"><h3>Ocupación de aulas</h3><Badge variant="neutral">Lun · 08:00</Badge></div>
              <div className="card-body">
                <div className="stack" style={{ gap: 12 }}>
                  {[
                    { c:'EA-304', o:28, t:30 },
                    { c:'EA-101', o:45, t:50 },
                    { c:'Lab LC-2', o:18, t:24 },
                    { c:'EB-207', o:12, t:40 },
                    { c:'Lab LC-1', o:22, t:24 },
                  ].map(a => {
                    const pct = Math.round(a.o/a.t*100);
                    return (
                      <div key={a.c}>
                        <div style={{ display:'flex', justifyContent:'space-between', fontSize: 13, marginBottom: 4 }}>
                          <span style={{ fontWeight: 500 }}>{a.c}</span>
                          <span className="mono muted">{a.o}/{a.t} · {pct}%</span>
                        </div>
                        <div className="progress">
                          <span style={{ width: `${pct}%`, background: pct>=90 ? 'var(--danger)' : pct>=75 ? 'var(--accent)' : 'var(--success)' }}/>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>

            {/* Mapa mini de prelaciones */}
            <div className="card">
              <div className="card-head"><h3>Tu progreso · ejemplo</h3><Badge variant="accent">Ing. Sistemas</Badge></div>
              <div className="prelmap">
                <div className="col">
                  <div className="col-head">Sem 1</div>
                  <div className="node done"><span className="code">MAT-101</span>Matemática I</div>
                  <div className="node done"><span className="code">PRG-101</span>Programación I</div>
                </div>
                <div className="col">
                  <div className="col-head">Sem 2</div>
                  <div className="node done"><span className="code">MAT-102</span>Matemática II</div>
                  <div className="node active"><span className="code">PRG-102</span>Programación II</div>
                </div>
                <div className="col">
                  <div className="col-head">Sem 3</div>
                  <div className="node active"><span className="code">ALG-201</span>Algoritmos II</div>
                  <div className="node locked"><span className="code">BDD-301</span>Bases de Datos</div>
                </div>
                <div className="col">
                  <div className="col-head">Sem 4</div>
                  <div className="node locked"><span className="code">RED-401</span>Redes</div>
                  <div className="node locked"><span className="code">SO-401</span>Sist. Operativos</div>
                </div>
              </div>
              <div className="card-foot">
                <div className="row" style={{ gap: 12, fontSize: 11 }}>
                  <span style={{ display:'flex', alignItems:'center', gap: 4 }}><span style={{ width: 8, height:8, borderRadius:2, background:'var(--success)' }}/> Aprobada</span>
                  <span style={{ display:'flex', alignItems:'center', gap: 4 }}><span style={{ width: 8, height:8, borderRadius:2, background:'var(--accent)' }}/> En curso</span>
                  <span style={{ display:'flex', alignItems:'center', gap: 4 }}><span style={{ width: 8, height:8, borderRadius:2, background:'var(--border-strong)' }}/> Bloqueada</span>
                </div>
              </div>
            </div>

            {/* Próximas evaluaciones */}
            <div className="card">
              <div className="card-head"><h3>Próximas evaluaciones</h3></div>
              <div className="list" style={{ border:'none' }}>
                {[
                  { t:'Quiz · Complejidad algorítmica', d:'Algoritmos II · Sec. A', date:'Mañana 10:00', k:'warning' },
                  { t:'Entrega · Diagrama ER', d:'Bases de Datos · Sec. B', date:'En 3 días', k:'info' },
                  { t:'Test · Redacción', d:'Redacción Periodística', date:'En 5 días', k:'neutral' },
                  { t:'Quiz · Derivadas parciales', d:'Matemática III', date:'En 1 semana', k:'neutral' },
                ].map((e,i)=>(
                  <div key={i} className="list-item">
                    <span className="avatar sm" style={{ background:'var(--bg-surface-2)', color:'var(--text-secondary)', borderRadius: 6 }}><Icon name="clock" size={13}/></span>
                    <div className="grow">
                      <div className="li-title">{e.t}</div>
                      <div className="li-meta">{e.d}</div>
                    </div>
                    <Badge variant={e.k}>{e.date}</Badge>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

Object.assign(window, { DashboardDemo });
