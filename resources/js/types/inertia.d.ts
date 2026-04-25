import type { UserRow } from './security'

declare module '@inertiajs/vue3' {
    interface PageProps {
        auth: {
            user: UserRow
            permissions: string[]
        }
        flash: {
            toast?: {
                type: 'success' | 'error' | 'password' | 'info'
                message: string
                password?: string
            }
        }
    }
}
