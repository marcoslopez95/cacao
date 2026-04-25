<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Badge from '@/components/base/Badge.vue'
import Button from '@/components/base/Button.vue'
import CreateRoleModal from '@/components/security/CreateRoleModal.vue'
import DeleteRoleModal from '@/components/security/DeleteRoleModal.vue'
import PermissionsModal from '@/components/security/PermissionsModal.vue'
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

const search          = ref('')
const editingRole     = ref<Role | null>(null)
const deletingRole    = ref<Role | null>(null)
const showCreate      = ref(false)

const filteredRoles = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return props.roles
    return props.roles.filter(r => r.name.toLowerCase().includes(q))
})

// ── Permission chips ───────────────────────────────────────────
const GROUP_LABELS: Record<string, string> = {
    roles:         'Roles',
    users:         'Usuarios',
    coordinations: 'Coordinaciones',
}
const GROUP_ORDER = ['roles', 'users', 'coordinations']

function permChips(permissions: string[]): { key: string; label: string; count: number }[] {
    const counts: Record<string, number> = {}
    for (const p of permissions) {
        const prefix = p.split('.')[0]
        counts[prefix] = (counts[prefix] ?? 0) + 1
    }
    const keys = [
        ...GROUP_ORDER.filter(k => counts[k]),
        ...Object.keys(counts).filter(k => !GROUP_ORDER.includes(k)),
    ]
    return keys.map(k => ({
        key:   k,
        label: GROUP_LABELS[k] ?? (k.charAt(0).toUpperCase() + k.slice(1)),
        count: counts[k],
    }))
}
</script>

<template>
    <Head title="Roles" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <!-- Page header -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Roles
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Gestioná los roles y permisos del sistema.
                </p>
            </div>
            <Button v-if="props.can.create" icon="plus" variant="primary" @click="showCreate = true">
                Nuevo rol
            </Button>
        </div>

        <div class="table-wrap">
            <!-- Search toolbar -->
            <div style="padding:12px 16px;border-bottom:1px solid var(--border);">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Buscar por nombre…"
                    class="input"
                    style="width:100%;max-width:320px;"
                />
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th style="width:28%;">Rol</th>
                        <th>Permisos</th>
                        <th style="width:100px;text-align:center;">Usuarios</th>
                        <th style="width:110px;text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="role in filteredRoles" :key="role.id">
                        <!-- Role name -->
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <strong style="font-size:13.5px;font-weight:600;color:var(--text-primary);">{{ role.name }}</strong>
                                <Badge v-if="role.isAdmin" variant="accent">Admin</Badge>
                            </div>
                        </td>

                        <!-- Permission chips -->
                        <td>
                            <div v-if="role.permissions.length" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                <span
                                    v-for="chip in permChips(role.permissions)"
                                    :key="chip.key"
                                    style="display:inline-flex;align-items:center;gap:6px;padding:3px 9px 3px 8px;border-radius:999px;font-size:11.5px;font-weight:500;background:var(--bg-surface-2);color:var(--text-secondary);border:1px solid var(--border);white-space:nowrap;"
                                    :title="`${chip.count} permiso${chip.count !== 1 ? 's' : ''} de ${chip.label.toLowerCase()}`"
                                >
                                    <span style="font-family:var(--font-mono);font-size:10.5px;font-weight:600;color:var(--text-primary);">
                                        {{ chip.label }}
                                    </span>
                                    <span style="background:color-mix(in srgb,var(--accent) 12%,transparent);color:var(--accent);border-radius:999px;padding:0 6px;min-width:18px;text-align:center;font-family:var(--font-mono);font-size:10.5px;font-weight:600;line-height:1.5;">
                                        {{ chip.count }}
                                    </span>
                                </span>
                                <button
                                    v-if="props.can.assignPermissions && !role.isAdmin"
                                    style="background:transparent;border:1px dashed var(--border-strong,var(--border));color:var(--text-muted);padding:3px 10px;border-radius:999px;font-size:11.5px;cursor:pointer;font-family:inherit;transition:border-color .12s,color .12s;"
                                    @click="editingRole = role"
                                    @mouseover="($event.target as HTMLElement).style.color='var(--accent)'"
                                    @mouseout="($event.target as HTMLElement).style.color='var(--text-muted)'"
                                >
                                    Ver todos
                                </button>
                            </div>
                            <span v-else style="display:inline-flex;align-items:center;gap:6px;font-size:var(--text-sm);color:var(--text-muted);font-style:italic;">
                                <span style="width:6px;height:6px;border-radius:50%;background:var(--border);display:inline-block;flex-shrink:0;"></span>
                                Sin permisos
                            </span>
                        </td>

                        <!-- User count -->
                        <td style="text-align:center;">
                            <span
                                style="font-family:var(--font-mono);font-size:13px;font-weight:500;font-variant-numeric:tabular-nums;"
                                :style="{ color: role.usersCount === 0 ? 'var(--text-muted)' : 'var(--text-primary)' }"
                            >
                                {{ role.usersCount }}
                            </span>
                        </td>

                        <!-- Actions -->
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    v-if="props.can.update && !role.isAdmin"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="edit"
                                    :aria-label="`Editar permisos de ${role.name}`"
                                    @click="editingRole = role"
                                />
                                <Button
                                    v-if="props.can.delete && !role.isAdmin"
                                    variant="ghost"
                                    size="sm"
                                    icon-only
                                    icon="trash"
                                    :aria-label="`Eliminar ${role.name}`"
                                    @click="deletingRole = role"
                                />
                            </div>
                        </td>
                    </tr>

                    <tr v-if="!filteredRoles.length">
                        <td colspan="4" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay roles que coincidan con la búsqueda
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
        @update:open="v => { if (!v) showCreate = false }"
    />

    <PermissionsModal
        v-if="editingRole"
        :open="!!editingRole"
        :role="editingRole"
        :permissions="props.permissions"
        @update:open="v => { if (!v) editingRole = null }"
    />

    <DeleteRoleModal
        v-if="deletingRole"
        :open="!!deletingRole"
        :role="deletingRole"
        @update:open="v => { if (!v) deletingRole = null }"
    />
</template>
