<script setup lang="ts">
import AppIcon from '@/components/UI/AppIcon.vue'
import type { EnrollmentFilters } from '@/types/enrollment'

const props = defineProps<{
    search: string
    filters: EnrollmentFilters
    resultsCount: number
}>()

const emit = defineEmits<{
    'update:search': [value: string]
    'update:filters': [value: EnrollmentFilters]
}>()

function setFilter<K extends keyof EnrollmentFilters>(key: K, value: EnrollmentFilters[K]) {
    emit('update:filters', { ...props.filters, [key]: value })
}

const TYPES = [
    { v: 'all'      as const, label: 'Todas' },
    { v: 'oblig'    as const, label: 'Obligatorias' },
    { v: 'electiva' as const, label: 'Electivas' },
]
</script>

<template>
    <div class="enr-toolbar">
        <!-- Search -->
        <div class="enr-search">
            <AppIcon name="search" :size="14" />
            <input
                :value="search"
                placeholder="Buscar materia o código…"
                @input="emit('update:search', ($event.target as HTMLInputElement).value)"
            />
            <button
                v-if="search"
                class="enr-search-clear"
                aria-label="Limpiar búsqueda"
                @click="emit('update:search', '')"
            >
                <AppIcon name="x" :size="11" />
            </button>
        </div>

        <!-- Type segmented -->
        <div class="enr-type-seg" role="radiogroup" aria-label="Tipo de materia">
            <button
                v-for="t in TYPES"
                :key="t.v"
                :class="['enr-type-seg-btn', filters.type === t.v && 'enr-type-seg-btn--active']"
                @click="setFilter('type', t.v)"
            >
                {{ t.label }}
            </button>
        </div>

        <!-- Filter chips -->
        <button
            :class="['enr-filter-chip', filters.recommendedOnly && 'enr-filter-chip--on']"
            @click="setFilter('recommendedOnly', !filters.recommendedOnly)"
        >
            <AppIcon name="calendar" :size="11" /> Trimestre sugerido
        </button>
        <button
            :class="['enr-filter-chip', filters.prereqsOnly && 'enr-filter-chip--on']"
            @click="setFilter('prereqsOnly', !filters.prereqsOnly)"
        >
            <AppIcon name="check" :size="11" /> Prereqs OK
        </button>
        <button
            :class="['enr-filter-chip', filters.hideCompleted && 'enr-filter-chip--on']"
            @click="setFilter('hideCompleted', !filters.hideCompleted)"
        >
            <AppIcon name="eye" :size="11" /> Ocultar aprobadas
        </button>

        <span class="enr-toolbar-count">{{ resultsCount }} materia{{ resultsCount === 1 ? '' : 's' }}</span>
    </div>
</template>

<style>
.enr-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    margin-bottom: 12px;
}

.enr-search {
    display: flex;
    align-items: center;
    gap: 8px;
    height: 32px;
    padding: 0 10px;
    background: var(--bg-page);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    flex: 1 1 200px;
    color: var(--text-muted);
    transition: border-color .15s;
}
.enr-search:focus-within { border-color: var(--accent); }
.enr-search input {
    flex: 1;
    border: 0;
    outline: 0;
    background: transparent;
    color: var(--text-primary);
    font-size: 13px;
    font-family: var(--font-sans);
}
.enr-search-clear {
    background: transparent;
    border: 0;
    color: var(--text-muted);
    cursor: pointer;
    padding: 2px;
    border-radius: 50%;
    line-height: 0;
}
.enr-search-clear:hover { background: var(--bg-sunken); color: var(--text-primary); }

.enr-type-seg {
    display: inline-flex;
    background: var(--bg-page);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 2px;
    gap: 2px;
}
.enr-type-seg-btn {
    border: 0;
    background: transparent;
    height: 26px;
    padding: 0 11px;
    border-radius: 3px;
    font-size: 12px;
    font-family: var(--font-sans);
    color: var(--text-secondary);
    cursor: pointer;
    white-space: nowrap;
}
.enr-type-seg-btn:hover { color: var(--text-primary); }
.enr-type-seg-btn--active {
    background: var(--bg-surface);
    color: var(--text-primary);
    font-weight: 500;
    box-shadow: 0 1px 2px rgba(0,0,0,.06);
}

.enr-filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 28px;
    padding: 0 11px;
    background: transparent;
    border: 1px solid var(--border);
    border-radius: var(--radius-pill);
    font-size: 12px;
    font-family: var(--font-sans);
    color: var(--text-secondary);
    cursor: pointer;
    transition: background .12s, border-color .12s, color .12s;
    white-space: nowrap;
}
.enr-filter-chip:hover { border-color: var(--border-strong); color: var(--text-primary); }
.enr-filter-chip--on {
    background: var(--accent-soft);
    border-color: color-mix(in srgb, var(--accent) 30%, transparent);
    color: var(--accent);
}

.enr-toolbar-count { font-size: 12px; color: var(--text-muted); margin-left: auto; white-space: nowrap; }
</style>
