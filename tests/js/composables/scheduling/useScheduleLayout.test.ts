import { describe, it, expect } from 'vitest'
import {
    toMinutes,
    minutesToPx,
    scheduleTop,
    scheduleHeight,
    formatDuration,
    formatMinutes,
    HOUR_START,
    PX_PER_HOUR,
} from '@/composables/scheduling/useScheduleLayout'
import type { Schedule } from '@/types/scheduling'

function makeSchedule(startTime: string, endTime: string): Schedule {
    return {
        id: 1,
        dayOfWeek: 'monday',
        dayLabel: 'Lunes',
        startTime,
        endTime,
        type: 'theory',
        typeLabel: 'Teórica',
        validFrom: '2026-01-01',
        validUntil: null,
        section: { id: 1, code: 'A', type: 'university' },
        subject: { id: 1, code: 'MAT101', name: 'Cálculo I' },
        professor: { id: 1, user: { name: 'Prof. García' } },
        classroom: { id: 1, identifier: 'A-101' },
        career: null,
    }
}

describe('toMinutes', () => {
    it('converts HH:MM time string to total minutes', () => {
        expect(toMinutes('07:00')).toBe(420)
        expect(toMinutes('08:30')).toBe(510)
        expect(toMinutes('13:00')).toBe(780)
    })

    it('handles midnight (00:00)', () => {
        expect(toMinutes('00:00')).toBe(0)
    })

    it('handles end of day (23:59)', () => {
        expect(toMinutes('23:59')).toBe(1439)
    })
})

describe('minutesToPx', () => {
    it('converts minutes offset from HOUR_START to pixels', () => {
        const startMinutes = HOUR_START * 60
        expect(minutesToPx(startMinutes)).toBe(0)
        expect(minutesToPx(startMinutes + 60)).toBe(PX_PER_HOUR)
        expect(minutesToPx(startMinutes + 30)).toBe(PX_PER_HOUR / 2)
    })

    it('returns negative pixels for minutes before HOUR_START', () => {
        expect(minutesToPx(0)).toBeLessThan(0)
    })
})

describe('scheduleTop', () => {
    it('returns 0 for a schedule starting exactly at HOUR_START', () => {
        const s = makeSchedule(`0${HOUR_START}:00`, `0${HOUR_START + 1}:00`)
        expect(scheduleTop(s)).toBe(0)
    })

    it('returns positive px for a schedule starting after HOUR_START', () => {
        const s = makeSchedule('08:00', '09:00')
        expect(scheduleTop(s)).toBeGreaterThan(0)
    })
})

describe('scheduleHeight', () => {
    it('returns PX_PER_HOUR for a 1-hour schedule', () => {
        const s = makeSchedule('08:00', '09:00')
        expect(scheduleHeight(s)).toBe(PX_PER_HOUR)
    })

    it('returns double PX_PER_HOUR for a 2-hour schedule', () => {
        const s = makeSchedule('08:00', '10:00')
        expect(scheduleHeight(s)).toBe(PX_PER_HOUR * 2)
    })

    it('returns half PX_PER_HOUR for a 30-minute schedule', () => {
        const s = makeSchedule('08:00', '08:30')
        expect(scheduleHeight(s)).toBe(PX_PER_HOUR / 2)
    })
})

describe('formatDuration', () => {
    it('formats a 1-hour schedule as "1h"', () => {
        const s = makeSchedule('08:00', '09:00')
        expect(formatDuration(s)).toBe('1h')
    })

    it('formats a 90-minute schedule as "1h 30m"', () => {
        const s = makeSchedule('08:00', '09:30')
        expect(formatDuration(s)).toBe('1h 30m')
    })

    it('formats a 2-hour schedule as "2h"', () => {
        const s = makeSchedule('08:00', '10:00')
        expect(formatDuration(s)).toBe('2h')
    })

    it('formats a 45-minute schedule as "0h 45m"', () => {
        const s = makeSchedule('08:00', '08:45')
        expect(formatDuration(s)).toBe('0h 45m')
    })
})

describe('formatMinutes', () => {
    it('formats minutes-since-midnight to HH:MM with zero-padding', () => {
        expect(formatMinutes(0)).toBe('00:00')
        expect(formatMinutes(60)).toBe('01:00')
        expect(formatMinutes(90)).toBe('01:30')
        expect(formatMinutes(780)).toBe('13:00')
    })

    it('pads single-digit hours and minutes', () => {
        expect(formatMinutes(7 * 60 + 5)).toBe('07:05')
    })
})
