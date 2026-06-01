<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import type { ClassSession } from '@/types/attendance'
import AppIcon from '@/components/UI/AppIcon.vue'
import AttDateBlock from '@/components/attendance/AttDateBlock.vue'
import AttBar from '@/components/attendance/AttBar.vue'
import AttStatusPill from '@/components/attendance/AttStatusPill.vue'
import AttTypePill from '@/components/attendance/AttTypePill.vue'
import { sheet } from '@/actions/App/Http/Controllers/Professor/AttendanceController'

const props = defineProps<{
    session: ClassSession
    rosterCount: number
    todayDate: string
}>()

const isToday = computed(() => props.session.heldAt === props.todayDate)

const isClickable = computed(
    () => props.session.hasRecord || props.session.status === 'scheduled' || props.session.status === 'advanced',
)

function goToSheet(): void {
    if (!isClickable.value) { return }
    router.visit(sheet.url({ section: props.session.sectionId, classSession: props.session.id }))
}

function stopAndGoToSheet(e: Event): void {
    e.stopPropagation()
    goToSheet()
}

const linkedLabel = computed(() => {
    if (!props.session.linkedSession) { return null }
    const linked = props.session.linkedSession
    const parts = linked.date.split('-').map(Number)
    const dt = new Date(parts[0], parts[1] - 1, parts[2])
    const MON = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic']
    const formatted = `${parts[2]} de ${MON[parts[1] - 1]}`

    if (props.session.type === 'makeup') { return `Recupera la sesión cancelada del ${formatted}` }
    if (props.session.type === 'advance') { return `Adelanta la clase del ${formatted} · asistencia copiada` }
    if (props.session.status === 'recovered') { return `Recuperada el ${formatted} (sesión especial)` }
    if (props.session.status === 'advanced') { return `Dictada por adelantado el ${formatted}` }
    return null
})
</script>

<template>
    <div
        :class="['att-card', isClickable && 'clickable', isToday && 'is-today']"
        @click="goToSheet"
    >
        <!-- Top: date block + head -->
        <div class="att-card-top">
            <AttDateBlock v-if="session.heldAt" :date="session.heldAt" />

            <!-- Fallback date block for sessions without heldAt -->
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
                <div
                    class="w-full text-center uppercase"
                    style="color: var(--text-muted); font-size: 9px; padding-bottom: 4px;"
                >
                    —
                </div>
            </div>

            <div class="att-card-head">
                <p class="att-card-topic">{{ session.topic || 'Sin tema' }}</p>
                <div class="att-card-tags">
                    <AttStatusPill :status="session.status" />
                    <AttTypePill :type="session.type" />
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="att-card-body">
            <!-- Recorded: attendance bar -->
            <AttBar
                v-if="session.hasRecord"
                :present="session.present"
                :absent="session.absent"
            />

            <!-- Scheduled / pending -->
            <div v-else-if="session.status === 'scheduled'" class="att-pending-note">
                <AppIcon v-if="isToday" name="clock" :size="13" />
                <AppIcon v-else name="calendar" :size="13" />
                <span v-if="isToday">Clase de hoy — falta pasar lista</span>
                <span v-else>Programada — aún no inicia</span>
            </div>

            <!-- Recovered: no direct record -->
            <div v-else-if="session.status === 'recovered'" class="att-pending-note">
                <AppIcon name="x" :size="13" />
                Sin asistencia (se dio en la recuperación)
            </div>

            <!-- Linked session note -->
            <div v-if="linkedLabel" class="att-link-note">
                <AppIcon name="arrowRight" :size="13" />
                <span v-html="linkedLabel" />
            </div>

            <!-- Uploaded by admin note -->
            <div
                v-if="session.professorPresent === false && session.hasRecord"
                class="att-upload-note"
            >
                <AppIcon name="user" :size="11" />
                Subida por {{ session.uploadedBy || 'Coordinación' }}
            </div>
        </div>

        <!-- Footer -->
        <div class="att-card-foot">
            <span class="foot-meta">
                {{ session.hasRecord ? `${session.present + session.absent} registros` : `${rosterCount} estudiantes` }}
            </span>

            <button
                v-if="session.status === 'scheduled'"
                class="att-card-cta"
                @click="stopAndGoToSheet"
            >
                Pasar lista
                <AppIcon name="arrowRight" :size="14" />
            </button>

            <button
                v-else-if="session.hasRecord"
                class="att-card-cta ghost"
                @click="stopAndGoToSheet"
            >
                Ver / editar
                <AppIcon name="chevronRight" :size="14" />
            </button>

            <span v-else class="foot-meta">—</span>
        </div>
    </div>
</template>
