<script setup lang="ts">
import { router, setLayoutProps } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { sheet as adminSheet } from '@/actions/App/Http/Controllers/Admin/AttendanceController'
import AttDateBlock from '@/components/attendance/AttDateBlock.vue'
import AttStatusPill from '@/components/attendance/AttStatusPill.vue'
import AppIcon from '@/components/UI/AppIcon.vue'
import type { ClassSession } from '@/types/attendance'

type AdminSession = ClassSession & {
    subject?: string | null
    code?: string | null
    cohort?: string | null
    career?: string | null
    careerColor?: string | null
    teacherName?: string | null
}

type Props = {
    pending_sessions: {
        data: AdminSession[]
    } | AdminSession[]
}

const props = defineProps<Props>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Admin', href: '#' },
        { title: 'Asistencia', href: '#' },
    ],
})

// Normalize — the backend returns ResourceCollection which wraps in { data: [] }
const sessions = computed<AdminSession[]>(() => {
    const ps = props.pending_sessions

    return Array.isArray(ps) ? ps : (ps as { data: AdminSession[] }).data
})

const pendingCount = computed(() => sessions.value.length)

// ---- Search ----
const query = ref('')

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase()

    if (!q) {
 return sessions.value 
}

    return sessions.value.filter((s) => {
        const fields = [
            s.topic,
            s.subject,
            s.cohort,
            s.career,
            s.teacherName,
        ]

        return fields.some((f) => (f ?? '').toLowerCase().includes(q))
    })
})

// ---- Date helpers ----
const MON_ES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic']
const DOW_ES = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb']

function parseDateLocal(iso: string): Date {
    const [y, m, d] = iso.split('-').map(Number)

    return new Date(y, m - 1, d)
}

function fmtDate(iso: string): string {
    const dt = parseDateLocal(iso)

    return `${dt.getDate()} de ${MON_ES[dt.getMonth()]}`
}

function dowShort(iso: string): string {
    return DOW_ES[parseDateLocal(iso).getDay()].toUpperCase()
}

// ---- Navigation ----
function goToSheet(session: AdminSession): void {
    router.visit(adminSheet.url({ section: session.sectionId, classSession: session.id }))
}
</script>

<template>
    <div class="att-root">

        <!-- Admin info banner -->
        <div class="att-admin-banner" style="margin-bottom: 20px;">
            <AppIcon name="info" :size="16" />
            <div class="ab-body">
                <strong>Subida administrativa de asistencia.</strong> Estas sesiones no fueron registradas por el profesor en la fecha programada.
                Como Coordinación / Admin podés subir la lista; cada sesión quedará marcada con
                <strong style="font-family: var(--font-mono);">professor_present = false</strong>.
            </div>
        </div>

        <!-- Header -->
        <div
            class="flex flex-wrap items-start justify-between gap-4 mb-5"
        >
            <div>
                <h1
                    class="m-0 font-bold"
                    style="font-size: 22px; color: var(--text-primary);"
                >
                    Asistencia · Coordinación
                </h1>
                <p
                    class="m-0 mt-1"
                    style="font-size: 13.5px; color: var(--text-muted);"
                >
                    Sesiones sin registrar que requieren intervención administrativa
                </p>
            </div>

            <!-- Counter badge -->
            <div
                v-if="pendingCount > 0"
                class="flex items-center gap-2 rounded-lg px-3 py-1.5"
                style="background: var(--warning-bg, rgba(251,146,60,0.1)); border: 1px solid var(--warning, #F97316); color: var(--warning, #F97316); font-size: 13px; font-weight: 600;"
            >
                <AppIcon name="alert" :size="14" />
                {{ pendingCount }} pendiente{{ pendingCount !== 1 ? 's' : '' }}
            </div>
        </div>

        <!-- Search -->
        <div class="att-rc-search" style="max-width: 420px; margin-bottom: 20px;">
            <AppIcon name="search" :size="15" />
            <input
                v-model="query"
                type="text"
                placeholder="Buscar por tema, sección o código…"
            />
        </div>

        <!-- Empty state — nothing pending -->
        <div
            v-if="sessions.length === 0"
            class="att-empty"
        >
            <div class="ic">
                <AppIcon name="check" :size="24" />
            </div>
            <h3>Todo al día</h3>
            <p>No hay sesiones pendientes de subir. El registro está al corriente.</p>
        </div>

        <!-- Empty state — filtered -->
        <div
            v-else-if="filtered.length === 0"
            class="att-empty"
        >
            <div class="ic">
                <AppIcon name="search" :size="20" />
            </div>
            <h3>Sin resultados</h3>
            <p>No hay sesiones que coincidan con "{{ query }}".</p>
        </div>

        <!-- Cards grid -->
        <div v-else class="att-cards">
            <div
                v-for="session in filtered"
                :key="session.id"
                class="att-card clickable"
                @click="goToSheet(session)"
            >
                <!-- Top: date + head -->
                <div class="att-card-top">
                    <AttDateBlock v-if="session.heldAt" :date="session.heldAt" />

                    <!-- Fallback when no date -->
                    <div
                        v-else
                        class="flex flex-col items-center border overflow-hidden shrink-0"
                        style="width: 46px; border-radius: var(--radius-md);"
                    >
                        <div
                            class="w-full text-center uppercase"
                            style="background-color: var(--bg-surface-2); color: var(--text-muted); font-size: 9px; padding: 2px 0;"
                        >
                            —
                        </div>
                        <div
                            class="w-full text-center font-bold"
                            style="color: var(--text-muted); font-size: 14px; line-height: 1.4; padding: 4px 0;"
                        >
                            ?
                        </div>
                    </div>

                    <div class="att-card-head">
                        <!-- Subject · Cohort -->
                        <p class="att-card-topic">
                            <template v-if="session.subject">
                                {{ session.subject }}
                                <span v-if="session.cohort" style="color: var(--text-muted); font-weight: 400;"> · {{ session.cohort }}</span>
                            </template>
                            <template v-else>
                                Sección #{{ session.sectionId }}
                            </template>
                        </p>
                        <div class="att-card-tags">
                            <!-- Code pill -->
                            <span
                                v-if="session.code"
                                class="att-pill"
                                style="font-family: var(--font-mono); font-size: 10px; background: var(--bg-surface-2);"
                            >
                                {{ session.code }}
                            </span>
                            <!-- "Sin registrar" warning badge -->
                            <span
                                class="att-pill warn"
                                style="display: inline-flex; align-items: center; gap: 4px;"
                            >
                                <span class="pdot" />
                                Sin registrar
                            </span>
                            <AttStatusPill :status="session.status" />
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="att-card-body">
                    <!-- Topic -->
                    <div
                        v-if="session.topic"
                        style="font-size: 12.5px; color: var(--text-secondary); display: flex; align-items: center; gap: 5px;"
                    >
                        <AppIcon name="book" :size="12" />
                        {{ session.topic }}
                    </div>

                    <!-- Date info -->
                    <div
                        v-if="session.heldAt"
                        style="font-size: 12.5px; color: var(--text-secondary); display: flex; align-items: center; gap: 5px;"
                    >
                        <AppIcon name="calendar" :size="12" />
                        {{ fmtDate(session.heldAt) }} · {{ dowShort(session.heldAt) }}
                    </div>

                    <!-- Professor note -->
                    <div
                        v-if="session.teacherName"
                        style="font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 5px; margin-top: 2px;"
                    >
                        <AppIcon name="user" :size="11" />
                        {{ session.teacherName }}
                    </div>

                    <!-- professor_present note -->
                    <div
                        class="att-upload-note"
                        style="font-size: 11.5px; font-family: var(--font-mono); color: var(--warning, #F97316); display: flex; align-items: center; gap: 5px; margin-top: 4px;"
                    >
                        <AppIcon name="alert" :size="11" />
                        professor_present = false
                    </div>
                </div>

                <!-- Footer -->
                <div class="att-card-foot">
                    <span class="foot-meta">
                        <AppIcon name="users" :size="12" />
                        <template v-if="session.career">{{ session.career }}</template>
                        <template v-else>Sección #{{ session.sectionId }}</template>
                    </span>

                    <button
                        class="att-card-cta"
                        @click.stop="goToSheet(session)"
                    >
                        <AppIcon name="upload" :size="13" />
                        Subir asistencia
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
