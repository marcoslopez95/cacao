<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, setLayoutProps } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import CreateScheduleModal from '@/components/scheduling/CreateScheduleModal.vue'
import DeleteScheduleModal from '@/components/scheduling/DeleteScheduleModal.vue'
import EditScheduleModal from '@/components/scheduling/EditScheduleModal.vue'
import WeeklyGrid from '@/components/scheduling/WeeklyGrid.vue'
import { useScheduleFilters } from '@/composables/filters/useScheduleFilters'
import { useSchedulePermissions } from '@/composables/permissions/useSchedulePermissions'
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

const showCreate       = ref(false)
const createDefaults   = ref<{ dayOfWeek?: string; startTime?: string }>({})
const editingSchedule  = ref<Schedule | null>(null)
const deletingSchedule = ref<Schedule | null>(null)

const sectionsForPeriod = computed(() => {
    if (!periodId.value) return props.sections
    return props.sections.filter((s) => s.periodId === periodId.value)
})

function handleCreateFromGrid(defaults: { dayOfWeek: string; startTime: string }): void {
    createDefaults.value = defaults
    showCreate.value = true
}

function handleEditFromGrid(schedule: Schedule): void {
    editingSchedule.value = schedule
}
</script>

<template>
    <Head title="Horarios" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Horarios
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Cuadrícula semanal de clases
                </p>
            </div>
            <Button v-if="canCreate" variant="primary" icon="plus" @click="showCreate = true">
                Nuevo horario
            </Button>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <select v-model="periodId" class="input" style="max-width:180px;" aria-label="Filtrar por período" @change="applyFilters">
                <option :value="null">Todos los períodos</option>
                <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <select v-model="sectionId" class="input" style="max-width:180px;" aria-label="Filtrar por sección" @change="applyFilters">
                <option :value="null">Todas las secciones</option>
                <option v-for="s in sectionsForPeriod" :key="s.id" :value="s.id">{{ s.code }} ({{ s.periodName }})</option>
            </select>
            <select v-model="professorId" class="input" style="max-width:200px;" aria-label="Filtrar por profesor" @change="applyFilters">
                <option :value="null">Todos los profesores</option>
                <option v-for="p in professors" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
        </div>

        <div class="card" style="padding:0;overflow:hidden;">
            <WeeklyGrid
                :schedules="schedules"
                :can-update="canUpdate"
                :can-delete="canDelete"
                @create="handleCreateFromGrid"
                @edit="handleEditFromGrid"
            />
        </div>
    </div>

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
        :open="editingSchedule !== null"
        :sections="sections"
        :professors="professors"
        :classrooms="classrooms"
        :subjects="subjects"
        @update:open="editingSchedule = $event ? editingSchedule : null"
    />

    <DeleteScheduleModal
        v-if="deletingSchedule"
        :schedule="deletingSchedule"
        :open="deletingSchedule !== null"
        @update:open="deletingSchedule = $event ? deletingSchedule : null"
    />
</template>
