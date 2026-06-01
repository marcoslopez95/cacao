<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/scheduling/schedules'
import type { Schedule } from '@/types/scheduling'

const props = defineProps<{
    open: boolean
    schedule: Schedule
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    useForm({}).delete(destroy.url({ schedule: props.schedule }), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Eliminar horario" size="sm" @update:open="close">
        <p style="font-size:var(--text-sm);color:var(--text-secondary);margin:0 0 20px;">
            ¿Confirmas que deseas eliminar el slot de <strong>{{ schedule.subject.code }}</strong>
            el {{ schedule.dayLabel }} de {{ schedule.startTime }}–{{ schedule.endTime }}?
        </p>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button dusk="delete-cancel-btn" type="button" variant="secondary" @click="close(false)">Cancelar</Button>
            <Button dusk="delete-confirm-btn" type="button" variant="danger" @click="submit">Eliminar</Button>
        </div>
    </Modal>
</template>
