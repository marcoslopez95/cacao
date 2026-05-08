import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

export function useSchedulePermissions() {
    const { can } = usePermission()

    const canCreate = computed(() => can('schedules.create'))
    const canUpdate = computed(() => can('schedules.update'))
    const canDelete = computed(() => can('schedules.delete'))

    return { canCreate, canUpdate, canDelete }
}
