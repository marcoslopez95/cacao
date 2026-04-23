/* global React, Icon, Button, Badge, Isotipo */
const { useState: useStateP, useMemo: useMemoP, useRef: useRefP, useEffect: useEffectP } = React;

// ============== DATA ==============
const ENROLLMENTS = [
  { id: 1, student: 'María Rodríguez', code: 'V-28471293', career: 'Ing. Sistemas', subject: 'Algoritmos II', semester: 3, status: 'pending',  period: '2026-I', date: '2026-04-18' },
  { id: 2, student: 'José Pérez',      code: 'V-30128477', career: 'Ing. Civil',    subject: 'Mecánica de Materiales', semester: 4, status: 'approved', period: '2026-I', date: '2026-04-17' },
  { id: 3, student: 'Andrea Quintero', code: 'V-29382911', career: 'Administración',subject: 'Microeconomía', semester: 2, status: 'approved', period: '2026-I', date: '2026-04-17' },
  { id: 4, student: 'Luis Hernández',  code: 'V-27883012', career: 'Ing. Sistemas', subject: 'Bases de Datos', semester: 5, status: 'rejected', period: '2026-I', date: '2026-04-16' },
  { id: 5, student: 'Valentina Gómez', code: 'V-30911828', career: 'Comunicación',  subject: 'Redacción Periodística', semester: 1, status: 'pending',  period: '2026-I', date: '2026-04-16' },
  { id: 6, student: 'Carlos Mendoza',  code: 'V-28012339', career: 'Ing. Civil',    subject: 'Topografía', semester: 3, status: 'approved', period: '2026-I', date: '2026-04-15' },
  { id: 7, student: 'Gabriela Silva',  code: 'V-29711244', career: 'Ing. Sistemas', subject: 'Redes de Computadoras', semester: 6, status: 'pending',  period: '2026-I', date: '2026-04-15' },
  { id: 8, student: 'Ricardo Blanco',  code: 'V-27442187', career: 'Administración',subject: 'Contabilidad II', semester: 3, status: 'approved', period: '2026-I', date: '2026-04-14' },
];

const STATUS_BADGE = {
  pending:  { variant: 'warning', dot: true, label: 'Pendiente' },
  approved: { variant: 'success', dot: true, label: 'Aprobada' },
  rejected: { variant: 'danger',  dot: true, label: 'Rechazada' },
};

// ============== TABLES ==============
const EnrollmentsTable = () => {
  const [sort, setSort] = useStateP({ key: 'date', dir: 'desc' });
  const [query, setQuery] = useStateP('');
  const [status, setStatus] = useStateP('all');
  const [selected, setSelected] = useStateP(new Set());
  const [page, setPage] = useStateP(1);
  const perPage = 5;

  const filtered = useMemoP(() => {
    let rows = ENROLLMENTS.slice();
    if (query) rows = rows.filter(r =>
      (r.student + ' ' + r.code + ' ' + r.subject + ' ' + r.career).toLowerCase().includes(query.toLowerCase()));
    if (status !== 'all') rows = rows.filter(r => r.status === status);
    rows.sort((a,b) => {
      const av = a[sort.key], bv = b[sort.key];
      if (av < bv) return sort.dir === 'asc' ? -1 : 1;
      if (av > bv) return sort.dir === 'asc' ? 1 : -1;
      return 0;
    });
    return rows;
  }, [sort, query, status]);

  const pages = Math.max(1, Math.ceil(filtered.length / perPage));
  const pageRows = filtered.slice((page-1)*perPage, page*perPage);

  useEffectP(() => { if (page > pages) setPage(1); }, [pages, page]);

  const toggleSort = (key) => {
    if (sort.key === key) setSort({ key, dir: sort.dir === 'asc' ? 'desc' : 'asc' });
    else setSort({ key, dir: 'asc' });
  };
  const sortInd = (key) => sort.key !== key ? '↕' : sort.dir === 'asc' ? '↑' : '↓';
  const toggleRow = (id) => {
    const s = new Set(selected); s.has(id) ? s.delete(id) : s.add(id); setSelected(s);
  };
  const allOn = pageRows.length > 0 && pageRows.every(r => selected.has(r.id));
  const toggleAll = () => {
    const s = new Set(selected);
    if (allOn) pageRows.forEach(r => s.delete(r.id));
    else pageRows.forEach(r => s.add(r.id));
    setSelected(s);
  };

  return (
    <div className="table-wrap">
      <div className="table-toolbar">
        <span className="title">Inscripciones</span>
        <span className="count">{filtered.length} registros</span>
        <span className="grow"/>
        <div className="input-group has-left" style={{ width: 240 }}>
          <span className="ig-icon"><Icon name="search" size={14}/></span>
          <input className="input" placeholder="Buscar…" value={query} onChange={e=>setQuery(e.target.value)}/>
        </div>
        <select className="select" style={{ width: 150 }} value={status} onChange={e=>setStatus(e.target.value)}>
          <option value="all">Todos los estados</option>
          <option value="pending">Pendientes</option>
          <option value="approved">Aprobadas</option>
          <option value="rejected">Rechazadas</option>
        </select>
        <Button variant="secondary" icon="download" size="sm">Exportar</Button>
        <Button variant="primary" icon="plus" size="sm">Nueva inscripción</Button>
      </div>

      {selected.size > 0 && (
        <div style={{ padding:'8px 16px', borderBottom:'1px solid var(--border)', background:'var(--accent-soft)', display:'flex', alignItems:'center', gap:12 }}>
          <strong style={{ fontSize: 13, color:'var(--accent)' }}>{selected.size} seleccionadas</strong>
          <span className="grow" style={{ flex:1 }}/>
          <Button variant="secondary" size="sm" icon="check">Aprobar en masa</Button>
          <Button variant="secondary" size="sm" icon="x">Rechazar</Button>
          <button className="btn btn-ghost btn-sm" onClick={()=>setSelected(new Set())}>Deseleccionar</button>
        </div>
      )}

      <div style={{ overflowX:'auto' }}>
        <table className="table">
          <thead>
            <tr>
              <th style={{ width: 40 }}>
                <label className="check" style={{ margin: 0 }}>
                  <input type="checkbox" checked={allOn} onChange={toggleAll}/><span className="box"/>
                </label>
              </th>
              <th className={`sortable ${sort.key==='student' ? 'sorted' : ''}`} onClick={()=>toggleSort('student')}>Estudiante <span className="sort-ind">{sortInd('student')}</span></th>
              <th>Cédula</th>
              <th className={`sortable ${sort.key==='career' ? 'sorted' : ''}`} onClick={()=>toggleSort('career')}>Carrera <span className="sort-ind">{sortInd('career')}</span></th>
              <th className={`sortable ${sort.key==='subject' ? 'sorted' : ''}`} onClick={()=>toggleSort('subject')}>Materia <span className="sort-ind">{sortInd('subject')}</span></th>
              <th className="num">Sem.</th>
              <th>Estado</th>
              <th className={`sortable ${sort.key==='date' ? 'sorted' : ''}`} onClick={()=>toggleSort('date')}>Fecha <span className="sort-ind">{sortInd('date')}</span></th>
              <th style={{ textAlign:'right' }}>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {pageRows.map(r => {
              const st = STATUS_BADGE[r.status];
              const isSel = selected.has(r.id);
              return (
                <tr key={r.id} className={isSel ? 'selected' : ''}>
                  <td>
                    <label className="check" style={{ margin: 0 }}>
                      <input type="checkbox" checked={isSel} onChange={()=>toggleRow(r.id)}/><span className="box"/>
                    </label>
                  </td>
                  <td>
                    <div style={{ display:'flex', alignItems:'center', gap: 10 }}>
                      <span className={`avatar sm avatar-${(r.id % 5)+1}`}>{r.student.split(' ').map(x=>x[0]).slice(0,2).join('')}</span>
                      <span style={{ fontWeight: 500 }}>{r.student}</span>
                    </div>
                  </td>
                  <td className="mono" style={{ fontSize: 12, color:'var(--text-secondary)' }}>{r.code}</td>
                  <td>{r.career}</td>
                  <td>{r.subject}</td>
                  <td className="num">{r.semester}</td>
                  <td><Badge variant={st.variant} dot={st.dot}>{st.label}</Badge></td>
                  <td className="mono" style={{ fontSize: 12, color:'var(--text-secondary)' }}>{r.date}</td>
                  <td>
                    <div className="row-actions">
                      <Button variant="ghost" size="sm" icon="eye" iconOnly aria-label="Ver"/>
                      <Button variant="ghost" size="sm" icon="edit" iconOnly aria-label="Editar"/>
                      <Button variant="ghost" size="sm" icon="moreV" iconOnly aria-label="Más"/>
                    </div>
                  </td>
                </tr>
              );
            })}
            {pageRows.length === 0 && (
              <tr><td colSpan={9}>
                <div className="empty">
                  <div className="empty-icon"><Icon name="search" size={20}/></div>
                  <h4>Sin resultados</h4>
                  <p>Ajusta los filtros o la búsqueda</p>
                </div>
              </td></tr>
            )}
          </tbody>
        </table>
      </div>
      <div className="table-foot">
        <span>Mostrando <strong>{pageRows.length}</strong> de <strong>{filtered.length}</strong></span>
        <div className="pager">
          <button onClick={()=>setPage(Math.max(1,page-1))} disabled={page===1} aria-label="Anterior"><Icon name="chevronLeft" size={14}/></button>
          {[...Array(pages)].map((_,i) => (
            <button key={i} className={page===i+1 ? 'active' : ''} onClick={()=>setPage(i+1)}>{i+1}</button>
          ))}
          <button onClick={()=>setPage(Math.min(pages,page+1))} disabled={page===pages} aria-label="Siguiente"><Icon name="chevronRight" size={14}/></button>
        </div>
      </div>
    </div>
  );
};

const TablesSection = () => (
  <section className="ds-section">
    <header className="ds-section-head">
      <h2>Tablas</h2>
      <p>Tabla de inscripciones — completamente funcional: ordenar, filtrar, buscar, seleccionar en masa y paginar.</p>
    </header>
    <EnrollmentsTable/>

    <div style={{ marginTop: 24 }} className="ds-subsection">
      <h3 className="ds-subsection-title">Tabla simple compacta · notas por sección</h3>
      <div className="table-wrap">
        <table className="table">
          <thead>
            <tr>
              <th>Estudiante</th><th className="num">Parcial 1</th><th className="num">Parcial 2</th>
              <th className="num">Quiz</th><th className="num">Final</th><th className="num">Nota</th><th>Estado</th>
            </tr>
          </thead>
          <tbody>
            {[
              { n:'María Rodríguez', p1:18, p2:17, q:19, f:18, nf:18.0, st:'approved' },
              { n:'José Pérez',      p1:12, p2:14, q:15, f:13, nf:13.5, st:'approved' },
              { n:'Andrea Quintero', p1:9,  p2:11, q:10, f:8,  nf:9.5,  st:'rejected' },
              { n:'Luis Hernández',  p1:16, p2:17, q:18, f:17, nf:17.0, st:'approved' },
              { n:'Gabriela Silva',  p1:15, p2:16, q:14, f:15, nf:15.0, st:'approved' },
            ].map((r,i)=>(
              <tr key={i}>
                <td>{r.n}</td>
                <td className="num">{r.p1.toFixed(1)}</td>
                <td className="num">{r.p2.toFixed(1)}</td>
                <td className="num">{r.q.toFixed(1)}</td>
                <td className="num">{r.f.toFixed(1)}</td>
                <td className="num" style={{ fontWeight:700, color: r.nf>=10 ? 'var(--success)':'var(--danger)' }}>{r.nf.toFixed(2)}</td>
                <td>{r.nf>=10 ? <Badge variant="success" dot>Aprobó</Badge> : <Badge variant="danger" dot>Reprobó</Badge>}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  </section>
);

// ============== LISTS ==============
const ListsSection = () => (
  <section className="ds-section">
    <header className="ds-section-head">
      <h2>Listas</h2>
      <p>Para cuando una tabla es excesiva — feeds de actividad, materiales de apoyo, recordatorios.</p>
    </header>

    <div className="dash-grid">
      <div className="ds-subsection" style={{ margin: 0 }}>
        <h3 className="ds-subsection-title">Lista con avatar + acción</h3>
        <div className="list">
          {[
            { name:'María Rodríguez', meta:'V-28471293 · Ing. Sistemas · 3er semestre', tag:'approved' },
            { name:'Andrea Quintero', meta:'V-29382911 · Administración · 2do semestre', tag:'approved' },
            { name:'Valentina Gómez', meta:'V-30911828 · Comunicación Social · 1er semestre', tag:'pending' },
            { name:'Luis Hernández',  meta:'V-27883012 · Ing. Sistemas · 5to semestre', tag:'rejected' },
          ].map((s,i)=>(
            <div key={i} className="list-item">
              <span className={`avatar avatar-${(i%5)+1}`}>{s.name.split(' ').map(x=>x[0]).slice(0,2).join('')}</span>
              <div className="grow">
                <div className="li-title">{s.name}</div>
                <div className="li-meta">{s.meta}</div>
              </div>
              <Badge variant={STATUS_BADGE[s.tag].variant} dot>{STATUS_BADGE[s.tag].label}</Badge>
              <Button variant="ghost" icon="chevronRight" iconOnly size="sm" aria-label="Ver ficha"/>
            </div>
          ))}
        </div>
      </div>

      <div className="ds-subsection" style={{ margin: 0 }}>
        <h3 className="ds-subsection-title">Materiales de apoyo</h3>
        <div className="list">
          {[
            { icon:'file', title:'Guía de Algoritmos — Capítulo 3', meta:'PDF · 2.4 MB · subido hace 2 días'},
            { icon:'file', title:'Tarea 2 — Complejidad Big-O', meta:'DOCX · 428 KB · subido hace 5 días'},
            { icon:'folder', title:'Carpeta de laboratorio — Semana 4', meta:'7 archivos · actualizado hoy'},
            { icon:'code', title:'Repositorio de ejemplos', meta:'Enlace externo · github.com/…'},
          ].map((m,i)=>(
            <div key={i} className="list-item">
              <span className="avatar" style={{ background:'var(--accent-soft)', color:'var(--accent)', borderRadius: 8 }}><Icon name={m.icon} size={16}/></span>
              <div className="grow">
                <div className="li-title">{m.title}</div>
                <div className="li-meta">{m.meta}</div>
              </div>
              <Button variant="secondary" size="sm" icon="download">Descargar</Button>
            </div>
          ))}
        </div>
      </div>
    </div>

    <div className="dash-grid" style={{ marginTop: 16 }}>
      <div className="ds-subsection" style={{ margin: 0 }}>
        <h3 className="ds-subsection-title">Actividad reciente</h3>
        <div className="list">
          {[
            { who:'Prof. Martín Álvarez', what:'publicó un nuevo material en', target:'Algoritmos II · Sección A', time:'hace 12 min', color:'accent' },
            { who:'admin@cacao', what:'aprobó la inscripción de', target:'José Pérez · Mecánica de Materiales', time:'hace 1 h', color:'success' },
            { who:'Valentina Gómez', what:'envió su entrega de', target:'Redacción Periodística · Tarea 3', time:'hace 3 h', color:'info' },
            { who:'Sistema', what:'marcó cupo agotado en', target:'Bases de Datos · Sección B', time:'hace 5 h', color:'warning' },
          ].map((a,i)=>(
            <div key={i} className="list-item">
              <span className="avatar sm" style={{
                background: `var(--${a.color === 'accent' ? 'accent-soft' : a.color+'-bg'})`,
                color: `var(--${a.color === 'accent' ? 'accent' : a.color+'-fg'})`
              }}>
                <Icon name={a.color==='success'?'check':a.color==='warning'?'alert':a.color==='info'?'upload':'bell'} size={14}/>
              </span>
              <div className="grow">
                <div className="li-title"><strong>{a.who}</strong> <span style={{ color:'var(--text-secondary)', fontWeight:400 }}>{a.what}</span> {a.target}</div>
                <div className="li-meta">{a.time}</div>
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="ds-subsection" style={{ margin: 0 }}>
        <h3 className="ds-subsection-title">Lista simple (sidebar)</h3>
        <div className="list" style={{ padding: 6 }}>
          {['Dashboard','Carreras y pensums','Materias','Secciones','Aulas','Profesores','Estudiantes'].map((l,i)=>(
            <button key={i} className="ds-nav-item" style={{ borderRadius: 6 }} aria-current={i===2 ? 'page' : undefined}>
              <span className="dot"/><span>{l}</span>
              {i===2 && <span style={{ marginLeft:'auto', fontSize: 11, color:'var(--text-muted)' }} className="mono">142</span>}
            </button>
          ))}
        </div>
      </div>
    </div>
  </section>
);

// ============== CARDS ==============
const CardsSection = () => (
  <section className="ds-section">
    <header className="ds-section-head">
      <h2>Cards</h2>
      <p>Stat cards para métricas, content cards para contenido de sección.</p>
    </header>

    <div className="ds-subsection">
      <h3 className="ds-subsection-title">Stat cards</h3>
      <div className="stats-grid">
        <div className="stat accent">
          <span className="stat-label">Inscripciones activas</span>
          <span className="stat-value">1.248</span>
          <span className="stat-delta up">▲ 12.4% vs período anterior</span>
          <span className="stat-footer">98 pendientes de aprobación</span>
        </div>
        <div className="stat">
          <span className="stat-label">Estudiantes</span>
          <span className="stat-value">3.412</span>
          <span className="stat-delta up">▲ 4.1% este mes</span>
          <span className="stat-footer">312 nuevos ingresos</span>
        </div>
        <div className="stat">
          <span className="stat-label">Aprobación por materia</span>
          <span className="stat-value">84,2<span style={{ fontSize: 18, color:'var(--text-muted)' }}> %</span></span>
          <span className="stat-delta down">▼ 2.3% vs 2025-II</span>
          <span className="stat-footer">7 materias con índice &lt; 60%</span>
        </div>
        <div className="stat">
          <span className="stat-label">Ocupación de aulas</span>
          <span className="stat-value">76<span style={{ fontSize: 18, color:'var(--text-muted)' }}> %</span></span>
          <span className="stat-delta up">▲ 3 aulas en pico</span>
          <span className="stat-footer">9 laboratorios disponibles</span>
        </div>
      </div>
    </div>

    <div className="ds-subsection">
      <h3 className="ds-subsection-title">Content cards</h3>
      <div className="dash-grid-3">
        <div className="card">
          <div className="card-head">
            <div>
              <h3>Algoritmos II</h3>
              <div className="sub">Sección A · Prof. Álvarez</div>
            </div>
            <Badge variant="accent">Activa</Badge>
          </div>
          <div className="card-body">
            <div className="stack" style={{ gap: 10 }}>
              <div style={{ display:'flex', justifyContent:'space-between', fontSize: 13 }}>
                <span className="muted">Cupo</span><span className="mono">28 / 30</span>
              </div>
              <div className="progress"><span style={{ width:'93%' }}/></div>
              <div style={{ display:'flex', justifyContent:'space-between', fontSize: 13, marginTop: 6 }}>
                <span className="muted">Horario</span><span>Lun · Mié · 08:00–10:00</span>
              </div>
              <div style={{ display:'flex', justifyContent:'space-between', fontSize: 13 }}>
                <span className="muted">Aula</span><span>EA-304 · Lab LC-2</span>
              </div>
            </div>
          </div>
          <div className="card-foot">
            <span>3 evaluaciones pendientes</span>
            <Button variant="link">Abrir sección →</Button>
          </div>
        </div>

        <div className="card">
          <div className="card-head">
            <div><h3>María Rodríguez</h3><div className="sub">V-28471293 · Ing. Sistemas</div></div>
            <Button variant="ghost" icon="moreV" iconOnly size="sm"/>
          </div>
          <div className="card-body" style={{ display:'flex', gap: 16 }}>
            <span className="avatar xl avatar-1">MR</span>
            <div className="stack" style={{ gap: 6, fontSize: 13 }}>
              <div><span className="muted">Semestre · </span><strong>3</strong></div>
              <div><span className="muted">Promedio · </span><strong style={{ color:'var(--success)' }}>17,24</strong></div>
              <div><span className="muted">Inscritas · </span><strong>5 materias</strong></div>
              <div><span className="muted">Asistencia · </span><strong>96%</strong></div>
            </div>
          </div>
          <div className="card-foot">
            <span className="mono" style={{ fontSize: 11 }}>Desde 2024-II</span>
            <Button variant="link">Ver perfil →</Button>
          </div>
        </div>

        <div className="card">
          <div className="card-head">
            <div><h3>Bases de Datos</h3><div className="sub">Materia · Semestre 5</div></div>
            <Badge variant="warning" dot>Cupo lleno</Badge>
          </div>
          <div className="card-body">
            <div className="stack" style={{ fontSize: 13 }}>
              <div><strong>Prelaciones requeridas</strong></div>
              <div className="row" style={{ gap: 6 }}>
                <Badge variant="outline">Estructuras I</Badge>
                <Badge variant="outline">Algoritmos I</Badge>
                <Badge variant="outline">Lógica</Badge>
              </div>
              <div style={{ marginTop: 8 }}><strong>3 secciones</strong> · 90 cupos totales</div>
            </div>
          </div>
          <div className="card-foot">
            <span>Dep. Computación</span>
            <Button variant="link">Ver secciones →</Button>
          </div>
        </div>
      </div>
    </div>
  </section>
);

// ============== NAVIGATION ==============
const NavigationSection = () => {
  const [tab, setTab] = useStateP('personales');
  const [seg, setSeg] = useStateP('semana');
  return (
    <section className="ds-section">
      <header className="ds-section-head">
        <h2>Navegación</h2>
        <p>Tabs, breadcrumbs y segmented controls.</p>
      </header>

      <div className="ds-subsection">
        <h3 className="ds-subsection-title">Tabs</h3>
        <div className="spec">
          <div className="spec-body stack" style={{ alignItems:'stretch' }}>
            <div className="tabs">
              {[
                ['personales','Datos personales'],
                ['geograficos','Geográficos'],
                ['socio','Socioeconómicos',2],
                ['educativos','Educativos'],
                ['rep','Representantes',1],
              ].map(([k,l,b]) => (
                <button key={k} className={`tab ${tab===k?'active':''}`} onClick={()=>setTab(k)}>
                  {l}
                  {b && <Badge variant={tab===k ? 'accent':'neutral'}>{b}</Badge>}
                </button>
              ))}
            </div>
            <div style={{ padding: 16, background:'var(--bg-surface-2)', borderRadius: 6, fontSize: 13, color:'var(--text-secondary)' }}>
              Contenido del tab <strong style={{ color:'var(--accent)' }}>{tab}</strong> — aquí iría el formulario de esa sección del perfil del estudiante.
            </div>
          </div>
        </div>
      </div>

      <div className="ds-subsection">
        <h3 className="ds-subsection-title">Breadcrumbs</h3>
        <div className="spec"><div className="spec-body">
          <nav className="crumbs">
            <a href="#"><Icon name="home" size={12}/></a>
            <span className="sep">/</span>
            <a href="#">Admin</a>
            <span className="sep">/</span>
            <a href="#">Carreras</a>
            <span className="sep">/</span>
            <a href="#">Ing. Sistemas</a>
            <span className="sep">/</span>
            <span className="current">Pensum 2024</span>
          </nav>
        </div></div>
      </div>

      <div className="ds-subsection">
        <h3 className="ds-subsection-title">Segmented control</h3>
        <div className="spec"><div className="spec-body">
          <div className="segmented">
            {['Día','Semana','Mes','Período'].map(v => (
              <button key={v} className={seg===v.toLowerCase()?'active':''} onClick={()=>setSeg(v.toLowerCase())}>{v}</button>
            ))}
          </div>
        </div></div>
      </div>
    </section>
  );
};

// ============== FEEDBACK ==============
const FeedbackSection = () => {
  const [modal, setModal] = useStateP(false);
  const [toasts, setToasts] = useStateP([]);
  const toastId = useRefP(0);
  const push = (variant, title, msg) => {
    const id = ++toastId.current;
    setToasts(t => [...t, { id, variant, title, msg }]);
    setTimeout(() => setToasts(t => t.filter(x => x.id !== id)), 4200);
  };

  return (
    <section className="ds-section">
      <header className="ds-section-head">
        <h2>Feedback</h2>
        <p>Alerts, toasts, estados vacíos, skeletons y modales.</p>
      </header>

      <div className="ds-subsection">
        <h3 className="ds-subsection-title">Alerts</h3>
        <div className="spec"><div className="spec-body stack">
          <div className="alert alert-info">
            <span className="alert-icon"><Icon name="info" size={16}/></span>
            <div className="alert-body">
              <div className="alert-title">Período de inscripciones abierto</div>
              Las inscripciones para 2026-II están disponibles hasta el 15 de julio de 2026.
            </div>
            <button className="alert-close" aria-label="Cerrar"><Icon name="x" size={14}/></button>
          </div>
          <div className="alert alert-success">
            <span className="alert-icon"><Icon name="check" size={16}/></span>
            <div className="alert-body"><div className="alert-title">Inscripción aprobada</div>María Rodríguez · Algoritmos II · Sección A</div>
          </div>
          <div className="alert alert-warning">
            <span className="alert-icon"><Icon name="alert" size={16}/></span>
            <div className="alert-body"><div className="alert-title">Cupo al 93%</div>Quedan 2 puestos en Algoritmos II — Sección A.</div>
          </div>
          <div className="alert alert-danger">
            <span className="alert-icon"><Icon name="x" size={16}/></span>
            <div className="alert-body">
              <div className="alert-title">Prelación no cumplida</div>
              No puedes inscribir <strong>Matemática II</strong> hasta aprobar <strong>Matemática I</strong>.
            </div>
          </div>
        </div></div>
      </div>

      <div className="ds-subsection">
        <h3 className="ds-subsection-title">Toasts</h3>
        <div className="spec"><div className="spec-body">
          <Button variant="tinta" onClick={()=>push('success','Cambios guardados','Se actualizó el pensum de Ing. Sistemas.')}>Lanzar éxito</Button>
          <Button variant="secondary" onClick={()=>push('warning','Revisa los cupos','3 secciones están al 90% de capacidad.')}>Advertencia</Button>
          <Button variant="danger" onClick={()=>push('danger','Error al procesar','No se pudo rechazar la inscripción #4.')}>Error</Button>
          <Button variant="ghost" onClick={()=>push('default','Material publicado','Guía de Algoritmos — Capítulo 3 ya está disponible.')}>Info</Button>
        </div></div>
      </div>

      <div className="ds-subsection">
        <h3 className="ds-subsection-title">Empty state</h3>
        <div className="spec"><div className="spec-body" style={{ padding: 0, flexDirection:'column' }}>
          <div className="empty" style={{ width: '100%' }}>
            <div className="empty-icon"><Icon name="folder" size={24}/></div>
            <h4>Aún no hay materiales</h4>
            <p>El profesor no ha publicado materiales para esta sección.<br/>Te avisaremos cuando haya novedades.</p>
            <Button variant="secondary" icon="bell">Suscribirse a avisos</Button>
          </div>
        </div></div>
      </div>

      <div className="ds-subsection">
        <h3 className="ds-subsection-title">Loading / skeleton</h3>
        <div className="spec"><div className="spec-body stack">
          {[...Array(3)].map((_,i)=>(
            <div key={i} style={{ display:'flex', gap: 12, alignItems:'center', width:'100%' }}>
              <div className="skeleton" style={{ width: 36, height: 36, borderRadius: '50%' }}/>
              <div style={{ flex:1 }}>
                <div className="skeleton" style={{ width: `${60+i*10}%`, height: 12, marginBottom: 8 }}/>
                <div className="skeleton" style={{ width: '40%', height: 10 }}/>
              </div>
              <div className="skeleton" style={{ width: 70, height: 22, borderRadius: 999 }}/>
            </div>
          ))}
        </div></div>
      </div>

      <div className="ds-subsection">
        <h3 className="ds-subsection-title">Modal / diálogo</h3>
        <div className="spec"><div className="spec-body">
          <Button variant="primary" onClick={()=>setModal(true)}>Aprobar inscripción</Button>
          <Button variant="danger" onClick={()=>setModal('danger')}>Rechazar con motivo</Button>
        </div></div>
      </div>

      {modal && (
        <div className="modal-backdrop" onClick={()=>setModal(false)}>
          <div className="modal" onClick={e=>e.stopPropagation()}>
            <div className="modal-head">
              <h3>{modal === 'danger' ? 'Rechazar inscripción' : 'Confirmar aprobación'}</h3>
              <Button variant="ghost" icon="x" iconOnly size="sm" onClick={()=>setModal(false)} aria-label="Cerrar"/>
            </div>
            <div className="modal-body">
              {modal === 'danger' ? (
                <>
                  <p>Estás a punto de rechazar la inscripción de <strong>María Rodríguez</strong> en <strong>Algoritmos II · Sección A</strong>. El estudiante será notificado por email.</p>
                  <div className="field full" style={{ marginTop: 16 }}>
                    <label className="label">Motivo del rechazo <span className="req">*</span></label>
                    <textarea className="textarea" placeholder="Explica al estudiante por qué se rechaza…"/>
                  </div>
                </>
              ) : (
                <p>Vas a aprobar la inscripción de <strong>María Rodríguez</strong> en <strong>Algoritmos II · Sección A</strong>. El cupo pasará de 28 a 29 estudiantes.</p>
              )}
            </div>
            <div className="modal-foot">
              <Button variant="ghost" onClick={()=>setModal(false)}>Cancelar</Button>
              <Button variant={modal==='danger'?'danger':'primary'} onClick={()=>{ push(modal==='danger'?'danger':'success', modal==='danger'?'Inscripción rechazada':'Inscripción aprobada', 'María Rodríguez · Algoritmos II'); setModal(false); }}>
                {modal==='danger' ? 'Rechazar inscripción' : 'Sí, aprobar'}
              </Button>
            </div>
          </div>
        </div>
      )}

      <div className="toast-stack">
        {toasts.map(t => (
          <div key={t.id} className={`toast ${t.variant}`}>
            <div className="t-body">
              <div className="t-title">{t.title}</div>
              <div style={{ color:'var(--text-secondary)' }}>{t.msg}</div>
            </div>
            <button className="alert-close" onClick={()=>setToasts(ts=>ts.filter(x=>x.id!==t.id))} aria-label="Cerrar"><Icon name="x" size={12}/></button>
          </div>
        ))}
      </div>
    </section>
  );
};

// ============== AVATAR / ISOTIPO ==============
const AvatarsSection = () => (
  <section className="ds-section">
    <header className="ds-section-head">
      <h2>Avatares</h2>
      <p>Iniciales sobre fondos de la paleta tierra. Grupos con solapamiento para secciones numerosas.</p>
    </header>
    <div className="spec"><div className="spec-body">
      <div className="state-group"><span className="state-label">Tamaños</span>
        <div className="row">
          <span className="avatar sm avatar-1">MR</span>
          <span className="avatar avatar-2">JP</span>
          <span className="avatar lg avatar-3">AQ</span>
          <span className="avatar xl avatar-4">LH</span>
        </div>
      </div>
      <div className="state-group"><span className="state-label">Variantes</span>
        <div className="row">
          <span className="avatar avatar-1">MR</span>
          <span className="avatar avatar-2">JP</span>
          <span className="avatar avatar-3">AQ</span>
          <span className="avatar avatar-4">LH</span>
          <span className="avatar avatar-5">VG</span>
        </div>
      </div>
      <div className="state-group"><span className="state-label">Grupo</span>
        <div className="avatar-group">
          <span className="avatar avatar-1">MR</span>
          <span className="avatar avatar-2">JP</span>
          <span className="avatar avatar-3">AQ</span>
          <span className="avatar avatar-4">+5</span>
        </div>
      </div>
    </div></div>
  </section>
);

Object.assign(window, { TablesSection, ListsSection, CardsSection, NavigationSection, FeedbackSection, AvatarsSection });
