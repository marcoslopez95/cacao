import { useForm } from '@inertiajs/vue3'
import {
    store,
    update,
    destroy,
    deactivate,
    resetPassword,
} from '@/routes/security/users'

type UserFormData = {
    name: string
    email: string
    role: string
    password_mode: 'link' | 'manual' | 'random'
    password: string
    password_confirmation: string
}

export function useUserForm(initial?: Partial<UserFormData>) {
    const form = useForm<UserFormData>({
        name:                  initial?.name ?? '',
        email:                 initial?.email ?? '',
        role:                  initial?.role ?? '',
        password_mode:         initial?.password_mode ?? 'link',
        password:              initial?.password ?? '',
        password_confirmation: initial?.password_confirmation ?? '',
    })

    function create(): void {
        form.post(store.url(), { preserveScroll: true })
    }

    function updateUser(id: number): void {
        form.patch(update.url(id), { preserveScroll: true })
    }

    function remove(id: number): void {
        form.delete(destroy.url(id))
    }

    function toggleActive(id: number): void {
        form.patch(deactivate.url(id), { preserveScroll: true })
    }

    function resetUserPassword(id: number): void {
        form.post(resetPassword.url(id), { preserveScroll: true })
    }

    return { form, create, updateUser, remove, toggleActive, resetUserPassword }
}
