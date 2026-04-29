<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/scheduling/sections/school'
import type { AvailablePeriod, PensumForSection, ProfessorForSection, SchoolSectionClassroom } from '@/types/scheduling'

const props = defineProps<{
    open: boolean
    periods: AvailablePeriod[]
    pensums: PensumForSection[]
    professors: ProfessorForSection[]
    classrooms: SchoolSectionClassroom[]
}>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        period_id:       null as number | null,
        pensum_id:       null as number | null,
        grade:           null as number | null,
        letter:          '',
        capacity:        30,
        main_teacher_id: null as number | null,
        classroom_id:    null as number | null,
    })
}

const form = ref(makeForm())

const selectedPensum = computed(() => props.pensums.find((p) => p.id === form.value.pensum_id) ?? null)

const gradeOptions = computed(() => {
    if (! selectedPensum.value) { return [] }
    return Array.from({ length: selectedPensum.value.totalPeriods }, (_, i) => i + 1)
})

function close(v: boolean): void {
    emit('update:open', v)
}

watch(
    () => props.open,
    (opened) => {
        if (opened) { form.value = makeForm() }
    },
)

watch(
    () => form.value.pensum_id,
    () => { form.value.grade = null },
)

function submit(): void {
    form.value.post(store.url(), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Nueva sección escolar" size="md" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="css-period" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Período</label>
                        <select id="css-period" v-model="form.period_id" class="input" required>
                            <option :value="null" disabled>Seleccionar período</option>
                            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <InputError :message="form.errors.period_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="css-pensum" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Pensum</label>
                        <select id="css-pensum" v-model="form.pensum_id" class="input" required>
                            <option :value="null" disabled>Seleccionar pensum</option>
                            <option v-for="p in pensums" :key="p.id" :value="p.id">{{ p.career.name }} — {{ p.name }}</option>
                        </select>
                        <InputError :message="form.errors.pensum_id" />
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="css-grade" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Grado</label>
                        <select id="css-grade" v-model="form.grade" class="input" required :disabled="! selectedPensum">
                            <option :value="null" disabled>Grado</option>
                            <option v-for="g in gradeOptions" :key="g" :value="g">{{ g }}°</option>
                        </select>
                        <InputError :message="form.errors.grade" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="css-letter" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Sección</label>
                        <input id="css-letter" v-model="form.letter" class="input" maxlength="1" placeholder="A" required style="text-transform:uppercase;" />
                        <InputError :message="form.errors.letter" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="css-capacity" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Cupo</label>
                        <input id="css-capacity" v-model.number="form.capacity" class="input" type="number" min="1" required />
                        <InputError :message="form.errors.capacity" />
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="css-teacher" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Docente principal</label>
                        <select id="css-teacher" v-model="form.main_teacher_id" class="input">
                            <option :value="null">Sin docente</option>
                            <option v-for="p in professors" :key="p.id" :value="p.id">{{ p.user.name }}</option>
                        </select>
                        <InputError :message="form.errors.main_teacher_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="css-classroom" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Aula</label>
                        <select id="css-classroom" v-model="form.classroom_id" class="input">
                            <option :value="null">Sin aula</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.identifier }} ({{ c.capacity }})</option>
                        </select>
                        <InputError :message="form.errors.classroom_id" />
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Crear sección</Button>
            </div>
        </form>
    </Modal>
</template>
