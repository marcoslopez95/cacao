import { useForm } from '@inertiajs/vue3'
import { store, update } from '@/routes/security/grade-configs'
import type { GradeConfigFormData, GradeSlotFormData, GradeLetterValueFormData, GradeConfig } from '@/types/grade-config'

const DEFAULT_SLOTS: GradeSlotFormData[] = [
    { name: 'Primer Parcial', weight: 33.33, sort_order: 1, is_remedial: false },
    { name: 'Segundo Parcial', weight: 33.33, sort_order: 2, is_remedial: false },
    { name: 'Examen Final', weight: 33.34, sort_order: 3, is_remedial: false },
]

export function useGradeConfigForm(existing?: GradeConfig) {
    const form = useForm<GradeConfigFormData>({
        level:          existing?.level ?? 'university',
        period_id:      existing?.period_id ?? null,
        scale_type:     existing?.scale_type ?? 'numeric',
        scale_min:      existing ? Number(existing.scale_min) : 0,
        scale_max:      existing ? Number(existing.scale_max) : 20,
        passing_value:  existing ? Number(existing.passing_value) : 10,
        slots:          existing?.slots.map(s => ({
            name:       s.name,
            weight:     Number(s.weight),
            sort_order: s.sort_order,
            is_remedial: s.is_remedial,
        })) ?? DEFAULT_SLOTS,
        letter_values:  existing?.letter_values.map(lv => ({
            letter:        lv.letter,
            numeric_equiv: Number(lv.numeric_equiv),
            is_passing:    lv.is_passing,
            sort_order:    lv.sort_order,
        })) ?? [],
    })

    function addSlot(): void {
        const nextOrder = form.slots.length + 1
        form.slots.push({ name: '', weight: 0, sort_order: nextOrder, is_remedial: false })
    }

    function removeSlot(index: number): void {
        form.slots.splice(index, 1)
        form.slots.forEach((s, i) => { s.sort_order = i + 1 })
    }

    function addLetterValue(): void {
        const nextOrder = form.letter_values.length + 1
        form.letter_values.push({ letter: '', numeric_equiv: 0, is_passing: false, sort_order: nextOrder })
    }

    function removeLetterValue(index: number): void {
        form.letter_values.splice(index, 1)
    }

    const nonRemedialWeightTotal = (): number =>
        form.slots.filter(s => !s.is_remedial).reduce((sum, s) => sum + Number(s.weight), 0)

    function submit(id?: number): void {
        if (id) {
            form.patch(update.url(id), { preserveScroll: true })
        } else {
            form.post(store.url(), { preserveScroll: true })
        }
    }

    return { form, addSlot, removeSlot, addLetterValue, removeLetterValue, nonRemedialWeightTotal, submit }
}
