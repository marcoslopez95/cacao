<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { index as enrollmentIndex } from '@/routes/enrollment'
import type { GuardianDashboardProps, GuardianStudent } from '@/types/guardian-dashboard'

const page = usePage()
const userName = computed(() => {
    const name: string = (page.props.auth as { user?: { name?: string } })?.user?.name ?? ''

    return name ? name.split(' ')[0] : ''
})

const props = defineProps<GuardianDashboardProps>()

const ucPercent = (s: GuardianStudent): number =>
    s.uc_pensum > 0 ? Math.round((s.uc_aprobadas / s.uc_pensum) * 100) : 0

const enrollmentLabel = (status: string | null): string => {
    const labels: Record<string, string> = {
        draft: 'Borrador',
        confirmed: 'Confirmada',
        approved: 'Aprobada',
        rejected: 'Rechazada',
    }

    return status ? (labels[status] ?? status) : 'Sin inscripción'
}

const badgeColor = (status: string | null): string => {
    if (status === 'approved') {
return 'var(--color-success, #4ade80)'
}

    if (status === 'confirmed') {
return 'var(--color-success, #4ade80)'
}

    if (status === 'rejected') {
return '#f87171'
}

    if (status === 'draft') {
return '#93c5fd'
}

    return 'var(--text-muted)'
}

const levelLabel = (level: string): string =>
    level === 'university' ? 'Universitario' : 'Escolar'

const enrollmentCtaLabel = (status: string | null): string => {
    if (status === null) {
return 'Inscribir'
}

    if (status === 'draft') {
return 'Continuar inscripción'
}

    return 'Ver inscripción'
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

        <!-- Empty state: no represented students -->
        <template v-if="props.students.length === 0">
            <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:48px 24px;text-align:center;">
                <div style="font-size:var(--text-md);font-weight:500;color:var(--text-secondary);margin-bottom:6px;">
                    No tienes estudiantes representados
                </div>
                <div style="font-size:var(--text-sm);color:var(--text-muted);">
                    Contacta al administrador del sistema para vincular un estudiante a tu cuenta.
                </div>
            </div>
        </template>

        <!-- Student cards -->
        <template v-else>
            <div style="display:flex;flex-direction:column;gap:20px;">
                <div
                    v-for="student in props.students"
                    :key="student.id"
                    style="background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;"
                >
                    <!-- Card header: name + enrollment badge -->
                    <div
                        style="
                            display:flex;
                            align-items:center;
                            justify-content:space-between;
                            flex-wrap:wrap;
                            gap:8px;
                            padding:16px 20px;
                            border-bottom:1px solid var(--border);
                        "
                    >
                        <div>
                            <div style="font-size:var(--text-base);font-weight:700;color:var(--text-primary);">
                                {{ student.name }}
                            </div>
                            <div style="font-size:var(--text-xs);color:var(--text-muted);margin-top:2px;">
                                {{ levelLabel(student.educational_level) }}
                                <template v-if="student.pensum_name"> · {{ student.pensum_name }}</template>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span
                                :style="{
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    height: '22px',
                                    padding: '0 10px',
                                    borderRadius: '999px',
                                    fontSize: 'var(--text-xs)',
                                    fontWeight: '600',
                                    background: badgeColor(student.enrollment_status) + '22',
                                    color: badgeColor(student.enrollment_status),
                                    border: '1px solid ' + badgeColor(student.enrollment_status) + '55',
                                }"
                            >
                                {{ enrollmentLabel(student.enrollment_status) }}
                            </span>
                            <Link
                                dusk="guardian-enroll-btn"
                                :href="enrollmentIndex.url({ query: { student_id: student.id } })"
                                style="
                                    display:inline-flex;
                                    align-items:center;
                                    gap:4px;
                                    background:var(--accent);
                                    color:#fff;
                                    font-size:var(--text-xs);
                                    font-weight:600;
                                    padding:6px 12px;
                                    border-radius:var(--radius-md);
                                    text-decoration:none;
                                    white-space:nowrap;
                                "
                            >
                                {{ enrollmentCtaLabel(student.enrollment_status) }} →
                            </Link>
                        </div>
                    </div>

                    <!-- Card body -->
                    <div style="padding:16px 20px;display:flex;flex-direction:column;gap:16px;">

                        <!-- 4 mini-stats row -->
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;">
                            <!-- UC inscritas -->
                            <div style="background:var(--bg-inset,var(--bg-surface));border:1px solid var(--border);border-radius:var(--radius-md);padding:10px 14px;">
                                <div style="font-size:var(--text-xs);color:var(--text-muted);margin-bottom:4px;">UC inscritas</div>
                                <div style="font-size:var(--text-xl);font-weight:700;color:var(--accent);font-variant-numeric:tabular-nums;line-height:1;">
                                    {{ student.uc_inscritas }}
                                </div>
                            </div>

                            <!-- Nota prom. -->
                            <div style="background:var(--bg-inset,var(--bg-surface));border:1px solid var(--border);border-radius:var(--radius-md);padding:10px 14px;">
                                <div style="font-size:var(--text-xs);color:var(--text-muted);margin-bottom:4px;">Nota prom.</div>
                                <div style="font-size:var(--text-xl);font-weight:700;color:var(--text-secondary);font-variant-numeric:tabular-nums;line-height:1;">
                                    —
                                </div>
                            </div>

                            <!-- Inasistencias -->
                            <div style="background:var(--bg-inset,var(--bg-surface));border:1px solid var(--border);border-radius:var(--radius-md);padding:10px 14px;">
                                <div style="font-size:var(--text-xs);color:var(--text-muted);margin-bottom:4px;">Inasistencias</div>
                                <div style="font-size:var(--text-xl);font-weight:700;color:var(--text-secondary);font-variant-numeric:tabular-nums;line-height:1;">
                                    —
                                </div>
                            </div>

                            <!-- % pensum -->
                            <div style="background:var(--bg-inset,var(--bg-surface));border:1px solid var(--border);border-radius:var(--radius-md);padding:10px 14px;">
                                <div style="font-size:var(--text-xs);color:var(--text-muted);margin-bottom:4px;">% pensum</div>
                                <div style="font-size:var(--text-xl);font-weight:700;color:var(--accent);font-variant-numeric:tabular-nums;line-height:1;">
                                    {{ ucPercent(student) }}%
                                </div>
                            </div>
                        </div>

                        <!-- Progress bar: UC aprobadas / UC pensum -->
                        <div>
                            <div
                                style="
                                    display:flex;
                                    align-items:center;
                                    justify-content:space-between;
                                    margin-bottom:6px;
                                "
                            >
                                <span style="font-size:var(--text-xs);color:var(--text-muted);">Progreso pensum</span>
                                <span style="font-size:var(--text-xs);color:var(--text-muted);font-variant-numeric:tabular-nums;">
                                    {{ student.uc_aprobadas }} / {{ student.uc_pensum }} UC
                                </span>
                            </div>
                            <div
                                :style="{
                                    background: 'var(--bg-inset, var(--bg-surface))',
                                    height: '6px',
                                    borderRadius: '999px',
                                    overflow: 'hidden',
                                }"
                            >
                                <div
                                    :style="{
                                        height: '100%',
                                        width: ucPercent(student) + '%',
                                        background: 'var(--accent)',
                                        borderRadius: '999px',
                                        transition: 'width 0.3s ease',
                                    }"
                                />
                            </div>
                        </div>

                        <!-- Subjects list -->
                        <div>
                            <div style="font-size:var(--text-xs);font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px;">
                                Materias inscritas
                            </div>
                            <template v-if="student.subjects.length === 0">
                                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                                    Sin inscripción activa
                                </p>
                            </template>
                            <template v-else>
                                <ul style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:4px;">
                                    <li
                                        v-for="subject in student.subjects"
                                        :key="subject.id"
                                        style="font-size:var(--text-sm);color:var(--text-secondary);"
                                    >
                                        {{ subject.name }}
                                    </li>
                                </ul>
                            </template>
                        </div>

                    </div>
                </div>
            </div>
        </template>

    </div>
</template>
