<script setup lang="ts">
import UserFormAutosave from '@/components/security/UserForm/UserFormAutosave.vue'
import type { AutosaveState } from '@/types/userForm'
import type { RoleKey } from '@/types/userFormCatalogs'
import { UF_ROLES } from '@/types/userFormCatalogs'

defineProps<{
    role: RoleKey
    isEdit?: boolean
    /** Overrides the default subtitle under the title. */
    description?: string
    completion: { pct: number }
    autosave: AutosaveState
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
                {{ description ?? (isEdit
                    ? 'Modificá los datos del perfil. Los cambios se guardan automáticamente.'
                    : 'Completá los datos del perfil.')
                }}
            </p>
            <span class="uf-role-pill">
                Rol:&nbsp;<strong>{{ UF_ROLES[role].label }}</strong>
                <button v-if="!isEdit" type="button" class="uf-role-pill-change" @click="$emit('changeRole')">
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
            <UserFormAutosave :autosave="autosave" />
        </div>
    </div>
</template>
