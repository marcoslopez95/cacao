<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/scheduling/sections/university'
import type { UniversitySection } from '@/types/scheduling'

const props = defineProps<{ section: UniversitySection; open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.delete(destroy.url({ section: props.section }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Eliminar sección" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 12px;">
                ¿Eliminar la sección <strong>{{ section.subject.code }} — {{ section.code }}</strong>?
            </p>
            <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
                No se puede eliminar si tiene horarios o inscripciones asignadas.
            </p>
            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="danger" :loading="form.processing">Eliminar</Button>
            </div>
        </form>
    </Modal>
</template>
