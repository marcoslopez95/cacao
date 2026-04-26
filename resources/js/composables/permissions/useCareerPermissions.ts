import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useCareerPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('careers.create'))
    const canUpdate = computed(() => can('careers.update'))
    const canDelete = computed(() => can('careers.delete'))

    return { canCreate, canUpdate, canDelete }
}
