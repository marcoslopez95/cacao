<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import ScheduleListView from '@/components/scheduling/ScheduleListView.vue'
import WeeklyGrid from '@/components/scheduling/WeeklyGrid.vue'
import type { Conflict } from '@/composables/scheduling/useScheduleLayout'
import type { ScheduleCollection } from '@/types/scheduling'

type Props = {
    schedules: ScheduleCollection
}

defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Estudiante', href: '#' },
            { title: 'Mi horario' },
        ],
    },
})

const conflicts = new Map<number, Conflict>()
</script>

<template>
    <Head title="Mi horario" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0;">
            Mi horario
        </h1>

        <div class="hidden lg:block card p-0 overflow-hidden">
            <WeeklyGrid
                :schedules="schedules"
                :conflicts="conflicts"
                :can-update="false"
                :can-delete="false"
            />
        </div>

        <div class="lg:hidden card p-0 overflow-hidden">
            <ScheduleListView
                :schedules="schedules"
                :conflicts="conflicts"
                :can-update="false"
                :can-delete="false"
            />
        </div>
    </div>
</template>
