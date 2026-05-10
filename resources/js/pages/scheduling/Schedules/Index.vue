<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, setLayoutProps } from '@inertiajs/vue3'
import WeeklyGrid from '@/components/scheduling/WeeklyGrid.vue'
import ScheduleListView from '@/components/scheduling/ScheduleListView.vue'
import ScheduleClusterPopover from '@/components/scheduling/ScheduleClusterPopover.vue'
import ScheduleStats from '@/components/scheduling/ScheduleStats.vue'
import ScheduleLegend from '@/components/scheduling/ScheduleLegend.vue'
import ScheduleConflictsBanner from '@/components/scheduling/ScheduleConflictsBanner.vue'
import ScheduleToolbar from '@/components/scheduling/ScheduleToolbar.vue'
import CreateScheduleModal from '@/components/scheduling/CreateScheduleModal.vue'
import EditScheduleModal from '@/components/scheduling/EditScheduleModal.vue'
import DeleteScheduleModal from '@/components/scheduling/DeleteScheduleModal.vue'
import { useScheduleFilters } from '@/composables/filters/useScheduleFilters'
import { useSchedulePermissions } from '@/composables/permissions/useSchedulePermissions'
import { detectConflicts, todayKey, DAY_KEYS } from '@/composables/scheduling/useScheduleLayout'
import { index } from '@/routes/scheduling/schedules'
import type {
    Schedule,
    ScheduleAvailableClassroom,
    ScheduleAvailablePeriod,
    ScheduleAvailableProfessor,
    ScheduleAvailableSection,
    ScheduleAvailableSubject,
    ScheduleCollection,
} from '@/types/scheduling'
import type { PopoverData } from '@/components/scheduling/ScheduleClusterPopover.vue'

type Props = {
    schedules: ScheduleCollection
    periods: ScheduleAvailablePeriod[]
    sections: ScheduleAvailableSection[]
    professors: ScheduleAvailableProfessor[]
    classrooms: ScheduleAvailableClassroom[]
    subjects: ScheduleAvailableSubject[]
    filters: { period_id: number | null; section_id: number | null; professor_id: number | null }
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
const { periodId, sectionId, professorId, applyFilters } = useScheduleFilters(
    props.filters.period_id,
    props.filters.section_id,
    props.filters.professor_id,
)

// View state
const view      = ref<'week' | 'day' | 'list'>('week')
const mobileDay = ref(DAY_KEYS.indexOf(todayKey() ?? 'monday'))
const searchQuery       = ref('')
const activeCareerIds   = ref(new Set<number>())

// Initialise activeCareerIds with all careers in the dataset
const allCareerIds = computed(() => {
    const ids = new Set<number>()
    for (const s of props.schedules) {
        if (s.career) ids.add(s.career.id)
    }
    return ids
})

// Toggle a career filter (if set not yet populated, first show all)
function toggleCareer(id: number): void {
    if (activeCareerIds.value.size === 0) {
        // Populate with all then remove the clicked one
        activeCareerIds.value = new Set(allCareerIds.value)
    }
    if (activeCareerIds.value.has(id)) {
        activeCareerIds.value.delete(id)
    } else {
        activeCareerIds.value.add(id)
    }
    activeCareerIds.value = new Set(activeCareerIds.value) // trigger reactivity
}

// Client-side filtered schedules
const filteredSchedules = computed(() => {
    const q = searchQuery.value.toLowerCase().trim()
    return props.schedules.filter((s) => {
        // Career filter (empty set = show all)
        if (activeCareerIds.value.size > 0 && s.career && !activeCareerIds.value.has(s.career.id)) {
            return false
        }
        // Text search
        if (q) {
            return (
                s.subject.name.toLowerCase().includes(q) ||
                s.subject.code.toLowerCase().includes(q) ||
                s.professor.user.name.toLowerCase().includes(q) ||
                s.classroom.identifier.toLowerCase().includes(q) ||
                s.section.code.toLowerCase().includes(q)
            )
        }
        return true
    })
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
            :schedules="filteredSchedules"
            :active-career-ids="activeCareerIds.size === 0 ? allCareerIds : activeCareerIds"
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
        @update:open="editingSchedule = $event ? editingSchedule : null"
    />

    <DeleteScheduleModal
        v-if="deletingSchedule"
        :schedule="deletingSchedule"
        :open="true"
        @update:open="deletingSchedule = $event ? deletingSchedule : null"
    />
</template>
