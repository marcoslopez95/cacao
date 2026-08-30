<script setup lang="ts">
import { router, setLayoutProps } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { sheet as adminSheet, storeSession as adminStoreSession } from '@/actions/App/Http/Controllers/Admin/AttendanceController'
import { index as adminIndex } from '@/actions/App/Http/Controllers/Admin/AttendanceController'
import AttSectionBanner from '@/components/attendance/AttSectionBanner.vue'
import AttSessionCard from '@/components/attendance/AttSessionCard.vue'
import AttStatusPill from '@/components/attendance/AttStatusPill.vue'
import AttTypePill from '@/components/attendance/AttTypePill.vue'
import AppIcon from '@/components/UI/AppIcon.vue'
import type { AttendanceSectionContext, ClassSession } from '@/types/attendance'

type Props = {
    section: AttendanceSectionContext
    sessions: ClassSession[] | { data: ClassSession[] }
    period?: string | null
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Admin', href: adminIndex.url() },
        { title: 'Asistencia', href: adminIndex.url() },
        { title: props.section.subject, href: '#' },
    ],
})

// Normalize sessions — may come as plain array or ResourceCollection
const allSessions = computed<ClassSession[]>(() => {
    const s = props.sessions

    return Array.isArray(s) ? s : (s as { data: ClassSession[] }).data
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

function parseDateLocal(iso: string): Date {
    const [y, m, d] = iso.split('-').map(Number)

    return new Date(y, m - 1, d)
}

function tableDateLabel(iso: string): string {
    const dt = parseDateLocal(iso)

    return `${dt.getDate()}/${dt.getMonth() + 1}/${dt.getFullYear()}`
}

function tableDowLabel(iso: string): string {
    const dt = parseDateLocal(iso)

    return DOW_ES[dt.getDay()]
}

// ---- Stats ----
const stats = computed(() => {
    const recorded = allSessions.value.filter((s) => s.hasRecord)
    const totalPresent = recorded.reduce((a, s) => a + s.present, 0)
    const totalSlots = recorded.reduce((a, s) => a + s.present + s.absent, 0)
    const pct = totalSlots ? Math.round((totalPresent / totalSlots) * 100) : 0
    const totalAbsent = allSessions.value.reduce((a, s) => a + s.absent, 0)
    const pending = allSessions.value.filter((s) => s.status === 'scheduled').length

    return { dadas: recorded.length, pct, totalAbsent, pending }
})

// ---- View state ----
type LayoutMode = 'cards' | 'table'
type FilterKey = 'all' | 'pending' | 'held' | 'special' | 'noprof'

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

    return allSessions.value.filter((s) => {
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

// ---- Navigation ----
function goToSheet(s: ClassSession): void {
    if (!s.hasRecord && s.status !== 'scheduled' && s.status !== 'advanced') {
 return 
}

    router.visit(adminSheet.url({ section: props.section.id, classSession: s.id }))
}

// ---- Create session modal ----
const showCreateModal = ref(false)
const creating = ref(false)
const createErrors = ref<Record<string, string>>({})

type SessionType = 'regular' | 'makeup' | 'advance'
const newSessionType = ref<SessionType>('regular')
const newTopic = ref('')
const newDate = ref('')
const newLinkedSessionId = ref<number | null>(null)

// Candidate sessions to link when creating a makeup (cancelled) or advance (future scheduled) session
const candidateSessions = computed<ClassSession[]>(() => {
    if (newSessionType.value === 'makeup') {
        return allSessions.value.filter((s) => s.status === 'cancelled')
    }

    if (newSessionType.value === 'advance') {
        return allSessions.value.filter((s) => s.status === 'scheduled' && (s.heldAt ?? '') > todayDate.value)
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
    creating.value = true
    router.post(
        adminStoreSession.url({ section: props.section.id }),
        {
            type: newSessionType.value,
            linked_session_id: newSessionType.value === 'regular' ? null : newLinkedSessionId.value,
            topic: newTopic.value.trim() || null,
            held_at: newDate.value || null,
        },
        {
            onSuccess: () => {
 creating.value = false; createErrors.value = {}; showCreateModal.value = false
},
            onError: (e) => {
 creating.value = false; createErrors.value = e
},
        }
    )
}
</script>

<template>
    <div class="att-root">
        <!-- Section context banner -->
        <AttSectionBanner :section="section" />

        <!-- Admin notice -->
        <div class="att-admin-banner" style="margin-bottom: 16px;">
            <AppIcon name="info" :size="15" />
            <div class="ab-body" style="font-size: 13px;">
                Vista administrativa — podés crear sesiones, ver el historial y subir asistencias sin restricción de ownership.
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
                    <div class="att-stat-lbl">Sesiones pendientes</div>
                </div>
            </div>
        </div>

        <!-- ViewBar -->
        <div class="att-viewbar">
            <div class="att-tabs">
                <button class="att-tab active">
                    <AppIcon name="calendar" :size="15" />
                    Sesiones
                    <span class="cnt">{{ allSessions.length }}</span>
                </button>
            </div>

            <!-- Layout switcher -->
            <div class="att-layout-switch" role="group" aria-label="Diseño de lista">
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
                @click="goToSheet(s)"
            />
        </div>

        <!-- Table layout -->
        <div v-else class="att-table-wrap">
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
