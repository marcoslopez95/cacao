import { computed, reactive, ref, watch } from 'vue'
import type { UserFormData } from '@/types/userForm'
import type { RoleKey, TabDef } from '@/types/userFormCatalogs'
import { UF_SECTIONS, UF_TABS } from '@/types/userFormCatalogs'

export type SectionStatus = 'empty' | 'partial' | 'complete' | 'editing'
type AutosaveStatus = 'idle' | 'saving' | 'saved'

export interface CompletionResult {
    pct: number
    sectionsComplete: Set<number>
    sectionsPartial: Set<number>
}

function computeCompletion(data: UserFormData, role: RoleKey | ''): CompletionResult {
    if (!role) {
return { pct: 0, sectionsComplete: new Set(), sectionsPartial: new Set() }
}

    const checks: Record<number, string[] | (() => boolean)> = {
        1:  ['firstName', 'lastName', 'docNumber', 'birthDate', 'nationality', 'phone1'],
        2:  ['email'],
        3:  () => (data.addresses?.length ?? 0) > 0
                    && !!data.addresses?.[0]?.country
                    && !!data.addresses?.[0]?.line1,
        4:  ['birthCity', 'birthCountry'],
        5:  ['emergencyName', 'emergencyPhone', 'emergencyRel', 'bloodType'],
        6:  () => !!data.consent_data,
        7:  () => (data.attachments?.length ?? 0) > 0,
        8:  ['studentCode', 'academicStatus', 'enrollDate'],
        9:  ['prevInstitution', 'prevInstitutionType', 'gradYear'],
        10: () => (data.languages?.length ?? 0) > 0,
        11: ['repMarital', 'living'],
        12: ['incomeRange', 'incomeSource'],
        13: () => (data.benefits?.length ?? 0) > 0,
        14: ['housing', 'tenure', 'rooms', 'peopleHome'],
        15: () => (data.guardians?.length ?? 0) > 0,
        16: ['occupation', 'employer'],
        17: ['empCode', 'degree', 'hireDate'],
    }

    const tabs: TabDef[] = UF_TABS[role] ?? []
    const sectionsToCheck = tabs.flatMap(t => t.sections)
    let done = 0
    let total = 0
    const sectionsComplete = new Set<number>()
    const sectionsPartial = new Set<number>()

    for (const sec of sectionsToCheck) {
        const check = checks[sec]

        if (!check) {
continue
}

        if (typeof check === 'function') {
            total++

            if (check()) {
                done++
                sectionsComplete.add(sec)
            }
        } else {
            total += check.length
            let secDone = 0

            for (const f of check) {
                const val = (data as Record<string, unknown>)[f]

                if (val != null && val !== '') {
                    done++
                    secDone++
                }
            }

            if (secDone === check.length) {
                sectionsComplete.add(sec)
            } else if (secDone > 0) {
                sectionsPartial.add(sec)
            }
        }
    }

    return {
        pct: total > 0 ? Math.round((done / total) * 100) : 0,
        sectionsComplete,
        sectionsPartial,
    }
}

export function useUserFormPage() {
    const activeRole      = ref<RoleKey | ''>('')
    const activeTab       = ref<string>('')
    const activeSection   = ref<number | null>(null)
    const formData        = reactive<UserFormData>({})
    const savedSections   = ref<Set<number>>(new Set())
    const editingSections = ref<Set<number>>(new Set())
    const autosave        = ref<{ status: AutosaveStatus; when: Date | null }>({
        status: 'idle',
        when: null,
    })
    let autosaveTimer: ReturnType<typeof setTimeout> | null = null

    const tabs = computed<TabDef[]>(() => UF_TABS[activeRole.value as RoleKey] ?? [])

    const activeTabDef = computed(
        () => tabs.value.find(t => t.key === activeTab.value) ?? tabs.value[0],
    )

    const completion = computed<CompletionResult>(
        () => computeCompletion(formData, activeRole.value),
    )

    function pickRole(key: RoleKey): void {
        activeRole.value = key
        activeTab.value = UF_TABS[key]?.[0]?.key ?? ''
        savedSections.value = new Set()
        editingSections.value = new Set()
    }

    function resetRole(): void {
        activeRole.value = ''
        activeTab.value = ''
    }

    function saveSection(n: number): void {
        savedSections.value = new Set([...savedSections.value, n])
        const next = new Set(editingSections.value)
        next.delete(n)
        editingSections.value = next
    }

    function editSection(n: number): void {
        editingSections.value = new Set([...editingSections.value, n])
    }

    function cancelSection(n: number): void {
        const next = new Set(editingSections.value)
        next.delete(n)
        editingSections.value = next
    }

    function statusFor(n: number): SectionStatus {
        if (editingSections.value.has(n)) {
return 'editing'
}

        if (completion.value.sectionsComplete.has(n)) {
return 'complete'
}

        if (completion.value.sectionsPartial.has(n)) {
return 'partial'
}

        return 'empty'
    }

    function scrollToSection(n: number): void {
        document.getElementById(`sec-${n}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }

    function setupScrollSpy(sectionNums: number[]): () => void {
        const els = sectionNums
            .map(n => document.getElementById(`sec-${n}`))
            .filter((el): el is HTMLElement => el !== null)

        if (!els.length) {
return () => {}
}

        const obs = new IntersectionObserver(
            entries => {
                const visible = entries
                    .filter(e => e.isIntersecting)
                    .sort(
                        (a, b) =>
                            a.target.getBoundingClientRect().top -
                            b.target.getBoundingClientRect().top,
                    )

                if (visible.length) {
                    activeSection.value = parseInt(visible[0].target.id.replace('sec-', ''))
                }
            },
            { rootMargin: '-30% 0px -50% 0px' },
        )

        els.forEach(el => obs.observe(el))

        return () => obs.disconnect()
    }

    function setField<K extends keyof UserFormData>(key: K, value: UserFormData[K]): void {
        ;(formData as Record<string, unknown>)[key as string] = value
    }

    // Autosave simulado
    watch(
        formData,
        () => {
            if (autosaveTimer) {
clearTimeout(autosaveTimer)
}

            autosave.value = { status: 'saving', when: null }
            autosaveTimer = setTimeout(() => {
                autosave.value = { status: 'saved', when: new Date() }
            }, 600)
        },
        { deep: true },
    )

    return {
        activeRole,
        activeTab,
        activeSection,
        formData,
        savedSections,
        editingSections,
        autosave,
        tabs,
        activeTabDef,
        completion,
        UF_SECTIONS,
        pickRole,
        resetRole,
        saveSection,
        editSection,
        cancelSection,
        statusFor,
        scrollToSection,
        setupScrollSpy,
        setField,
    }
}
