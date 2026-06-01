<script setup lang="ts">
import { computed } from 'vue'
import type { AttendanceSectionContext } from '@/types/attendance'
import AppIcon from '@/components/UI/AppIcon.vue'

const props = defineProps<{
    section: AttendanceSectionContext
}>()

/** Parse hex color into r,g,b channels for opacity variants */
function hexToRgb(hex: string): string {
    const h = hex.replace('#', '')
    const r = parseInt(h.substring(0, 2), 16)
    const g = parseInt(h.substring(2, 4), 16)
    const b = parseInt(h.substring(4, 6), 16)
    return `${r}, ${g}, ${b}`
}

const rgb = computed(() => {
    try {
        return hexToRgb(props.section.careerColor)
    } catch {
        return '99, 102, 241' // fallback indigo
    }
})

const badgeStyle = computed(() => ({
    backgroundColor: `rgba(${rgb.value}, 0.12)`,
    borderColor: `rgba(${rgb.value}, 0.25)`,
    color: props.section.careerColor,
}))

const avatarStyle = computed(() => ({
    backgroundColor: `rgba(${rgb.value}, 0.15)`,
    color: props.section.careerColor,
}))

const accentBarStyle = computed(() => ({
    backgroundColor: props.section.careerColor,
}))
</script>

<template>
    <div
        class="relative flex flex-wrap items-center gap-4 border pl-5 pr-4 py-4 mb-5"
        style="
            background-color: var(--bg-surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xs);
        "
    >
        <!-- Accent bar left -->
        <div
            class="absolute left-0 top-0 bottom-0 rounded-l-[10px]"
            :style="[accentBarStyle, { width: '4px' }]"
        />

        <!-- Cohort badge -->
        <div
            class="flex items-center justify-center border rounded-lg shrink-0 font-bold"
            :style="[badgeStyle, { width: '52px', height: '52px', fontSize: '15px' }]"
        >
            {{ section.cohort }}
        </div>

        <!-- Subject info -->
        <div class="flex-1 min-w-0">
            <div
                class="font-semibold truncate"
                style="font-size: 17px; color: var(--text-primary);"
            >
                {{ section.subject }}
            </div>
            <div
                class="font-mono truncate mb-1"
                style="font-size: 11px; color: var(--text-muted);"
            >
                {{ section.code }}
            </div>
            <div
                class="flex flex-wrap items-center gap-x-3 gap-y-0.5"
                style="font-size: 12px; color: var(--text-secondary);"
            >
                <span class="flex items-center gap-1">
                    <AppIcon name="graduation-cap" :size="12" />
                    {{ section.career }}
                </span>
                <span class="flex items-center gap-1">
                    <AppIcon name="clock" :size="12" />
                    {{ section.scheduleDisplay }}
                </span>
                <span class="flex items-center gap-1">
                    <AppIcon name="building" :size="12" />
                    {{ section.room }}
                </span>
                <span class="flex items-center gap-1">
                    <AppIcon name="users" :size="12" />
                    {{ section.rosterCount }} estudiantes
                </span>
            </div>
        </div>

        <!-- Teacher block -->
        <div class="flex items-center gap-2.5 shrink-0">
            <div
                class="flex items-center justify-center rounded-full shrink-0 font-semibold"
                :style="[avatarStyle, { width: '34px', height: '34px', fontSize: '12px' }]"
            >
                {{ section.teacherInitials }}
            </div>
            <div>
                <div
                    class="font-medium leading-tight"
                    style="font-size: 13px; color: var(--text-primary);"
                >
                    {{ section.teacherName }}
                </div>
                <div style="font-size: 11px; color: var(--text-muted);">
                    Profesor
                </div>
            </div>
        </div>
    </div>
</template>
