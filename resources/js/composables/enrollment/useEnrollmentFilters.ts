import { computed, ref } from 'vue'
import type { EnrollmentSubject, EnrollmentFilters } from '@/types/enrollment'

export function useEnrollmentFilters(subjects: EnrollmentSubject[]) {
    const search = ref('')
    const filters = ref<EnrollmentFilters>({
        type: 'all',
        recommendedOnly: false,
        prereqsOnly: false,
        hideCompleted: true,
    })

    const filteredSubjects = computed(() =>
        subjects.filter(m => {
            if (m.completed && filters.value.hideCompleted) {
return false
}

            if (filters.value.type !== 'all' && m.type !== filters.value.type) {
return false
}

            if (filters.value.recommendedOnly && !m.recommendedTrim) {
return false
}

            if (filters.value.prereqsOnly && !m.prereqsOk) {
return false
}

            if (search.value) {
                const q = search.value.toLowerCase()

                if (!m.name.toLowerCase().includes(q) && !m.code.toLowerCase().includes(q)) {
                    return false
                }
            }

            return true
        }),
    )

    return { search, filters, filteredSubjects }
}
