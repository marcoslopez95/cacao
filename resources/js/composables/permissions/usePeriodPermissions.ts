import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function usePeriodPermissions() {
    const { can } = usePermission()

    const canCreate   = computed(() => can('periods.create'))
    const canUpdate   = computed(() => can('periods.update'))
    const canDelete   = computed(() => can('periods.delete'))
    const canActivate = computed(() => can('periods.update'))
    const canClose    = computed(() => can('periods.update'))

    return { canCreate, canUpdate, canDelete, canActivate, canClose }
}
