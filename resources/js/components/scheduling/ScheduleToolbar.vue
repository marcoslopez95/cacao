<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount, watch } from 'vue'

import type { ScheduleAvailablePeriod, ScheduleAvailableSection, ScheduleAvailableProfessor } from '@/types/scheduling'

const props = defineProps<{
    view: 'week' | 'day' | 'list'
    query: string
    periodId: number | null
    sectionId: number | null
    professorId: number | null
    periods: ScheduleAvailablePeriod[]
    sections: ScheduleAvailableSection[]
    professors: ScheduleAvailableProfessor[]
    canCreate?: boolean
}>()

const emit = defineEmits<{
    'update:view':        ['week' | 'day' | 'list']
    'update:query':       [string]
    'update:periodId':    [number | null]
    'update:sectionId':   [number | null]
    'update:professorId': [number | null]
    create:               []
    applyFilters:         []
}>()

// Dropdown visibility
const showPeriodDrop  = ref(false)
const showFilterPanel = ref(false)

// Filter panel pending values (applied on "Aplicar")
const pendingSectionId   = ref<number | null>(props.sectionId)
const pendingProfessorId = ref<number | null>(props.professorId)
watch(() => props.sectionId,   (v) => {
 pendingSectionId.value   = v 
})
watch(() => props.professorId, (v) => {
 pendingProfessorId.value = v 
})

// Computed labels
const activePeriodName  = computed(() => props.periods.find((p) => p.id === props.periodId)?.name ?? null)
const activeSectionCode = computed(() => props.sections.find((s) => s.id === props.sectionId)?.code ?? null)
const activeProfName    = computed(() => props.professors.find((p) => p.id === props.professorId)?.name ?? null)
const hasActiveFilters  = computed(() => activePeriodName.value || activeSectionCode.value || activeProfName.value)

// Week range display
const MONTH_NAMES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic']
const weekRange = computed(() => {
    const now = new Date()
    const dow = now.getDay()
    const diff = dow === 0 ? -6 : 1 - dow
    const monday = new Date(now); monday.setDate(now.getDate() + diff)
    const saturday = new Date(monday); saturday.setDate(monday.getDate() + 5)
    const mDay = monday.getDate(); const mMon = MONTH_NAMES[monday.getMonth()]
    const sDay = saturday.getDate(); const sMon = MONTH_NAMES[saturday.getMonth()]

    return mMon === sMon
        ? `${mDay}–${sDay} ${sMon} ${monday.getFullYear()}`
        : `${mDay} ${mMon} – ${sDay} ${sMon} ${monday.getFullYear()}`
})

// Period selector
function selectPeriod(id: number | null): void {
    emit('update:periodId', id)
    emit('applyFilters')
    showPeriodDrop.value = false
}

// Filter panel
function applyFilters(): void {
    emit('update:sectionId', pendingSectionId.value)
    emit('update:professorId', pendingProfessorId.value)
    emit('applyFilters')
    showFilterPanel.value = false
}

function clearAll(): void {
    emit('update:periodId', null)
    emit('update:sectionId', null)
    emit('update:professorId', null)
    emit('applyFilters')
    pendingSectionId.value   = null
    pendingProfessorId.value = null
}

// Close dropdowns on outside click
function onDocClick(e: MouseEvent): void {
    const t = e.target as Element

    if (!t.closest('.period-wrap')) {
showPeriodDrop.value = false
}

    if (!t.closest('.filter-panel-wrap')) {
showFilterPanel.value = false
}
}
onMounted(()    => document.addEventListener('click', onDocClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocClick))
</script>

<template>
    <div>
        <!-- ── Toolbar row ─────────────────────────────────────── -->
        <div class="sch-toolbar">

            <!-- Period selector (left) -->
            <div class="period-wrap">
                <button
                    dusk="period-dropdown-btn"
                    class="sch-period"
                    :class="{ active: periodId !== null }"
                    @click.stop="showPeriodDrop = !showPeriodDrop; showFilterPanel = false"
                >
                    <span class="lbl-mini">Período</span>
                    <span class="val">{{ activePeriodName ?? 'Todos' }}</span>
                    <span class="chevron">▾</span>
                </button>
                <div v-if="showPeriodDrop" class="drop-panel period-drop">
                    <button
                        dusk="period-option-all"
                        class="drop-item"
                        :class="{ selected: periodId === null }"
                        @click.stop="selectPeriod(null)"
                    >
                        Todos los períodos
                    </button>
                    <button
                        v-for="p in periods"
                        :key="p.id"
                        :dusk="`period-option-${p.id}`"
                        class="drop-item"
                        :class="{ selected: periodId === p.id }"
                        @click.stop="selectPeriod(p.id)"
                    >
                        {{ p.name }}
                    </button>
                </div>
            </div>

            <!-- Week range display -->
            <div class="sch-datenav">
                <span class="range">📅 {{ weekRange }}</span>
            </div>

            <div class="sch-toolbar-spacer" />

            <!-- Search -->
            <div class="sch-search">
                <span class="icon">⌕</span>
                <input
                    dusk="search-input"
                    :value="query"
                    type="search"
                    placeholder="Buscar asignatura, profesor, aula…"
                    @input="emit('update:query', ($event.target as HTMLInputElement).value)"
                />
            </div>

            <!-- View switcher (right) -->
            <div dusk="view-switcher" class="sch-views">
                <button dusk="btn-view-week" :class="{ active: view === 'week' }" @click="emit('update:view', 'week')">⊞ Semana</button>
                <button dusk="btn-view-day"  :class="{ active: view === 'day' }"  @click="emit('update:view', 'day')">▭ Día</button>
                <button dusk="btn-view-list" :class="{ active: view === 'list' }" @click="emit('update:view', 'list')">☰ Lista</button>
            </div>

            <!-- Create button -->
            <button v-if="canCreate" dusk="btn-create-schedule" class="btn primary" @click="emit('create')">
                + Nuevo horario
            </button>
        </div>

        <!-- ── Filter chips row ────────────────────────────────── -->
        <div class="sch-filters">
            <!-- Active filter chips -->
            <span v-if="activePeriodName" class="filter-chip">
                <span class="key">Período:</span>
                <span class="val">{{ activePeriodName }}</span>
                <button dusk="filter-chip-period-remove" class="x" aria-label="Quitar" @click="emit('update:periodId', null); emit('applyFilters')">✕</button>
            </span>
            <span v-if="activeSectionCode" class="filter-chip">
                <span class="key">Sección:</span>
                <span class="val">{{ activeSectionCode }}</span>
                <button dusk="filter-chip-section-remove" class="x" aria-label="Quitar" @click="emit('update:sectionId', null); pendingSectionId = null; emit('applyFilters')">✕</button>
            </span>
            <span v-if="activeProfName" class="filter-chip">
                <span class="key">Profesor:</span>
                <span class="val">{{ activeProfName }}</span>
                <button dusk="filter-chip-professor-remove" class="x" aria-label="Quitar" @click="emit('update:professorId', null); pendingProfessorId = null; emit('applyFilters')">✕</button>
            </span>

            <!-- + Filtro dropdown -->
            <div class="filter-panel-wrap">
                <button
                    dusk="filter-add-btn"
                    class="filter-add"
                    @click.stop="showFilterPanel = !showFilterPanel; showPeriodDrop = false"
                >
                    + Filtro
                </button>
                <div v-if="showFilterPanel" class="drop-panel filter-panel">
                    <div class="filter-panel-row">
                        <label>Sección</label>
                        <select dusk="filter-section-select" v-model="pendingSectionId">
                            <option :value="null">Todas</option>
                            <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.code }}</option>
                        </select>
                    </div>
                    <div class="filter-panel-row">
                        <label>Profesor</label>
                        <select dusk="filter-professor-select" v-model="pendingProfessorId">
                            <option :value="null">Todos</option>
                            <option v-for="p in professors" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div class="filter-panel-actions">
                        <button dusk="filter-apply-btn" class="fp-apply" @click.stop="applyFilters">Aplicar</button>
                    </div>
                </div>
            </div>

            <!-- Clear all -->
            <button v-if="hasActiveFilters" dusk="filters-clear-btn" class="filters-clear" @click="clearAll">
                Limpiar todos
            </button>
        </div>
    </div>
</template>

<style scoped>
/* ── Toolbar ──────────────────────────────────────────────── */
.sch-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 0;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
}
.sch-toolbar-spacer { flex: 1; min-width: 0; }

/* Period pill */
.period-wrap { position: relative; }
.sch-period {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 34px;
    padding: 0 12px;
    background: var(--bg-surface);
    border: 1px solid var(--border-strong);
    border-radius: var(--r-md);
    cursor: pointer;
    font-size: 13px;
    font-family: inherit;
    color: var(--text-primary);
    white-space: nowrap;
}
.sch-period:hover { background: var(--bg-surface-2); }
.sch-period.active { border-color: var(--accent); }
.sch-period .lbl-mini {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    font-weight: 500;
}
.sch-period .val { font-weight: 600; }
.sch-period .chevron { font-size: 10px; color: var(--text-muted); margin-left: 2px; }

/* Week range */
.sch-datenav {
    display: inline-flex;
    align-items: center;
    height: 34px;
    padding: 0 14px;
    background: var(--bg-surface);
    border: 1px solid var(--border-strong);
    border-radius: var(--r-md);
    white-space: nowrap;
}
.sch-datenav .range {
    font-size: 13px;
    font-weight: 500;
    font-variant-numeric: tabular-nums;
    color: var(--text-secondary);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* View switcher */
.sch-views {
    display: inline-flex;
    background: var(--bg-surface-2);
    border: 1px solid var(--border);
    border-radius: var(--r-md);
    padding: 3px;
    gap: 2px;
}
.sch-views button {
    height: 28px;
    padding: 0 12px;
    border: 0;
    background: transparent;
    color: var(--text-muted);
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: var(--r-sm);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.sch-views button:hover { color: var(--text-primary); }
.sch-views button.active { background: var(--bg-surface); color: var(--text-primary); box-shadow: var(--shadow-xs); }

/* Search */
.sch-search {
    position: relative;
    display: inline-flex;
    align-items: center;
    height: 34px;
    width: 220px;
}
.sch-search .icon { position: absolute; left: 10px; color: var(--text-muted); pointer-events: none; font-size: 15px; }
.sch-search input {
    width: 100%;
    height: 34px;
    padding: 0 12px 0 32px;
    background: var(--bg-surface);
    border: 1px solid var(--border-strong);
    border-radius: var(--r-md);
    color: var(--text-primary);
    font-family: inherit;
    font-size: 13px;
}
.sch-search input::placeholder { color: var(--text-muted); }
.sch-search input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent); }

/* Create button */
.btn.primary {
    height: 34px; padding: 0 16px;
    background: var(--accent); color: #fff;
    border: 0; border-radius: var(--r-md);
    font-family: inherit; font-size: 13px; font-weight: 600;
    cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    white-space: nowrap;
}
.btn.primary:hover { background: color-mix(in srgb, var(--accent) 85%, #000); }

/* ── Filter chips row ─────────────────────────────────────── */
.sch-filters {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    padding: 8px 0;
    min-height: 44px;
}

.filter-chip {
    display: inline-flex; align-items: center; gap: 6px;
    height: 28px; padding: 0 4px 0 10px;
    background: var(--bg-surface); border: 1px solid var(--border-strong);
    border-radius: 999px; font-size: 12px; color: var(--text-primary); font-family: inherit;
}
.filter-chip .key { color: var(--text-muted); font-weight: 500; }
.filter-chip .val { font-weight: 600; }
.filter-chip .x {
    width: 20px; height: 20px;
    border: 0; background: transparent; color: var(--text-muted);
    cursor: pointer; border-radius: 50%;
    display: grid; place-items: center; font-size: 11px;
}
.filter-chip .x:hover { background: var(--bg-sunken); color: var(--text-primary); }

/* + Filtro button */
.filter-panel-wrap { position: relative; }
.filter-add {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 28px;
    padding: 0 12px;
    background: transparent;
    border: 1px dashed var(--border-strong);
    border-radius: 999px;
    color: var(--text-muted);
    font-family: inherit;
    font-size: 12px;
    cursor: pointer;
}
.filter-add:hover { border-color: var(--accent); color: var(--accent); }

/* Clear all */
.filters-clear {
    margin-left: auto; background: transparent; border: 0;
    color: var(--text-muted); font-size: 12px; font-family: inherit; cursor: pointer;
    text-decoration: underline; text-underline-offset: 2px;
}
.filters-clear:hover { color: var(--text-primary); }

/* ── Shared dropdown panel ────────────────────────────────── */
.drop-panel {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    background: var(--bg-surface);
    border: 1px solid var(--border-strong);
    border-radius: var(--r-md);
    box-shadow: var(--shadow-md, 0 8px 24px rgba(0,0,0,0.18));
    z-index: 100;
    overflow: hidden;
    animation: drop-in 0.1s ease-out;
}
@keyframes drop-in { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }

/* Period dropdown */
.period-drop { min-width: 200px; padding: 4px 0; }
.drop-item {
    display: block;
    width: 100%;
    padding: 8px 14px;
    text-align: left;
    background: transparent;
    border: 0;
    font-family: inherit;
    font-size: 13px;
    color: var(--text-primary);
    cursor: pointer;
    white-space: nowrap;
}
.drop-item:hover { background: var(--bg-surface-2); }
.drop-item.selected { color: var(--accent); font-weight: 600; }
.drop-item.selected::before { content: '✓ '; }

/* Filter panel (section + professor) */
.filter-panel { min-width: 240px; padding: 12px; display: flex; flex-direction: column; gap: 10px; }
.filter-panel-row { display: flex; flex-direction: column; gap: 4px; }
.filter-panel-row label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); }
.filter-panel-row select {
    height: 32px;
    padding: 0 8px;
    background: var(--bg-surface-2);
    border: 1px solid var(--border-strong);
    border-radius: var(--r-sm);
    font-family: inherit;
    font-size: 13px;
    color: var(--text-primary);
    cursor: pointer;
}
.filter-panel-row select:focus { outline: none; border-color: var(--accent); }
.filter-panel-actions { display: flex; justify-content: flex-end; padding-top: 4px; border-top: 1px solid var(--border); }
.fp-apply {
    height: 30px; padding: 0 14px;
    background: var(--accent); color: #fff;
    border: 0; border-radius: var(--r-sm);
    font-family: inherit; font-size: 13px; font-weight: 600;
    cursor: pointer;
}
.fp-apply:hover { background: color-mix(in srgb, var(--accent) 85%, #000); }

/* ── Mobile ───────────────────────────────────────────────── */
@media (max-width: 820px) {
    .sch-search { width: 100%; order: 10; }
    .sch-toolbar-spacer { display: none; }
    .sch-views { width: 100%; order: 9; justify-content: stretch; }
    .sch-views button { flex: 1; justify-content: center; height: 36px; }
    .sch-datenav { width: 100%; justify-content: center; }
    .sch-period { width: 100%; justify-content: space-between; }
}
</style>
