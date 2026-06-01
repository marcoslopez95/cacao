/* global React */
// ============================================================
// CACAO · Asistencia — datos mock
// Modela el feature 15-attendance-module:
//   class_sessions (regular | makeup | advance) con status y vínculos
//   attendance_records (present | absent) por estudiante × sesión
//   totales de inasistencia derivados (COUNT absent por enrollment_detail_id)
// ============================================================

const CURRENT_PERIOD = '2026-I';
// "Hoy" del prototipo: miércoles 27 de mayo de 2026
const TODAY = '2026-05-27';

// ---- Sección activa del profesor ----------------------------------------
const SECTION = {
  id: 'sec-pi-4a',
  subject: 'Programación I',
  code: 'SIS-1204',
  cohort: '4° A',
  career: 'Ing. en Sistemas',
  careerColor: '#C8521A',
  professor: { name: 'Marco Salas', initials: 'MS' },
  // Horario regular (schedule slots): Lun y Mié 09:00–11:00, Aula 201
  schedule: 'Lun · Mié — 09:00 a 11:00',
  room: 'Aula 201',
};

// ---- Roster: 26 estudiantes con enrollment_detail_id --------------------
const ROSTER_NAMES = [
  'Camila Ríos Vargas', 'Andrés Belmonte Cruz', 'Valentina Carrasco León',
  'Diego Fuenmayor Soto', 'Isabella Nava Quintero', 'Sebastián Ortega Brito',
  'Daniela Quiroz Mendoza', 'Mateo Rangel Acosta', 'Gabriela Salcedo Pérez',
  'Ricardo Colmenares Díaz', 'Antonella Briceño Rivas', 'Joaquín Medina Flores',
  'Renata Guerrero Lara', 'Emiliano Salazar Gómez', 'Mariana Blanco Torres',
  'Thiago Navarro Reyes', 'Salomé Méndez Castro', 'Iván Quintero Rojas',
  'Astrid Romero Villa', 'Héctor Brito Sánchez', 'Paula Acosta Marín',
  'Rafael Mendoza Peña', 'Génesis Rangel Ortiz', 'Manuel Silva Bravo',
  'Stefany Colina Páez', 'Adrián Morales Lugo',
];

function seedRand(seed) {
  let s = seed;
  return () => { s = (s * 9301 + 49297) % 233280; return s / 233280; };
}

// Algunos estudiantes con patrón de inasistencia más alto (índices)
const CHRONIC = { 3: 0.42, 9: 0.30, 17: 0.34, 23: 0.26, 6: 0.22 };

const ROSTER = ROSTER_NAMES.map((name, i) => {
  const parts = name.split(' ');
  return {
    enrollmentDetailId: 4001 + i,
    studentId: 9001 + i,
    name,
    initials: (parts[0][0] + (parts[1]?.[0] || '')).toUpperCase(),
    code: `EST-2023-${String(418 + i * 7).padStart(4, '0')}`,
    absentRate: CHRONIC[i] ?? (0.04 + (i % 5) * 0.015),
  };
});

// ---- Sesiones de clase ---------------------------------------------------
// status: scheduled | held | cancelled | recovered | advanced
// type:   regular | makeup | advance
// professor_present: boolean
// linked_session_id: vínculo (makeup→cancelada, advance→futura)
const RAW_SESSIONS = [
  { id: 1,  date: '2026-04-02', type: 'regular', status: 'held',      topic: 'Introducción · entorno y sintaxis', professorPresent: true },
  { id: 2,  date: '2026-04-07', type: 'regular', status: 'held',      topic: 'Variables y tipos de datos',        professorPresent: true },
  { id: 3,  date: '2026-04-09', type: 'regular', status: 'held',      topic: 'Operadores y expresiones',          professorPresent: true },
  // Sesión cancelada (prof. enfermo) → recuperada por la makeup #20
  { id: 4,  date: '2026-04-14', type: 'regular', status: 'recovered', topic: 'Condicionales (if / switch)',       professorPresent: false, linkedSessionId: 20 },
  { id: 5,  date: '2026-04-16', type: 'regular', status: 'held',      topic: 'Operador ternario y lógica',        professorPresent: true },
  // Sesión donde el profesor faltó → asistencia subida por admin
  { id: 6,  date: '2026-04-21', type: 'regular', status: 'held',      topic: 'Bucles while / for',                professorPresent: false, uploadedBy: 'Coordinación' },
  { id: 7,  date: '2026-04-23', type: 'regular', status: 'held',      topic: 'Arreglos unidimensionales',         professorPresent: true },
  { id: 8,  date: '2026-04-28', type: 'regular', status: 'held',      topic: 'Funciones y parámetros',            professorPresent: true },
  { id: 9,  date: '2026-04-30', type: 'regular', status: 'held',      topic: 'Ámbito y recursión',                professorPresent: true },
  { id: 10, date: '2026-05-05', type: 'regular', status: 'held',      topic: 'Matrices',                          professorPresent: true },
  { id: 11, date: '2026-05-07', type: 'regular', status: 'held',      topic: 'Cadenas de caracteres',             professorPresent: true },
  { id: 12, date: '2026-05-12', type: 'regular', status: 'held',      topic: 'Estructuras / registros',           professorPresent: true },
  { id: 13, date: '2026-05-14', type: 'regular', status: 'held',      topic: 'Punteros — introducción',           professorPresent: true },
  // Sesión adelantada: su contenido se dictó antes en la #21 → queda advanced
  { id: 14, date: '2026-05-21', type: 'regular', status: 'advanced',  topic: 'Memoria dinámica',                  professorPresent: false, linkedSessionId: 21 },
  { id: 15, date: '2026-05-26', type: 'regular', status: 'held',      topic: 'Archivos — lectura y escritura',    professorPresent: true },
  // HOY — pendiente de pasar lista (CTA principal)
  { id: 16, date: '2026-05-27', type: 'regular', status: 'scheduled', topic: 'Archivos — práctica guiada',        professorPresent: true },
  // Futuras
  { id: 17, date: '2026-06-01', type: 'regular', status: 'scheduled', topic: 'Manejo de errores',                 professorPresent: true },
  { id: 18, date: '2026-06-03', type: 'regular', status: 'scheduled', topic: 'Repaso integrador',                 professorPresent: true },
  { id: 19, date: '2026-06-08', type: 'regular', status: 'scheduled', topic: 'Evaluación final — taller',         professorPresent: true },

  // makeup: recupera la #4 (cancelada). Se dictó un sábado.
  { id: 20, date: '2026-04-19', type: 'makeup',  status: 'held',      topic: 'Condicionales (recuperación)',      professorPresent: true, linkedSessionId: 4 },
  // advance: adelanta la #14. Se dictó antes; su asistencia se copió a la #14.
  { id: 21, date: '2026-05-19', type: 'advance', status: 'held',      topic: 'Memoria dinámica (adelanto)',       professorPresent: true, linkedSessionId: 14 },
];

// Sesiones que TIENEN registro de asistencia (para derivar totales)
const RECORDED_STATUSES = new Set(['held', 'advanced']); // recovered NO (se dio en la makeup); advanced SÍ (copiada)

// Genera attendance_records: { [sessionId]: { [enrollmentDetailId]: 'present'|'absent' } }
function buildRecords() {
  const rnd = seedRand(73);
  const records = {};
  // Orden cronológico para que los totales tengan sentido temporal
  const recorded = RAW_SESSIONS
    .filter(s => RECORDED_STATUSES.has(s.status))
    .sort((a, b) => a.date.localeCompare(b.date));

  for (const s of recorded) {
    const rec = {};
    for (const st of ROSTER) {
      // Camila (demo) casi siempre presente
      const rate = st.enrollmentDetailId === 4001 ? 0.03 : st.absentRate;
      rec[st.enrollmentDetailId] = rnd() < rate ? 'absent' : 'present';
    }
    records[s.id] = rec;
  }

  // Regla 4: la sesión advance copia su asistencia a la regular vinculada (#14)
  const advance = RAW_SESSIONS.find(s => s.type === 'advance');
  if (advance && advance.linkedSessionId && records[advance.id]) {
    records[advance.linkedSessionId] = { ...records[advance.id] };
  }
  return records;
}

const ATTENDANCE = buildRecords();

// Totales de inasistencia por estudiante (regla 7)
function computeAbsenceTotals() {
  const totals = {};
  for (const st of ROSTER) totals[st.enrollmentDetailId] = 0;
  // Evitar doble conteo: advanced copió de advance → contar solo una vez.
  // Contamos por sesión registrada EXCEPTO la advance (su copia vive en la #14).
  const counted = Object.keys(ATTENDANCE).filter(id => {
    const s = RAW_SESSIONS.find(x => x.id === Number(id));
    return s && s.type !== 'advance';
  });
  for (const sid of counted) {
    const rec = ATTENDANCE[sid];
    for (const eid in rec) if (rec[eid] === 'absent') totals[eid]++;
  }
  return totals;
}

const ABSENCE_TOTALS = computeAbsenceTotals();
// Nº de sesiones contadas para el denominador
const SESSIONS_COUNTED = Object.keys(ATTENDANCE).filter(id => {
  const s = RAW_SESSIONS.find(x => x.id === Number(id));
  return s && s.type !== 'advance';
}).length;

// ---- Helpers de presentación -------------------------------------------
const SESSION_TYPE = {
  regular: { label: 'Regular',      short: 'Regular' },
  makeup:  { label: 'Recuperación', short: 'Recup.'  },
  advance: { label: 'Adelanto',     short: 'Adelanto'},
};

const SESSION_STATUS = {
  held:      { label: 'Dada',      tone: 'ok'      },
  scheduled: { label: 'Pendiente', tone: 'warn'    },
  cancelled: { label: 'Cancelada', tone: 'danger'  },
  recovered: { label: 'Recuperada',tone: 'neutral' },
  advanced:  { label: 'Adelantada',tone: 'info'    },
};

const MESES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
const DOW = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
const DOW_FULL = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

function parseDate(iso) {
  const [y, m, d] = iso.split('-').map(Number);
  return new Date(y, m - 1, d);
}
function fmtDate(iso) {
  const dt = parseDate(iso);
  return `${dt.getDate()} ${MESES[dt.getMonth()]}`;
}
function fmtDateLong(iso) {
  const dt = parseDate(iso);
  return `${DOW_FULL[dt.getDay()]} ${dt.getDate()} de ${['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'][dt.getMonth()]}, ${dt.getFullYear()}`;
}
function dowShort(iso) { return DOW[parseDate(iso).getDay()]; }
function isPast(iso)   { return iso < TODAY; }
function isToday(iso)  { return iso === TODAY; }
function isFuture(iso) { return iso > TODAY; }

// Sesión enriquecida con datos de asistencia y vínculos resueltos
function enrichSession(s) {
  const rec = ATTENDANCE[s.id];
  const present = rec ? Object.values(rec).filter(v => v === 'present').length : 0;
  const absent  = rec ? Object.values(rec).filter(v => v === 'absent').length : 0;
  const linked  = s.linkedSessionId ? RAW_SESSIONS.find(x => x.id === s.linkedSessionId) : null;
  return { ...s, present, absent, hasRecord: !!rec, linked };
}

const SESSIONS = RAW_SESSIONS.map(enrichSession);
// Sesiones que se muestran en la línea principal (regulares + makeup; las advance
// se muestran como "vínculo" desde la regular). Orden: más reciente arriba.
function mainSessions() {
  return SESSIONS
    .filter(s => s.type !== 'advance')
    .slice()
    .sort((a, b) => b.date.localeCompare(a.date));
}

Object.assign(window, {
  CURRENT_PERIOD, TODAY, SECTION, ROSTER, SESSIONS, ATTENDANCE,
  ABSENCE_TOTALS, SESSIONS_COUNTED,
  SESSION_TYPE, SESSION_STATUS, MESES, DOW, DOW_FULL,
  parseDate, fmtDate, fmtDateLong, dowShort, isPast, isToday, isFuture,
  enrichSession, mainSessions, RAW_SESSIONS,
});
