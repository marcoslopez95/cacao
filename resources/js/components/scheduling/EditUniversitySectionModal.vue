<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/scheduling/sections/university'
import type { ClassroomForSection, UniversitySection } from '@/types/scheduling'

const props = defineProps<{
    section: UniversitySection
    open: boolean
    classrooms: ClassroomForSection[]
}>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        code:                props.section.code,
        capacity:            props.section.capacity,
        theory_classroom_id: props.section.theoryClassroom?.id ?? null,
        lab_classroom_id:    props.section.labClassroom?.id ?? null,
    })
}

const form = ref(makeForm())
const theoryClassrooms = props.classrooms.filter((c) => c.type === 'theory')
const labClassrooms    = props.classrooms.filter((c) => c.type === 'laboratory')

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

function submit(): void {
    form.value.patch(update.url({ section: props.section }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Editar sección" size="md" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:4px;">
                    <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">Materia</p>
                    <p style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);margin:0;">
                        {{ section.subject.code }} — {{ section.subject.name }}
                    </p>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="es-code" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Código
                        </label>
                        <input id="es-code" v-model="form.code" class="input" maxlength="10" required />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="es-capacity" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Cupo
                        </label>
                        <input id="es-capacity" v-model.number="form.capacity" type="number" min="1" class="input" required />
                        <InputError :message="form.errors.capacity" />
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="es-theory" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Aula teórica
                        </label>
                        <select id="es-theory" v-model="form.theory_classroom_id" class="input">
                            <option :value="null">Sin aula teórica</option>
                            <option v-for="c in theoryClassrooms" :key="c.id" :value="c.id">{{ c.identifier }}</option>
                        </select>
                        <InputError :message="form.errors.theory_classroom_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="es-lab" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Laboratorio
                        </label>
                        <select id="es-lab" v-model="form.lab_classroom_id" class="input">
                            <option :value="null">Sin laboratorio</option>
                            <option v-for="c in labClassrooms" :key="c.id" :value="c.id">{{ c.identifier }}</option>
                        </select>
                        <InputError :message="form.errors.lab_classroom_id" />
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Guardar cambios</Button>
            </div>
        </form>
    </Modal>
</template>
