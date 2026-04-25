<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { deactivate } from '@/routes/security/users'
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
    form.patch(deactivate.url(props.user), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal
        :open="open"
        :title="user.active ? 'Desactivar usuario' : 'Reactivar usuario'"
        size="sm"
        @update:open="close"
    >
        <p style="color:var(--text-secondary);font-size:var(--text-sm);line-height:1.6;margin:0 0 24px;">
            <template v-if="user.active">
                ¿Desactivar a <strong>{{ user.name }}</strong>? No podrá iniciar sesión hasta que reactives su cuenta.
            </template>
            <template v-else>
                ¿Reactivar acceso de <strong>{{ user.name }}</strong>? Podrá iniciar sesión nuevamente.
            </template>
        </p>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button :variant="user.active ? 'danger' : 'primary'" :loading="form.processing" @click="submit">
                {{ user.active ? 'Desactivar' : 'Reactivar' }}
            </Button>
        </div>
    </Modal>
</template>
