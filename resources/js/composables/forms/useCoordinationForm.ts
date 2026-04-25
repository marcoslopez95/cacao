import { useForm } from '@inertiajs/vue3'
import { store, update, destroy } from '@/routes/security/coordinations'

type CoordinationFormData = {
    name: string
    type: 'career' | 'grade' | 'academic' | ''
    education_level: 'university' | 'secondary' | ''
    secondary_type: 'media_general' | 'bachillerato' | ''
    career_id: number | ''
    grade_year: number | ''
    active: boolean
}

export function useCoordinationForm(initial?: Partial<CoordinationFormData>) {
    const form = useForm<CoordinationFormData>({
        name:            initial?.name ?? '',
        type:            initial?.type ?? '',
        education_level: initial?.education_level ?? '',
        secondary_type:  initial?.secondary_type ?? '',
        career_id:       initial?.career_id ?? '',
        grade_year:      initial?.grade_year ?? '',
        active:          initial?.active ?? true,
    })

    function create(): void {
        form.post(store.url(), { preserveScroll: true })
    }

    function updateCoordination(id: number): void {
        form.patch(update.url(id), { preserveScroll: true })
    }

    function remove(id: number): void {
        form.delete(destroy.url(id))
    }

    return { form, create, updateCoordination, remove }
}
