import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { index } from '@/routes/infrastructure/classrooms'

export function useClassroomFilters(initialBuildingId: number | null = null) {
    const buildingId = ref<number | null>(initialBuildingId)

    function applyFilter(): void {
        router.get(
            index.url(),
            buildingId.value ? { building_id: buildingId.value } : {},
            { preserveState: true, replace: true },
        )
    }

    return { buildingId, applyFilter }
}
