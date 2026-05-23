<script setup lang="ts">
import { ref, computed, toRef } from 'vue'
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { useMediaQuery } from '@vueuse/core'
import AppIcon from '@/components/UI/AppIcon.vue'
import EnrollmentToolbar from '@/components/enrollment/EnrollmentToolbar.vue'
import EnrollmentMateriaRow from '@/components/enrollment/EnrollmentMateriaRow.vue'
import EnrollmentSummaryPanel from '@/components/enrollment/EnrollmentSummaryPanel.vue'
import { useEnrollmentState } from '@/composables/enrollment/useEnrollmentState'
import { useEnrollmentFilters } from '@/composables/enrollment/useEnrollmentFilters'
import { useEnrollmentForm } from '@/composables/enrollment/useEnrollmentForm'
import { useEnrollmentPermissions } from '@/composables/enrollment/useEnrollmentPermissions'
import { backendToCatalog } from '@/types/enrollment'
import { index as enrollmentIndex } from '@/routes/enrollment'
import type {
    BackendEnrollment,
    BackendEnrollmentSubject,
    BackendEnrollmentRules,
    EnrollmentSelections,
    EnrollmentRules,
    EnrollmentGhostCandidate,
} from '@/types/enrollment'

const props = defineProps<{
    enrollment: BackendEnrollment | null
    catalog: BackendEnrollmentSubject[]
    rules: BackendEnrollmentRules
    can: { confirm: boolean }
}>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Estudiante', href: '#' },
        { title: 'Inscripción', href: enrollmentIndex.url() },
    ],
})

const isMobile = useMediaQuery('(max-width: 820px)')

// ---------------------------------------------------------------------------
// Map backend data to UI types
// ---------------------------------------------------------------------------

const subjects = computed(() => backendToCatalog(props.catalog))

const uiRules = computed<EnrollmentRules>(() => ({
    creditsMin: props.rules.credits_min,
    creditsMax: props.rules.credits_max,
    period: props.rules.period ?? '—',
    studentName: props.rules.student_name,
    studentCode: props.rules.student_code,
    career: props.rules.career,
    trimester: props.rules.trimester,
    deadline: props.rules.deadline ?? '—',
    daysLeft: props.rules.days_left,
}))

// ---------------------------------------------------------------------------
// Selections: initialized from existing draft details via catalog
// ---------------------------------------------------------------------------

const selections = ref<EnrollmentSelections>(
    Object.fromEntries(
        props.catalog
            .filter(s => s.selected_section_id !== null)
            .flatMap(s => {
                const idx = s.sections.findIndex(sec => sec.id === s.selected_section_id)
                return idx >= 0 ? [[s.code, idx]] : []
            })
    )
)

// ---------------------------------------------------------------------------
// State: composables
// ---------------------------------------------------------------------------

const expanded = ref(new Set<string>())
const ghost = ref<EnrollmentGhostCandidate | null>(null)

const { search, filters, filteredSubjects } = useEnrollmentFilters(subjects.value)

const { summary, creditsPct, creditsStatus, findConflict } =
    useEnrollmentState(
        subjects.value,
        selections,
        fn => { selections.value = fn(selections.value) },
    )

const enrollmentRef = toRef(props, 'enrollment')
const { canConfirm, isReadOnly } = useEnrollmentPermissions(enrollmentRef, props.can)

const { addSubject, removeSubject, confirmEnrollment, isLoading, error, clearError } =
    useEnrollmentForm(enrollmentRef, selections, isReadOnly)

// ---------------------------------------------------------------------------
// Handlers
// ---------------------------------------------------------------------------

function toggleExpanded(code: string): void {
    const next = new Set(expanded.value)
    if (next.has(code)) {
        next.delete(code)
    } else {
        next.add(code)
    }
    expanded.value = next
}

function handleSelect(code: string, sectionIdx: number): void {
    if (isReadOnly.value) return
    const subject = subjects.value.find(s => s.code === code)
    const section = subject?.sections[sectionIdx]
    if (! subject?.id || ! section?.id) {
        return
    }
    void addSubject(subject.id, section.id, code, sectionIdx)
}

function handleUnselect(code: string): void {
    if (isReadOnly.value) return
    void removeSubject(code)
}

async function handleConfirm(): Promise<void> {
    clearError()
    await confirmEnrollment()
}
</script>

<template>
    <Head title="Inscripción" />

    <!-- Page header -->
    <div class="enr-page-head">
        <div class="enr-page-head-l">
            <span class="enr-page-eyebrow">Período {{ uiRules.period }} · {{ uiRules.trimester }}</span>
            <h1 class="enr-page-title">Inscripción de materias</h1>
            <p class="enr-page-sub">
                Selecciona las materias y secciones que cursarás este trimestre.
                Solo puedes elegir <strong>una sección por materia</strong>.
            </p>
        </div>
        <div class="enr-page-head-r">
            <div v-if="uiRules.deadline !== '—'" class="enr-deadline">
                <AppIcon name="clock" :size="13" />
                <div>
                    <div class="enr-deadline-t">Cierra el <strong>{{ uiRules.deadline }}</strong></div>
                    <div class="enr-deadline-s">faltan {{ uiRules.daysLeft }} días</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error banner -->
    <div v-if="error" class="enr-error-banner" role="alert">
        <AppIcon name="alert-triangle" :size="14" />
        <span>{{ error }}</span>
        <button class="enr-error-close" @click="clearError">
            <AppIcon name="x" :size="12" />
        </button>
    </div>

    <!-- Empty state: no active period or no pensum -->
    <div v-if="! enrollment" class="enr-empty-state">
        <AppIcon name="calendar-x" :size="32" />
        <div class="enr-empty-state-t">No hay período activo</div>
        <div class="enr-empty-state-s">El proceso de inscripción no está disponible en este momento.</div>
    </div>

    <!-- Layout -->
    <div v-else :class="['enr-layout', isMobile ? 'enr-layout--mobile' : 'enr-layout--split']">
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
                    @select="(code, idx) => handleSelect(code, idx)"
                    @unselect="(code) => handleUnselect(code)"
                    @ghost-enter="g => ghost = g"
                    @ghost-leave="ghost = null"
                />
            </div>
        </div>

        <!-- Summary panel (desktop: sticky aside / mobile: below) -->
        <aside class="enr-aside">
            <EnrollmentSummaryPanel
                :summary="summary"
                :rules="uiRules"
                :credits-pct="creditsPct"
                :credits-status="creditsStatus"
                :subjects="subjects"
                :selections="selections"
                :ghost="isMobile ? null : ghost"
                :mobile="isMobile"
                :is-read-only="isReadOnly"
                :can-confirm="canConfirm"
                @confirm="handleConfirm"
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

/* Error banner */
.enr-error-banner {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    margin-bottom: 12px;
    background: color-mix(in srgb, var(--error, #dc2626) 10%, transparent);
    border: 1px solid color-mix(in srgb, var(--error, #dc2626) 30%, transparent);
    border-radius: var(--radius-md);
    font-size: 13px;
    color: var(--error, #dc2626);
}
.enr-error-banner span { flex: 1; }
.enr-error-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 2px;
    color: inherit;
    opacity: 0.6;
}
.enr-error-close:hover { opacity: 1; }

/* Empty state */
.enr-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 64px 20px;
    color: var(--text-muted);
    text-align: center;
}
.enr-empty-state-t { font-size: 16px; font-weight: 500; color: var(--text-secondary); }
.enr-empty-state-s { font-size: 13px; max-width: 40ch; }

/* Layout: split (desktop) */
.enr-layout { display: flex; gap: 20px; align-items: flex-start; }
.enr-layout--split .enr-main { flex: 1; min-width: 0; }
.enr-layout--split .enr-aside { width: 600px; flex-shrink: 0; }

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
