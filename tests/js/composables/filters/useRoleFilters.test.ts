import { ref } from 'vue'
import { describe, it, expect } from 'vitest'
import type { Role } from '@/types/security'
import { useRoleFilters } from '@/composables/filters/useRoleFilters'

function makeRole(overrides: Partial<Role> = {}): Role {
    return {
        id: 1,
        name: 'admin',
        isAdmin: false,
        usersCount: 0,
        permissions: [],
        ...overrides,
    }
}

describe('useRoleFilters', () => {
    it('initializes search as empty string', () => {
        const roles = ref<Role[]>([makeRole()])
        const { search } = useRoleFilters(() => roles.value)
        expect(search.value).toBe('')
    })

    it('returns all roles when search is empty', () => {
        const roleList = [makeRole({ id: 1, name: 'admin' }), makeRole({ id: 2, name: 'profesor' })]
        const roles = ref<Role[]>(roleList)
        const { filteredRoles } = useRoleFilters(() => roles.value)
        expect(filteredRoles.value).toHaveLength(2)
    })

    it('filters roles by name (case-insensitive)', () => {
        const roleList = [makeRole({ id: 1, name: 'admin' }), makeRole({ id: 2, name: 'profesor' })]
        const roles = ref<Role[]>(roleList)
        const { search, filteredRoles } = useRoleFilters(() => roles.value)

        search.value = 'PROF'

        expect(filteredRoles.value).toHaveLength(1)
        expect(filteredRoles.value[0].name).toBe('profesor')
    })

    it('returns empty array when no role matches the search term', () => {
        const roleList = [makeRole({ id: 1, name: 'admin' })]
        const roles = ref<Role[]>(roleList)
        const { search, filteredRoles } = useRoleFilters(() => roles.value)

        search.value = 'xyz'

        expect(filteredRoles.value).toHaveLength(0)
    })

    it('trims whitespace before filtering', () => {
        const roleList = [makeRole({ id: 1, name: 'admin' }), makeRole({ id: 2, name: 'estudiante' })]
        const roles = ref<Role[]>(roleList)
        const { search, filteredRoles } = useRoleFilters(() => roles.value)

        search.value = '  admin  '

        expect(filteredRoles.value).toHaveLength(1)
        expect(filteredRoles.value[0].name).toBe('admin')
    })

    it('reacts to changes in the roles source array', () => {
        const roles = ref<Role[]>([makeRole({ id: 1, name: 'admin' })])
        const { filteredRoles } = useRoleFilters(() => roles.value)

        expect(filteredRoles.value).toHaveLength(1)

        roles.value = [...roles.value, makeRole({ id: 2, name: 'profesor' })]

        expect(filteredRoles.value).toHaveLength(2)
    })
})
