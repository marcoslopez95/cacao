import type { Schedule } from '@/types/scheduling'

export const HOUR_START = 7
export const HOUR_END = 20
export const PX_PER_HOUR = 56
export const GRID_HEIGHT = (HOUR_END - HOUR_START) * PX_PER_HOUR

export const DAY_KEYS = [
    'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday',
] as const
export type DayKey = (typeof DAY_KEYS)[number]

export const DAY_ABBRS = ['LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB']
export const DAY_LABELS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']

export function toMinutes(time: string): number {
    const [h, m] = time.split(':').map(Number)
    return h * 60 + m
}

export function minutesToPx(minutes: number): number {
    return ((minutes - HOUR_START * 60) / 60) * PX_PER_HOUR
}

export function scheduleTop(s: Schedule): number {
    return minutesToPx(toMinutes(s.startTime))
}

export function scheduleHeight(s: Schedule): number {
    return ((toMinutes(s.endTime) - toMinutes(s.startTime)) / 60) * PX_PER_HOUR
}

export function formatDuration(s: Schedule): string {
    const m = toMinutes(s.endTime) - toMinutes(s.startTime)
    const h = Math.floor(m / 60)
    const mm = m % 60
    return mm ? `${h}h ${mm}m` : `${h}h`
}

export function formatMinutes(m: number): string {
    return `${String(Math.floor(m / 60)).padStart(2, '0')}:${String(m % 60).padStart(2, '0')}`
}

// Career color palette — assigned round-robin by career ID
const CAREER_COLORS = [
    '#C8521A', '#7C5A3A', '#2E7D5C', '#5B5A8A',
    '#A36B2D', '#3D6B8A', '#7A3578', '#2D7A6B',
]
const colorCache = new Map<number, string>()

export function careerColor(careerId: number): string {
    if (!colorCache.has(careerId)) {
        colorCache.set(careerId, CAREER_COLORS[colorCache.size % CAREER_COLORS.length])
    }
    return colorCache.get(careerId)!
}

export function scheduleColor(s: Schedule): string {
    return s.career ? careerColor(s.career.id) : '#888780'
}

// Lane-packing layout (mirrors Google Calendar overlap behaviour)
const MAX_LANES = 3

export type LayoutEvent = {
    kind: 'event'
    schedule: Schedule
    lane: number
    lanes: number
}

export type LayoutOverflow = {
    kind: 'overflow'
    schedules: Schedule[]
    lane: number
    lanes: number
    startMin: number
    endMin: number
}

export type LayoutItem = LayoutEvent | LayoutOverflow

export function layoutDaySchedules(daySchedules: Schedule[]): LayoutItem[] {
    const sorted = [...daySchedules].sort(
        (a, b) =>
            toMinutes(a.startTime) - toMinutes(b.startTime) ||
            toMinutes(b.endTime) - toMinutes(a.endTime),
    )

    const out: LayoutItem[] = []
    let cluster: Schedule[] = []
    let clusterEnd = -Infinity

    const flush = (): void => {
        if (!cluster.length) return
        const laneEnds: number[] = []
        const laneMap = new Map<Schedule, number>()
        cluster.forEach((s) => {
            let li = laneEnds.findIndex((end) => end <= toMinutes(s.startTime))
            if (li === -1) {
                li = laneEnds.length
                laneEnds.push(0)
            }
            laneEnds[li] = toMinutes(s.endTime)
            laneMap.set(s, li)
        })

        const total = laneEnds.length
        if (total <= MAX_LANES) {
            cluster.forEach((s) =>
                out.push({ kind: 'event', schedule: s, lane: laneMap.get(s)!, lanes: total }),
            )
        } else {
            const visible = cluster.filter((s) => laneMap.get(s)! < MAX_LANES - 1)
            const hidden = cluster.filter((s) => laneMap.get(s)! >= MAX_LANES - 1)
            visible.forEach((s) =>
                out.push({ kind: 'event', schedule: s, lane: laneMap.get(s)!, lanes: MAX_LANES }),
            )
            const startMin = Math.min(...hidden.map((s) => toMinutes(s.startTime)))
            const endMin = Math.max(...hidden.map((s) => toMinutes(s.endTime)))
            out.push({
                kind: 'overflow',
                schedules: [...hidden].sort(
                    (a, b) => toMinutes(a.startTime) - toMinutes(b.startTime),
                ),
                lane: MAX_LANES - 1,
                lanes: MAX_LANES,
                startMin,
                endMin,
            })
        }
        cluster = []
    }

    sorted.forEach((s) => {
        if (toMinutes(s.startTime) >= clusterEnd) flush()
        cluster.push(s)
        clusterEnd = Math.max(clusterEnd, toMinutes(s.endTime))
    })
    flush()
    return out
}

// Conflict detection — professor double-booked or room double-booked
export type ConflictType = 'professor' | 'room'

export interface Conflict {
    scheduleId: number
    type: ConflictType
    reason: string
}

export function detectConflicts(schedules: Schedule[]): Map<number, Conflict> {
    const map = new Map<number, Conflict>()
    for (let i = 0; i < schedules.length; i++) {
        for (let j = i + 1; j < schedules.length; j++) {
            const a = schedules[i]
            const b = schedules[j]
            if (a.dayOfWeek !== b.dayOfWeek) continue
            const overlap =
                toMinutes(a.startTime) < toMinutes(b.endTime) &&
                toMinutes(b.startTime) < toMinutes(a.endTime)
            if (!overlap) continue
            if (a.professor.id === b.professor.id) {
                const reason = `${a.professor.user.name} tiene otra clase a la misma hora`
                map.set(a.id, { scheduleId: a.id, type: 'professor', reason })
                map.set(b.id, { scheduleId: b.id, type: 'professor', reason })
            }
            if (a.classroom.id === b.classroom.id) {
                const reason = `El aula ${a.classroom.identifier} ya está ocupada`
                map.set(a.id, { scheduleId: a.id, type: 'room', reason })
                map.set(b.id, { scheduleId: b.id, type: 'room', reason })
            }
        }
    }
    return map
}

// Current day of week key (null on Sundays since we don't show Sunday)
export function todayKey(): DayKey | null {
    const d = new Date().getDay() // 0=Sun, 1=Mon … 6=Sat
    return d >= 1 && d <= 6 ? DAY_KEYS[d - 1] : null
}

// Current time in minutes since midnight
export function nowMinutes(): number {
    const n = new Date()
    return n.getHours() * 60 + n.getMinutes()
}

// Current week dates (Mon–Sat) as day-of-month strings
export function currentWeekDates(): string[] {
    const now = new Date()
    const dow = now.getDay()
    const diff = dow === 0 ? -6 : 1 - dow
    const monday = new Date(now)
    monday.setDate(now.getDate() + diff)
    return Array.from({ length: 6 }, (_, i) => {
        const d = new Date(monday)
        d.setDate(monday.getDate() + i)
        return String(d.getDate())
    })
}
