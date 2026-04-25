import { computed, ref } from 'vue'
import type { Role } from '@/types/security'

export function useRoleFilters(roles: () => Role[]) {
    const search = ref('')

    const filteredRoles = computed(() => {
        const q = search.value.trim().toLowerCase()
        if (!q) { return roles() }
        return roles().filter(r => r.name.toLowerCase().includes(q))
    })

    return { search, filteredRoles }
}
