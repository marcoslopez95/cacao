<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/security/roles'
import type { Role } from '@/types'

const props = defineProps<{
    open: boolean
    role: Role
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function close(v: boolean): void {
    emit('update:open', v)
}
</script>

<template>
    <Modal
        :open="open"
        title="Eliminar rol"
        size="sm"
        @update:open="close"
    >
        <template v-if="props.role.usersCount > 0">
            <p style="font-size:var(--text-sm);color:var(--text-secondary);">
                Este rol tiene <strong>{{ props.role.usersCount }}</strong>
                {{ props.role.usersCount === 1 ? 'usuario asignado' : 'usuarios asignados' }}
                y no puede eliminarse.
            </p>
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:16px;border-top:1px solid var(--border);margin-top:16px;">
                <Button variant="secondary" type="button" @click="close(false)">Cerrar</Button>
            </div>
        </template>

        <Form
            v-else
            v-bind="destroy.form(props.role.id)"
            v-slot="{ processing }"
            @success="close(false)"
        >
            <p style="font-size:var(--text-sm);color:var(--text-secondary);">
                Esta acción eliminará el rol <strong>"{{ props.role.name }}"</strong> permanentemente.
                Esta acción no se puede deshacer.
            </p>
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:16px;border-top:1px solid var(--border);margin-top:16px;">
                <Button variant="secondary" type="button" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="danger" :loading="processing">Eliminar</Button>
            </div>
        </Form>
    </Modal>
</template>
