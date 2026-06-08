import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { index } from '@/routes/academic/students'
import type { StudentFilters, StudentLevel } from '@/types/student'

export function useStudentFilters(initial: StudentFilters, perPage: number) {
    const search          = ref(initial.search ?? '')
    const careerIds       = ref<number[]>(initial.career_id ?? [])
    const academicYears   = ref<number[]>(initial.academic_year ?? [])
    const enrollStatuses  = ref<string[]>(initial.enrollment_status ?? [])
    const level           = ref<StudentLevel>(initial.level ?? 'all')
    const sectionLetters  = ref<string[]>(initial.section_letter ?? [])
    const quickView       = ref<string>(initial.quick_view ?? 'all')
    let debounceTimer: ReturnType<typeof setTimeout>

    function applyFilters(overrides: Partial<StudentFilters> = {}): void {
        const s     = overrides.search          ?? (search.value || undefined)
        const cids  = overrides.career_id       ?? (careerIds.value.length      ? careerIds.value      : undefined)
        const years = overrides.academic_year   ?? (academicYears.value.length  ? academicYears.value  : undefined)
        const enr   = overrides.enrollment_status ?? (enrollStatuses.value.length ? enrollStatuses.value : undefined)
        const lvl   = overrides.level           ?? (level.value !== 'all' ? level.value : undefined)
        const sl    = overrides.section_letter  ?? (sectionLetters.value.length ? sectionLetters.value : undefined)
        const qv    = overrides.quick_view      ?? (quickView.value !== 'all' ? quickView.value : undefined)

        router.get(
            index.url(),
            { search: s, career_id: cids, academic_year: years, enrollment_status: enr,
              level: lvl, section_letter: sl, quick_view: qv,
              per_page: perPage !== 25 ? perPage : undefined },
            { preserveState: true, replace: true },
        )
    }

    function onSearchInput(): void {
        clearTimeout(debounceTimer)
        debounceTimer = setTimeout(() => applyFilters(), 350)
    }

    function applyLevel(newLevel: StudentLevel): void {
        search.value         = ''
        careerIds.value      = []
        academicYears.value  = []
        enrollStatuses.value = []
        sectionLetters.value = []
        quickView.value      = 'all'
        level.value          = newLevel

        applyFilters()
    }

    function applyQuickView(key: string): void {
        search.value         = ''
        careerIds.value      = []
        academicYears.value  = []
        enrollStatuses.value = []
        sectionLetters.value = []
        quickView.value      = key

        if (key === 'pending')               {
enrollStatuses.value = ['draft', 'none']
}

        if (key === 'newcomers' || key === 'year_1' || key === 'grade_1') {
academicYears.value = [1]
}

        if (key === 'grade_6')               {
academicYears.value  = [6]
}

        if (key === 'year_5')                {
academicYears.value  = [5]
}

        if (key === 'enrolled')              {
enrollStatuses.value = ['confirmed', 'approved', 'draft']
}
        // 'no_guardian' — no frontend filter available; backend handles count but we can't filter locally

        applyFilters({
            level:      level.value !== 'all' ? level.value : undefined,
            quick_view: key !== 'all' ? key : undefined,
        })
    }

    const paginationFilters = computed(() => ({
        search:            search.value || undefined,
        career_id:         careerIds.value.length      ? careerIds.value      : undefined,
        academic_year:     academicYears.value.length  ? academicYears.value  : undefined,
        enrollment_status: enrollStatuses.value.length ? enrollStatuses.value : undefined,
        level:             level.value !== 'all'       ? level.value          : undefined,
        section_letter:    sectionLetters.value.length ? sectionLetters.value : undefined,
        quick_view:        quickView.value !== 'all'   ? quickView.value      : undefined,
    }))

    return {
        search, careerIds, academicYears, enrollStatuses, level, sectionLetters, quickView,
        applyFilters, onSearchInput, applyLevel, applyQuickView, paginationFilters,
    }
}
