import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useProfessorPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('professors.create'))
    const canUpdate = computed(() => can('professors.update'))
    const canDelete = computed(() => can('professors.delete'))

    return { canCreate, canUpdate, canDelete }
}
