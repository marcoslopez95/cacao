<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/base/Button.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/actions/App/Http/Controllers/Security/CoordinationAssignmentController'
import type { CoordinationRow } from '@/types/security'

const props = defineProps<{
    open: boolean
    coordination: CoordinationRow
    coordinators: { id: number; name: string }[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const formKey = ref(0)

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) formKey.value++
}
</script>

<template>
    <Modal
        :open="open"
        title="Asignar coordinador"
        :description="`Coordinación: ${coordination.name}`"
        size="sm"
        @update:open="close"
    >
        <div
            v-if="coordination.current_coordinator"
            style="padding:12px;background:var(--bg-surface-2);border-radius:8px;margin-bottom:16px;font-size:var(--text-sm);"
        >
            <span style="color:var(--text-muted);">Coordinador actual:</span>
            <span style="font-weight:500;color:var(--text-primary);margin-left:6px;">
                {{ coordination.current_coordinator.name }}
            </span>
        </div>

        <Form
            :key="formKey"
            v-bind="store.form(coordination)"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="ac-coordinator" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nuevo coordinador
                    </label>
                    <select id="ac-coordinator" name="user_id" class="input" required>
                        <option value="" disabled>Seleccionar coordinador...</option>
                        <option v-for="coordinator in coordinators" :key="coordinator.id" :value="coordinator.id">
                            {{ coordinator.name }}
                        </option>
                    </select>
                    <p v-if="coordinators.length === 0" style="font-size:var(--text-xs);color:var(--text-muted);">
                        No hay usuarios con rol de Coordinador de Área disponibles.
                    </p>
                    <InputError :message="errors.user_id" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing" :disabled="coordinators.length === 0">
                    Asignar coordinador
                </Button>
            </div>
        </Form>
    </Modal>
</template>
