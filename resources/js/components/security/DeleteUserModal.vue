<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/security/users'
import type { UserRow } from '@/types'

const props = defineProps<{
    open: boolean
    user: UserRow
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.delete(destroy.url(props.user), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal
        :open="open"
        title="Eliminar usuario"
        size="sm"
        @update:open="close"
    >
        <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
            ¿Eliminar a <strong>{{ user.name }}</strong> ({{ user.email }})? Esta acción no se puede deshacer.
        </p>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button variant="danger" :loading="form.processing" @click="submit">Eliminar</Button>
        </div>
    </Modal>
</template>
