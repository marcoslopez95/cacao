import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { index } from '@/routes/scheduling/schedules'

export function useScheduleFilters(
    initialPeriodId: number | null = null,
    initialSectionId: number | null = null,
    initialProfessorId: number | null = null,
    initialCareerIds: number[] | null = null,
) {
    const periodId    = ref<number | null>(initialPeriodId)
    const sectionId   = ref<number | null>(initialSectionId)
    const professorId = ref<number | null>(initialProfessorId)
    const careerIds   = ref<number[] | null>(initialCareerIds)

    function applyFilters(): void {
        const query: Record<string, string | number | string[]> = {}

        if (periodId.value)    {
query.period_id    = periodId.value
}

        if (sectionId.value)   {
query.section_id   = sectionId.value
}

        if (professorId.value) {
query.professor_id = professorId.value
}

        if (careerIds.value?.length) {
query.career_ids = careerIds.value.map(String)
}

        router.get(index.url(), query, { preserveState: true, replace: true })
    }

    return { periodId, sectionId, professorId, careerIds, applyFilters }
}
