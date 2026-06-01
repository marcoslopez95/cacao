/* global React, Icon, SECTION, SESSIONS, ROSTER, ABSENCE_TOTALS, SESSIONS_COUNTED,
          SESSION_STATUS, SESSION_TYPE, ATTENDANCE, CURRENT_PERIOD,
          mainSessions, fmtDate, fmtDateLong, dowShort, isToday, isPast, isFuture, parseDate, MESES, DOW */
// ============================================================
// CACAO · Asistencia — Vista del profesor
// ============================================================
const { useState: useStateP, useMemo: useMemoP } = React;

// ---- pills ----
function StatusPill({ status }) {
  const s = SESSION_STATUS[status];
  return <span className={`att-pill ${s.tone}`}><span className="pdot" />{s.label}</span>;
}
function TypePill({ type }) {
  if (type === 'regular') return null;
  return <span className={`att-pill type ${type}`}>{SESSION_TYPE[type].label}</span>;
}

// ---- attendance bar ----
function AttendanceBar({ present, absent }) {
  const total = present + absent || 1;
  return (
    <div className="att-bar-wrap">
      <div className="att-bar">
        <div className="seg-present" style={{ width: `${(present / total) * 100}%` }} />
        <div className="seg-absent" style={{ width: `${(absent / total) * 100}%` }} />
      </div>
      <div className="att-bar-legend">
        <span><span className="lg-dot present" /><strong>{present}</strong> presente</span>
        <span><span className="lg-dot absent" /><strong>{absent}</strong> ausente</span>
      </div>
    </div>
  );
}

// ---- date block ----
function DateBlock({ iso }) {
  const dt = parseDate(iso);
  return (
    <div className="att-card-date">
      <div className="d-dow">{dowShort(iso)}</div>
      <div className="d-num">{dt.getDate()}</div>
      <div className="d-mon">{MESES[dt.getMonth()]}</div>
    </div>
  );
}

// ---- linked session note ----
function LinkedNote({ session }) {
  if (!session.linked) return null;
  if (session.type === 'makeup') {
    return (
      <div className="att-link-note">
        <Icon name="arrowRight" size={13} />
        <span>Recupera la sesión cancelada del <strong>{fmtDate(session.linked.date)}</strong></span>
      </div>
    );
  }
  if (session.type === 'advance') {
    return (
      <div className="att-link-note">
        <Icon name="arrowRight" size={13} />
        <span>Adelanta la clase del <strong>{fmtDate(session.linked.date)}</strong> · asistencia copiada</span>
      </div>
    );
  }
  // regular recovered/advanced referencing its special session
  if (session.status === 'recovered') {
    return (
      <div className="att-link-note">
        <Icon name="check" size={13} />
        <span>Recuperada el <strong>{fmtDate(session.linked.date)}</strong> (sesión sábado)</span>
      </div>
    );
  }
  if (session.status === 'advanced') {
    return (
      <div className="att-link-note">
        <Icon name="check" size={13} />
        <span>Dictada por adelantado el <strong>{fmtDate(session.linked.date)}</strong></span>
      </div>
    );
  }
  return null;
}

// ============================================================
// Session card
// ============================================================
function SessionCard({ session, onOpen }) {
  const today = isToday(session.date);
  const recorded = session.hasRecord;
  const pending = session.status === 'scheduled';
  const clickable = recorded || pending || session.status === 'advanced';

  return (
    <div className={`att-card ${clickable ? 'clickable' : ''} ${today ? 'is-today' : ''}`}
         onClick={clickable ? () => onOpen(session) : undefined}>
      <div className="att-card-top">
        <DateBlock iso={session.date} />
        <div className="att-card-head">
          <h4 className="att-card-topic">{session.topic}</h4>
          <div className="att-card-tags">
            <StatusPill status={session.status} />
            <TypePill type={session.type} />
          </div>
        </div>
      </div>

      <div className="att-card-body">
        {recorded ? (
          <AttendanceBar present={session.present} absent={session.absent} />
        ) : pending ? (
          <div className="att-pending-note">
            {today
              ? <><Icon name="clock" size={13} /> Clase de hoy — falta pasar lista</>
              : <><Icon name="calendar" size={13} /> Programada — aún no inicia</>}
          </div>
        ) : session.status === 'recovered' ? (
          <div className="att-pending-note"><Icon name="x" size={13} /> Sin asistencia (se dio en la recuperación)</div>
        ) : null}

        <LinkedNote session={session} />

        {session.professorPresent === false && session.hasRecord && (
          <div className="att-upload-note">
            <Icon name="user" size={11} /> professor_present = false · subida por {session.uploadedBy || 'Coordinación'}
          </div>
        )}
      </div>

      <div className="att-card-foot">
        <span className="foot-meta">
          {recorded ? `${session.present + session.absent} registros` : `${ROSTER.length} estudiantes`}
        </span>
        {pending ? (
          <button className="att-card-cta" onClick={(e) => { e.stopPropagation(); onOpen(session); }}>
            Pasar lista <Icon name="arrowRight" size={14} />
          </button>
        ) : recorded ? (
          <button className="att-card-cta ghost" onClick={(e) => { e.stopPropagation(); onOpen(session); }}>
            Ver / editar <Icon name="chevronRight" size={14} />
          </button>
        ) : (
          <span className="foot-meta">—</span>
        )}
      </div>
    </div>
  );
}

// ============================================================
// Table layout
// ============================================================
function SessionsTable({ sessions, onOpen }) {
  return (
    <div className="att-table-wrap">
      <table className="att-table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Tema</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Asistencia</th>
            <th className="t-right">Acción</th>
          </tr>
        </thead>
        <tbody>
          {sessions.map(s => {
            const total = s.present + s.absent || 1;
            return (
              <tr key={s.id} onClick={() => (s.hasRecord || s.status === 'scheduled') && onOpen(s)}>
                <td>
                  <div className="t-date">
                    <span className="dn">{fmtDate(s.date)}</span>
                    <span className="dw">{dowShort(s.date)}{isToday(s.date) ? ' · hoy' : ''}</span>
                  </div>
                </td>
                <td className="t-topic">{s.topic}</td>
                <td>{s.type === 'regular' ? <span style={{ color: 'var(--text-muted)', fontSize: 12 }}>Regular</span> : <TypePill type={s.type} />}</td>
                <td><StatusPill status={s.status} /></td>
                <td>
                  {s.hasRecord ? (
                    <div className="t-mini-bar">
                      <div className="att-mini-bar">
                        <div className="sp" style={{ width: `${(s.present / total) * 100}%` }} />
                        <div className="sa" style={{ width: `${(s.absent / total) * 100}%` }} />
                      </div>
                      <div style={{ fontSize: 11, color: 'var(--text-muted)', marginTop: 4, fontFamily: 'var(--font-mono)' }}>
                        {s.present}P · {s.absent}A
                      </div>
                    </div>
                  ) : (
                    <span style={{ color: 'var(--text-muted)', fontSize: 12 }}>—</span>
                  )}
                </td>
                <td className="t-right">
                  {s.status === 'scheduled'
                    ? <button className="att-card-cta" onClick={(e) => { e.stopPropagation(); onOpen(s); }}>Pasar lista <Icon name="arrowRight" size={13} /></button>
                    : s.hasRecord
                      ? <button className="att-card-cta ghost" onClick={(e) => { e.stopPropagation(); onOpen(s); }}>Ver <Icon name="chevronRight" size={13} /></button>
                      : <span style={{ color: 'var(--text-muted)', fontSize: 12 }}>—</span>}
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}

// ============================================================
// Agenda / calendar layout (agrupado por semana)
// ============================================================
function weekKey(iso) {
  const d = parseDate(iso);
  const onejan = new Date(d.getFullYear(), 0, 1);
  const week = Math.ceil((((d - onejan) / 86400000) + onejan.getDay() + 1) / 7);
  return `${d.getFullYear()}-W${week}`;
}
function weekLabel(sessions) {
  const dates = sessions.map(s => parseDate(s.date)).sort((a, b) => a - b);
  const a = dates[0], b = dates[dates.length - 1];
  return `${a.getDate()} ${MESES[a.getMonth()]} – ${b.getDate()} ${MESES[b.getMonth()]}`;
}
function SessionsAgenda({ sessions, onOpen }) {
  const byWeek = useMemoP(() => {
    const asc = sessions.slice().sort((a, b) => b.date.localeCompare(a.date));
    const groups = [];
    const map = {};
    for (const s of asc) {
      const k = weekKey(s.date);
      if (!map[k]) { map[k] = { key: k, items: [] }; groups.push(map[k]); }
      map[k].items.push(s);
    }
    return groups;
  }, [sessions]);

  const railColor = (s) => {
    if (s.status === 'held') return 'var(--success)';
    if (s.status === 'scheduled') return isToday(s.date) ? 'var(--accent)' : 'var(--border-strong)';
    if (s.status === 'cancelled') return 'var(--danger)';
    if (s.status === 'recovered') return 'var(--text-muted)';
    if (s.status === 'advanced') return 'var(--info)';
    return 'var(--border-strong)';
  };

  return (
    <div className="att-agenda">
      {byWeek.map(g => (
        <div className="att-agenda-week" key={g.key}>
          <div className="att-agenda-whead">Semana del {weekLabel(g.items)}</div>
          {g.items.map(s => {
            const total = s.present + s.absent || 1;
            return (
              <div className={`att-agenda-row ${isToday(s.date) ? 'today' : ''}`} key={s.id}
                   onClick={() => (s.hasRecord || s.status === 'scheduled') && onOpen(s)}>
                <div className="att-agenda-date">
                  <div className="ad-dow">{dowShort(s.date)}</div>
                  <div className="ad-num">{parseDate(s.date).getDate()}</div>
                </div>
                <div className="att-agenda-rail" style={{ background: railColor(s) }} />
                <div className="att-agenda-main">
                  <div className="am-topic">{s.topic}</div>
                  <div className="am-meta">
                    <StatusPill status={s.status} />
                    <TypePill type={s.type} />
                    {s.professorPresent === false && s.hasRecord && <span className="att-upload-note"><Icon name="user" size={10} /> subida admin</span>}
                  </div>
                </div>
                <div className="att-agenda-att">
                  {s.hasRecord ? (
                    <div style={{ width: 130 }}>
                      <div className="att-mini-bar">
                        <div className="sp" style={{ width: `${(s.present / total) * 100}%` }} />
                        <div className="sa" style={{ width: `${(s.absent / total) * 100}%` }} />
                      </div>
                      <div style={{ fontSize: 11, color: 'var(--text-muted)', marginTop: 4, fontFamily: 'var(--font-mono)', textAlign: 'right' }}>
                        {s.absent} ausente{s.absent !== 1 ? 's' : ''}
                      </div>
                    </div>
                  ) : s.status === 'scheduled' ? (
                    <button className="att-card-cta" onClick={(e) => { e.stopPropagation(); onOpen(s); }}>Pasar lista <Icon name="arrowRight" size={13} /></button>
                  ) : <span style={{ color: 'var(--text-muted)', fontSize: 12 }}>—</span>}
                </div>
              </div>
            );
          })}
        </div>
      ))}
    </div>
  );
}

// ============================================================
// Totals / inasistencias panel
// ============================================================
function TotalsPanel() {
  const rows = useMemoP(() => {
    return ROSTER.map(st => ({ ...st, absences: ABSENCE_TOTALS[st.enrollmentDetailId] || 0 }))
      .sort((a, b) => b.absences - a.absences || a.name.localeCompare(b.name));
  }, []);
  const maxAbs = Math.max(SESSIONS_COUNTED, ...rows.map(r => r.absences), 1);
  const totalAbs = rows.reduce((a, r) => a + r.absences, 0);
  const atRisk = rows.filter(r => r.absences >= 6).length;

  const cls = (n) => (n >= 6 ? 'hi' : n >= 3 ? 'mid' : 'lo');

  return (
    <div className="att-totals">
      <div className="att-totals-head">
        <div>
          <h3>Inasistencias acumuladas</h3>
          <div className="sub">Total de faltas por estudiante en {SESSIONS_COUNTED} sesiones registradas del período {CURRENT_PERIOD} · {totalAbs} faltas en total</div>
        </div>
        <div className="att-totals-legend">
          <span><i style={{ background: 'var(--success)' }} /> 0–2 faltas</span>
          <span><i style={{ background: 'var(--warning)' }} /> 3–5</span>
          <span><i style={{ background: 'var(--danger)' }} /> 6+ en riesgo ({atRisk})</span>
        </div>
      </div>
      {rows.map(r => (
        <div className="att-roster-row" key={r.enrollmentDetailId}>
          <div className="att-roster-av">{r.initials}</div>
          <div className="att-roster-id">
            <div className="att-roster-name">{r.name}{r.absences >= 6 && <span className="att-risk">riesgo</span>}</div>
            <div className="att-roster-code">{r.code}</div>
          </div>
          <div className="att-roster-track">
            <div className={`fill ${cls(r.absences)}`} style={{ width: `${Math.max(4, (r.absences / maxAbs) * 100)}%` }} />
          </div>
          <div className={`att-roster-count ${cls(r.absences) === 'hi' ? 'hi' : cls(r.absences) === 'mid' ? 'mid' : ''}`}>
            {r.absences}<span className="of"> / {SESSIONS_COUNTED}</span>
          </div>
        </div>
      ))}
    </div>
  );
}

Object.assign(window, {
  StatusPill, TypePill, AttendanceBar, SessionCard,
  SessionsTable, SessionsAgenda, TotalsPanel,
});
