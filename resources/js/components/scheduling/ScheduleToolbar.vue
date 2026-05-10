<script setup lang="ts">
import { computed } from 'vue'
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
    'update:view':       ['week' | 'day' | 'list']
    'update:query':      [string]
    'update:periodId':   [number | null]
    'update:sectionId':  [number | null]
    'update:professorId':[number | null]
    create:              []
    applyFilters:        []
}>()

const activePeriodName   = computed(() => props.periods.find((p) => p.id === props.periodId)?.name   ?? null)
const activeSectionCode  = computed(() => props.sections.find((s) => s.id === props.sectionId)?.code  ?? null)
const activeProfName     = computed(() => props.professors.find((p) => p.id === props.professorId)?.name ?? null)
</script>

<template>
    <div>
        <!-- Toolbar row -->
        <div class="sch-toolbar">
            <!-- View switcher -->
            <div class="sch-views">
                <button :class="{ active: view === 'week' }"  @click="emit('update:view', 'week')">⊞ Semana</button>
                <button :class="{ active: view === 'day' }"   @click="emit('update:view', 'day')">▭ Día</button>
                <button :class="{ active: view === 'list' }"  @click="emit('update:view', 'list')">☰ Lista</button>
            </div>

            <div class="sch-toolbar-spacer" />

            <!-- Search -->
            <div class="sch-search">
                <span class="icon">⌕</span>
                <input
                    :value="query"
                    type="search"
                    placeholder="Buscar asignatura, profesor, aula…"
                    @input="emit('update:query', ($event.target as HTMLInputElement).value)"
                />
            </div>

            <!-- Create button -->
            <button v-if="canCreate" class="btn primary" @click="emit('create')">
                + Nuevo horario
            </button>
        </div>

        <!-- Filter chips row -->
        <div v-if="activePeriodName || activeSectionCode || activeProfName" class="sch-filters">
            <span v-if="activePeriodName" class="filter-chip">
                <span class="key">Período:</span>
                <span class="val">{{ activePeriodName }}</span>
                <button class="x" aria-label="Quitar" @click="emit('update:periodId', null); emit('applyFilters')">✕</button>
            </span>
            <span v-if="activeSectionCode" class="filter-chip">
                <span class="key">Sección:</span>
                <span class="val">{{ activeSectionCode }}</span>
                <button class="x" aria-label="Quitar" @click="emit('update:sectionId', null); emit('applyFilters')">✕</button>
            </span>
            <span v-if="activeProfName" class="filter-chip">
                <span class="key">Profesor:</span>
                <span class="val">{{ activeProfName }}</span>
                <button class="x" aria-label="Quitar" @click="emit('update:professorId', null); emit('applyFilters')">✕</button>
            </span>
            <button
                class="filters-clear"
                @click="emit('update:periodId', null); emit('update:sectionId', null); emit('update:professorId', null); emit('applyFilters')"
            >
                Limpiar todos
            </button>
        </div>
    </div>
</template>

<style scoped>
.sch-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 0;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
}
.sch-toolbar-spacer { flex: 1; }

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

.btn.primary {
    height: 34px; padding: 0 16px;
    background: var(--accent); color: #fff;
    border: 0; border-radius: var(--r-md);
    font-family: inherit; font-size: 13px; font-weight: 600;
    cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
}
.btn.primary:hover { background: color-mix(in srgb, var(--accent) 85%, #000); }

/* Filter chips */
.sch-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 10px 0; }
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
.filters-clear {
    margin-left: auto; background: transparent; border: 0;
    color: var(--text-muted); font-size: 12px; font-family: inherit; cursor: pointer;
    text-decoration: underline; text-underline-offset: 2px;
}
.filters-clear:hover { color: var(--text-primary); }

@media (max-width: 820px) {
    .sch-search { width: 100%; order: 10; }
    .sch-toolbar-spacer { display: none; }
    .sch-views { width: 100%; order: 9; }
    .sch-views button { flex: 1; justify-content: center; height: 36px; }
}
</style>
