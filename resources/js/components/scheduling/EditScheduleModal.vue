<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import ProfessorHoursBar from '@/components/scheduling/ProfessorHoursBar.vue'
import Button from '@/components/UI/AppButton.vue'
import { update } from '@/routes/scheduling/schedules'
import type {
    Schedule,
    ScheduleAvailableClassroom,
    ScheduleAvailableProfessor,
    ScheduleAvailableSection,
    ScheduleAvailableSubject,
} from '@/types/scheduling'

const props = defineProps<{
    open: boolean
    schedule: Schedule
    sections: ScheduleAvailableSection[]
    professors: ScheduleAvailableProfessor[]
    classrooms: ScheduleAvailableClassroom[]
    subjects: ScheduleAvailableSubject[]
    activeCareerIds: number[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const DAYS = [
    { value: 'monday',    label: 'Lunes' },
    { value: 'tuesday',   label: 'Martes' },
    { value: 'wednesday', label: 'Miércoles' },
    { value: 'thursday',  label: 'Jueves' },
    { value: 'friday',    label: 'Viernes' },
    { value: 'saturday',  label: 'Sábado' },
]

const TYPES = [
    { value: 'theory', label: 'Teórica' },
    { value: 'lab',    label: 'Laboratorio' },
]

function makeForm() {
    return useForm({
        section_id:   props.schedule.section.id,
        professor_id: props.schedule.professor.id,
        classroom_id: props.schedule.classroom.id,
        subject_id:   props.schedule.subject.id,
        day_of_week:  props.schedule.dayOfWeek,
        start_time:   props.schedule.startTime,
        end_time:     props.schedule.endTime,
        type:         props.schedule.type,
        valid_from:   props.schedule.validFrom,
        valid_until:  props.schedule.validUntil,
    })
}

const form = ref(makeForm())

const selectedProfessor = computed(() =>
    props.professors.find((p) => p.id === form.value.professor_id) ?? null
)

const visibleSections = computed(() => {
    if (props.activeCareerIds.length === 0) {
return props.sections
}

    return props.sections.filter(
        (s) => s.careerId != null && props.activeCareerIds.includes(s.careerId),
    )
})

watch(
    () => props.open,
    (opened) => {
 if (opened) {
form.value = makeForm()
} 
},
)

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.value.patch(update.url({ schedule: props.schedule }), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Editar horario" size="md" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:14px;">
                <div
                    v-if="activeCareerIds.length > 0"
                    dusk="career-filter-banner"
                    style="font-size:var(--text-xs);color:var(--text-secondary);background:var(--bg-surface-2);border:1px solid var(--border);border-radius:var(--r-sm);padding:8px 12px;"
                >
                    Las secciones se muestran filtradas por las carreras activas en la vista principal.
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Sección</label>
                        <select dusk="edit-section-select" v-model="form.section_id" class="input" required>
                            <option v-for="s in visibleSections" :key="s.id" :value="s.id">{{ s.code }} ({{ s.periodName }})</option>
                        </select>
                        <InputError :message="form.errors.section_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Materia</label>
                        <select dusk="edit-subject-select" v-model="form.subject_id" class="input" required>
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.code }} — {{ s.name }}</option>
                        </select>
                        <InputError :message="form.errors.subject_id" />
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Profesor</label>
                        <select dusk="edit-professor-select" v-model="form.professor_id" class="input" required>
                            <option v-for="p in professors" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <InputError :message="form.errors.professor_id" />
                        <ProfessorHoursBar
                            v-if="selectedProfessor"
                            :current-hours="selectedProfessor.currentWeeklyHours"
                            :limit-hours="selectedProfessor.weeklyHourLimit"
                        />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Aula</label>
                        <select dusk="edit-classroom-select" v-model="form.classroom_id" class="input" required>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.identifier }}</option>
                        </select>
                        <InputError :message="form.errors.classroom_id" />
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Día</label>
                        <select dusk="edit-day-select" v-model="form.day_of_week" class="input" required>
                            <option v-for="d in DAYS" :key="d.value" :value="d.value">{{ d.label }}</option>
                        </select>
                        <InputError :message="form.errors.day_of_week" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Inicio</label>
                        <input dusk="edit-start-time" v-model="form.start_time" type="time" step="900" class="input" required />
                        <InputError :message="form.errors.start_time" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Fin</label>
                        <input dusk="edit-end-time" v-model="form.end_time" type="time" step="900" class="input" required />
                        <InputError :message="form.errors.end_time" />
                    </div>
                </div>
                <div style="display:grid;gap:6px;">
                    <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Tipo</label>
                    <select v-model="form.type" class="input" required>
                        <option v-for="t in TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                    <InputError :message="form.errors.type" />
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Válido desde</label>
                        <input v-model="form.valid_from" type="date" class="input" required />
                        <InputError :message="form.errors.valid_from" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Válido hasta (opcional)</label>
                        <input v-model="form.valid_until" type="date" class="input" />
                        <InputError :message="form.errors.valid_until" />
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button dusk="edit-submit-btn" type="submit" variant="primary" :loading="form.processing">Guardar cambios</Button>
            </div>
        </form>
    </Modal>
</template>
