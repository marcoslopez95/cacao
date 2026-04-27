import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useBuildingPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('buildings.create'))
    const canUpdate = computed(() => can('buildings.update'))
    const canDelete = computed(() => can('buildings.delete'))

    return { canCreate, canUpdate, canDelete }
}
