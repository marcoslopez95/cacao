import { computed } from 'vue'
import type { Ref } from 'vue'
import { toMinutes } from '@/composables/scheduling/useScheduleLayout'
import type {
    EnrollmentSelections,
    EnrollmentSubject,
    EnrollmentSection,
    EnrollmentSlot,
    EnrollmentSummary,
    EnrollmentConflict,
} from '@/types/enrollment'

export function useEnrollmentState(
    subjects: EnrollmentSubject[],
    selections: Ref<EnrollmentSelections>,
    setSelections: (fn: (prev: EnrollmentSelections) => EnrollmentSelections) => void,
) {
    function slotsOverlap(a: EnrollmentSlot, b: EnrollmentSlot): boolean {
        if (a.day !== b.day) return false
        return toMinutes(a.start) < toMinutes(b.end) && toMinutes(b.start) < toMinutes(a.end)
    }

    function findConflict(
        candidate: EnrollmentSection,
        ownCode: string,
    ): EnrollmentConflict | null {
        if (!candidate.slots.length) return null
        for (const [subjectCode, secIdx] of Object.entries(selections.value)) {
            if (subjectCode === ownCode) continue
            const subject = subjects.find(s => s.code === subjectCode)
            const section = subject?.sections[secIdx]
            if (!section?.slots.length) continue
            for (const a of candidate.slots) {
                for (const b of section.slots) {
                    if (slotsOverlap(a, b)) {
                        return { subject: subject!, section, slotA: a, slotB: b }
                    }
                }
            }
        }
        return null
    }

    function slotHours(slots: EnrollmentSlot[]): number {
        return slots.reduce(
            (acc, s) => acc + (toMinutes(s.end) - toMinutes(s.start)) / 60,
            0,
        )
    }

    const summary = computed<EnrollmentSummary>(() => {
        let credits = 0, hours = 0, scheduled = 0, pending = 0
        const items = []
        for (const [code, secIdx] of Object.entries(selections.value)) {
            const subject = subjects.find(s => s.code === code)
            if (!subject) continue
            const section = subject.sections[secIdx]
            if (!section) continue
            credits += subject.credits
            hours += slotHours(section.slots)
            section.noSchedule ? pending++ : scheduled++
            items.push({ subject, section, sectionIdx: secIdx })
        }
        return { credits, hours, scheduled, pending, count: items.length, items }
    })

    const creditsPct = computed(() =>
        Math.min(100, (summary.value.credits / 24) * 100),
    )

    const creditsStatus = computed<'low' | 'ok' | 'high'>(() => {
        const c = summary.value.credits
        if (c > 24) return 'high'
        if (c < 12) return 'low'
        return 'ok'
    })

    function select(subjectCode: string, sectionIdx: number): void {
        setSelections(prev => ({ ...prev, [subjectCode]: sectionIdx }))
    }

    function unselect(subjectCode: string): void {
        setSelections(prev => {
            const next = { ...prev }
            delete next[subjectCode]
            return next
        })
    }

    return { summary, creditsPct, creditsStatus, findConflict, select, unselect }
}
