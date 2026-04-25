import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useCoordinationPermissions() {
    const { can } = usePermission()

    const canCreate      = computed(() => can('coordinations.create'))
    const canEdit        = computed(() => can('coordinations.edit'))
    const canDelete      = computed(() => can('coordinations.delete'))
    const canAssign      = computed(() => can('coordinations.assign'))
    const canViewHistory = computed(() => can('coordinations.view_history'))

    return { canCreate, canEdit, canDelete, canAssign, canViewHistory }
}
