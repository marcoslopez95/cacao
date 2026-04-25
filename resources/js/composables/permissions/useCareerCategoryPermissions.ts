import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useCareerCategoryPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('career-categories.create'))
    const canUpdate = computed(() => can('career-categories.update'))
    const canDelete = computed(() => can('career-categories.delete'))

    return { canCreate, canUpdate, canDelete }
}
