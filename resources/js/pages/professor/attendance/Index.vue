<script setup lang="ts">
import { router, setLayoutProps } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { sheet } from '@/actions/App/Http/Controllers/Professor/AttendanceController'
import AttSectionBanner from '@/components/attendance/AttSectionBanner.vue'
import AttSessionCard from '@/components/attendance/AttSessionCard.vue'
import AttStatusPill from '@/components/attendance/AttStatusPill.vue'
import AttTotalsPanel from '@/components/attendance/AttTotalsPanel.vue'
import AttTypePill from '@/components/attendance/AttTypePill.vue'
import AppIcon from '@/components/UI/AppIcon.vue'
import { useClassSessionForm } from '@/composables/forms/useClassSessionForm'
import type { AttendanceSectionContext, AttendanceRosterEntry, ClassSession } from '@/types/attendance'

type Props = {
    section: AttendanceSectionContext
    sessions: ClassSession[]
    period: string | null
    roster: AttendanceRosterEntry[]
    absenceTotals: Record<number, number>
    sessionsCounted: number
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Académico', href: '#' },
        { title: 'Asistencia', href: '#' },
        { title: props.section.subject, href: '#' },
    ],
})

// ---- Date helpers ----
const TODAY = new Date()
const todayDate = computed(() => {
    const y = TODAY.getFullYear()
    const m = String(TODAY.getMonth() + 1).padStart(2, '0')
    const d = String(TODAY.getDate()).padStart(2, '0')

    return `${y}-${m}-${d}`
})

const DOW_ES = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado']
const MON_ES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic']

function parseDateLocal(iso: string): Date {
    const [y, m, d] = iso.split('-').map(Number)

    return new Date(y, m - 1, d)
}

// ---- Today session ----
const todaySession = computed(() => {
    const scheduled = props.sessions.filter((s) => s.status === 'scheduled')

    return scheduled.find((s) => s.heldAt === todayDate.value) ?? scheduled[0] ?? null
})

const todayDt = computed(() => {
    if (!todaySession.value?.heldAt) {
 return null 
}

    return parseDateLocal(todaySession.value.heldAt)
})

function goToTodaySheet(): void {
    if (!todaySession.value) {
 return 
}

    router.visit(
        sheet.url({ section: props.section.id, classSession: todaySession.value.id }),
    )
}

// ---- Stats ----
const stats = computed(() => {
    const recorded = props.sessions.filter((s) => s.hasRecord)
    const totalPresent = recorded.reduce((a, s) => a + s.present, 0)
    const totalSlots = recorded.reduce((a, s) => a + s.present + s.absent, 0)
    const pct = totalSlots ? Math.round((totalPresent / totalSlots) * 100) : 0
    const totalAbsent = props.sessions.reduce((a, s) => a + s.absent, 0)
    const pending = props.sessions.filter((s) => s.status === 'scheduled').length

    return { dadas: recorded.length, pct, totalAbsent, pending }
})

// ---- View state ----
type ViewMode = 'sessions' | 'totals'
type LayoutMode = 'cards' | 'table' | 'agenda'
type FilterKey = 'all' | 'pending' | 'held' | 'special' | 'noprof'

const activeView = ref<ViewMode>('sessions')
const activeLayout = ref<LayoutMode>('cards')
const activeFilter = ref<FilterKey>('all')

const FILTERS: { key: FilterKey; label: string; dot?: string }[] = [
    { key: 'all',     label: 'Todas' },
    { key: 'pending', label: 'Pendientes',        dot: 'var(--warning)' },
    { key: 'held',    label: 'Dadas',             dot: 'var(--success)' },
    { key: 'special', label: 'Recup. / Adelanto', dot: 'var(--info)' },
    { key: 'noprof',  label: 'Sin profesor',      dot: 'var(--danger)' },
]

const filteredSessions = computed(() => {
    const f = activeFilter.value

    return props.sessions.filter((s) => {
        if (f === 'pending') {
 return s.status === 'scheduled' 
}

        if (f === 'held')    {
 return s.hasRecord 
}

        if (f === 'special') {
 return s.type !== 'regular' || s.status === 'recovered' || s.status === 'advanced' 
}

        if (f === 'noprof')  {
 return s.professorPresent === false 
}

        return true
    })
})

// ---- Agenda grouping ----
function weekKey(iso: string): string {
    const dt = parseDateLocal(iso)
    const onejan = new Date(dt.getFullYear(), 0, 1)
    const week = Math.ceil((((dt.getTime() - onejan.getTime()) / 86400000) + onejan.getDay() + 1) / 7)

    return `${dt.getFullYear()}-W${week}`
}

function weekLabel(sessions: ClassSession[]): string {
    const dates = sessions
        .filter((s) => s.heldAt)
        .map((s) => parseDateLocal(s.heldAt!))
        .sort((a, b) => a.getTime() - b.getTime())

    if (dates.length === 0) {
 return '—' 
}

    const a = dates[0]
    const b = dates[dates.length - 1]

    return `${a.getDate()} ${MON_ES[a.getMonth()]} – ${b.getDate()} ${MON_ES[b.getMonth()]}`
}

const agendaGroups = computed(() => {
    const asc = [...filteredSessions.value].sort((a, b) => {
        const ka = a.heldAt ?? ''
        const kb = b.heldAt ?? ''

        return kb.localeCompare(ka)
    })
    const groups: { key: string; items: ClassSession[] }[] = []
    const map: Record<string, { key: string; items: ClassSession[] }> = {}

    for (const s of asc) {
        const k = s.heldAt ? weekKey(s.heldAt) : 'no-date'

        if (!map[k]) {
 map[k] = { key: k, items: [] }; groups.push(map[k]) 
}

        map[k].items.push(s)
    }

    return groups
})

function railColor(s: ClassSession): string {
    if (s.status === 'held')      {
 return 'var(--success)' 
}

    if (s.status === 'scheduled') {
 return s.heldAt === todayDate.value ? 'var(--accent)' : 'var(--border-strong)' 
}

    if (s.status === 'cancelled') {
 return 'var(--danger)' 
}

    if (s.status === 'recovered') {
 return 'var(--text-muted)' 
}

    if (s.status === 'advanced')  {
 return 'var(--info)' 
}

    return 'var(--border-strong)'
}

function goToSheet(s: ClassSession): void {
    if (!s.hasRecord && s.status !== 'scheduled' && s.status !== 'advanced') {
 return 
}

    router.visit(sheet.url({ section: props.section.id, classSession: s.id }))
}

// ---- Totals panel (simple inline implementation) ----
// No roster data on Index — show placeholder note
// Full TotalsPanel is Task 13, here we show a simple empty state if no roster

// ---- Create session modal ----
const showCreateModal = ref(false)
const { processing: creating, errors: createErrors, create } = useClassSessionForm(props.section.id)

type SessionType = 'regular' | 'makeup' | 'advance'
const newSessionType = ref<SessionType>('regular')
const newTopic = ref('')
const newDate = ref('')
const newLinkedSessionId = ref<number | null>(null)

// Candidate sessions to link when creating a makeup (cancelled) or advance (future scheduled) session
const candidateSessions = computed<ClassSession[]>(() => {
    if (newSessionType.value === 'makeup') {
        return props.sessions.filter((s) => s.status === 'cancelled')
    }

    if (newSessionType.value === 'advance') {
        return props.sessions.filter((s) => s.status === 'scheduled' && (s.heldAt ?? '') > todayDate.value)
    }

    return []
})

watch(newSessionType, () => {
    newLinkedSessionId.value = null
})

function openCreateModal(): void {
    newSessionType.value = 'regular'
    newTopic.value = ''
    newDate.value = todayDate.value
    newLinkedSessionId.value = null
    showCreateModal.value = true
}

function closeCreateModal(): void {
    showCreateModal.value = false
}

function submitNewSession(): void {
    create(
        {
            type: newSessionType.value,
            linked_session_id: newSessionType.value === 'regular' ? null : newLinkedSessionId.value,
            topic: newTopic.value.trim() || null,
            held_at: newDate.value || null,
        },
        () => {
 showCreateModal.value = false
},
    )
}

// ---- Table date helpers ----
function tableDateLabel(iso: string): string {
    const dt = parseDateLocal(iso)

    return `${dt.getDate()}/${dt.getMonth() + 1}/${dt.getFullYear()}`
}

function tableDowLabel(iso: string): string {
    const dt = parseDateLocal(iso)

    return DOW_ES[dt.getDay()]
}
</script>

<template>
    <div class="att-root" style="--att-pad: 1;">
        <!-- Section context banner -->
        <AttSectionBanner :section="section" />

        <!-- Today highlight card -->
        <div
            v-if="todaySession"
            class="att-today"
        >
            <!-- Date column -->
            <div class="att-today-date">
                <span class="dow">{{ todayDt ? DOW_ES[todayDt.getDay()].slice(0, 3).toUpperCase() : '—' }}</span>
                <span class="dnum">{{ todayDt ? todayDt.getDate() : '?' }}</span>
                <span class="mon">{{ todayDt ? MON_ES[todayDt.getMonth()].toUpperCase() : '—' }}</span>
            </div>

            <!-- Body -->
            <div class="att-today-body">
                <span class="att-today-tag">
                    <span class="att-today-pulse" />
                    Clase de hoy · pendiente
                </span>
                <h3>{{ todaySession.topic || 'Sin tema' }}</h3>
                <div class="att-today-meta">
                    <span><AppIcon name="clock" :size="13" /> {{ section.scheduleDisplay }}</span>
                    <span><AppIcon name="building" :size="13" /> {{ section.room }}</span>
                    <span><AppIcon name="users" :size="13" /> {{ section.rosterCount }} estudiantes</span>
                </div>
            </div>

            <!-- Action -->
            <div class="att-today-action">
                <div class="roster-mini">
                    <span
                        v-for="i in Math.min(4, section.rosterCount)"
                        :key="i"
                        class="av"
                    >
                        {{ i }}
                    </span>
                    <span v-if="section.rosterCount > 4" class="av more">
                        +{{ section.rosterCount - 4 }}
                    </span>
                </div>
                <button
                    class="inline-flex items-center gap-2 font-semibold rounded-lg px-4 py-2"
                    style="background: var(--accent-fg); color: var(--accent); font-size: 13.5px;"
                    @click="goToTodaySheet"
                >
                    <AppIcon name="check" :size="16" />
                    Pasar lista
                </button>
            </div>
        </div>

        <!-- Stats row -->
        <div class="att-stats">
            <div class="att-stat ok">
                <div class="att-stat-ico"><AppIcon name="check" :size="18" /></div>
                <div class="att-stat-info">
                    <div class="att-stat-val">{{ stats.dadas }}</div>
                    <div class="att-stat-lbl">Sesiones registradas</div>
                </div>
            </div>
            <div class="att-stat">
                <div class="att-stat-ico"><AppIcon name="chart" :size="18" /></div>
                <div class="att-stat-info">
                    <div class="att-stat-val">{{ stats.pct }}%</div>
                    <div class="att-stat-lbl">Asistencia promedio</div>
                </div>
            </div>
            <div class="att-stat danger">
                <div class="att-stat-ico"><AppIcon name="alert" :size="18" /></div>
                <div class="att-stat-info">
                    <div class="att-stat-val">{{ stats.totalAbsent }}</div>
                    <div class="att-stat-lbl">Inasistencias del período</div>
                </div>
            </div>
            <div class="att-stat warn">
                <div class="att-stat-ico"><AppIcon name="calendar" :size="18" /></div>
                <div class="att-stat-info">
                    <div class="att-stat-val">{{ stats.pending }}</div>
                    <div class="att-stat-lbl">Sesiones por dar</div>
                </div>
            </div>
        </div>

        <!-- ViewBar: tabs + layout switcher + new session -->
        <div class="att-viewbar">
            <div class="att-tabs">
                <button
                    dusk="tab-sessions"
                    :class="['att-tab', activeView === 'sessions' && 'active']"
                    @click="activeView = 'sessions'"
                >
                    <AppIcon name="calendar" :size="15" />
                    Sesiones
                    <span class="cnt">{{ sessions.length }}</span>
                </button>
                <button
                    dusk="tab-totals"
                    :class="['att-tab', activeView === 'totals' && 'active']"
                    @click="activeView = 'totals'"
                >
                    <AppIcon name="users" :size="15" />
                    Inasistencias
                    <span class="cnt">{{ section.rosterCount }}</span>
                </button>
            </div>

            <!-- Layout switcher (only in sessions view) -->
            <div v-if="activeView === 'sessions'" class="att-layout-switch" role="group" aria-label="Diseño de lista">
                <button
                    :class="activeLayout === 'cards' && 'active'"
                    title="Tarjetas"
                    @click="activeLayout = 'cards'"
                >
                    <AppIcon name="grid" :size="15" />
                </button>
                <button
                    :class="activeLayout === 'table' && 'active'"
                    title="Tabla"
                    @click="activeLayout = 'table'"
                >
                    <AppIcon name="more" :size="15" />
                </button>
                <button
                    :class="activeLayout === 'agenda' && 'active'"
                    title="Agenda"
                    @click="activeLayout = 'agenda'"
                >
                    <AppIcon name="layout-list" :size="15" />
                </button>
            </div>

            <button
                dusk="new-session-btn"
                class="inline-flex items-center gap-1.5 font-semibold rounded-lg px-3 py-2"
                style="background: var(--accent); color: var(--accent-fg); font-size: 13px;"
                @click="openCreateModal"
            >
                <AppIcon name="plus" :size="15" />
                Nueva sesión
            </button>
        </div>

        <!-- Sessions tab -->
        <template v-if="activeView === 'sessions'">
            <!-- Filter chips -->
            <div class="att-filters">
                <button
                    v-for="f in FILTERS"
                    :key="f.key"
                    :class="['att-fchip', activeFilter === f.key && 'active']"
                    @click="activeFilter = f.key"
                >
                    <span v-if="f.dot" class="dot" :style="{ background: f.dot }" />
                    {{ f.label }}
                </button>
            </div>

            <!-- Empty state -->
            <div v-if="filteredSessions.length === 0" class="att-empty">
                <div class="ic"><AppIcon name="calendar" :size="20" /></div>
                <h3>Sin sesiones</h3>
                <p>No hay sesiones que coincidan con este filtro.</p>
            </div>

            <!-- Cards layout -->
            <div v-else-if="activeLayout === 'cards'" class="att-cards">
                <AttSessionCard
                    v-for="s in filteredSessions"
                    :key="s.id"
                    :session="s"
                    :roster-count="section.rosterCount"
                    :today-date="todayDate"
                />
            </div>

            <!-- Table layout -->
            <div v-else-if="activeLayout === 'table'" class="att-table-wrap">
                <table class="att-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tema</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Asistencia</th>
                            <th class="t-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="s in filteredSessions"
                            :key="s.id"
                            @click="goToSheet(s)"
                        >
                            <td>
                                <div class="t-date">
                                    <span class="dn">{{ s.heldAt ? tableDateLabel(s.heldAt) : '—' }}</span>
                                    <span class="dw">
                                        {{ s.heldAt ? tableDowLabel(s.heldAt) : '' }}{{ s.heldAt === todayDate ? ' · hoy' : '' }}
                                    </span>
                                </div>
                            </td>
                            <td class="t-topic">{{ s.topic || 'Sin tema' }}</td>
                            <td>
                                <span v-if="s.type === 'regular'" style="color: var(--text-muted); font-size: 12px;">Regular</span>
                                <AttTypePill v-else :type="s.type" />
                            </td>
                            <td><AttStatusPill :status="s.status" /></td>
                            <td>
                                <div v-if="s.hasRecord" class="t-mini-bar">
                                    <div class="att-mini-bar">
                                        <div
                                            class="sp"
                                            :style="{ width: `${(s.present / Math.max(s.present + s.absent, 1)) * 100}%` }"
                                        />
                                        <div
                                            class="sa"
                                            :style="{ width: `${(s.absent / Math.max(s.present + s.absent, 1)) * 100}%` }"
                                        />
                                    </div>
                                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; font-family: var(--font-mono);">
                                        {{ s.present }}P · {{ s.absent }}A
                                    </div>
                                </div>
                                <span v-else style="color: var(--text-muted); font-size: 12px;">—</span>
                            </td>
                            <td class="t-right">
                                <button
                                    v-if="s.status === 'scheduled'"
                                    class="att-card-cta"
                                    @click.stop="goToSheet(s)"
                                >
                                    Pasar lista <AppIcon name="arrowRight" :size="13" />
                                </button>
                                <button
                                    v-else-if="s.hasRecord"
                                    class="att-card-cta ghost"
                                    @click.stop="goToSheet(s)"
                                >
                                    Ver <AppIcon name="chevronRight" :size="13" />
                                </button>
                                <span v-else style="color: var(--text-muted); font-size: 12px;">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Agenda layout -->
            <div v-else class="att-agenda">
                <div
                    v-for="group in agendaGroups"
                    :key="group.key"
                    class="att-agenda-week"
                >
                    <div class="att-agenda-whead">Semana del {{ weekLabel(group.items) }}</div>
                    <div
                        v-for="s in group.items"
                        :key="s.id"
                        :class="['att-agenda-row', s.heldAt === todayDate && 'today']"
                        @click="goToSheet(s)"
                    >
                        <div class="att-agenda-date">
                            <div class="ad-dow">
                                {{ s.heldAt ? DOW_ES[parseDateLocal(s.heldAt).getDay()].slice(0, 3) : '—' }}
                            </div>
                            <div class="ad-num">
                                {{ s.heldAt ? parseDateLocal(s.heldAt).getDate() : '?' }}
                            </div>
                        </div>
                        <div class="att-agenda-rail" :style="{ background: railColor(s) }" />
                        <div class="att-agenda-main">
                            <div class="am-topic">{{ s.topic || 'Sin tema' }}</div>
                            <div class="am-meta">
                                <AttStatusPill :status="s.status" />
                                <AttTypePill :type="s.type" />
                                <span
                                    v-if="s.professorPresent === false && s.hasRecord"
                                    class="att-upload-note"
                                >
                                    <AppIcon name="user" :size="10" />
                                    subida admin
                                </span>
                            </div>
                        </div>
                        <div class="att-agenda-att">
                            <div v-if="s.hasRecord" style="width: 130px;">
                                <div class="att-mini-bar">
                                    <div
                                        class="sp"
                                        :style="{ width: `${(s.present / Math.max(s.present + s.absent, 1)) * 100}%` }"
                                    />
                                    <div
                                        class="sa"
                                        :style="{ width: `${(s.absent / Math.max(s.present + s.absent, 1)) * 100}%` }"
                                    />
                                </div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; font-family: var(--font-mono); text-align: right;">
                                    {{ s.absent }} ausente{{ s.absent !== 1 ? 's' : '' }}
                                </div>
                            </div>
                            <button
                                v-else-if="s.status === 'scheduled'"
                                class="att-card-cta"
                                @click.stop="goToSheet(s)"
                            >
                                Pasar lista <AppIcon name="arrowRight" :size="13" />
                            </button>
                            <span v-else style="color: var(--text-muted); font-size: 12px;">—</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Totals tab -->
        <template v-else>
            <AttTotalsPanel
                :roster="props.roster"
                :absence-totals="props.absenceTotals"
                :sessions-counted="props.sessionsCounted"
                :period="props.period"
            />
        </template>

        <!-- Create session modal -->
        <Teleport to="body">
            <div
                v-if="showCreateModal"
                class="fixed inset-0 z-80 flex items-center justify-center p-5"
                style="background: rgba(19,17,16,0.5);"
                @click.self="closeCreateModal"
            >
                <div
                    class="flex flex-col overflow-hidden"
                    style="background: var(--bg-surface); border-radius: var(--radius-xl); box-shadow: var(--shadow-lg); max-width: 520px; width: 100%; max-height: calc(100vh - 40px);"
                >
                    <!-- Modal head -->
                    <div
                        class="flex items-center justify-between px-6 py-4 shrink-0"
                        style="border-bottom: 1px solid var(--border);"
                    >
                        <h3 class="m-0 font-semibold" style="font-size: 17px;">Nueva sesión</h3>
                        <button
                            class="flex items-center justify-center rounded"
                            style="width: 30px; height: 30px; background: transparent; border: 0; color: var(--text-muted); cursor: pointer;"
                            @click="closeCreateModal"
                        >
                            <AppIcon name="x" :size="18" />
                        </button>
                    </div>

                    <!-- Modal body -->
                    <div class="p-6 overflow-y-auto" style="display: flex; flex-direction: column; gap: 18px;">
                        <!-- Type selector -->
                        <div>
                            <label
                                class="block mb-2 font-medium"
                                style="font-size: 12.5px; color: var(--text-primary);"
                            >
                                Tipo de sesión
                            </label>
                            <div class="grid grid-cols-3 gap-2.5">
                                <button
                                    v-for="opt in [
                                        { value: 'regular', name: 'Regular', desc: 'Clase ordinaria del horario' },
                                        { value: 'makeup',  name: 'Recuperación', desc: 'Repone una sesión cancelada' },
                                        { value: 'advance', name: 'Adelanto', desc: 'Anticipa una sesión futura' },
                                    ]"
                                    :key="opt.value"
                                    :dusk="`session-type-${opt.value}`"
                                    :class="['flex flex-col gap-1 text-left p-3 rounded-lg border-[1.5px] cursor-pointer font-inherit transition-all',
                                             newSessionType === opt.value ? 'border-[var(--accent)] bg-[var(--accent-soft)]' : 'border-[var(--border)] bg-[var(--bg-surface)]']"
                                    style="font-family: inherit;"
                                    @click="newSessionType = (opt.value as SessionType)"
                                >
                                    <span class="font-semibold" style="font-size: 13px; color: var(--text-primary);">{{ opt.name }}</span>
                                    <span style="font-size: 11px; color: var(--text-muted); line-height: 1.4;">{{ opt.desc }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Date field -->
                        <div class="flex flex-col gap-1.5">
                            <label class="font-medium" style="font-size: 12.5px; color: var(--text-primary);">
                                Fecha
                            </label>
                            <input
                                dusk="new-session-date"
                                v-model="newDate"
                                type="date"
                                class="w-full rounded-lg px-3"
                                style="height: 38px; border: 1px solid var(--border-strong); background: var(--bg-surface); color: var(--text-primary); font-size: 13px; font-family: inherit;"
                            />
                            <p v-if="createErrors.held_at" style="font-size: 12px; color: var(--danger); margin: 0;">
                                {{ createErrors.held_at }}
                            </p>
                        </div>

                        <!-- Linked session selector (makeup/advance only) -->
                        <div
                            v-if="newSessionType !== 'regular'"
                            class="flex flex-col gap-1.5"
                        >
                            <label class="font-medium" style="font-size: 12.5px; color: var(--text-primary);">
                                Sesión vinculada
                            </label>
                            <select
                                dusk="linked-session-select"
                                v-model.number="newLinkedSessionId"
                                class="w-full rounded-lg px-3"
                                style="height: 38px; border: 1px solid var(--border-strong); background: var(--bg-surface); color: var(--text-primary); font-size: 13px; font-family: inherit;"
                            >
                                <option :value="null" disabled>Selecciona una sesión…</option>
                                <option
                                    v-for="cs in candidateSessions"
                                    :key="cs.id"
                                    :value="cs.id"
                                >
                                    {{ cs.heldAt ?? 'Sin fecha' }} — {{ cs.topic || 'Sin tema' }}
                                </option>
                            </select>
                            <p v-if="createErrors.linked_session_id" style="font-size: 12px; color: var(--danger); margin: 0;">
                                {{ createErrors.linked_session_id }}
                            </p>

                            <!-- Hint bar -->
                            <div
                                class="flex items-start gap-2"
                                style="background: var(--info-bg, rgba(59,130,246,0.08)); border: 1px solid color-mix(in srgb, var(--info, #3B82F6) 28%, transparent); border-radius: var(--radius-md); padding: 11px 13px; margin-top: 4px;"
                            >
                                <AppIcon name="info" :size="14" style="color: var(--info-fg, var(--info, #3B82F6)); flex-shrink: 0; margin-top: 1px;" />
                                <span style="font-size: 12px; color: var(--info-fg, var(--info, #3B82F6));">
                                    {{ newSessionType === 'makeup'
                                        ? 'Elegí la sesión cancelada que se está recuperando. Quedará marcada como "Recuperada".'
                                        : 'Elegí la sesión futura que se está adelantando. Quedará marcada como "Adelantada" y su asistencia se copiará automáticamente.' }}
                                </span>
                            </div>
                        </div>

                        <!-- Topic field -->
                        <div class="flex flex-col gap-1.5">
                            <label class="font-medium" style="font-size: 12.5px; color: var(--text-primary);">
                                Tema <span style="color: var(--text-muted); font-weight: 400;">(opcional)</span>
                            </label>
                            <input
                                dusk="new-session-topic"
                                v-model="newTopic"
                                type="text"
                                placeholder="Ej. Funciones de orden superior"
                                class="w-full rounded-lg px-3"
                                style="height: 38px; border: 1px solid var(--border-strong); background: var(--bg-surface); color: var(--text-primary); font-size: 13px; font-family: inherit;"
                            />
                            <p v-if="createErrors.topic" style="font-size: 12px; color: var(--danger); margin: 0;">
                                {{ createErrors.topic }}
                            </p>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div
                        class="flex gap-2.5 justify-end px-6 py-4 shrink-0"
                        style="background: var(--bg-surface-2); border-top: 1px solid var(--border);"
                    >
                        <button
                            class="rounded-lg px-4 py-2 font-medium"
                            style="background: transparent; border: 1px solid var(--border); color: var(--text-secondary); cursor: pointer; font-size: 13.5px; font-family: inherit;"
                            @click="closeCreateModal"
                        >
                            Cancelar
                        </button>
                        <button
                            dusk="new-session-submit"
                            :disabled="creating"
                            class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 font-semibold"
                            style="background: var(--accent); color: var(--accent-fg); border: 0; cursor: pointer; font-size: 13.5px; font-family: inherit;"
                            @click="submitNewSession"
                        >
                            <AppIcon v-if="creating" name="loader" :size="14" />
                            Crear sesión
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
