<script setup lang="ts">
import type { RoleKey } from '@/types/userFormCatalogs'
import { UF_ROLES } from '@/types/userFormCatalogs'

defineProps<{
    role: RoleKey
    isEdit?: boolean
    completion: { pct: number }
    autosave: { status: 'idle' | 'saving' | 'saved' | 'error'; when: Date | null; message?: string }
}>()

defineEmits<{ changeRole: [] }>()
</script>

<template>
    <div class="uf-form-head">
        <div class="uf-form-head-left">
            <h1>
                {{ isEdit ? 'Editar usuario' : 'Nuevo usuario' }}
                <span class="uf-mode-tag" :class="{ edit: isEdit }">
                    {{ isEdit ? 'MODO EDICIÓN' : 'MODO CREACIÓN' }}
                </span>
            </h1>
            <p>
                {{ isEdit
                    ? 'Modificá los datos del perfil. Los cambios se guardan automáticamente.'
                    : 'Completá los datos del perfil. Podés guardar borradores en cualquier momento.'
                }}
            </p>
            <span class="uf-role-pill">
                Rol:&nbsp;<strong>{{ UF_ROLES[role].label }}</strong>
                <button type="button" class="uf-role-pill-change" @click="$emit('changeRole')">
                    cambiar
                </button>
            </span>
        </div>

        <div class="uf-form-head-right">
            <div class="uf-progress-wrap">
                <div class="uf-progress-track">
                    <div class="uf-progress-fill" :style="{ width: completion.pct + '%' }" />
                </div>
                <span class="uf-progress-pct">{{ completion.pct }}%</span>
            </div>
            <span class="uf-autosave" :class="autosave.status">
                <span class="uf-autosave-dot" />
                <span v-if="autosave.status === 'saving'">Guardando…</span>
                <span v-else-if="autosave.status === 'saved'">Guardado</span>
                <span v-else-if="autosave.status === 'error'" :title="autosave.message">Error al guardar</span>
                <span v-else>Sin cambios</span>
            </span>
        </div>
    </div>
</template>
