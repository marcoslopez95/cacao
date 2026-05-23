import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useGradeConfigPermissions(can: { create?: boolean } = {}) {
    const page = usePage()

    const canCreate = computed(() => can.create ?? false)
    const canEdit = computed(() => (page.props as any).auth?.user?.roles?.includes('Administrador') ?? false)

    return { canCreate, canEdit }
}
