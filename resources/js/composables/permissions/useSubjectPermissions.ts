import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useSubjectPermissions() {
    const { can } = usePermission()

    const canCreate               = computed(() => can('subjects.create'))
    const canUpdate               = computed(() => can('subjects.update'))
    const canDelete               = computed(() => can('subjects.delete'))
    const canManagePrerequisites  = computed(() => can('subjects.manage-prerequisites'))

    return { canCreate, canUpdate, canDelete, canManagePrerequisites }
}
