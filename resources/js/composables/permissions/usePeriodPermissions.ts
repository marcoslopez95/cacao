import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function usePeriodPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('periods.create'))
    const canUpdate = computed(() => can('periods.update'))
    const canDelete = computed(() => can('periods.delete'))

    return { canCreate, canUpdate, canDelete }
}
