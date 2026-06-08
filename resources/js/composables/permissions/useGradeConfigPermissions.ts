import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function useGradeConfigPermissions(can: { create?: boolean } = {}) {
    const page = usePage()

    const canCreate = computed(() => can.create ?? false)
    const canEdit = computed(() => (page.props as any).auth?.user?.roles?.includes('Administrador') ?? false)

    return { canCreate, canEdit }
}
