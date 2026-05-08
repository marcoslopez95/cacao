<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import ProfessorHoursBar from '@/components/scheduling/ProfessorHoursBar.vue'
import { store } from '@/routes/scheduling/schedules'
import type {
    ScheduleAvailableClassroom,
    ScheduleAvailableProfessor,
    ScheduleAvailableSection,
    ScheduleAvailableSubject,
} from '@/types/scheduling'

const props = defineProps<{
    open: boolean
    sections: ScheduleAvailableSection[]
    professors: ScheduleAvailableProfessor[]
    classrooms: ScheduleAvailableClassroom[]
    subjects: ScheduleAvailableSubject[]
    defaultSectionId?: number | null
    defaultDayOfWeek?: string
    defaultStartTime?: string
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
        section_id:   (props.defaultSectionId ?? null) as number | null,
        professor_id: null as number | null,
        classroom_id: null as number | null,
        subject_id:   null as number | null,
        day_of_week:  props.defaultDayOfWeek ?? 'monday',
        start_time:   props.defaultStartTime ?? '08:00',
        end_time:     '08:45',
        type:         'theory' as 'theory' | 'lab',
        valid_from:   '',
        valid_until:  null as string | null,
    })
}

const form = ref(makeForm())

const selectedProfessor = computed(() =>
    props.professors.find((p) => p.id === form.value.professor_id) ?? null
)

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
    form.value.post(store.url(), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Nuevo horario" size="md" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:14px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Sección</label>
                        <select v-model="form.section_id" class="input" required>
                            <option :value="null" disabled>Seleccionar sección</option>
                            <option v-for="s in sections" :key="s.id" :value="s.id">
                                {{ s.code }} ({{ s.periodName }})
                            </option>
                        </select>
                        <InputError :message="form.errors.section_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Materia</label>
                        <select v-model="form.subject_id" class="input" required>
                            <option :value="null" disabled>Seleccionar materia</option>
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.code }} — {{ s.name }}</option>
                        </select>
                        <InputError :message="form.errors.subject_id" />
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Profesor</label>
                        <select v-model="form.professor_id" class="input" required>
                            <option :value="null" disabled>Seleccionar profesor</option>
                            <option v-for="p in professors" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <InputError :message="form.errors.professor_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Aula</label>
                        <select v-model="form.classroom_id" class="input" required>
                            <option :value="null" disabled>Seleccionar aula</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.identifier }}</option>
                        </select>
                        <InputError :message="form.errors.classroom_id" />
                    </div>
                </div>

                <ProfessorHoursBar
                    v-if="selectedProfessor"
                    :current-hours="selectedProfessor.currentWeeklyHours"
                    :limit-hours="selectedProfessor.weeklyHourLimit"
                />

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Día</label>
                        <select v-model="form.day_of_week" class="input" required>
                            <option v-for="d in DAYS" :key="d.value" :value="d.value">{{ d.label }}</option>
                        </select>
                        <InputError :message="form.errors.day_of_week" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Inicio</label>
                        <input v-model="form.start_time" type="time" step="900" class="input" required />
                        <InputError :message="form.errors.start_time" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Fin</label>
                        <input v-model="form.end_time" type="time" step="900" class="input" required />
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
                <Button type="submit" variant="primary" :loading="form.processing">Crear horario</Button>
            </div>
        </form>
    </Modal>
</template>
