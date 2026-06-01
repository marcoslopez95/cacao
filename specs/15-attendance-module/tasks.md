# Tasks — Attendance Module
**Feature:** `15-attendance-module`
**Fecha:** 2026-06-01

---

- [ ] Task 1 — Migraciones: `class_sessions` (type, status, topic, professor_present, linked_session_id, uploaded_by_id) + `attendance_records` (UNIQUE constraint)
- [ ] Task 2 — Enums: `ClassSessionType`, `ClassSessionStatus`, `AttendanceStatus` + Modelos `ClassSession` y `AttendanceRecord` con relaciones completas (section, schedule, linkedSession, uploadedBy, attendanceRecords) + relaciones inversas en `Section`, `EnrollmentDetail`, `Schedule`
- [ ] Task 3 — `ClassSessionPolicy`: profesor solo accede a sus secciones; admin/coordinador acceden a todo
- [ ] Task 4 — Actions base: `CreateClassSessionAction` + `TakeAttendanceAction` (upsert con unicidad)
- [ ] Task 5 — Actions de vínculo: `CreateMakeupSessionAction` (crea makeup + marca linked como `recovered`) + `CreateAdvanceSessionAction` (crea advance + marca linked como `advanced` + copia attendance_records)
- [ ] Task 6 — Backend profesor: `Professor\AttendanceController` (index sesiones, storeSession, upsertAttendance) + FormRequests + `ClassSessionResource` + `AttendanceSheetResource`
- [ ] Task 7 — Backend admin: `Admin\AttendanceController` (index, storeSession, upsertAttendance) + FormRequests — con `professor_present: false` por defecto
- [ ] Task 8 — Rutas: `/professor/sections/{section}/attendance/*` + `/admin/sections/{section}/attendance/*` + Wayfinder regenerado
- [ ] Task 9 — Types TypeScript: interfaces en `resources/js/types/classSession.ts` + `resources/js/types/attendanceRecord.ts` + composables `resources/js/composables/forms/useClassSessionForm.ts` (create/store/upsertAttendance) + `resources/js/composables/forms/useAttendanceSheetForm.ts` (marks map Presente/Ausente, save). Tipos clave: `ClassSession { id, date, type: 'regular'|'makeup'|'advance', status: 'scheduled'|'held'|'cancelled'|'recovered'|'advanced', topic, professorPresent, uploadedBy?, linked?: ClassSession, present, absent, hasRecord }`. `AttendanceRecord { enrollmentDetailId, studentId, name, initials, code, status: 'present'|'absent' }`. `AttendanceSheet { session: ClassSession, roster: AttendanceRecord[], absenceTotals: Record<number,number>, sessionsCounted: number }`. `ClassSessionCollection` = colección paginada de sesiones.

- [ ] Task 10 — Componentes UI compartidos de asistencia (`resources/js/components/attendance/`):
  - `AttStatusPill.vue` — pill de status con dot: `ok` (Dada), `warn` (Pendiente), `danger` (Cancelada), `neutral` (Recuperada), `info` (Adelantada). `font-size: 10.5px, font-weight: 600, border-radius: pill, height 20px`. Dot de 6px con `background: currentColor`.
  - `AttTypePill.vue` — pill de tipo solo para `makeup`/`advance` (regular = null). Borde outline con color info (makeup) o warning (advance).
  - `AttDateBlock.vue` — bloque de fecha: DOW arriba 9px uppercase muted, número 19px 700, mes 9px uppercase muted. `width: 46px, border-radius: md, border: 1px solid border`. Fondo header `bg-surface-2`.
  - `AttBar.vue` — barra presente/absent: `height 8px, border-radius pill`. Segmento verde (success) + rojo (danger). Leyenda debajo con dots 7px + `<strong>N</strong> presente/ausente`, `font-size: 11.5px`.
  - `AttMiniBar.vue` — variante tabla: `height 6px`, sin leyenda, solo texto `NP · NA` en mono 11px debajo.
  - `AttSectionBanner.vue` — banner de contexto de sección: barra izquierda 4px con `--sec-color` (careerColor), badge cuadrado con cohort (fondo color 12% opacity), info (subject + code mono + meta: career/schedule/room/roster count), avatar del profesor con iniciales. `padding 16px 20px, border-radius lg, margin-bottom 20px`. Responsive: apila en <820px.

- [ ] Task 11 — `resources/js/pages/professor/attendance/Index.vue` — página principal del profesor:
  - **TodayCard** (si hay sesión `scheduled` hoy): bloque con columna izquierda `bg-accent` (DOW/número/mes en papel), cuerpo (tag "Clase de hoy · pendiente" con pulse dot animado terracota + título sesión + meta hora/aula/roster), acción derecha (mini-avatares solapados + botón `btn-primary btn-lg` "Pasar lista"). `border: 1px solid accent 40%, border-radius lg, margin-bottom 24px`.
  - **Stats row** (4 tiles `att-stat`): ① Sesiones registradas (ok: bg success-bg, icon check) ② Asistencia promedio % (neutral) ③ Inasistencias del período (danger) ④ Sesiones por dar (warn). Cada tile: `bg-surface, border, border-radius lg, display flex, gap 14px`. Ícono 38px cuadrado `border-radius md`. Valor 24px 600 tabular-nums. Label 11.5px muted. Grid 4→2→1 columnas.
  - **ViewBar**: tabs con borde inferior acento (Sesiones | Inasistencias con contadores pill mono), layout-switcher 3 botones (cards/table/agenda) con activo bg-surface + shadow-xs, botón "Nueva sesión" `btn-primary btn-md`. Flex row, flex-wrap.
  - **Filter chips** (solo en tab Sesiones): Todas | Pendientes (dot warning) | Dadas (dot success) | Recup./Adelanto (dot info) | Sin profesor (dot danger). Pill con border, activo: `bg-tinta text-papel border-tinta`. `font-size: 12.5px, padding 6px 12px`.
  - **3 layouts de sesiones**:
    - *Cards grid* (`auto-fill minmax(310px,1fr), gap 14px`): cada `AttSessionCard.vue` — top: DateBlock + head (topic 14px 600 + pills). Body: AttBar si hasRecord / nota pendiente si scheduled / nota recuperada si recovered. LinkedNote (dashed border, icon arrowRight). Footer: `bg-surface-2, border-top`, meta registros + CTA "Pasar lista" (accent) o "Ver/editar" (ghost). `clickable:hover` → `border-strong, shadow-md, translateY(-1px)`. `is-today` → borde doble con accent 45%.
    - *Table layout* (`att-table-wrap bg-surface border border-radius-lg`): columnas Fecha · Tema · Tipo · Estado · Asistencia · Acción. Header `font-size 10.5px uppercase letter-spacing 0.08em muted bg-surface-2`. Fila: `height 52px, hover bg-surface-2, cursor pointer`. Celda fecha: `dn font-weight-600` + `dw font-size 11px muted`. Celda asistencia: AttMiniBar 120px o dash.
    - *Agenda layout* (agrupado por semana): `att-agenda-week bg-surface border border-radius-lg`. Cabecera semana `11px uppercase muted bg-surface-2`. Cada fila: date column 58px (DOW 10px + número 22px 700) + rail 3px colored (success=dada, accent=hoy, danger=cancelada, muted=recovered, info=advanced) + main (topic 13.5px + pills) + columna asistencia/CTA. `today row: background accent 6%`.
  - **EmptyState**: ícono 52px `bg-surface-2 border-radius-md` + title 15px + desc 13px. Centrado, padding 56px.
  - Filtrado reactivo: `all/pending/held/special/noprof`. `pending` = status scheduled. `held` = hasRecord. `special` = type !== regular OR status recovered/advanced. `noprof` = professorPresent false.

- [ ] Task 12 — `resources/js/pages/professor/attendance/Sheet.vue` — pantalla completa pasar lista + modal nueva sesión:
  - **Sheet.vue** full-screen (`position fixed inset-0 z-70 bg-page flex-col, animation fadeIn 180ms`):
    - **Topbar** (`bg-surface border-bottom, padding 12px 24px, flex gap 16px`): botón "Volver" (`border border-radius-md, padding 7px 12px, font-size 13px`) + títulos (topic 16px 600 + badge "Subida administrativa" `bg-warning-bg color-warning-fg font-size 11px` si modo admin + sub con fecha/materia/hora 12px muted) + contadores Presente/Ausente (`bg-surface-2 border border-radius-md, n 20px 700 tabular-nums, l 10px uppercase muted`). Contador Presente: `n color success`. Ausente: `n color danger`.
    - **Body** (`flex-1 overflow-y-auto padding 22px 24px 120px`): banner admin info si modo admin. Progress bar `6px bg-sunken fill accent transition-width 200ms` + texto "N estudiantes" + bulk actions ("Todos presente" / "Todos ausente") + búsqueda (`height 40px, padding-left 38px con icon absolute, focus border-accent ring 3px`).
    - **Variant A — Toggle list** (default): lista `border border-radius-lg overflow-hidden bg-surface`. Cada item: número 22px mono muted + avatar 36px (iniciales, bg accent 12%) + info (name 13.5px + code mono 11px + PriorChip) + segmented control (`Presente ✓ | Ausente ✗`, activo presente: `bg-success color-white`, activo ausente: `bg-danger color-white`, inactivo: `bg-surface-2`).
    - **Variant B — FastList**: toda la fila clickeable, check circle 26px (verde outline + bg 10% si presente, bg danger si ausente), fila `absent: bg danger 5%`. Label "PRESENTE/AUSENTE" 11px uppercase.
    - **Variant C — TileGrid** (`auto-fill minmax(150px,1fr), gap 10px`): tiles `border 1.5px success bg-success-8%, absent: border danger bg-danger-9%`. Avatar 44px bg-success (o X si absent). Name 12.5px 600. State 10.5px uppercase.
    - **PriorChip**: 0 faltas = "sin faltas" muted. 1-2 = warn. 6+ = danger 600.
    - **Sticky footer** (`position absolute bottom-0 left-0 right-0, backdrop-filter blur(8px) bg-surface-92%, border-top`): summary "N presente · N ausente · X% asistencia" + botones "Cancelar" (secondary) + "Guardar asistencia" (primary). Toast de confirmación al guardar: `bg-text-primary color-bg-surface, slide-up animation 250ms`.
  - **CreateSessionModal.vue** — modal centrado `max-width 560px`:
    - **Scrim**: `rgba(19,17,16,0.5) fixed inset-0 z-80, fadeIn 150ms`.
    - **Panel**: `bg-surface border-radius-xl shadow-lg, flex-col, max-height calc(100vh - 40px)`. Header `padding 18px 22px border-bottom` (title 17px 600 + X button `30px border-radius-sm hover bg-surface-2`). Body scrollable `padding 22px`. Footer `bg-surface-2 border-top padding 16px 22px flex justify-end gap 10px`.
    - **Type selector** (3 tarjetas en grid 3 cols): Regular (`icon: calendar, desc: "Clase del horario habitual"`), Recuperación (`icon: arrowRight`), Adelanto (`icon: clock`). Seleccionada: `border-accent bg-accent-soft`. Cada card: `border 1.5px border-radius-md padding 14px 12px flex-col gap 4px`. Name 13px 600 + desc 11px muted.
    - **Form grid** 2 cols: Sección (select) · Fecha (date input) · Inicio (time) · Fin (time). Para makeup/advance: selector "Sesión vinculada" full-width. Tema (text, opcional). Inputs `height 38px border-strong border-radius-md font-size 13px, focus: border-accent ring 3px`.
    - **Hint bar** (solo para makeup/advance): `bg-info-bg border info 28% border-radius-md padding 11px 13px`, icon info + texto explicativo 12px `color-info-fg`.

- [ ] Task 13 — `resources/js/components/attendance/AttTotalsPanel.vue` — panel inasistencias acumuladas (tab "Inasistencias" del profesor):
  - Header: título "Inasistencias acumuladas" 15px 600 + sub "Total de faltas por estudiante en N sesiones…" 12px muted + leyenda: 3 dots (`bg-success` 0-2 · `bg-warning` 3-5 · `bg-danger` 6+ con "en riesgo (N)").
  - Roster ordenado desc por ausencias: grid 4 cols (`34px auto 150px 80px`). Avatar 32px circular `bg-accent 12% border-accent 22%`. Name 13px 500 + badge "riesgo" inline `10px uppercase bg-danger-bg color-danger-fg padding 1px 6px border-radius-sm`. Code mono 11px muted. Track `height 8px border-radius-pill fill: lo=success / mid=warning / hi=danger`. Count `13px 600 tabular-nums / .of color-muted 11px`. `hi: color-danger`, `mid: color-warning-fg`. Hover: `bg-surface-2`.
  - Responsive: track se oculta en <820px, grid pasa a 3 cols.

- [ ] Task 14 — `resources/js/pages/admin/attendance/Index.vue` — vista de coordinación:
  - **Banner admin warning**: `bg-warning-bg border-warning-30% border-radius-lg padding 14px 16px margin-bottom 20px`. Icono info 16px + texto "Subida administrativa de asistencia..." 12.5px. Explica que `professor_present = false`.
  - **ViewBar**: tab "Pendientes de registrar" con conteo. Sin layout-switcher (siempre cards).
  - **Search**: `max-width 380px height 40px`. Filtra por subject/prof/code/cohort.
  - **Cards de sesión sin registrar** (reutiliza `AttSessionCard.vue` adaptado): DateBlock con `border-color: color-mix(in srgb, careerColor 30%, border)`. Head: `topic · subject · cohort` + pill "Sin registrar" (warn) + code pill (mono outline). Body: topic 12.5px secondary + nota del profesor (`icon user + "Profesor X · razón"` en linked-note dashed) + upload-note `professor_present = false` (warning-fg mono 11px). Footer: count estudiantes + CTA "Subir asistencia" (`icon upload, accent`).
  - **EmptyState** ("Todo al día"): ícono check + título + desc.
  - Clic en card → abre `Sheet.vue` en modo admin: banner "Estás registrando como Coordinación / Admin" visible, `professor_present = false`, sin opción de cambiar. La sesión queda guardada con `professorPresent: false`.

- [ ] Task 15 — QA Gate: qa_manager verifica todos los UCs de `specs/15-attendance-module/qa.md` en verde
