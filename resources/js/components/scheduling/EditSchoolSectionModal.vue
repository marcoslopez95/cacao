<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import Button from '@/components/UI/AppButton.vue'
import { update } from '@/routes/scheduling/sections/school'
import type { ProfessorForSection, SchoolSection, SchoolSectionClassroom } from '@/types/scheduling'

const props = defineProps<{
    open: boolean
    section: SchoolSection | null
    professors: ProfessorForSection[]
    classrooms: SchoolSectionClassroom[]
}>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        letter:          props.section?.letter ?? '',
        capacity:        props.section?.capacity ?? 30,
        main_teacher_id: props.section?.mainTeacher?.id ?? null as number | null,
        classroom_id:    props.section?.classroom?.id ?? null as number | null,
    })
}

const form = ref(makeForm())

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
    if (! props.section) {
 return 
}

    form.value.patch(update.url({ section: props.section }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" :title="section ? `Editar sección ${section.code}` : 'Editar sección'" size="md" @update:open="close">
        <form v-if="section" @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="es-letter" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Sección</label>
                        <input id="es-letter" v-model="form.letter" class="input" maxlength="1" placeholder="A" required style="text-transform:uppercase;" />
                        <InputError :message="form.errors.letter" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="es-capacity" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Cupo</label>
                        <input id="es-capacity" v-model.number="form.capacity" class="input" type="number" min="1" required />
                        <InputError :message="form.errors.capacity" />
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="es-teacher" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Docente principal</label>
                        <select id="es-teacher" v-model="form.main_teacher_id" class="input">
                            <option :value="null">Sin docente</option>
                            <option v-for="p in professors" :key="p.id" :value="p.id">{{ p.user.name }}</option>
                        </select>
                        <InputError :message="form.errors.main_teacher_id" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="es-classroom" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Aula</label>
                        <select id="es-classroom" v-model="form.classroom_id" class="input">
                            <option :value="null">Sin aula</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.identifier }} ({{ c.capacity }})</option>
                        </select>
                        <InputError :message="form.errors.classroom_id" />
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Guardar cambios</Button>
            </div>
        </form>
    </Modal>
</template>
