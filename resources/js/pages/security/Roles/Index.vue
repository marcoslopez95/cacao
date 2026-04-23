<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import DeleteRoleModal from '@/components/security/DeleteRoleModal.vue';
import EditRoleModal from '@/components/security/EditRoleModal.vue';
import CreateRoleModal from '@/components/security/CreateRoleModal.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/security/roles';
import type { Role } from '@/types';

type Props = {
    roles: Role[];
    permissions: string[];
    can: {
        create: boolean;
        update: boolean;
        delete: boolean;
        assignPermissions: boolean;
    };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Seguridad', href: '#' },
            { title: 'Roles', href: index.url() },
        ],
    },
});

const editingRole = ref<Role | null>(null);
const deletingRole = ref<Role | null>(null);
</script>

<template>
    <Head title="Roles" />

    <h1 class="sr-only">Roles</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Roles"
                description="Gestiona los roles y permisos del sistema"
            />

            <CreateRoleModal
                v-if="props.can.create"
                :permissions="props.permissions"
                :can-assign-permissions="props.can.assignPermissions"
            >
                <Button>
                    <Plus />
                    Nuevo rol
                </Button>
            </CreateRoleModal>
        </div>

        <div class="rounded-md border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th scope="col" class="px-4 py-3 text-left font-medium">Rol</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">Permisos</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">Usuarios</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="role in props.roles"
                        :key="role.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ role.name }}</span>
                                <Badge v-if="role.isAdmin" variant="secondary">Admin</Badge>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            <span v-if="role.permissions.length > 0">
                                {{ role.permissions.join(', ') }}
                            </span>
                            <span v-else class="italic">Sin permisos</span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ role.usersCount }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <template v-if="!role.isAdmin">
                                    <Button
                                        v-if="props.can.update"
                                        variant="ghost"
                                        size="sm"
                                        :aria-label="`Editar rol ${role.name}`"
                                        @click="editingRole = role"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        v-if="props.can.delete"
                                        variant="ghost"
                                        size="sm"
                                        :aria-label="`Eliminar rol ${role.name}`"
                                        @click="deletingRole = role"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </template>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="props.roles.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                            No hay roles registrados
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <EditRoleModal
        v-if="editingRole"
        :role="editingRole"
        :permissions="props.permissions"
        :can-assign-permissions="props.can.assignPermissions"
        :open="true"
        @update:open="(v) => { if (!v) editingRole = null; }"
    />

    <DeleteRoleModal
        v-if="deletingRole"
        :role="deletingRole"
        :open="true"
        @update:open="(v) => { if (!v) deletingRole = null; }"
    />
</template>
