<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { store } from '@/routes/security/invitations'

defineProps<{
    open: boolean
    roles: string[]
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
        title="Invitar por correo"
        description="El usuario recibirá un enlace para crear su contraseña. Expira en 48 horas."
        size="sm"
        @update:open="close"
    >
        <Form
            :key="formKey"
            v-bind="store.form()"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label
                        for="inv-email"
                        style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                    >
                        Correo institucional
                    </label>
                    <input
                        id="inv-email"
                        name="email"
                        type="email"
                        class="input"
                        placeholder="correo@institucion.edu.ve"
                        required
                    />
                    <InputError :message="errors.email" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label
                        for="inv-role"
                        style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                    >
                        Rol
                    </label>
                    <select id="inv-role" name="role" class="input" required>
                        <option value="">Seleccionar rol...</option>
                        <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                    </select>
                    <InputError :message="errors.role" />
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:16px;border-top:1px solid var(--border);">
                    <Button variant="secondary" type="button" @click="close(false)">Cancelar</Button>
                    <Button type="submit" variant="primary" :loading="processing">Enviar invitación</Button>
                </div>
            </div>
        </Form>
    </Modal>
</template>
