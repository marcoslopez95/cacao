<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { update } from '@/routes/security/roles'
import { groupPermissions, permissionGroupLabel } from '@/utils/permissions'
import type { Role } from '@/types'

const props = defineProps<{
    open: boolean
    role: Role
    permissions: string[]
    canAssignPermissions?: boolean
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()
const formKey = ref(0)
const checkedPerms = ref<string[]>([...props.role.permissions])

watch(
    [() => props.open, () => props.role.id],
    ([open]) => {
        if (open) {
            checkedPerms.value = [...props.role.permissions]
        }
    },
)

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) formKey.value++
}
</script>

<template>
    <Modal
        :open="open"
        title="Editar rol"
        :description="`Modifica el rol ${props.role.name}.`"
        size="md"
        @update:open="close"
    >
        <Form
            :key="formKey"
            v-bind="update.form(props.role.id)"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label
                        for="edit-name"
                        style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                    >
                        Nombre
                    </label>
                    <input
                        id="edit-name"
                        name="name"
                        class="input"
                        :value="props.role.name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div v-if="canAssignPermissions && permissions.length" style="display:grid;gap:10px;">
                    <span style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Permisos
                    </span>
                    <div
                        v-for="(groupPerms, group) in groupPermissions(permissions)"
                        :key="group"
                    >
                        <p style="font-size:var(--text-xs);font-weight:600;color:var(--text-muted);text-transform:uppercase;margin:0 0 6px;">
                            {{ permissionGroupLabel(group) }}
                        </p>
                        <div style="display:grid;gap:4px;padding-left:8px;">
                            <label
                                v-for="permission in groupPerms"
                                :key="permission"
                                style="display:flex;align-items:center;gap:8px;font-size:var(--text-sm);cursor:pointer;"
                            >
                                <input
                                    v-model="checkedPerms"
                                    type="checkbox"
                                    name="permissions[]"
                                    :value="permission"
                                    style="width:14px;height:14px;accent-color:var(--accent);"
                                />
                                {{ permission }}
                            </label>
                        </div>
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:16px;border-top:1px solid var(--border);">
                    <Button variant="secondary" type="button" @click="close(false)">Cancelar</Button>
                    <Button type="submit" variant="primary" :loading="processing">Guardar cambios</Button>
                </div>
            </div>
        </Form>
    </Modal>
</template>
