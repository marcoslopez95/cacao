import { describe, it, expect } from 'vitest'
import { groupPermissions, permissionGroupLabel } from '@/utils/permissions'

describe('groupPermissions', () => {
    it('groups permissions by dot-prefix into named buckets', () => {
        const input = ['users.create', 'users.delete', 'roles.edit']
        const result = groupPermissions(input)
        expect(result).toEqual({
            users: ['users.create', 'users.delete'],
            roles: ['roles.edit'],
        })
    })

    it('returns an empty object for an empty array', () => {
        expect(groupPermissions([])).toEqual({})
    })

    it('creates a single group when all permissions share the same prefix', () => {
        const input = ['admin.view', 'admin.export']
        const result = groupPermissions(input)
        expect(Object.keys(result)).toHaveLength(1)
        expect(result['admin']).toEqual(['admin.view', 'admin.export'])
    })

    it('uses only the first segment as the group key when permission has multiple dots', () => {
        const input = ['users.profile.edit', 'users.profile.view']
        const result = groupPermissions(input)
        expect(result['users']).toEqual(['users.profile.edit', 'users.profile.view'])
    })

    it('treats permissions without a dot as their own group key', () => {
        const input = ['dashboard']
        const result = groupPermissions(input)
        expect(result['dashboard']).toEqual(['dashboard'])
    })
})

describe('permissionGroupLabel', () => {
    it('capitalizes the first letter of the group name', () => {
        expect(permissionGroupLabel('users')).toBe('Users')
    })

    it('preserves already-uppercase first letter', () => {
        expect(permissionGroupLabel('Roles')).toBe('Roles')
    })

    it('returns a single character capitalized for a one-character group', () => {
        expect(permissionGroupLabel('a')).toBe('A')
    })

    it('returns an empty string for an empty input', () => {
        expect(permissionGroupLabel('')).toBe('')
    })

    it('does not mutate the rest of the string', () => {
        expect(permissionGroupLabel('admin_panel')).toBe('Admin_panel')
    })
})
