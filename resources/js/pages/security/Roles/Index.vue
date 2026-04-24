<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import Badge from '@/components/base/Badge.vue'
import Button from '@/components/base/Button.vue'
import DeleteRoleModal from '@/components/security/DeleteRoleModal.vue'
import EditRoleModal from '@/components/security/EditRoleModal.vue'
import CreateRoleModal from '@/components/security/CreateRoleModal.vue'
import { index } from '@/routes/security/roles'
import type { Role } from '@/types'

type Props = {
    roles: Role[]
    permissions: string[]
    can: {
        create: boolean
        update: boolean
        delete: boolean
        assignPermissions: boolean
    }
}

const props = defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Seguridad', href: '#' },
            { title: 'Roles', href: index.url() },
        ],
    },
})

const editingRole = ref<Role | null>(null)
const deletingRole = ref<Role | null>(null)
const showCreate = ref(false)
</script>

<template>
    <Head title="Roles" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Roles
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Gestiona los roles y permisos del sistema
                </p>
            </div>
            <Button
                v-if="props.can.create"
                icon="plus"
                variant="primary"
                @click="showCreate = true"
            >
                Nuevo rol
            </Button>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Permisos</th>
                        <th>Usuarios</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="role in props.roles" :key="role.id">
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="font-weight:500;">{{ role.name }}</span>
                                <Badge v-if="role.isAdmin" variant="accent">Admin</Badge>
                            </div>
                        </td>
                        <td style="color:var(--text-secondary);">
                            <span v-if="role.permissions.length">{{ role.permissions.join(', ') }}</span>
                            <span v-else style="font-style:italic;color:var(--text-muted);">Sin permisos</span>
                        </td>
                        <td style="color:var(--text-muted);">{{ role.usersCount }}</td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <template v-if="!role.isAdmin">
                                    <Button
                                        v-if="props.can.update"
                                        variant="ghost"
                                        size="sm"
                                        icon-only
                                        icon="edit"
                                        :aria-label="`Editar ${role.name}`"
                                        @click="editingRole = role"
                                    />
                                    <Button
                                        v-if="props.can.delete"
                                        variant="ghost"
                                        size="sm"
                                        icon-only
                                        icon="trash"
                                        :aria-label="`Eliminar ${role.name}`"
                                        @click="deletingRole = role"
                                    />
                                </template>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!props.roles.length">
                        <td colspan="4" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay roles registrados
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateRoleModal
        :open="showCreate"
        :permissions="props.permissions"
        :can-assign-permissions="props.can.assignPermissions"
        @update:open="(v) => { if (!v) showCreate = false }"
    />

    <EditRoleModal
        v-if="editingRole"
        :role="editingRole"
        :permissions="props.permissions"
        :can-assign-permissions="props.can.assignPermissions"
        :open="true"
        @update:open="(v) => { if (!v) editingRole = null }"
    />

    <DeleteRoleModal
        v-if="deletingRole"
        :role="deletingRole"
        :open="true"
        @update:open="(v) => { if (!v) deletingRole = null }"
    />
</template>
