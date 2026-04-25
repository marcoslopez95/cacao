<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Badge from '@/components/base/Badge.vue'
import Button from '@/components/base/Button.vue'
import Pagination from '@/components/base/Pagination.vue'
import CreateUserModal from '@/components/security/CreateUserModal.vue'
import DeactivateUserModal from '@/components/security/DeactivateUserModal.vue'
import DeleteUserModal from '@/components/security/DeleteUserModal.vue'
import EditUserModal from '@/components/security/EditUserModal.vue'
import InviteUserModal from '@/components/security/InviteUserModal.vue'
import ResetPasswordModal from '@/components/security/ResetPasswordModal.vue'
import { usePermission } from '@/composables/usePermission'
import { index } from '@/routes/security/users'
import type { UserPaginator, UserRow } from '@/types'

type Props = {
    users: UserPaginator
    roles: string[]
    filters: { search?: string; role?: string; status?: string }
    can: { create: boolean; invite: boolean }
}

const props = defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Seguridad', href: '#' },
            { title: 'Usuarios', href: index.url() },
        ],
    },
})

const { can } = usePermission()

const search = ref(props.filters.search ?? '')
const roleFilter = ref(props.filters.role ?? '')
const statusFilter = ref(props.filters.status ?? '')

let debounceTimer: ReturnType<typeof setTimeout>

function applyFilters(): void {
    router.get(index.url(), {
        search:   search.value || undefined,
        role:     roleFilter.value || undefined,
        status:   statusFilter.value || undefined,
        per_page: props.users.per_page !== 20 ? props.users.per_page : undefined,
    }, { preserveState: true, replace: true })
}

function onSearchInput(): void {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(applyFilters, 350)
}

const paginationFilters = computed(() => ({
    search:  search.value || undefined,
    role:    roleFilter.value || undefined,
    status:  statusFilter.value || undefined,
}))

const editingUser    = ref<UserRow | null>(null)
const resetUser      = ref<UserRow | null>(null)
const deactivateUser = ref<UserRow | null>(null)
const deleteUser     = ref<UserRow | null>(null)
const showCreate     = ref(false)
const showInvite     = ref(false)

const authId = (usePage().props as any).auth?.user?.id
</script>

<template>
    <Head title="Usuarios" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <!-- Header -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Usuarios
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Gestiona los usuarios y sus accesos al sistema
                </p>
            </div>
            <div style="display:flex;gap:8px;">
                <Button
                    v-if="props.can.invite"
                    variant="ghost"
                    icon="mail"
                    @click="showInvite = true"
                >
                    Invitar por correo
                </Button>
                <Button
                    v-if="props.can.create"
                    variant="primary"
                    icon="plus"
                    @click="showCreate = true"
                >
                    Nuevo usuario
                </Button>
            </div>
        </div>

        <!-- Card: toolbar + table + pagination -->
        <div class="table-wrap">
            <!-- Filters toolbar -->
            <div style="display:flex;gap:12px;flex-wrap:wrap;padding:12px 16px;border-bottom:1px solid var(--border);">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Buscar por nombre o correo..."
                    class="input"
                    style="flex:1;min-width:200px;max-width:320px;"
                    @input="onSearchInput"
                />
                <select v-model="roleFilter" class="input" style="width:160px;" @change="applyFilters">
                    <option value="">Todos los roles</option>
                    <option v-for="r in props.roles" :key="r" :value="r">{{ r }}</option>
                </select>
                <select v-model="statusFilter" class="input" style="width:160px;" @change="applyFilters">
                    <option value="">Todos los estados</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </select>
            </div>

            <!-- Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Roles</th>
                        <th>Estado</th>
                        <th>Creado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in props.users.data" :key="user.id">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--accent-soft);color:var(--accent);border:1.5px solid color-mix(in srgb,var(--accent) 25%,transparent);display:grid;place-items:center;font-weight:600;font-size:13px;flex-shrink:0;">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div style="font-weight:500;color:var(--text-primary);">{{ user.name }}</div>
                                    <div style="font-size:11.5px;color:var(--text-muted);font-family:var(--font-mono);">{{ user.email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                <Badge
                                    v-for="role in user.roles"
                                    :key="role"
                                    :variant="role === 'Admin' ? 'accent' : 'neutral'"
                                >{{ role }}</Badge>
                                <span v-if="!user.roles.length" style="color:var(--text-muted);font-style:italic;font-size:var(--text-sm);">Sin rol</span>
                            </div>
                        </td>
                        <td>
                            <Badge :variant="user.active ? 'success' : 'neutral'" dot>
                                {{ user.active ? 'Activo' : 'Inactivo' }}
                            </Badge>
                        </td>
                        <td style="color:var(--text-muted);font-size:var(--text-xs);font-family:var(--font-mono);">
                            {{ user.created_at }}
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <Button
                                    v-if="can('users.update') && user.id !== authId"
                                    variant="ghost" size="sm" icon-only icon="edit"
                                    :aria-label="`Editar ${user.name}`"
                                    @click="editingUser = user"
                                />
                                <Button
                                    v-if="can('users.reset-password')"
                                    variant="ghost" size="sm" icon-only icon="key"
                                    :aria-label="`Cambiar contraseña de ${user.name}`"
                                    @click="resetUser = user"
                                />
                                <Button
                                    v-if="can('users.deactivate') && user.id !== authId"
                                    variant="ghost" size="sm" icon-only
                                    :icon="user.active ? 'toggle-right' : 'toggle-left'"
                                    :aria-label="user.active ? `Desactivar ${user.name}` : `Reactivar ${user.name}`"
                                    @click="deactivateUser = user"
                                />
                                <Button
                                    v-if="can('users.delete')"
                                    variant="ghost" size="sm" icon-only icon="trash"
                                    :aria-label="`Eliminar ${user.name}`"
                                    @click="deleteUser = user"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!props.users.data.length">
                        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay usuarios que coincidan con los filtros
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <Pagination
                :paginator="props.users"
                :route-url="index.url()"
                :filters="paginationFilters"
            />
        </div>
    </div>

    <CreateUserModal
        :open="showCreate"
        :roles="props.roles"
        @update:open="showCreate = $event"
    />

    <InviteUserModal
        :open="showInvite"
        :roles="props.roles"
        @update:open="showInvite = $event"
    />

    <EditUserModal
        v-if="editingUser"
        :open="!!editingUser"
        :user="editingUser"
        :roles="props.roles"
        @update:open="v => { if (!v) editingUser = null }"
    />

    <ResetPasswordModal
        v-if="resetUser"
        :open="!!resetUser"
        :user="resetUser"
        @update:open="v => { if (!v) resetUser = null }"
    />

    <DeactivateUserModal
        v-if="deactivateUser"
        :open="!!deactivateUser"
        :user="deactivateUser"
        @update:open="v => { if (!v) deactivateUser = null }"
    />

    <DeleteUserModal
        v-if="deleteUser"
        :open="!!deleteUser"
        :user="deleteUser"
        @update:open="v => { if (!v) deleteUser = null }"
    />
</template>
