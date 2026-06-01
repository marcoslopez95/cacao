<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import type { AttendanceSheet, AttendanceSectionContext, AttendanceMarks, AttendanceStatus } from '@/types/attendance'
import { useAttendanceSheetForm } from '@/composables/forms/useAttendanceSheetForm'
import { index } from '@/actions/App/Http/Controllers/Professor/AttendanceController'
import AppIcon from '@/components/UI/AppIcon.vue'

// ---- Props ----------------------------------------------------------------

type Props = {
    sheet: AttendanceSheet
    section: AttendanceSectionContext
    mode?: 'prof' | 'admin'
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'prof',
})

// ---- State ----------------------------------------------------------------

/** Initialize every student as 'present' — the rule: start present, mark absent */
const marks = ref<AttendanceMarks>(
    Object.fromEntries(
        props.sheet.roster.map((st) => [st.enrollmentDetailId, 'present' as AttendanceStatus]),
    ),
)

const query = ref('')

type Variant = 'toggle' | 'fast' | 'grid'
const variant = ref<Variant>('toggle')

// ---- Composable -----------------------------------------------------------

const { save, processing, saved } = useAttendanceSheetForm(props.section.id, props.sheet.session.id)

// ---- Derived --------------------------------------------------------------

const isAdmin = computed(() => props.mode === 'admin')

const presentCount = computed(
    () => Object.values(marks.value).filter((v) => v === 'present').length,
)
const absentCount = computed(() => props.sheet.roster.length - presentCount.value)

const attendancePct = computed(() => {
    if (props.sheet.roster.length === 0) { return 100 }
    return Math.round((presentCount.value / props.sheet.roster.length) * 100)
})

const filteredRoster = computed(() => {
    const q = query.value.trim().toLowerCase()
    if (!q) { return props.sheet.roster }
    return props.sheet.roster.filter(
        (st) =>
            st.name.toLowerCase().includes(q) ||
            st.code.toLowerCase().includes(q),
    )
})

// ---- Helpers ---------------------------------------------------------------

function setMark(eid: number, status: AttendanceStatus): void {
    marks.value = { ...marks.value, [eid]: status }
}

function toggleMark(eid: number): void {
    marks.value = {
        ...marks.value,
        [eid]: marks.value[eid] === 'absent' ? 'present' : 'absent',
    }
}

function markAll(status: AttendanceStatus): void {
    marks.value = Object.fromEntries(
        props.sheet.roster.map((st) => [st.enrollmentDetailId, status]),
    )
}

function goBack(): void {
    router.visit(index.url({ section: props.section.id }))
}

function handleSave(): void {
    const professorPresent = !isAdmin.value
    save(marks.value, professorPresent)
}

// ---- Date formatting -------------------------------------------------------

const MON_ES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic']
const DOW_ES = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado']

function fmtDateLong(iso: string): string {
    const [y, m, d] = iso.split('-').map(Number)
    const dt = new Date(y, m - 1, d)
    return `${DOW_ES[dt.getDay()]} ${d} de ${MON_ES[m - 1]} de ${y}`
}

// ---- PriorChip helper ------------------------------------------------------

function priorClass(n: number): string {
    if (n >= 6) { return 'danger' }
    if (n >= 3) { return 'warn' }
    return ''
}

function priorLabel(n: number): string {
    if (n === 0) { return 'sin faltas' }
    return `${n} falta${n !== 1 ? 's' : ''} previas`
}
</script>

<template>
    <div class="att-rc" role="dialog" aria-modal="true">
        <!-- ===== Top bar ===== -->
        <div class="att-rc-bar">
            <button class="att-rc-back" @click="goBack">
                <AppIcon name="chevronLeft" :size="15" /> Volver
            </button>

            <div class="att-rc-titles">
                <h2>
                    {{ sheet.session.topic || 'Sesión sin tema' }}
                    <span v-if="isAdmin" class="att-rc-admin-flag">
                        <AppIcon name="alert" :size="11" /> Subida administrativa
                    </span>
                </h2>
                <div class="sub">
                    <span v-if="sheet.session.heldAt">
                        <AppIcon name="calendar" :size="12" />
                        {{ fmtDateLong(sheet.session.heldAt) }}
                    </span>
                    <span>
                        <AppIcon name="users" :size="12" />
                        {{ section.subject }} · {{ section.cohort }}
                    </span>
                    <span>
                        <AppIcon name="clock" :size="12" />
                        {{ section.scheduleDisplay }}
                    </span>
                </div>
            </div>

            <div class="att-rc-counts">
                <div class="att-rc-count present">
                    <span class="n">{{ presentCount }}</span>
                    <span class="l">Presente</span>
                </div>
                <div class="att-rc-count absent">
                    <span class="n">{{ absentCount }}</span>
                    <span class="l">Ausente</span>
                </div>
            </div>
        </div>

        <!-- ===== Body ===== -->
        <div class="att-rc-body">
            <div class="att-rc-inner">
                <!-- Admin banner -->
                <div v-if="isAdmin" class="att-admin-banner" style="margin-bottom: 18px;">
                    <AppIcon name="info" :size="16" />
                    <div class="ab-body">
                        Estás registrando esta asistencia como <strong>Coordinación / Admin</strong>
                        porque el profesor no la pasó.
                        La sesión quedará marcada con <strong>professor_present = false</strong>.
                    </div>
                </div>

                <!-- Progress bar + student count + bulk actions -->
                <div class="att-rc-progress">
                    <span>
                        <strong style="color: var(--text-primary);">{{ sheet.roster.length }}</strong>
                        estudiantes
                    </span>
                    <div class="track">
                        <div
                            class="fill"
                            :style="{ width: `${attendancePct}%` }"
                        />
                    </div>
                    <div class="att-rc-bulk">
                        <button @click="markAll('present')">Todos presente</button>
                        <button @click="markAll('absent')">Todos ausente</button>
                    </div>
                </div>

                <!-- Search -->
                <div class="att-rc-search">
                    <AppIcon name="search" :size="15" />
                    <input
                        v-model="query"
                        type="text"
                        placeholder="Buscar estudiante por nombre o código…"
                    />
                </div>

                <!-- Variant switcher -->
                <div class="att-rc-variant-bar">
                    <button
                        :class="variant === 'toggle' && 'active'"
                        @click="variant = 'toggle'"
                    >
                        <AppIcon name="layout-list" :size="13" /> Lista
                    </button>
                    <button
                        :class="variant === 'fast' && 'active'"
                        @click="variant = 'fast'"
                    >
                        <AppIcon name="check" :size="13" /> Rápido
                    </button>
                    <button
                        :class="variant === 'grid' && 'active'"
                        @click="variant = 'grid'"
                    >
                        <AppIcon name="grid" :size="13" /> Cuadrícula
                    </button>
                </div>

                <!-- ---- Variant A: Toggle list ---- -->
                <div v-if="variant === 'toggle'" class="att-rc-list">
                    <div
                        v-for="(st, i) in filteredRoster"
                        :key="st.enrollmentDetailId"
                        class="att-rc-item"
                    >
                        <span class="rc-num">{{ i + 1 }}</span>
                        <div class="rc-av">{{ st.initials }}</div>
                        <div class="rc-info">
                            <div class="rc-name">{{ st.name }}</div>
                            <div class="rc-sub">
                                {{ st.code }}
                                <!-- PriorChip inline -->
                                <span
                                    :class="['rc-prior', priorClass(sheet.absenceTotals[st.enrollmentDetailId] ?? 0)]"
                                >
                                    <AppIcon
                                        v-if="(sheet.absenceTotals[st.enrollmentDetailId] ?? 0) > 0"
                                        name="alert"
                                        :size="9"
                                    />
                                    {{ priorLabel(sheet.absenceTotals[st.enrollmentDetailId] ?? 0) }}
                                </span>
                            </div>
                        </div>
                        <div class="rc-seg">
                            <button
                                :class="['present', marks[st.enrollmentDetailId] === 'present' && 'on']"
                                @click="setMark(st.enrollmentDetailId, 'present')"
                            >
                                <AppIcon name="check" :size="13" /> Presente
                            </button>
                            <button
                                :class="['absent', marks[st.enrollmentDetailId] === 'absent' && 'on']"
                                @click="setMark(st.enrollmentDetailId, 'absent')"
                            >
                                <AppIcon name="x" :size="13" /> Ausente
                            </button>
                        </div>
                    </div>
                    <div v-if="filteredRoster.length === 0" class="att-empty">
                        <div class="ic"><AppIcon name="search" :size="20" /></div>
                        <h3>Sin resultados</h3>
                        <p>No hay estudiantes que coincidan con "{{ query }}".</p>
                    </div>
                </div>

                <!-- ---- Variant B: Fast list ---- -->
                <div v-else-if="variant === 'fast'" class="att-rc-fastlist">
                    <div
                        v-for="(st, i) in filteredRoster"
                        :key="st.enrollmentDetailId"
                        :class="['att-rc-fastrow', marks[st.enrollmentDetailId] === 'absent' && 'absent']"
                        @click="toggleMark(st.enrollmentDetailId)"
                    >
                        <span class="rc-num">{{ i + 1 }}</span>
                        <div class="rc-check">
                            <AppIcon
                                :name="marks[st.enrollmentDetailId] === 'absent' ? 'x' : 'check'"
                                :size="14"
                            />
                        </div>
                        <div class="rc-info">
                            <div class="rc-name">{{ st.name }}</div>
                            <div class="rc-sub">
                                {{ st.code }}
                                <span
                                    v-if="(sheet.absenceTotals[st.enrollmentDetailId] ?? 0) > 0"
                                    :class="['rc-prior', priorClass(sheet.absenceTotals[st.enrollmentDetailId] ?? 0)]"
                                >
                                    {{ priorLabel(sheet.absenceTotals[st.enrollmentDetailId] ?? 0) }}
                                </span>
                            </div>
                        </div>
                        <span class="rc-state-lbl">
                            {{ marks[st.enrollmentDetailId] === 'absent' ? 'Ausente' : 'Presente' }}
                        </span>
                    </div>
                    <div v-if="filteredRoster.length === 0" class="att-empty">
                        <div class="ic"><AppIcon name="search" :size="20" /></div>
                        <h3>Sin resultados</h3>
                        <p>No hay estudiantes que coincidan con "{{ query }}".</p>
                    </div>
                </div>

                <!-- ---- Variant C: Tile grid ---- -->
                <div v-else class="att-rc-grid">
                    <div
                        v-for="st in filteredRoster"
                        :key="st.enrollmentDetailId"
                        :class="['att-rc-tile', marks[st.enrollmentDetailId] === 'absent' && 'absent']"
                        @click="toggleMark(st.enrollmentDetailId)"
                    >
                        <div class="tile-av">
                            <template v-if="marks[st.enrollmentDetailId] === 'absent'">
                                <AppIcon name="x" :size="18" />
                            </template>
                            <template v-else>
                                {{ st.initials }}
                            </template>
                        </div>
                        <div class="tile-name">{{ st.name }}</div>
                        <div class="tile-state">
                            {{ marks[st.enrollmentDetailId] === 'absent' ? 'AUSENTE' : 'PRESENTE' }}
                        </div>
                        <div
                            v-if="(sheet.absenceTotals[st.enrollmentDetailId] ?? 0) > 0"
                            class="tile-abs"
                        >
                            {{ sheet.absenceTotals[st.enrollmentDetailId] }}
                            falta{{ sheet.absenceTotals[st.enrollmentDetailId] !== 1 ? 's' : '' }}
                        </div>
                    </div>
                    <div v-if="filteredRoster.length === 0" class="att-empty" style="grid-column: 1/-1;">
                        <div class="ic"><AppIcon name="search" :size="20" /></div>
                        <h3>Sin resultados</h3>
                        <p>No hay estudiantes que coincidan con "{{ query }}".</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== Sticky footer ===== -->
        <div class="att-rc-foot">
            <div class="summary">
                <strong>{{ presentCount }}</strong> presente ·
                <strong>{{ absentCount }}</strong> ausente
                <span
                    v-if="absentCount > 0 && sheet.roster.length > 0"
                    style="color: var(--text-muted);"
                >
                    · {{ attendancePct }}% asistencia
                </span>
            </div>
            <div class="actions">
                <button class="btn btn-secondary btn-md" @click="goBack">
                    Cancelar
                </button>
                <button
                    class="btn btn-primary btn-md"
                    :disabled="processing"
                    @click="handleSave"
                >
                    <AppIcon v-if="processing" name="loader" :size="15" />
                    <AppIcon v-else name="check" :size="15" />
                    Guardar asistencia
                </button>
            </div>
        </div>

        <!-- ===== Toast ===== -->
        <div v-if="saved" class="att-toast">
            <AppIcon name="check" :size="16" />
            Asistencia guardada · sesión marcada como dada
        </div>
    </div>
</template>
