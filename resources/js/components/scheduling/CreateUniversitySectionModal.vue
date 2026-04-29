<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/scheduling/sections/university'
import type { AvailablePeriod, ClassroomForSection, SubjectForSection } from '@/types/scheduling'

const props = defineProps<{
    open: boolean
    periods: AvailablePeriod[]
    subjects: SubjectForSection[]
    classrooms: ClassroomForSection[]
}>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        period_id:           null as number | null,
        subject_id:          null as number | null,
        code:                '',
        capacity:            30,
        theory_classroom_id: null as number | null,
        lab_classroom_id:    null as number | null,
    })
}

const form = ref(makeForm())

const selectedPeriodType = computed(() => {
    if (! form.value.period_id) { return null }
    return props.periods.find((p) => p.id === form.value.period_id)?.type ?? null
})

const filteredSubjects = computed(() => {
    if (! selectedPeriodType.value) { return props.subjects }
    return props.subjects.filter((s) => s.pensumPeriodType === selectedPeriodType.value)
})

const theoryClassrooms = computed(() => props.classrooms.filter((c) => c.type === 'theory'))
const labClassrooms    = computed(() => props.classrooms.filter((c) => c.type === 'laboratory'))

function close(v: boolean): void {
    emit('update:open', v)
}

watch(
    () => props.open,
    (opened) => {
        if (opened) {
            form.value = makeForm()
        }
    },
)

watch(
    () => form.value.period_id,
    () => {
        form.value.subject_id = null
    },
)

function submit(): void {
    form.value.post(store.url(), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Nueva sección universitaria" size="md" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="cs-period" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Período
                        </label>
                        <select id="cs-period" v-model="form.period_id" class="input" required>
                            <option :value="null" disabled>Seleccionar período</option>
                            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <InputError :message="form.errors.period_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="cs-code" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Código
                        </label>
                        <input id="cs-code" v-model="form.code" class="input" placeholder="Ej: 01" maxlength="10" required />
                        <InputError :message="form.errors.code" />
                    </div>
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="cs-subject" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Materia
                    </label>
                    <select id="cs-subject" v-model="form.subject_id" class="input" required :disabled="!form.period_id">
                        <option :value="null" disabled>{{ form.period_id ? 'Seleccionar materia' : 'Selecciona un período primero' }}</option>
                        <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.code }} — {{ s.name }}</option>
                    </select>
                    <InputError :message="form.errors.subject_id" />
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="cs-capacity" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Cupo
                    </label>
                    <input id="cs-capacity" v-model.number="form.capacity" type="number" min="1" class="input" required />
                    <InputError :message="form.errors.capacity" />
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="cs-theory" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Aula teórica (opcional)
                        </label>
                        <select id="cs-theory" v-model="form.theory_classroom_id" class="input">
                            <option :value="null">Sin aula teórica</option>
                            <option v-for="c in theoryClassrooms" :key="c.id" :value="c.id">{{ c.identifier }} ({{ c.capacity }})</option>
                        </select>
                        <InputError :message="form.errors.theory_classroom_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="cs-lab" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Aula laboratorio (opcional)
                        </label>
                        <select id="cs-lab" v-model="form.lab_classroom_id" class="input">
                            <option :value="null">Sin laboratorio</option>
                            <option v-for="c in labClassrooms" :key="c.id" :value="c.id">{{ c.identifier }} ({{ c.capacity }})</option>
                        </select>
                        <InputError :message="form.errors.lab_classroom_id" />
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Crear sección</Button>
            </div>
        </form>
    </Modal>
</template>
