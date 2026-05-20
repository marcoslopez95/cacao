<script setup lang="ts">
import { ref } from 'vue'
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { useMediaQuery } from '@vueuse/core'
import AppIcon from '@/components/UI/AppIcon.vue'
import EnrollmentToolbar from '@/components/enrollment/EnrollmentToolbar.vue'
import EnrollmentMateriaRow from '@/components/enrollment/EnrollmentMateriaRow.vue'
import EnrollmentSummaryPanel from '@/components/enrollment/EnrollmentSummaryPanel.vue'
import { useEnrollmentState } from '@/composables/enrollment/useEnrollmentState'
import { useEnrollmentFilters } from '@/composables/enrollment/useEnrollmentFilters'
import {
    ENROLLMENT_SUBJECTS,
    ENROLLMENT_RULES,
    ENROLLMENT_INITIAL_SELECTIONS,
} from '@/composables/enrollment/enrollmentMockData'
import { index as enrollmentIndex } from '@/routes/enrollment'
import type { EnrollmentSelections, EnrollmentGhostCandidate } from '@/types/enrollment'

setLayoutProps({
    breadcrumbs: [
        { title: 'Estudiante', href: '#' },
        { title: 'Inscripción', href: enrollmentIndex.url() },
    ],
})

const isMobile = useMediaQuery('(max-width: 820px)')

// State
const selections = ref<EnrollmentSelections>({ ...ENROLLMENT_INITIAL_SELECTIONS })
const expanded = ref(new Set<string>())
const ghost = ref<EnrollmentGhostCandidate | null>(null)

// Composables
const { search, filters, filteredSubjects } = useEnrollmentFilters(ENROLLMENT_SUBJECTS)
const { summary, creditsPct, creditsStatus, findConflict, select, unselect } =
    useEnrollmentState(
        ENROLLMENT_SUBJECTS,
        selections,
        fn => { selections.value = fn(selections.value) },
    )

function toggleExpanded(code: string): void {
    const next = new Set(expanded.value)
    if (next.has(code)) {
        next.delete(code)
    } else {
        next.add(code)
    }
    expanded.value = next
}

function handleConfirm(): void {
    alert('Inscripción confirmada (demo)')
}
</script>

<template>
    <Head title="Inscripción" />

    <!-- Page header -->
    <div class="enr-page-head">
        <div class="enr-page-head-l">
            <span class="enr-page-eyebrow">Período {{ ENROLLMENT_RULES.period }} · {{ ENROLLMENT_RULES.trimester }}</span>
            <h1 class="enr-page-title">Inscripción de materias</h1>
            <p class="enr-page-sub">
                Selecciona las materias y secciones que cursarás este trimestre.
                Solo puedes elegir <strong>una sección por materia</strong>.
            </p>
        </div>
        <div class="enr-page-head-r">
            <div class="enr-deadline">
                <AppIcon name="clock" :size="13" />
                <div>
                    <div class="enr-deadline-t">Cierra el <strong>{{ ENROLLMENT_RULES.deadline }}</strong></div>
                    <div class="enr-deadline-s">faltan {{ ENROLLMENT_RULES.daysLeft }} días</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Layout -->
    <div :class="['enr-layout', isMobile ? 'enr-layout--mobile' : 'enr-layout--split']">
        <!-- Main panel -->
        <div class="enr-main">
            <EnrollmentToolbar
                :search="search"
                :filters="filters"
                :results-count="filteredSubjects.length"
                @update:search="v => search = v"
                @update:filters="v => filters = v"
            />

            <!-- Subject list -->
            <div class="enr-list" role="list">
                <div v-if="filteredSubjects.length === 0" class="enr-empty">
                    <AppIcon name="search" :size="20" />
                    <div class="enr-empty-t">Sin resultados</div>
                    <div class="enr-empty-s">Prueba con otros filtros o limpia la búsqueda.</div>
                </div>
                <EnrollmentMateriaRow
                    v-for="subject in filteredSubjects"
                    :key="subject.code"
                    :subject="subject"
                    :selections="selections"
                    :expanded="expanded.has(subject.code)"
                    :find-conflict="findConflict"
                    @toggle="toggleExpanded(subject.code)"
                    @select="(code, idx) => select(code, idx)"
                    @unselect="(code) => unselect(code)"
                    @ghost-enter="g => ghost = g"
                    @ghost-leave="ghost = null"
                />
            </div>
        </div>

        <!-- Summary panel (desktop: sticky aside / mobile: below) -->
        <aside class="enr-aside">
            <EnrollmentSummaryPanel
                :summary="summary"
                :rules="ENROLLMENT_RULES"
                :credits-pct="creditsPct"
                :credits-status="creditsStatus"
                :subjects="ENROLLMENT_SUBJECTS"
                :selections="selections"
                :ghost="isMobile ? null : ghost"
                :mobile="isMobile"
                @confirm="handleConfirm"
                @draft="() => {}"
            />
        </aside>
    </div>
</template>

<style>
/* Page header */
.enr-page-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    padding-bottom: 18px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 16px;
}
.enr-page-eyebrow {
    display: inline-block;
    font-family: var(--font-mono);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--accent);
    margin-bottom: 6px;
}
.enr-page-title {
    font-size: 24px;
    font-weight: 600;
    letter-spacing: -0.02em;
    margin: 0 0 4px;
    color: var(--text-primary);
}
.enr-page-sub {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 0;
    max-width: 60ch;
}
.enr-deadline {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: var(--accent-soft);
    border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
    border-radius: var(--radius-md);
}
.enr-deadline-t { font-size: 13px; color: var(--text-primary); }
.enr-deadline-s { font-size: 11px; color: var(--text-muted); font-family: var(--font-mono); }

/* Layout: split (desktop) */
.enr-layout { display: flex; gap: 20px; align-items: flex-start; }
.enr-layout--split .enr-main { flex: 1; min-width: 0; }
.enr-layout--split .enr-aside { width: 300px; flex-shrink: 0; }

/* Layout: mobile */
.enr-layout--mobile { flex-direction: column; }
.enr-layout--mobile .enr-main { width: 100%; }
.enr-layout--mobile .enr-aside { width: 100%; }

/* Subject list */
.enr-list { display: flex; flex-direction: column; }

/* Empty state */
.enr-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 40px 20px;
    color: var(--text-muted);
    text-align: center;
}
.enr-empty-t { font-size: 14px; font-weight: 500; color: var(--text-secondary); }
.enr-empty-s { font-size: 13px; }

@media (max-width: 820px) {
    .enr-page-head { flex-direction: column; align-items: flex-start; }
    .enr-page-title { font-size: 20px; }
}
</style>
