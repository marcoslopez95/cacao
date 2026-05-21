import { describe, it, expect } from 'vitest'
import { getInitials, useInitials } from '@/composables/useInitials'

describe('getInitials', () => {
    it('returns empty string for undefined input', () => {
        expect(getInitials(undefined)).toBe('')
    })

    it('returns empty string for empty string input', () => {
        expect(getInitials('')).toBe('')
    })

    it('returns single initial for a single-word name', () => {
        expect(getInitials('Marcos')).toBe('M')
    })

    it('returns first and last initials for a two-word name', () => {
        expect(getInitials('Marcos López')).toBe('ML')
    })

    it('returns first and last initials for a name with more than two words', () => {
        expect(getInitials('María de los Santos')).toBe('MS')
    })

    it('returns uppercase initials regardless of input case', () => {
        expect(getInitials('ana pérez')).toBe('AP')
    })

    it('trims leading/trailing spaces before splitting', () => {
        expect(getInitials('  Juan García  ')).toBe('JG')
    })
})

describe('useInitials', () => {
    it('exposes getInitials function', () => {
        const { getInitials: fn } = useInitials()
        expect(typeof fn).toBe('function')
    })

    it('getInitials via composable returns same result as standalone function', () => {
        const { getInitials: fn } = useInitials()
        expect(fn('Pedro Ramírez')).toBe(getInitials('Pedro Ramírez'))
    })
})
