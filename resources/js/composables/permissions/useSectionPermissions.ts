import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useSectionPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('sections.create'))
    const canUpdate = computed(() => can('sections.update'))
    const canDelete = computed(() => can('sections.delete'))

    return { canCreate, canUpdate, canDelete }
}
