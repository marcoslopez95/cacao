<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { ProfessorDashboardProps, TodaySchedule } from '@/types/professor-dashboard'

const page = usePage()
const userName = computed(() => {
    const name: string = (page.props.auth as { user?: { name?: string } })?.user?.name ?? ''

    return name ? name.split(' ')[0] : ''
})

const props = defineProps<ProfessorDashboardProps>()

function formatTime(time: string): string {
    // time is "HH:MM:SS" or "HH:MM"
    const parts = time.split(':')
    const h = parseInt(parts[0], 10)
    const m = parts[1] ?? '00'
    const ampm = h >= 12 ? 'pm' : 'am'
    const h12 = h % 12 === 0 ? 12 : h % 12

    return `${h12}:${m} ${ampm}`
}

function timeRange(item: TodaySchedule): string {
    return `${formatTime(item.start_time)} – ${formatTime(item.end_time)}`
}
</script>

<template>
    <div style="display:flex;flex-direction:column;gap:24px;">

        <!-- Header -->
        <div>
            <h1 style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                Bienvenido/a{{ userName ? ', ' + userName : '' }}
            </h1>
            <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                <template v-if="props.period">
                    {{ props.period.name }}
                </template>
                <template v-else>
                    No hay un período académico activo en este momento.
                </template>
            </p>
        </div>

        <!-- Stats row -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;">
            <!-- Secciones activas -->
            <div class="stat">
                <div class="stat-label">Secciones activas</div>
                <div style="font-size:var(--text-4xl);font-weight:700;color:var(--accent);font-variant-numeric:tabular-nums;line-height:1;margin-bottom:8px;">
                    {{ props.sections_count }}
                </div>
                <div style="font-size:var(--text-xs);color:var(--text-muted);">
                    este período
                </div>
            </div>

            <!-- Estudiantes totales -->
            <div class="stat">
                <div class="stat-label">Estudiantes totales</div>
                <div style="font-size:var(--text-4xl);font-weight:700;color:var(--accent);font-variant-numeric:tabular-nums;line-height:1;margin-bottom:8px;">
                    {{ props.total_students }}
                </div>
                <div style="font-size:var(--text-xs);color:var(--text-muted);">
                    en tus secciones
                </div>
            </div>

            <!-- Horas por semana -->
            <div class="stat">
                <div class="stat-label">Horas / semana</div>
                <div style="font-size:var(--text-4xl);font-weight:700;color:var(--accent);font-variant-numeric:tabular-nums;line-height:1;margin-bottom:8px;">
                    {{ props.hours_per_week }}
                </div>
                <div style="font-size:var(--text-xs);color:var(--text-muted);">
                    horas de clase
                </div>
            </div>
        </div>

        <!-- Today's timeline -->
        <div>
            <div style="font-size:var(--text-xs);font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:12px;">
                Hoy — {{ props.today_label }}
            </div>

            <!-- Empty state -->
            <template v-if="props.today_schedules.length === 0">
                <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:32px 24px;text-align:center;">
                    <div style="font-size:var(--text-md);font-weight:500;color:var(--text-secondary);margin-bottom:6px;">
                        Sin clases programadas hoy
                    </div>
                    <div style="font-size:var(--text-sm);color:var(--text-muted);">
                        Disfruta tu día libre o revisa el material de tus secciones.
                    </div>
                </div>
            </template>

            <!-- Schedule list -->
            <template v-else>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div
                        v-for="item in props.today_schedules"
                        :key="item.section_id"
                        :style="{
                            display: 'flex',
                            alignItems: 'stretch',
                            background: 'var(--bg-surface)',
                            border: '1px solid var(--border)',
                            borderRadius: 'var(--radius-lg)',
                            overflow: 'hidden',
                            boxShadow: 'var(--shadow-xs)',
                        }"
                    >
                        <!-- Colored left accent bar -->
                        <div
                            :style="{
                                width: '4px',
                                flexShrink: '0',
                                background: item.is_current ? 'var(--accent)' : 'var(--border-strong)',
                            }"
                        />

                        <!-- Content -->
                        <div style="flex:1;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                            <div>
                                <!-- Time range + AHORA badge -->
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                                    <span
                                        :style="{
                                            fontSize: 'var(--text-xs)',
                                            fontWeight: '600',
                                            color: item.is_current ? 'var(--accent)' : 'var(--text-muted)',
                                            fontVariantNumeric: 'tabular-nums',
                                        }"
                                    >
                                        {{ timeRange(item) }}
                                    </span>
                                    <span
                                        v-if="item.is_current"
                                        class="badge badge-accent"
                                        style="font-size:10px;height:18px;padding:0 7px;font-weight:700;letter-spacing:0.04em;"
                                    >
                                        AHORA
                                    </span>
                                </div>

                                <!-- Subject + section code -->
                                <div style="font-size:var(--text-base);font-weight:600;color:var(--text-primary);">
                                    {{ item.subject_name }}
                                    <span style="font-weight:400;color:var(--text-muted);">— {{ item.section_code }}</span>
                                </div>
                            </div>

                            <!-- Classroom + students -->
                            <div style="text-align:right;">
                                <div style="font-size:var(--text-sm);color:var(--text-secondary);font-weight:500;">
                                    {{ item.classroom_name }}
                                </div>
                                <div style="font-size:var(--text-xs);color:var(--text-muted);margin-top:2px;">
                                    {{ item.students_count }} estudiante{{ item.students_count !== 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

    </div>
</template>
