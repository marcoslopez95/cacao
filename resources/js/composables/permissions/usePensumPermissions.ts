import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function usePensumPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('pensums.create'))
    const canUpdate = computed(() => can('pensums.update'))
    const canDelete = computed(() => can('pensums.delete'))

    return { canCreate, canUpdate, canDelete }
}
