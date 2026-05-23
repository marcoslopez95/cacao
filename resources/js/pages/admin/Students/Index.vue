<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import Pagination from '@/components/UI/AppPagination.vue'
import Button from '@/components/UI/AppButton.vue'
import { useStudentFilters } from '@/composables/filters/useStudentFilters'
import { index } from '@/routes/academic/students'
import type { StudentCollection, StudentFilters, StudentLevel, StudentListItem } from '@/types/student'

type Props = {
    students: StudentCollection
    careers: Array<{ id: number; name: string }>
    activePeriod: string | null
    quickCounts: Record<string, number>
    filters: StudentFilters
}

const props = defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Académico', href: '#' },
            { title: 'Estudiantes', href: index.url() },
        ],
    },
})

const {
    search, careerIds, academicYears, enrollStatuses, level, sectionLetters,
    applyFilters, onSearchInput, applyLevel, applyQuickView, paginationFilters,
} = useStudentFilters(props.filters, props.students.meta.per_page)

const activeQuickView = ref<string>('all')

// ── Level tabs ────────────────────────────────────────────

const LEVEL_TABS: Array<{ key: StudentLevel; label: string }> = [
    { key: 'all',        label: 'Todos' },
    { key: 'primary',    label: 'Primaria' },
    { key: 'secondary',  label: 'Bachillerato' },
    { key: 'university', label: 'Universitario' },
]

function handleLevelClick(newLevel: StudentLevel): void {
    activeQuickView.value = 'all'
    applyLevel(newLevel)
}

// ── Quick views per level ─────────────────────────────────

type QuickViewDef = { key: string; label: string; disabled?: boolean }

const QUICK_VIEWS_BY_LEVEL: Record<StudentLevel, QuickViewDef[]> = {
    all: [
        { key: 'all',     label: 'Todos' },
        { key: 'pending', label: 'Sin confirmar' },
        { key: 'top',     label: 'Alto rendimiento (≥17)', disabled: true },
        { key: 'risk',    label: 'En riesgo (<12)', disabled: true },
    ],
    primary: [
        { key: 'all',          label: 'Todos' },
        { key: 'no_guardian',  label: 'Sin representante' },
        { key: 'grade_1',      label: '1er grado' },
        { key: 'grade_6',      label: '6to grado' },
        { key: 'enrolled',     label: 'Con inscripción' },
    ],
    secondary: [
        { key: 'all',      label: 'Todos' },
        { key: 'year_1',   label: '1er año' },
        { key: 'year_5',   label: '5to año' },
        { key: 'enrolled', label: 'Con inscripción' },
    ],
    university: [
        { key: 'all',       label: 'Todos' },
        { key: 'pending',   label: 'Sin confirmar' },
        { key: 'top',       label: 'Alto rendimiento (≥17)', disabled: true },
        { key: 'risk',      label: 'En riesgo (<12)', disabled: true },
        { key: 'newcomers', label: '1er año' },
    ],
}

const currentQuickViews = computed<QuickViewDef[]>(() => QUICK_VIEWS_BY_LEVEL[level.value])

function handleQuickViewClick(key: string): void {
    activeQuickView.value = key
    applyQuickView(key)
}

// ── Filter options ────────────────────────────────────────

const ENROLL_OPTIONS = [
    { value: 'confirmed', label: 'Confirmada' },
    { value: 'draft',     label: 'Pendiente' },
    { value: 'none',      label: 'Sin inscribir' },
    { value: 'rejected',  label: 'Rechazada' },
]

const yearOptions = computed<number[]>(() => {
    if (level.value === 'primary')   return Array.from({ length: 6 }, (_, i) => i + 1)
    if (level.value === 'secondary') return Array.from({ length: 5 }, (_, i) => i + 1)
    return Array.from({ length: 10 }, (_, i) => i + 1)
})

const yearLabel = computed<string>(() => level.value === 'primary' ? 'Grado' : 'Año')

const SECTION_LETTERS = ['A', 'B', 'C', 'D']

// ── Stats ─────────────────────────────────────────────────

const total     = computed(() => props.students.meta.total)
const confirmed = computed(() => props.students.data.filter(s => s.enrollment_status === 'confirmed' || s.enrollment_status === 'approved').length)
const pending   = computed(() => props.students.data.filter(s => s.enrollment_status === 'draft').length)
const none      = computed(() => props.students.data.filter(s => !s.enrollment_status).length)

// ── Active filters count ──────────────────────────────────

function activeFiltersCount(): number {
    return (
        (search.value                 ? 1 : 0) +
        (careerIds.value.length       ? 1 : 0) +
        (academicYears.value.length   ? 1 : 0) +
        (enrollStatuses.value.length  ? 1 : 0) +
        (sectionLetters.value.length  ? 1 : 0)
    )
}

function clearAll(): void {
    search.value         = ''
    careerIds.value      = []
    academicYears.value  = []
    enrollStatuses.value = []
    sectionLetters.value = []
    applyFilters({ search: undefined, career_id: [], academic_year: [], enrollment_status: [], section_letter: [] })
}

// ── Helpers ───────────────────────────────────────────────

function avatarInitials(name: string): string {
    const parts = name.trim().split(/\s+/)
    return ((parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '')).toUpperCase()
}

function enrollLabel(s: StudentListItem): string {
    if (!s.enrollment_status)                                                       return 'Sin inscribir'
    if (s.enrollment_status === 'confirmed' || s.enrollment_status === 'approved') return 'Confirmada'
    if (s.enrollment_status === 'draft')                                            return 'Pendiente'
    if (s.enrollment_status === 'rejected')                                         return 'Rechazada'
    return '—'
}

function enrollClass(s: StudentListItem): string {
    if (!s.enrollment_status)                                                       return 'enroll-none'
    if (s.enrollment_status === 'confirmed' || s.enrollment_status === 'approved') return 'enroll-ok'
    if (s.enrollment_status === 'draft')                                            return 'enroll-warn'
    if (s.enrollment_status === 'rejected')                                         return 'enroll-danger'
    return 'enroll-none'
}

function levelPillColor(lvl: 'university' | 'primary' | 'secondary'): string {
    if (lvl === 'primary')    return '#2E7D5C'
    if (lvl === 'secondary')  return '#7C5A3A'
    return '#C8521A'
}

function levelLabel(lvl: 'university' | 'primary' | 'secondary'): string {
    if (lvl === 'primary')   return 'Primaria'
    if (lvl === 'secondary') return 'Bachillerato'
    return 'Universitario'
}

// ── Filter toggles ────────────────────────────────────────

function isCareerSelected(id: number): boolean { return careerIds.value.includes(id) }
function toggleCareer(id: number): void {
    careerIds.value = isCareerSelected(id)
        ? careerIds.value.filter(c => c !== id)
        : [...careerIds.value, id]
    applyFilters()
}

function isYearSelected(y: number): boolean { return academicYears.value.includes(y) }
function toggleYear(y: number): void {
    academicYears.value = isYearSelected(y)
        ? academicYears.value.filter(v => v !== y)
        : [...academicYears.value, y]
    applyFilters()
}

function isStatusSelected(v: string): boolean { return enrollStatuses.value.includes(v) }
function toggleStatus(v: string): void {
    enrollStatuses.value = isStatusSelected(v)
        ? enrollStatuses.value.filter(s => s !== v)
        : [...enrollStatuses.value, v]
    applyFilters()
}

function isLetterSelected(l: string): boolean { return sectionLetters.value.includes(l) }
function toggleSectionLetter(l: string): void {
    sectionLetters.value = isLetterSelected(l)
        ? sectionLetters.value.filter(s => s !== l)
        : [...sectionLetters.value, l]
    applyFilters()
}
</script>

<template>
    <Head title="Estudiantes" />

    <div style="display:flex;flex-direction:column;gap:0;">

        <!-- ── Header ─────────────────────────────────── -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Estudiantes
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Buscá, filtrá y gestioná el cuerpo estudiantil — por carrera, año, promedio o estado de inscripción.
                </p>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span
                    v-if="props.activePeriod"
                    style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--text-secondary);background:var(--bg-page,#F4F2EF);border:1px solid var(--border);padding:4px 10px;border-radius:999px;"
                >
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Período <strong style="color:var(--text-primary);font-weight:600;">{{ props.activePeriod }}</strong>
                </span>
                <Button variant="ghost" icon="download" size="sm">Exportar CSV</Button>
                <Button variant="primary" icon="plus" size="sm">Nuevo estudiante</Button>
            </div>
        </div>

        <!-- ── Level tabs ──────────────────────────────── -->
        <div style="display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:16px;">
            <button
                v-for="tab in LEVEL_TABS"
                :key="tab.key"
                type="button"
                :style="[
                    'background:transparent;border:0;padding:8px 16px;font-size:13.5px;font-family:inherit;cursor:pointer;transition:color .12s;white-space:nowrap;',
                    level === tab.key
                        ? 'border-bottom:2px solid var(--color-terracota,#C8521A);color:var(--color-terracota,#C8521A);font-weight:600;margin-bottom:-1px;'
                        : 'border-bottom:2px solid transparent;color:var(--text-secondary);font-weight:500;margin-bottom:-1px;'
                ]"
                @click="handleLevelClick(tab.key)"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- ── Quick views ─────────────────────────────── -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
            <button
                v-for="qv in currentQuickViews"
                :key="qv.key"
                type="button"
                :disabled="qv.disabled"
                :style="[
                    'display:inline-flex;align-items:center;gap:8px;padding:7px 12px 7px 10px;background:var(--bg-surface,#fff);border:1px solid var(--border);border-radius:999px;font-size:12.5px;font-weight:500;color:var(--text-secondary);font-family:inherit;transition:border-color .12s,color .12s,background .12s;',
                    qv.disabled
                        ? 'opacity:.45;cursor:not-allowed;'
                        : 'cursor:pointer;',
                    !qv.disabled && activeQuickView.value === qv.key
                        ? 'border-color:var(--color-terracota,#C8521A);background:color-mix(in srgb,#C8521A 10%,transparent);color:var(--color-terracota,#C8521A);font-weight:600;'
                        : '',
                ]"
                @click="!qv.disabled && handleQuickViewClick(qv.key)"
            >
                <span style="font-size:12.5px;">{{ qv.label }}</span>
                <span
                    style="font-variant-numeric:tabular-nums;font-size:11px;padding:1px 7px;border-radius:999px;background:var(--bg-page,#F4F2EF);color:var(--text-muted);font-weight:600;"
                >{{ (props.quickCounts[qv.key] ?? 0).toLocaleString('es-VE') }}</span>
            </button>
        </div>

        <!-- ── Main card ───────────────────────────────── -->
        <div class="table-wrap" style="padding:0;">

            <!-- Toolbar -->
            <div style="display:flex;align-items:stretch;gap:8px;flex-wrap:wrap;padding:14px;border-bottom:1px solid var(--border);">

                <!-- Search (always shown) -->
                <div style="position:relative;flex:1 1 240px;min-width:200px;">
                    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por nombre o correo…"
                        class="input"
                        style="padding-left:32px;width:100%;height:38px;"
                        @input="onSearchInput"
                    />
                </div>

                <!-- Career multi-select — only for Universitario -->
                <details
                    v-if="level === 'university'"
                    style="position:relative;"
                >
                    <summary
                        class="input"
                        :style="careerIds.length ? 'height:38px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;list-style:none;padding:0 10px;white-space:nowrap;border-color:var(--color-terracota,#C8521A);background:color-mix(in srgb,#C8521A 8%,transparent);' : 'height:38px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;list-style:none;padding:0 10px;white-space:nowrap;'"
                    >
                        <span style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;font-weight:600;">Carrera</span>
                        <span :style="careerIds.length ? 'font-size:13px;font-weight:600;color:var(--color-terracota,#C8521A);' : 'font-size:13px;font-weight:500;color:var(--text-primary);'">
                            {{ careerIds.length === 0 ? 'Todas' : careerIds.length === 1 ? (props.careers.find(c => c.id === careerIds[0])?.name ?? '1 sel.') : `${careerIds.length} seleccionadas` }}
                        </span>
                    </summary>
                    <div style="position:absolute;top:calc(100% + 4px);left:0;background:var(--bg-surface,#fff);border:1px solid var(--border-strong,#ccc);border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.08);padding:6px;z-index:100;display:flex;flex-direction:column;gap:1px;min-width:220px;max-height:260px;overflow-y:auto;">
                        <label
                            v-for="c in props.careers"
                            :key="c.id"
                            style="display:flex;align-items:center;gap:8px;padding:5px 8px;border-radius:5px;font-size:13px;cursor:pointer;user-select:none;"
                        >
                            <input
                                type="checkbox"
                                :checked="isCareerSelected(c.id)"
                                style="accent-color:var(--color-terracota,#C8521A);margin:0;cursor:pointer;"
                                @change="toggleCareer(c.id)"
                            />
                            {{ c.name }}
                        </label>
                    </div>
                </details>

                <!-- Year dropdown — for Primaria, Bachillerato, Universitario (not Todos) -->
                <details
                    v-if="level !== 'all'"
                    style="position:relative;"
                >
                    <summary
                        class="input"
                        :style="academicYears.length ? 'height:38px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;list-style:none;padding:0 10px;white-space:nowrap;border-color:var(--color-terracota,#C8521A);background:color-mix(in srgb,#C8521A 8%,transparent);' : 'height:38px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;list-style:none;padding:0 10px;white-space:nowrap;'"
                    >
                        <span style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;font-weight:600;">{{ yearLabel }}</span>
                        <span :style="academicYears.length ? 'font-size:13px;font-weight:600;color:var(--color-terracota,#C8521A);' : 'font-size:13px;font-weight:500;color:var(--text-primary);'">
                            {{ academicYears.length ? `${academicYears.length} sel.` : 'Todos' }}
                        </span>
                    </summary>
                    <div style="position:absolute;top:calc(100% + 4px);left:0;background:var(--bg-surface,#fff);border:1px solid var(--border-strong,#ccc);border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.08);padding:6px;z-index:100;display:flex;flex-direction:column;gap:1px;min-width:130px;">
                        <label
                            v-for="y in yearOptions"
                            :key="y"
                            style="display:flex;align-items:center;gap:8px;padding:5px 8px;border-radius:5px;font-size:13px;cursor:pointer;user-select:none;"
                        >
                            <input
                                type="checkbox"
                                :checked="isYearSelected(y)"
                                style="accent-color:var(--color-terracota,#C8521A);margin:0;cursor:pointer;"
                                @change="toggleYear(y)"
                            />
                            {{ y }}° {{ yearLabel.toLowerCase() }}
                        </label>
                    </div>
                </details>

                <!-- Section letters — for Primaria and Bachillerato only -->
                <details
                    v-if="level === 'primary' || level === 'secondary'"
                    style="position:relative;"
                >
                    <summary
                        class="input"
                        :style="sectionLetters.length ? 'height:38px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;list-style:none;padding:0 10px;white-space:nowrap;border-color:var(--color-terracota,#C8521A);background:color-mix(in srgb,#C8521A 8%,transparent);' : 'height:38px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;list-style:none;padding:0 10px;white-space:nowrap;'"
                    >
                        <span style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;font-weight:600;">Sección</span>
                        <span :style="sectionLetters.length ? 'font-size:13px;font-weight:600;color:var(--color-terracota,#C8521A);' : 'font-size:13px;font-weight:500;color:var(--text-primary);'">
                            {{ sectionLetters.length ? sectionLetters.join(', ') : 'Todas' }}
                        </span>
                    </summary>
                    <div style="position:absolute;top:calc(100% + 4px);left:0;background:var(--bg-surface,#fff);border:1px solid var(--border-strong,#ccc);border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.08);padding:6px;z-index:100;display:flex;flex-direction:column;gap:1px;min-width:120px;">
                        <label
                            v-for="l in SECTION_LETTERS"
                            :key="l"
                            style="display:flex;align-items:center;gap:8px;padding:5px 8px;border-radius:5px;font-size:13px;cursor:pointer;user-select:none;"
                        >
                            <input
                                type="checkbox"
                                :checked="isLetterSelected(l)"
                                style="accent-color:var(--color-terracota,#C8521A);margin:0;cursor:pointer;"
                                @change="toggleSectionLetter(l)"
                            />
                            Sección {{ l }}
                        </label>
                    </div>
                </details>

                <!-- Enrollment status (always shown) -->
                <details style="position:relative;">
                    <summary
                        class="input"
                        :style="enrollStatuses.length ? 'height:38px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;list-style:none;padding:0 10px;white-space:nowrap;border-color:var(--color-terracota,#C8521A);background:color-mix(in srgb,#C8521A 8%,transparent);' : 'height:38px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;list-style:none;padding:0 10px;white-space:nowrap;'"
                    >
                        <span style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;font-weight:600;">Inscripción</span>
                        <span :style="enrollStatuses.length ? 'font-size:13px;font-weight:600;color:var(--color-terracota,#C8521A);' : 'font-size:13px;font-weight:500;color:var(--text-primary);'">
                            {{ enrollStatuses.length ? `${enrollStatuses.length} sel.` : 'Todos' }}
                        </span>
                    </summary>
                    <div style="position:absolute;top:calc(100% + 4px);left:0;background:var(--bg-surface,#fff);border:1px solid var(--border-strong,#ccc);border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.08);padding:6px;z-index:100;display:flex;flex-direction:column;gap:1px;min-width:160px;">
                        <label
                            v-for="opt in ENROLL_OPTIONS"
                            :key="opt.value"
                            style="display:flex;align-items:center;gap:8px;padding:5px 8px;border-radius:5px;font-size:13px;cursor:pointer;user-select:none;"
                        >
                            <input
                                type="checkbox"
                                :checked="isStatusSelected(opt.value)"
                                style="accent-color:var(--color-terracota,#C8521A);margin:0;cursor:pointer;"
                                @change="toggleStatus(opt.value)"
                            />
                            {{ opt.label }}
                        </label>
                    </div>
                </details>

                <!-- Clear all -->
                <button
                    v-if="activeFiltersCount() > 0"
                    type="button"
                    style="height:38px;padding:0 12px;background:transparent;border:0;color:var(--text-muted);font-size:12.5px;font-family:inherit;cursor:pointer;display:inline-flex;align-items:center;gap:6px;border-radius:6px;"
                    @click="clearAll"
                >
                    Limpiar
                    <span style="display:inline-grid;place-items:center;min-width:16px;height:16px;padding:0 5px;border-radius:999px;background:var(--color-terracota,#C8521A);color:#fff;font-size:10.5px;font-weight:700;">
                        {{ activeFiltersCount() }}
                    </span>
                </button>
            </div>

            <!-- Result bar -->
            <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:10px 16px;background:var(--bg-surface-2,#f8f7f5);border-bottom:1px solid var(--border);font-size:12px;color:var(--text-secondary);flex-wrap:wrap;">
                <span>
                    <strong style="color:var(--text-primary);font-weight:700;font-size:13px;">{{ total.toLocaleString('es-VE') }}</strong>
                    {{ total === 1 ? ' estudiante' : ' estudiantes' }}
                    <span v-if="activeFiltersCount() > 0" style="color:var(--text-muted);"> de {{ props.students.meta.total.toLocaleString('es-VE') }}</span>
                </span>
                <div v-if="total > 0" style="display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
                    <span style="display:inline-flex;align-items:center;gap:6px;">
                        <span style="width:7px;height:7px;border-radius:50%;background:var(--color-success,#22c55e);display:inline-block;" />
                        {{ confirmed }} confirmadas
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:6px;">
                        <span style="width:7px;height:7px;border-radius:50%;background:#f59e0b;display:inline-block;" />
                        {{ pending }} pendientes
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:6px;">
                        <span style="width:7px;height:7px;border-radius:50%;background:var(--text-muted);display:inline-block;" />
                        {{ none }} sin inscribir
                    </span>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="total === 0"
                style="text-align:center;padding:48px 24px;color:var(--text-muted);"
            >
                <div style="width:40px;height:40px;border-radius:50%;background:var(--bg-surface-2,#f8f7f5);display:grid;place-items:center;margin:0 auto 12px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <h3 style="font-size:var(--text-base);font-weight:600;color:var(--text-primary);margin:0 0 6px;">Sin resultados</h3>
                <p style="font-size:var(--text-sm);margin:0 0 16px;">Probá ajustando los filtros o limpiándolos.</p>
                <button
                    type="button"
                    class="btn btn-secondary"
                    style="font-size:var(--text-sm);"
                    @click="clearAll"
                >Limpiar filtros</button>
            </div>

            <!-- Table -->
            <template v-else>
                <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">

                    <!-- ── Todos ── -->
                    <table v-if="level === 'all'" class="table" style="min-width:860px;">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Nivel</th>
                                <th>Cohorte</th>
                                <th>Detalle</th>
                                <th>Inscripción {{ props.activePeriod ?? '' }}</th>
                                <th style="text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in props.students.data" :key="s.id">
                                <!-- Estudiante -->
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="width:32px;height:32px;flex-shrink:0;border-radius:50%;background:color-mix(in srgb,#3D3A36 10%,transparent);color:#3D3A36;border:1.5px solid color-mix(in srgb,#3D3A36 20%,transparent);display:grid;place-items:center;font-weight:600;font-size:11px;letter-spacing:.02em;">
                                            {{ avatarInitials(s.name) }}
                                        </div>
                                        <div>
                                            <div style="font-weight:500;color:var(--text-primary);">{{ s.name }}</div>
                                            <div style="font-size:11.5px;color:var(--text-muted);font-family:var(--font-mono);">{{ s.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <!-- Nivel pill -->
                                <td>
                                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:500;">
                                        <span
                                            :style="`width:7px;height:7px;border-radius:50%;background:${levelPillColor(s.educational_level)};display:inline-block;flex-shrink:0;`"
                                        />
                                        {{ levelLabel(s.educational_level) }}
                                    </span>
                                </td>
                                <!-- Cohorte -->
                                <td style="font-variant-numeric:tabular-nums;color:var(--text-secondary);font-size:13px;">
                                    {{ s.academic_year != null ? `${s.academic_year}° año` : '—' }}
                                </td>
                                <!-- Detalle -->
                                <td style="font-size:13px;color:var(--text-secondary);">
                                    <span v-if="s.educational_level === 'university'">—</span>
                                    <span v-else>{{ s.guardian_name ?? '—' }}</span>
                                </td>
                                <!-- Inscripción -->
                                <td>
                                    <span
                                        :class="['enroll-pill', enrollClass(s)]"
                                        style="display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:11.5px;font-weight:500;white-space:nowrap;"
                                    >
                                        <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;" />
                                        {{ enrollLabel(s) }}
                                    </span>
                                </td>
                                <!-- Actions -->
                                <td>
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                        <Button variant="ghost" size="sm" icon-only icon="eye" :aria-label="`Ver perfil de ${s.name}`" />
                                        <Button variant="ghost" size="sm" icon-only icon="edit" :aria-label="`Editar ${s.name}`" />
                                        <Button variant="ghost" size="sm" icon-only icon="more-vertical" :aria-label="`Más opciones de ${s.name}`" />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- ── Primaria ── -->
                    <table v-else-if="level === 'primary'" class="table" style="min-width:800px;">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Grado</th>
                                <th>Sección</th>
                                <th>Representante</th>
                                <th>Inscripción {{ props.activePeriod ?? '' }}</th>
                                <th style="text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in props.students.data" :key="s.id">
                                <!-- Estudiante -->
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="width:32px;height:32px;flex-shrink:0;border-radius:50%;background:color-mix(in srgb,#3D3A36 10%,transparent);color:#3D3A36;border:1.5px solid color-mix(in srgb,#3D3A36 20%,transparent);display:grid;place-items:center;font-weight:600;font-size:11px;letter-spacing:.02em;">
                                            {{ avatarInitials(s.name) }}
                                        </div>
                                        <div>
                                            <div style="font-weight:500;color:var(--text-primary);">{{ s.name }}</div>
                                            <div style="font-size:11.5px;color:var(--text-muted);font-family:var(--font-mono);">{{ s.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <!-- Grado -->
                                <td style="font-variant-numeric:tabular-nums;font-weight:600;color:var(--text-primary);">
                                    {{ s.academic_year != null ? `${s.academic_year}° grado` : '—' }}
                                </td>
                                <!-- Sección -->
                                <td style="font-size:13px;font-weight:600;color:var(--text-secondary);">
                                    {{ s.section_letter ?? '—' }}
                                </td>
                                <!-- Representante -->
                                <td style="font-size:13px;color:var(--text-secondary);">
                                    <span v-if="s.guardian_name">
                                        {{ s.guardian_name }}
                                        <span v-if="s.guardian_relation" style="color:var(--text-muted);font-size:12px;"> · {{ s.guardian_relation }}</span>
                                    </span>
                                    <span v-else style="color:var(--text-muted);">—</span>
                                </td>
                                <!-- Inscripción -->
                                <td>
                                    <span
                                        :class="['enroll-pill', enrollClass(s)]"
                                        style="display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:11.5px;font-weight:500;white-space:nowrap;"
                                    >
                                        <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;" />
                                        {{ enrollLabel(s) }}
                                    </span>
                                </td>
                                <!-- Actions -->
                                <td>
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                        <Button variant="ghost" size="sm" icon-only icon="eye" :aria-label="`Ver perfil de ${s.name}`" />
                                        <Button variant="ghost" size="sm" icon-only icon="edit" :aria-label="`Editar ${s.name}`" />
                                        <Button variant="ghost" size="sm" icon-only icon="more-vertical" :aria-label="`Más opciones de ${s.name}`" />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- ── Bachillerato ── -->
                    <table v-else-if="level === 'secondary'" class="table" style="min-width:800px;">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Año</th>
                                <th>Sección</th>
                                <th>Representante</th>
                                <th>Inscripción {{ props.activePeriod ?? '' }}</th>
                                <th style="text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in props.students.data" :key="s.id">
                                <!-- Estudiante -->
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="width:32px;height:32px;flex-shrink:0;border-radius:50%;background:color-mix(in srgb,#3D3A36 10%,transparent);color:#3D3A36;border:1.5px solid color-mix(in srgb,#3D3A36 20%,transparent);display:grid;place-items:center;font-weight:600;font-size:11px;letter-spacing:.02em;">
                                            {{ avatarInitials(s.name) }}
                                        </div>
                                        <div>
                                            <div style="font-weight:500;color:var(--text-primary);">{{ s.name }}</div>
                                            <div style="font-size:11.5px;color:var(--text-muted);font-family:var(--font-mono);">{{ s.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <!-- Año -->
                                <td style="font-variant-numeric:tabular-nums;font-weight:600;color:var(--text-primary);">
                                    {{ s.academic_year != null ? `${s.academic_year}° año` : '—' }}
                                </td>
                                <!-- Sección -->
                                <td style="font-size:13px;font-weight:600;color:var(--text-secondary);">
                                    {{ s.section_letter ?? '—' }}
                                </td>
                                <!-- Representante -->
                                <td style="font-size:13px;color:var(--text-secondary);">
                                    <span v-if="s.guardian_name">
                                        {{ s.guardian_name }}
                                        <span v-if="s.guardian_relation" style="color:var(--text-muted);font-size:12px;"> · {{ s.guardian_relation }}</span>
                                    </span>
                                    <span v-else style="color:var(--text-muted);">—</span>
                                </td>
                                <!-- Inscripción -->
                                <td>
                                    <span
                                        :class="['enroll-pill', enrollClass(s)]"
                                        style="display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:11.5px;font-weight:500;white-space:nowrap;"
                                    >
                                        <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;" />
                                        {{ enrollLabel(s) }}
                                    </span>
                                </td>
                                <!-- Actions -->
                                <td>
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                        <Button variant="ghost" size="sm" icon-only icon="eye" :aria-label="`Ver perfil de ${s.name}`" />
                                        <Button variant="ghost" size="sm" icon-only icon="edit" :aria-label="`Editar ${s.name}`" />
                                        <Button variant="ghost" size="sm" icon-only icon="more-vertical" :aria-label="`Más opciones de ${s.name}`" />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- ── Universitario ── -->
                    <table v-else class="table" style="min-width:920px;">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Carrera</th>
                                <th style="text-align:center;">Año</th>
                                <th style="text-align:center;">UC</th>
                                <th>Promedio</th>
                                <th>Inscripción {{ props.activePeriod ?? '' }}</th>
                                <th style="text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in props.students.data" :key="s.id">
                                <!-- Estudiante -->
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="width:32px;height:32px;flex-shrink:0;border-radius:50%;background:color-mix(in srgb,#3D3A36 10%,transparent);color:#3D3A36;border:1.5px solid color-mix(in srgb,#3D3A36 20%,transparent);display:grid;place-items:center;font-weight:600;font-size:11px;letter-spacing:.02em;">
                                            {{ avatarInitials(s.name) }}
                                        </div>
                                        <div>
                                            <div style="font-weight:500;color:var(--text-primary);">{{ s.name }}</div>
                                            <div style="font-size:11.5px;color:var(--text-muted);font-family:var(--font-mono);">{{ s.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <!-- Carrera -->
                                <td>
                                    <span style="font-size:13px;color:var(--text-secondary);white-space:nowrap;">
                                        {{ s.career_name ?? '—' }}
                                    </span>
                                </td>
                                <!-- Año -->
                                <td style="text-align:center;font-variant-numeric:tabular-nums;font-weight:600;color:var(--text-primary);">
                                    {{ s.academic_year != null ? `${s.academic_year}°` : '—' }}
                                </td>
                                <!-- UC -->
                                <td style="text-align:center;font-variant-numeric:tabular-nums;font-size:13px;color:var(--text-secondary);">
                                    {{ s.uc_inscritas ?? '—' }}
                                </td>
                                <!-- Promedio -->
                                <td>
                                    <div style="display:inline-flex;flex-direction:column;gap:4px;min-width:78px;">
                                        <span style="font-weight:600;font-size:13px;font-variant-numeric:tabular-nums;color:var(--text-muted);font-family:var(--font-mono);">—</span>
                                        <span style="display:block;height:4px;background:var(--border);border-radius:2px;width:78px;overflow:hidden;">
                                            <span style="display:block;height:100%;border-radius:2px;background:var(--border);width:0%;" />
                                        </span>
                                    </div>
                                </td>
                                <!-- Inscripción -->
                                <td>
                                    <span
                                        :class="['enroll-pill', enrollClass(s)]"
                                        style="display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:11.5px;font-weight:500;white-space:nowrap;"
                                    >
                                        <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;" />
                                        {{ enrollLabel(s) }}
                                    </span>
                                </td>
                                <!-- Actions -->
                                <td>
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                        <Button variant="ghost" size="sm" icon-only icon="eye" :aria-label="`Ver perfil de ${s.name}`" />
                                        <Button variant="ghost" size="sm" icon-only icon="edit" :aria-label="`Editar ${s.name}`" />
                                        <Button variant="ghost" size="sm" icon-only icon="more-vertical" :aria-label="`Más opciones de ${s.name}`" />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>

                <Pagination
                    :paginator="props.students.meta"
                    :route-url="index.url()"
                    :filters="paginationFilters"
                />
            </template>
        </div>
    </div>
</template>

<style scoped>
.enroll-pill.enroll-ok     { background: var(--success-bg, #dcfce7); color: var(--success-fg, #15803d); }
.enroll-pill.enroll-warn   { background: #fef9c3; color: #a16207; }
.enroll-pill.enroll-none   { background: var(--bg-surface-2, #f8f7f5); color: var(--text-muted); border: 1px solid var(--border); }
.enroll-pill.enroll-danger { background: var(--danger-bg, #fee2e2); color: var(--danger-fg, #b91c1c); }
</style>
