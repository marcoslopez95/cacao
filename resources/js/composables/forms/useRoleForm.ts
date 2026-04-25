import { useForm } from '@inertiajs/vue3'
import { store, update, destroy } from '@/routes/security/roles'

type RoleFormData = {
    name: string
    permissions: string[]
}

export function useRoleForm(initial?: Partial<RoleFormData>) {
    const form = useForm<RoleFormData>({
        name:        initial?.name ?? '',
        permissions: initial?.permissions ?? [],
    })

    function create(): void {
        form.post(store.url(), { preserveScroll: true })
    }

    function updateRole(id: number): void {
        form.patch(update.url(id), { preserveScroll: true })
    }

    function remove(id: number): void {
        form.delete(destroy.url(id))
    }

    return { form, create, updateRole, remove }
}
