<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/actions/App/Http/Controllers/Scheduling/LapseController'
import type { Lapse, Period } from '@/types/scheduling'

const props = defineProps<{ open: boolean; period: Period; lapse: Lapse }>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.delete(destroy.url({ period: props.period, lapse: props.lapse }), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Eliminar lapso" size="sm" @update:open="close">
        <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
            ¿Eliminar el lapso <strong>{{ lapse.name }}</strong> del período
            <strong>{{ period.name }}</strong>? Esta acción no se puede deshacer.
        </p>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button variant="danger" :loading="form.processing" @click="submit">Eliminar</Button>
        </div>
    </Modal>
</template>
