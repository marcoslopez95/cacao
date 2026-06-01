<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import ScheduleEventCard from '@/components/scheduling/ScheduleEventCard.vue'
import ScheduleOverflowTile from '@/components/scheduling/ScheduleOverflowTile.vue'
import {
    DAY_KEYS, DAY_ABBRS,
    HOUR_START, HOUR_END, PX_PER_HOUR, GRID_HEIGHT,
    layoutDaySchedules, minutesToPx,
    todayKey, nowMinutes, currentWeekDates, formatMinutes,
} from '@/composables/scheduling/useScheduleLayout'
import type { Schedule, ScheduleCollection } from '@/types/scheduling'
import type { Conflict } from '@/composables/scheduling/useScheduleLayout'

const props = defineProps<{
    schedules: ScheduleCollection
    conflicts: Map<number, Conflict>
    canUpdate?: boolean
    canDelete?: boolean
    mobileDay?: number
    dayMode?: boolean
}>()

const emit = defineEmits<{
    create:        [{ dayOfWeek: string; startTime: string }]
    openEvent:     [schedule: Schedule]
    openCluster:   [{ dayIndex: number; startMin: number; endMin: number; schedules: Schedule[] }]
    editSchedule:  [schedule: Schedule]
    deleteSchedule:[schedule: Schedule]
    'update:mobileDay': [value: number]
}>()

const today    = todayKey()
const weekDates = currentWeekDates()
const hours    = Array.from({ length: HOUR_END - HOUR_START + 1 }, (_, i) => HOUR_START + i)
const nowMin   = ref(nowMinutes())

let ticker: ReturnType<typeof setInterval>
onMounted(()    => { ticker = setInterval(() => { nowMin.value = nowMinutes() }, 60_000) })
onBeforeUnmount(() => clearInterval(ticker))

const visibleDays = computed(() =>
    props.dayMode ? [props.mobileDay ?? 0] : [0, 1, 2, 3, 4, 5],
)
const nowTop     = computed(() => minutesToPx(nowMin.value))
const nowVisible = computed(() => nowMin.value >= HOUR_START * 60 && nowMin.value <= HOUR_END * 60)

function schedulesForDay(i: number): Schedule[] {
    return props.schedules.filter((s) => s.dayOfWeek === DAY_KEYS[i])
}

function handleDayClick(dayIndex: number, e: MouseEvent): void {
    if (!props.canUpdate) return
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
    const clickY = e.clientY - rect.top
    const totalMin = Math.floor((clickY / PX_PER_HOUR) * 60) + HOUR_START * 60
    const h  = Math.min(Math.max(Math.floor(totalMin / 60), HOUR_START), HOUR_END - 1)
    const m  = Math.floor((totalMin % 60) / 15) * 15
    emit('create', {
        dayOfWeek: DAY_KEYS[dayIndex],
        startTime: `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`,
    })
}
</script>

<template>
    <div class="sch-grid-wrap">
        <!-- Mobile day navigation (hidden on desktop) -->
        <div class="sch-mobile-nav">
            <button
                :disabled="(mobileDay ?? 0) === 0"
                aria-label="Día anterior"
                @click="emit('update:mobileDay', Math.max(0, (mobileDay ?? 0) - 1))"
            >‹</button>
            <div class="sch-mobile-dots">
                <button
                    v-for="(abbr, i) in DAY_ABBRS"
                    :key="i"
                    :class="{ active: i === (mobileDay ?? 0), 'has-events': schedulesForDay(i).length > 0 }"
                    @click="emit('update:mobileDay', i)"
                >{{ abbr }}</button>
            </div>
            <button
                :disabled="(mobileDay ?? 0) === 5"
                aria-label="Día siguiente"
                @click="emit('update:mobileDay', Math.min(5, (mobileDay ?? 0) + 1))"
            >›</button>
        </div>

        <!-- Calendar grid -->
        <div
            dusk="weekly-grid"
            class="sch-grid"
            :style="dayMode ? { gridTemplateColumns: '64px 1fr' } : undefined"
        >
            <!-- Sticky header: gutter corner -->
            <div class="sch-gutter-head" />

            <!-- Sticky header: day columns -->
            <div
                v-for="i in visibleDays"
                :key="'h' + i"
                class="sch-day-head"
                :class="{
                    today: DAY_KEYS[i] === today,
                    'active-mobile': i === (mobileDay ?? 0),
                }"
            >
                <span class="dow">{{ DAY_ABBRS[i] }}</span>
                <span class="dnum">{{ weekDates[i] }}</span>
                <span class="meta">
                    {{ schedulesForDay(i).length }}
                    clase{{ schedulesForDay(i).length !== 1 ? 's' : '' }}
                </span>
            </div>

            <!-- Time gutter -->
            <div class="sch-gutter" :style="{ height: GRID_HEIGHT + 'px' }">
                <div
                    v-for="(h, hi) in hours"
                    :key="h"
                    class="sch-gutter-tick"
                    :style="{ top: hi * PX_PER_HOUR - 7 + 'px' }"
                >
                    {{ String(h).padStart(2, '0') }}:00
                </div>
            </div>

            <!-- Day columns -->
            <div
                v-for="i in visibleDays"
                :key="'c' + i"
                :dusk="`grid-col-${DAY_KEYS[i]}`"
                class="sch-day-col"
                :class="{
                    today:   DAY_KEYS[i] === today,
                    weekend: i === 5,
                    'active-mobile': i === (mobileDay ?? 0),
                }"
                :style="{ height: GRID_HEIGHT + 'px', cursor: canUpdate ? 'pointer' : 'default' }"
                @click.self="(e) => handleDayClick(i, e)"
            >
                <!-- Hour grid lines -->
                <template v-for="(_, hi) in hours" :key="'l' + hi">
                    <div class="sch-hline" :style="{ top: hi * PX_PER_HOUR + 'px' }" />
                    <div
                        v-if="hi < hours.length - 1"
                        class="sch-hline half"
                        :style="{ top: hi * PX_PER_HOUR + PX_PER_HOUR / 2 + 'px' }"
                    />
                </template>

                <!-- Now indicator -->
                <div
                    v-if="DAY_KEYS[i] === today && nowVisible"
                    class="sch-now"
                    :style="{ top: nowTop + 'px' }"
                >
                    <span class="sch-now-label">{{ formatMinutes(nowMin) }}</span>
                    <div class="sch-now-line" />
                </div>

                <!-- Events and overflow tiles -->
                <template
                    v-for="item in layoutDaySchedules(schedulesForDay(i))"
                    :key="item.kind === 'event' ? item.schedule.id : `ov-${i}-${item.startMin}`"
                >
                    <ScheduleEventCard
                        v-if="item.kind === 'event'"
                        :schedule="item.schedule"
                        :lane="item.lane"
                        :lanes="item.lanes"
                        :conflict="conflicts.get(item.schedule.id)"
                        :can-update="canUpdate"
                        :can-delete="canDelete"
                        @open="emit('openEvent', item.schedule)"
                        @edit="emit('editSchedule', item.schedule)"
                        @delete="emit('deleteSchedule', item.schedule)"
                    />
                    <ScheduleOverflowTile
                        v-else
                        :schedules="item.schedules"
                        :lane="item.lane"
                        :lanes="item.lanes"
                        :start-min="item.startMin"
                        :end-min="item.endMin"
                        :has-conflict="item.schedules.some((s) => conflicts.has(s.id))"
                        @open="emit('openCluster', { dayIndex: i, startMin: item.startMin, endMin: item.endMin, schedules: item.schedules })"
                    />
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.sch-grid-wrap {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    overflow: hidden;
}

/* Mobile nav (hidden by default, shown on ≤820px) */
.sch-mobile-nav { display: none; }

@media (max-width: 820px) {
    .sch-mobile-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: var(--bg-surface-2);
        border-bottom: 1px solid var(--border);
        gap: 8px;
    }
    .sch-mobile-nav > button {
        width: 32px; height: 32px;
        background: var(--bg-surface);
        border: 1px solid var(--border-strong);
        border-radius: var(--r-sm);
        color: var(--text-primary);
        cursor: pointer;
        display: grid; place-items: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .sch-mobile-nav > button:disabled { opacity: 0.4; cursor: default; }
}

.sch-mobile-dots {
    display: flex;
    gap: 4px;
    flex: 1;
    justify-content: center;
}
.sch-mobile-dots button {
    width: 38px; height: 38px;
    border-radius: 50%;
    border: 0;
    background: transparent;
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    position: relative;
}
.sch-mobile-dots button.active { background: var(--accent); color: #fff; }
.sch-mobile-dots button.has-events::after {
    content: '';
    display: block;
    width: 4px; height: 4px;
    background: var(--text-muted);
    border-radius: 50%;
    position: absolute;
    bottom: 4px;
    left: 50%;
    transform: translateX(-50%);
}
.sch-mobile-dots button.active.has-events::after { background: rgba(255,255,255,0.7); }

/* Grid layout */
.sch-grid {
    display: grid;
    grid-template-columns: 64px repeat(6, 1fr);
    overflow-x: auto;
    min-width: 640px;
}

@media (max-width: 820px) {
    .sch-grid { grid-template-columns: 48px 1fr; min-width: 0; }
    .sch-day-head:not(.active-mobile) { display: none; }
    .sch-day-col:not(.active-mobile)  { display: none; }
}

/* Sticky header */
.sch-gutter-head {
    position: sticky; top: 0; z-index: 5;
    background: var(--bg-surface-2);
    border-bottom: 1px solid var(--border);
    border-right:  1px solid var(--border);
}
.sch-day-head {
    position: sticky; top: 0; z-index: 5;
    background: var(--bg-surface-2);
    border-bottom: 1px solid var(--border);
    border-left:   1px solid var(--border);
    padding: 12px 12px 10px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.sch-day-head .dow  { font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); font-weight: 600; }
.sch-day-head .dnum { font-size: 18px; font-weight: 600; color: var(--text-primary); font-variant-numeric: tabular-nums; }
.sch-day-head .meta { font-size: 11px; color: var(--text-muted); }
.sch-day-head.today .dnum { color: var(--accent); }

/* Gutter */
.sch-gutter {
    border-right: 1px solid var(--border);
    position: relative;
    background: var(--bg-surface);
}
.sch-gutter-tick {
    position: absolute;
    left: 0; right: 0;
    display: flex;
    align-items: flex-start;
    justify-content: flex-end;
    padding: 2px 8px 0 0;
    font-size: 11px;
    color: var(--text-muted);
    font-variant-numeric: tabular-nums;
    user-select: none;
}

/* Day columns */
.sch-day-col {
    position: relative;
    border-left: 1px solid var(--border);
    background: var(--bg-surface);
    container-type: inline-size;
}
.sch-day-col.today   { background: color-mix(in srgb, var(--accent) 4%, var(--bg-surface)); }
.sch-day-col.weekend { background: var(--bg-surface-2); }

/* Grid lines */
.sch-hline      { position: absolute; left: 0; right: 0; border-top: 1px solid var(--border); pointer-events: none; }
.sch-hline.half { border-top-style: dashed; border-top-color: color-mix(in srgb, var(--border) 60%, transparent); }

/* Now indicator */
.sch-now { position: absolute; left: 0; right: 0; z-index: 4; pointer-events: none; }
.sch-now::before {
    content: '';
    position: absolute;
    left: -4px; top: -4px;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--accent);
}
.sch-now-line { height: 2px; background: var(--accent); border-radius: 1px; }
.sch-now-label {
    position: absolute;
    left: -56px; top: -10px;
    width: 48px;
    text-align: right;
    font-size: 10.5px;
    font-weight: 700;
    color: var(--accent);
    font-variant-numeric: tabular-nums;
    background: var(--bg-surface);
    padding: 2px 4px;
    border-radius: 3px;
}
</style>
