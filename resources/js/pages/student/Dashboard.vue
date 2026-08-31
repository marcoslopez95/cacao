<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { index as enrollmentIndex } from '@/routes/enrollment'
import type { StudentDashboardProps, StudentTodaySchedule } from '@/types/student-dashboard'

const page = usePage()
const userName = computed(() => {
    const name: string = (page.props.auth as { user?: { name?: string } })?.user?.name ?? ''

    return name ? name.split(' ')[0] : ''
})

const props = defineProps<StudentDashboardProps>()

const isUniversityStudent = computed(() => props.student.educational_level === 'university')

const pensumDisplay = computed(() => {
    if (props.uc_pensum === 0) {
        return '—'
    }

    return `${props.uc_aprobadas} / ${props.uc_pensum} UC`
})

function formatTime(time: string): string {
    // time is "HH:MM:SS" or "HH:MM"
    const parts = time.split(':')
    const h = parseInt(parts[0], 10)
    const m = parts[1] ?? '00'
    const ampm = h >= 12 ? 'pm' : 'am'
    const h12 = h % 12 === 0 ? 12 : h % 12

    return `${h12}:${m} ${ampm}`
}

function timeRange(item: StudentTodaySchedule): string {
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
            <!-- Materias inscritas -->
            <div class="stat">
                <div class="stat-label">Materias inscritas</div>
                <div style="font-size:var(--text-4xl);font-weight:700;color:var(--accent);font-variant-numeric:tabular-nums;line-height:1;margin-bottom:8px;">
                    {{ props.subjects_count }}
                </div>
                <div style="font-size:var(--text-xs);color:var(--text-muted);">
                    este período
                </div>
            </div>

            <!-- UC inscritas -->
            <div class="stat">
                <div class="stat-label">UC inscritas</div>
                <div style="font-size:var(--text-4xl);font-weight:700;color:var(--accent);font-variant-numeric:tabular-nums;line-height:1;margin-bottom:8px;">
                    {{ props.enrollment ? props.enrollment.uc_inscritas : 0 }}
                </div>
                <div style="font-size:var(--text-xs);color:var(--text-muted);">
                    unidades de crédito
                </div>
            </div>

            <!-- Progreso pensum -->
            <div class="stat">
                <div class="stat-label">Progreso pensum</div>
                <div style="font-size:var(--text-4xl);font-weight:700;color:var(--accent);font-variant-numeric:tabular-nums;line-height:1;margin-bottom:8px;">
                    {{ pensumDisplay }}
                </div>
                <div style="font-size:var(--text-xs);color:var(--text-muted);">
                    UC aprobadas
                </div>
            </div>
        </div>

        <!-- Mi representante -->
        <template v-if="props.guardians.length">
            <div>
                <div style="font-size:var(--text-xs);font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:12px;">
                    {{ props.guardians.length > 1 ? 'Mis representantes' : 'Mi representante' }}
                </div>

                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div
                        v-for="guardian in props.guardians"
                        :key="guardian.email"
                        style="background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;"
                    >
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                                <span style="font-size:var(--text-base);font-weight:600;color:var(--text-primary);">
                                    {{ guardian.name }}
                                </span>
                                <span
                                    v-if="guardian.is_primary"
                                    class="badge badge-accent"
                                    style="font-size:10px;height:18px;padding:0 7px;font-weight:700;letter-spacing:0.04em;"
                                >
                                    PRINCIPAL
                                </span>
                            </div>
                            <div style="font-size:var(--text-sm);color:var(--text-muted);">
                                {{ guardian.kinship ?? '—' }}
                            </div>
                        </div>

                        <div style="text-align:right;">
                            <div style="font-size:var(--text-sm);color:var(--text-secondary);font-weight:500;">
                                {{ guardian.email }}
                            </div>
                            <div v-if="guardian.phone" style="font-size:var(--text-xs);color:var(--text-muted);">
                                {{ guardian.phone }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- CTA banner — no active enrollment -->
        <template v-if="props.enrollment === null">
            <div
                style="
                    background:var(--bg-surface);
                    border:1px solid var(--border);
                    border-left:4px solid var(--accent);
                    border-radius:var(--radius-lg);
                    padding:20px 24px;
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    flex-wrap:wrap;
                    gap:12px;
                "
            >
                <div>
                    <div style="font-size:var(--text-base);font-weight:600;color:var(--text-primary);margin-bottom:4px;">
                        No tienes inscripción activa para este período
                    </div>
                    <div style="font-size:var(--text-sm);color:var(--text-muted);">
                        <template v-if="isUniversityStudent">
                            Accede al módulo de inscripciones para iniciar el proceso.
                        </template>
                        <template v-else>
                            Tu representante debe inscribirte.
                        </template>
                    </div>
                </div>
                <Link
                    v-if="isUniversityStudent"
                    :href="enrollmentIndex.url()"
                    style="
                        display:inline-flex;
                        align-items:center;
                        gap:6px;
                        background:var(--accent);
                        color:#fff;
                        font-size:var(--text-sm);
                        font-weight:600;
                        padding:8px 18px;
                        border-radius:var(--radius-md);
                        text-decoration:none;
                        white-space:nowrap;
                    "
                >
                    Ir a inscripciones →
                </Link>
            </div>
        </template>

        <!-- Today's timeline — only when enrollment exists -->
        <template v-else>
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
                            Disfruta tu día libre o revisa los materiales de tus secciones.
                        </div>
                    </div>
                </template>

                <!-- Schedule list -->
                <template v-else>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <div
                            v-for="item in props.today_schedules"
                            :key="item.section_code"
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

                                <!-- Classroom -->
                                <div style="text-align:right;">
                                    <div style="font-size:var(--text-sm);color:var(--text-secondary);font-weight:500;">
                                        {{ item.classroom_name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

    </div>
</template>
