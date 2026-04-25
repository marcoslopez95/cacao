import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useRolePermissions() {
    const { can } = usePermission()

    const canCreate           = computed(() => can('roles.create'))
    const canUpdate           = computed(() => can('roles.update'))
    const canDelete           = computed(() => can('roles.delete'))
    const canAssignPermissions = computed(() => can('roles.assign-permissions'))

    return { canCreate, canUpdate, canDelete, canAssignPermissions }
}
