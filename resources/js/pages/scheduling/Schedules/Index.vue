<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import CreateScheduleModal from '@/components/scheduling/CreateScheduleModal.vue'
import DeleteScheduleModal from '@/components/scheduling/DeleteScheduleModal.vue'
import EditScheduleModal from '@/components/scheduling/EditScheduleModal.vue'
import ScheduleClusterPopover from '@/components/scheduling/ScheduleClusterPopover.vue'
import type { PopoverData } from '@/components/scheduling/ScheduleClusterPopover.vue'
import ScheduleConflictsBanner from '@/components/scheduling/ScheduleConflictsBanner.vue'
import ScheduleLegend from '@/components/scheduling/ScheduleLegend.vue'
import ScheduleListView from '@/components/scheduling/ScheduleListView.vue'
import ScheduleStats from '@/components/scheduling/ScheduleStats.vue'
import ScheduleToolbar from '@/components/scheduling/ScheduleToolbar.vue'
import WeeklyGrid from '@/components/scheduling/WeeklyGrid.vue'
import { useScheduleFilters } from '@/composables/filters/useScheduleFilters'
import { useSchedulePermissions } from '@/composables/permissions/useSchedulePermissions'
import { detectConflicts, todayKey, DAY_KEYS } from '@/composables/scheduling/useScheduleLayout'
import { index } from '@/routes/scheduling/schedules'
import type {
    Schedule,
    ScheduleAvailableCareer,
    ScheduleAvailableClassroom,
    ScheduleAvailablePeriod,
    ScheduleAvailableProfessor,
    ScheduleAvailableSection,
    ScheduleAvailableSubject,
    ScheduleCollection,
} from '@/types/scheduling'

type Props = {
    schedules: ScheduleCollection
    periods: ScheduleAvailablePeriod[]
    sections: ScheduleAvailableSection[]
    professors: ScheduleAvailableProfessor[]
    classrooms: ScheduleAvailableClassroom[]
    subjects: ScheduleAvailableSubject[]
    careers: ScheduleAvailableCareer[]
    filters: { period_id: number | null; section_id: number | null; professor_id: number | null; career_ids: number[] | null }
    can: { create: boolean; update: boolean; delete: boolean }
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Horarios', href: '#' },
        { title: 'Horarios', href: index.url() },
    ],
})

const { canCreate, canUpdate, canDelete } = useSchedulePermissions()
const { periodId, sectionId, professorId, careerIds, applyFilters } = useScheduleFilters(
    props.filters.period_id,
    props.filters.section_id,
    props.filters.professor_id,
    props.filters.career_ids,
)

// View state
const view      = ref<'week' | 'day' | 'list'>('week')
const mobileDay = ref(DAY_KEYS.indexOf(todayKey() ?? 'monday'))
const searchQuery = ref('')

// Career filter — server-side with URL persistence
const activeLegendCareerIds = computed((): Set<number> => {
    if (!careerIds.value || careerIds.value.length === 0) {
        return new Set(props.careers.map((c) => c.id))
    }

    return new Set(careerIds.value)
})

function toggleCareer(id: number): void {
    const allIds = props.careers.map((c) => c.id)
    const current = careerIds.value ? [...careerIds.value] : [...allIds]
    const next = current.includes(id) ? current.filter((x) => x !== id) : [...current, id]
    careerIds.value = next.length === 0 || next.length === allIds.length ? null : next
    applyFilters()
}

// Text search is the only remaining client-side filter
const filteredSchedules = computed(() => {
    const q = searchQuery.value.toLowerCase().trim()

    if (!q) {
return props.schedules
}

    return props.schedules.filter(
        (s) =>
            s.subject.name.toLowerCase().includes(q) ||
            s.subject.code.toLowerCase().includes(q) ||
            s.professor.user.name.toLowerCase().includes(q) ||
            s.classroom.identifier.toLowerCase().includes(q) ||
            s.section.code.toLowerCase().includes(q),
    )
})

const conflicts = computed(() => detectConflicts(filteredSchedules.value))

// Modal state
const showCreate       = ref(false)
const createDefaults   = ref<{ dayOfWeek?: string; startTime?: string }>({})
const editingSchedule  = ref<Schedule | null>(null)
const deletingSchedule = ref<Schedule | null>(null)
const popoverData      = ref<PopoverData | null>(null)

const sectionsForPeriod = computed(() =>
    periodId.value ? props.sections.filter((s) => s.periodId === periodId.value) : props.sections
)

// Grid event handlers
function handleCreateFromGrid(defaults: { dayOfWeek: string; startTime: string }): void {
    createDefaults.value = defaults
    showCreate.value = true
}

function handleOpenEvent(schedule: Schedule): void {
    popoverData.value = {
        kind: 'event',
        schedule,
        conflict: conflicts.value.get(schedule.id),
    }
}

function handleOpenCluster(data: { dayIndex: number; startMin: number; endMin: number; schedules: Schedule[] }): void {
    popoverData.value = { kind: 'cluster', ...data }
}

function handleOpenConflicts(): void {
    popoverData.value = {
        kind: 'conflicts',
        schedules: filteredSchedules.value.filter((s) => conflicts.value.has(s.id)),
    }
}

// Popover → modal escalation
function handleEditFromPopover(schedule: Schedule): void {
    popoverData.value = null
    editingSchedule.value = schedule
}

function handleDeleteFromPopover(schedule: Schedule): void {
    popoverData.value = null
    deletingSchedule.value = schedule
}
</script>

<template>
    <Head title="Horarios" />

    <div class="flex flex-col">

        <!-- Page header -->
        <div class="flex items-start justify-between gap-4 flex-wrap pb-4">
            <div>
                <h1 class="text-xl font-bold text-[var(--text-primary)] mb-1">
                    Horarios
                </h1>
                <p class="text-sm text-[var(--text-muted)]">
                    Cuadrícula semanal de clases · período activo
                </p>
            </div>
        </div>

        <!-- Toolbar + filter chips -->
        <div class="mb-4">
            <ScheduleToolbar
                :view="view"
                :query="searchQuery"
                :period-id="periodId"
                :section-id="sectionId"
                :professor-id="professorId"
                :periods="periods"
                :sections="sectionsForPeriod"
                :professors="professors"
                :can-create="canCreate"
                @update:view="view = $event"
                @update:query="searchQuery = $event"
                @update:period-id="periodId = $event"
                @update:section-id="sectionId = $event"
                @update:professor-id="professorId = $event"
                @create="showCreate = true"
                @apply-filters="applyFilters"
            />
        </div>

        <!-- Stats -->
        <ScheduleStats
            :schedules="filteredSchedules"
            :conflicts-count="conflicts.size"
            @open-conflicts="handleOpenConflicts"
        />

        <!-- Career legend -->
        <ScheduleLegend
            :careers="careers"
            :active-career-ids="activeLegendCareerIds"
            @toggle="toggleCareer"
        />

        <!-- Conflicts inline banner -->
        <ScheduleConflictsBanner :schedules="filteredSchedules" :conflicts="conflicts" />

        <!-- Calendar / List view -->
        <div class="card p-0 overflow-hidden">
            <WeeklyGrid
                v-if="view !== 'list'"
                :schedules="filteredSchedules"
                :conflicts="conflicts"
                :can-update="canUpdate"
                :can-delete="canDelete"
                :mobile-day="mobileDay"
                :day-mode="view === 'day'"
                @create="handleCreateFromGrid"
                @open-event="handleOpenEvent"
                @open-cluster="handleOpenCluster"
                @edit-schedule="editingSchedule = $event"
                @delete-schedule="deletingSchedule = $event"
                @update:mobile-day="mobileDay = $event"
            />
            <ScheduleListView
                v-else
                :schedules="filteredSchedules"
                :conflicts="conflicts"
                :can-update="canUpdate"
                :can-delete="canDelete"
                @open-event="handleOpenEvent"
                @edit-schedule="editingSchedule = $event"
                @delete-schedule="deletingSchedule = $event"
            />
        </div>
    </div>

    <!-- Cluster / event popover -->
    <ScheduleClusterPopover
        :data="popoverData"
        :conflicts="conflicts"
        @close="popoverData = null"
        @edit-schedule="handleEditFromPopover"
        @delete-schedule="handleDeleteFromPopover"
    />

    <!-- CRUD modals (existing, unchanged) -->
    <CreateScheduleModal
        :open="showCreate"
        :sections="sections"
        :professors="professors"
        :classrooms="classrooms"
        :subjects="subjects"
        :active-career-ids="careerIds ?? []"
        :default-section-id="sectionId"
        :default-day-of-week="createDefaults.dayOfWeek"
        :default-start-time="createDefaults.startTime"
        @update:open="showCreate = $event"
    />

    <EditScheduleModal
        v-if="editingSchedule"
        :schedule="editingSchedule"
        :open="true"
        :sections="sections"
        :professors="professors"
        :classrooms="classrooms"
        :subjects="subjects"
        :active-career-ids="careerIds ?? []"
        @update:open="editingSchedule = $event ? editingSchedule : null"
    />

    <DeleteScheduleModal
        v-if="deletingSchedule"
        :schedule="deletingSchedule"
        :open="true"
        @update:open="deletingSchedule = $event ? deletingSchedule : null"
    />
</template>
