const PALETTE = [
    '#C8521A', '#2E7D5C', '#7C5A3A', '#5B5A8A',
    '#A36B2D', '#1F5F8B', '#B12A1F', '#4C7A1F',
    '#6C4A7A', '#3C7A8B', '#A8511A', '#5B7A3A',
]

export function enrollmentColor(subjectCode: string): string {
    let hash = 0
    for (let i = 0; i < subjectCode.length; i++) {
        hash = ((hash * 31) + subjectCode.charCodeAt(i)) >>> 0
    }
    return PALETTE[hash % PALETTE.length]
}
