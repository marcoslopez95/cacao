import { describe, it, expect } from 'vitest'
import { enrollmentColor } from '@/utils/enrollmentColor'

const PALETTE = [
    '#C8521A', '#2E7D5C', '#7C5A3A', '#5B5A8A',
    '#A36B2D', '#1F5F8B', '#B12A1F', '#4C7A1F',
    '#6C4A7A', '#3C7A8B', '#A8511A', '#5B7A3A',
]

describe('enrollmentColor', () => {
    it('returns a string from the palette for a non-empty subject code', () => {
        const color = enrollmentColor('MAT101')
        expect(PALETTE).toContain(color)
    })

    it('returns the same color for the same subject code (deterministic)', () => {
        expect(enrollmentColor('FIS101')).toBe(enrollmentColor('FIS101'))
    })

    it('different codes can produce different colors', () => {
        // Find at least one pair of codes that differ in color
        const colors = new Set(
            ['MAT101', 'FIS101', 'QUI101', 'BIO101', 'HIS101', 'GEO101',
             'MAT201', 'FIS201', 'ENG101', 'ESP101', 'SOC101', 'ECO101'].map(enrollmentColor),
        )
        expect(colors.size).toBeGreaterThan(1)
    })

    it('returns a palette color for a single-character code', () => {
        const color = enrollmentColor('A')
        expect(PALETTE).toContain(color)
    })

    it('returns a palette color for an empty string (edge case)', () => {
        const color = enrollmentColor('')
        expect(PALETTE).toContain(color)
    })

    it('always returns a hex color string starting with #', () => {
        const codes = ['MAT101', 'FIS201', 'QUI301', 'EST001']

        for (const code of codes) {
            expect(enrollmentColor(code)).toMatch(/^#[0-9A-Fa-f]{6}$/)
        }
    })
})
