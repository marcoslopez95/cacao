<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { computed, onUnmounted, ref, watch } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import { update } from '@/routes/security/roles'
import type { Role } from '@/types'

const props = defineProps<{
    open: boolean
    role: Role
    permissions: string[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

// ── Labels ─────────────────────────────────────────────────────
const GROUP_LABELS: Record<string, string> = {
    roles:          'Roles',
    users:          'Usuarios',
    coordinations:  'Coordinaciones',
}

const PERM_LABELS: Record<string, string> = {
    'roles.view':               'Ver roles',
    'roles.create':             'Crear roles',
    'roles.update':             'Editar roles',
    'roles.delete':             'Eliminar roles',
    'roles.assign-permissions': 'Asignar permisos a roles',
    'users.view':               'Ver usuarios',
    'users.create':             'Crear usuarios',
    'users.update':             'Editar usuarios',
    'users.delete':             'Eliminar usuarios',
    'users.deactivate':         'Desactivar usuarios',
    'users.reset-password':     'Restablecer contraseña',
    'users.invite':             'Invitar usuarios',
    'coordinations.view':       'Ver coordinaciones',
    'coordinations.create':     'Crear coordinaciones',
    'coordinations.edit':       'Editar coordinaciones',
    'coordinations.delete':     'Eliminar coordinaciones',
    'coordinations.assign':     'Asignar coordinador',
    'coordinations.view_history': 'Ver historial',
}

function groupLabel(prefix: string): string {
    return GROUP_LABELS[prefix] ?? (prefix.charAt(0).toUpperCase() + prefix.slice(1))
}

function permLabel(code: string): string {
    return PERM_LABELS[code] ?? code
}

// ── Form state ─────────────────────────────────────────────────
const search = ref('')

const form = useForm({
    name:        props.role.name,
    permissions: [...props.role.permissions] as string[],
})

watch([() => props.open, () => props.role.id], ([open]) => {
    if (open) {
        form.name        = props.role.name
        form.permissions = [...props.role.permissions]
        search.value     = ''
    }
})

// ── Derived ────────────────────────────────────────────────────
const groupedAll = computed<Record<string, string[]>>(() => {
    const GROUP_ORDER = ['roles', 'users', 'coordinations']
    const groups: Record<string, string[]> = {}
    for (const p of props.permissions) {
        const prefix = p.split('.')[0]
        if (!groups[prefix]) groups[prefix] = []
        groups[prefix].push(p)
    }
    const orderedKeys = [
        ...GROUP_ORDER.filter(k => groups[k]),
        ...Object.keys(groups).filter(k => !GROUP_ORDER.includes(k)),
    ]
    return Object.fromEntries(orderedKeys.map(k => [k, groups[k]]))
})

const filteredGroups = computed<Record<string, string[]>>(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return groupedAll.value
    return Object.fromEntries(
        Object.entries(groupedAll.value)
            .map(([prefix, perms]) => [
                prefix,
                perms.filter(p =>
                    permLabel(p).toLowerCase().includes(q) ||
                    p.toLowerCase().includes(q) ||
                    groupLabel(prefix).toLowerCase().includes(q),
                ),
            ])
            .filter(([, perms]) => (perms as string[]).length > 0),
    )
})

const totalSelected = computed(() => form.permissions.length)

const hasChanges = computed(() => {
    if (form.permissions.length !== props.role.permissions.length) return true
    const orig = new Set(props.role.permissions)
    return form.permissions.some(p => !orig.has(p))
})

// ── Actions ────────────────────────────────────────────────────
function toggle(code: string): void {
    const idx = form.permissions.indexOf(code)
    if (idx === -1) form.permissions.push(code)
    else form.permissions.splice(idx, 1)
}

function toggleGroup(groupPerms: string[]): void {
    const allChecked = groupPerms.every(p => form.permissions.includes(p))
    if (allChecked) {
        form.permissions = form.permissions.filter(p => !groupPerms.includes(p))
    } else {
        for (const p of groupPerms) {
            if (!form.permissions.includes(p)) form.permissions.push(p)
        }
    }
}

function selectAll(): void { form.permissions = [...props.permissions] }
function clearAll(): void  { form.permissions = [] }

function close(): void { emit('update:open', false) }

function submit(): void {
    form.patch(update.url(props.role), { onSuccess: close })
}

// ESC key
function onKey(e: KeyboardEvent): void { if (e.key === 'Escape') close() }
watch(() => props.open, val => {
    if (val) document.addEventListener('keydown', onKey)
    else     document.removeEventListener('keydown', onKey)
}, { immediate: true })
onUnmounted(() => document.removeEventListener('keydown', onKey))
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            style="position:fixed;inset:0;background:rgba(19,17,16,0.5);backdrop-filter:blur(2px);z-index:1000;display:grid;place-items:center;padding:20px;animation:fade-in 180ms ease;"
            @click.self="close"
        >
            <div
                role="dialog"
                aria-modal="true"
                style="background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-xl);box-shadow:var(--shadow-lg);width:100%;max-width:720px;max-height:85vh;display:flex;flex-direction:column;overflow:hidden;animation:modal-in 180ms ease;"
            >
                <!-- Header -->
                <div style="padding:20px 24px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:16px;">
                    <h2 style="flex:1;margin:0;font-size:var(--text-lg);font-weight:600;color:var(--text-primary);">
                        Permisos · {{ role.name }}
                    </h2>
                    <button
                        class="btn btn-ghost btn-icon btn-sm"
                        style="color:var(--text-muted);flex-shrink:0;"
                        aria-label="Cerrar"
                        type="button"
                        @click="close"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                <!-- Toolbar -->
                <div style="padding:12px 24px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;border-bottom:1px solid var(--border);background:var(--bg-surface-2);">
                    <div style="flex:1;min-width:180px;position:relative;">
                        <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Buscar permiso…"
                            class="input"
                            style="padding-left:32px;height:32px;font-size:var(--text-xs);"
                        />
                    </div>
                    <button class="btn btn-secondary btn-sm" type="button" @click="selectAll">Seleccionar todo</button>
                    <button class="btn btn-secondary btn-sm" type="button" @click="clearAll">Limpiar</button>
                    <span style="font-size:11.5px;color:var(--text-muted);font-family:var(--font-mono);white-space:nowrap;">
                        <strong style="color:var(--accent);">{{ totalSelected }}</strong> / {{ permissions.length }}
                    </span>
                </div>

                <!-- Body (scrollable) -->
                <div style="flex:1;overflow-y:auto;padding:4px 24px 24px;">
                    <div v-if="Object.keys(filteredGroups).length === 0" style="padding:40px 0;text-align:center;color:var(--text-muted);font-size:var(--text-sm);">
                        No hay permisos que coincidan con "{{ search }}"
                    </div>
                    <div
                        v-for="(groupPerms, prefix) in filteredGroups"
                        :key="prefix"
                        style="border-bottom:1px solid var(--border);padding:14px 0 10px;"
                    >
                        <!-- Group heading -->
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                            <span style="font-size:10.5px;font-weight:700;color:var(--text-primary);text-transform:uppercase;letter-spacing:0.1em;font-family:var(--font-mono);">
                                {{ groupLabel(String(prefix)) }}
                            </span>
                            <span style="font-size:11px;color:var(--text-muted);font-family:var(--font-mono);">
                                {{ groupPerms.filter(p => form.permissions.includes(p)).length }} / {{ groupPerms.length }}
                            </span>
                            <button
                                type="button"
                                style="margin-left:auto;background:transparent;border:0;font-size:11.5px;color:var(--accent);cursor:pointer;padding:4px 8px;border-radius:var(--radius-sm);font-family:inherit;font-weight:500;"
                                @click="toggleGroup(groupPerms as string[])"
                            >
                                {{ (groupPerms as string[]).every(p => form.permissions.includes(p)) ? 'Quitar todos' : 'Seleccionar todos' }}
                            </button>
                        </div>
                        <!-- 2-col grid -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2px 16px;">
                            <label
                                v-for="p in (groupPerms as string[])"
                                :key="p"
                                style="display:flex;align-items:flex-start;gap:10px;padding:7px 10px;border-radius:var(--radius-sm);cursor:pointer;user-select:none;transition:background .08s;"
                                :style="{ background: form.permissions.includes(p) ? 'color-mix(in srgb,var(--accent) 6%,transparent)' : '' }"
                            >
                                <input
                                    type="checkbox"
                                    :checked="form.permissions.includes(p)"
                                    style="width:15px;height:15px;accent-color:var(--accent);cursor:pointer;flex-shrink:0;margin-top:2px;"
                                    @change="toggle(p)"
                                />
                                <span style="font-size:var(--text-sm);color:var(--text-primary);line-height:1.3;">
                                    {{ permLabel(p) }}
                                    <span style="display:block;font-family:var(--font-mono);font-size:10px;color:var(--text-muted);margin-top:2px;">{{ p }}</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div style="padding:14px 24px;border-top:1px solid var(--border);background:var(--bg-surface-2);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <span style="font-size:var(--text-xs);color:var(--text-muted);">
                        {{ hasChanges ? 'Hay cambios sin guardar.' : 'Sin cambios pendientes.' }}
                    </span>
                    <div style="display:flex;gap:8px;">
                        <Button variant="secondary" type="button" @click="close">Cancelar</Button>
                        <Button
                            variant="primary"
                            type="button"
                            :loading="form.processing"
                            :disabled="!hasChanges"
                            @click="submit"
                        >
                            Guardar cambios
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
