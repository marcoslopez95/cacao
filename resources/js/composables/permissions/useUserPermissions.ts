import { computed } from 'vue'
import { usePermission } from '@/composables/usePermission'
import type { UserRow } from '@/types/security'

export function useUserPermissions() {
    const { can } = usePermission()

    const canCreate        = computed(() => can('users.create'))
    const canInvite        = computed(() => can('users.invite'))
    const canResetPassword = computed(() => can('users.reset-password'))

    const canUpdate     = (_user: UserRow) => can('users.update')
    const canDelete     = (_user: UserRow) => can('users.delete')
    const canDeactivate = (_user: UserRow) => can('users.deactivate')

    return { canCreate, canInvite, canResetPassword, canUpdate, canDelete, canDeactivate }
}
