<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = withDefaults(defineProps<{
    paginator: {
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
    routeUrl: string
    filters?: Record<string, string | number | undefined>
    perPageOptions?: number[]
}>(), {
    perPageOptions: () => [10, 15, 20, 25, 50, 100],
})

const from = computed(() =>
    props.paginator.total === 0
        ? 0
        : (props.paginator.current_page - 1) * props.paginator.per_page + 1,
)

const to = computed(() =>
    Math.min(props.paginator.current_page * props.paginator.per_page, props.paginator.total),
)

function pageRange(): (number | '…')[] {
    const current = props.paginator.current_page
    const total = props.paginator.last_page
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
    const result: (number | '…')[] = [1]
    if (current > 4) result.push('…')
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    for (let i = start; i <= end; i++) result.push(i)
    if (current < total - 3) result.push('…')
    result.push(total)
    return result
}

function activeFilters(): Record<string, string | number> {
    const out: Record<string, string | number> = {}
    if (props.filters) {
        for (const [k, v] of Object.entries(props.filters)) {
            if (v !== undefined && v !== '') out[k] = v
        }
    }
    return out
}

function go(page: number): void {
    if (page < 1 || page > props.paginator.last_page) return
    router.get(
        props.routeUrl,
        { ...activeFilters(), per_page: props.paginator.per_page, page },
        { preserveState: true, replace: true },
    )
}

function changePerPage(e: Event): void {
    const value = parseInt((e.target as HTMLSelectElement).value, 10)
    router.get(
        props.routeUrl,
        { ...activeFilters(), per_page: value, page: 1 },
        { preserveState: true, replace: true },
    )
}
</script>

<template>
    <div class="pg-footer">
        <span class="pg-info">
            Mostrando <strong>{{ from }}–{{ to }}</strong> de {{ paginator.total }}
        </span>

        <div class="pg-pager">
            <button
                class="pg-btn"
                :disabled="paginator.current_page <= 1"
                aria-label="Primera página"
                @click="go(1)"
            >
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m11 17-5-5 5-5M18 17l-5-5 5-5" />
                </svg>
            </button>
            <button
                class="pg-btn"
                :disabled="paginator.current_page <= 1"
                aria-label="Página anterior"
                @click="go(paginator.current_page - 1)"
            >
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6" />
                </svg>
            </button>

            <template v-for="p in pageRange()" :key="typeof p === 'number' ? p : `e-${p}`">
                <span v-if="p === '…'" class="pg-ellipsis">…</span>
                <button
                    v-else
                    :class="['pg-btn', p === paginator.current_page ? 'pg-btn--active' : '']"
                    :aria-current="p === paginator.current_page ? 'page' : undefined"
                    @click="go(Number(p))"
                >{{ p }}</button>
            </template>

            <button
                class="pg-btn"
                :disabled="paginator.current_page >= paginator.last_page"
                aria-label="Página siguiente"
                @click="go(paginator.current_page + 1)"
            >
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </button>
            <button
                class="pg-btn"
                :disabled="paginator.current_page >= paginator.last_page"
                aria-label="Última página"
                @click="go(paginator.last_page)"
            >
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m6 17 5-5-5-5M13 17l5-5-5-5" />
                </svg>
            </button>
        </div>

        <div class="pg-size">
            <span class="pg-info">Filas por página</span>
            <select
                :value="paginator.per_page"
                class="input"
                style="width:70px;height:32px;padding:0 8px;font-size:var(--text-sm);"
                @change="changePerPage"
            >
                <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
            </select>
        </div>
    </div>
</template>

<style scoped>
.pg-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 12px 16px;
    border-top: 1px solid var(--border);
    background: var(--bg-surface-2);
    flex-wrap: wrap;
}

.pg-info {
    font-size: var(--text-sm);
    color: var(--text-muted);
    white-space: nowrap;
}

.pg-info strong {
    color: var(--text-primary);
    font-weight: 600;
}

.pg-pager {
    display: flex;
    align-items: center;
    gap: 3px;
}

.pg-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1px solid var(--border);
    background: var(--bg-surface);
    font-size: var(--text-sm);
    font-family: var(--font-sans);
    color: var(--text-primary);
    cursor: pointer;
    transition: background 120ms, color 120ms, border-color 120ms;
    line-height: 1;
}

.pg-btn:hover:not(:disabled):not(.pg-btn--active) {
    background: var(--bg-surface-2);
    border-color: var(--border-strong);
}

.pg-btn--active {
    background: var(--accent);
    color: var(--accent-fg);
    border-color: var(--accent);
    font-weight: 600;
}

.pg-btn:disabled {
    opacity: 0.38;
    cursor: not-allowed;
}

.pg-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    font-size: var(--text-sm);
    color: var(--text-muted);
    user-select: none;
}

.pg-size {
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}
</style>
