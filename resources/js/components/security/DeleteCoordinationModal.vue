<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/security/coordinations'
import type { CoordinationRow } from '@/types/security'

const props = defineProps<{
    open: boolean
    coordination: CoordinationRow
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.delete(destroy.url(props.coordination), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Eliminar coordinación" size="sm" @update:open="close">
        <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 12px;">
            ¿Eliminar la coordinación <strong>{{ coordination.name }}</strong>? Esta acción no se puede deshacer.
        </p>

        <p
            v-if="coordination.current_coordinator"
            style="color:var(--color-warning, #b45309);font-size:var(--text-sm);margin:0 0 24px;"
        >
            Advertencia: esta coordinación tiene un coordinador activo ({{ coordination.current_coordinator.name }}).
            Debes reasignarlo antes de eliminarla.
        </p>
        <p v-else style="margin:0 0 24px;" />

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button variant="danger" :loading="form.processing" @click="submit">Eliminar</Button>
        </div>
    </Modal>
</template>
