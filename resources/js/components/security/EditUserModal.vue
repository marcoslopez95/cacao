<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { update } from '@/routes/security/users'
import type { UserRow } from '@/types'

const props = defineProps<{
    open: boolean
    user: UserRow
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
        title="Editar usuario"
        :description="`Modifica el nombre, correo o roles de ${user.name}.`"
        size="md"
        @update:open="close"
    >
        <Form
            :key="formKey"
            v-bind="update.form(user)"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="eu-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Nombre completo</label>
                    <input id="eu-name" name="name" class="input" :value="user.name" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="eu-email" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Correo institucional</label>
                    <input id="eu-email" name="email" type="email" class="input" :value="user.email" required />
                    <InputError :message="errors.email" />
                </div>

                <div style="display:grid;gap:8px;">
                    <span style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Roles</span>
                    <div style="display:grid;gap:6px;padding-left:4px;">
                        <label
                            v-for="r in roles"
                            :key="r"
                            style="display:flex;align-items:center;gap:8px;font-size:var(--text-sm);cursor:pointer;"
                        >
                            <input
                                type="checkbox"
                                name="roles[]"
                                :value="r"
                                :checked="user.roles.includes(r)"
                                style="width:14px;height:14px;accent-color:var(--accent);"
                            />
                            {{ r }}
                        </label>
                    </div>
                    <InputError :message="errors.roles" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Guardar cambios</Button>
            </div>
        </Form>
    </Modal>
</template>
