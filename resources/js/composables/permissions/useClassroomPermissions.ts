import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useClassroomPermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('classrooms.create'))
    const canUpdate = computed(() => can('classrooms.update'))
    const canDelete = computed(() => can('classrooms.delete'))

    return { canCreate, canUpdate, canDelete }
}
