<script setup lang="ts">
import { computed } from 'vue'
import type { AttendanceRosterEntry } from '@/types/attendance'

type Props = {
    roster: AttendanceRosterEntry[]
    absenceTotals: Record<number, number>
    sessionsCounted: number
    period: string | null
}

const props = defineProps<Props>()

const rows = computed(() =>
    props.roster
        .map((st) => ({ ...st, absences: props.absenceTotals[st.enrollmentDetailId] ?? 0 }))
        .sort((a, b) => b.absences - a.absences || a.name.localeCompare(b.name)),
)

const maxAbs = computed(() => Math.max(props.sessionsCounted, ...rows.value.map((r) => r.absences), 1))
const totalAbsences = computed(() => rows.value.reduce((a, r) => a + r.absences, 0))
const atRisk = computed(() => rows.value.filter((r) => r.absences >= 6).length)

function riskClass(n: number): 'hi' | 'mid' | 'lo' {
    if (n >= 6) { return 'hi' }
    if (n >= 3) { return 'mid' }
    return 'lo'
}
</script>

<template>
    <div class="att-totals">
        <!-- Header -->
        <div class="att-totals-head">
            <div>
                <h3>Inasistencias acumuladas</h3>
                <div class="sub">
                    Total de faltas por estudiante en {{ sessionsCounted }} sesiones registradas del período
                    {{ period ?? '—' }} · {{ totalAbsences }} faltas en total
                </div>
            </div>
            <div class="att-totals-legend">
                <span><i style="background: var(--success)" /> 0–2 faltas</span>
                <span><i style="background: var(--warning)" /> 3–5</span>
                <span><i style="background: var(--danger)" /> 6+ en riesgo ({{ atRisk }})</span>
            </div>
        </div>

        <!-- Roster rows -->
        <div
            v-for="r in rows"
            :key="r.enrollmentDetailId"
            class="att-roster-row"
        >
            <!-- Avatar circular con iniciales -->
            <div class="att-roster-av">{{ r.initials }}</div>

            <!-- Nombre + badge riesgo + código -->
            <div class="att-roster-id">
                <div class="att-roster-name">
                    {{ r.name }}
                    <span v-if="r.absences >= 6" class="att-risk">riesgo</span>
                </div>
                <div class="att-roster-code">{{ r.code }}</div>
            </div>

            <!-- Track bar (proporción de ausencias) -->
            <div class="att-roster-track">
                <div
                    :class="['fill', riskClass(r.absences)]"
                    :style="{ width: `${Math.max(4, (r.absences / maxAbs) * 100)}%` }"
                />
            </div>

            <!-- Conteo numérico -->
            <div :class="['att-roster-count', riskClass(r.absences) === 'hi' ? 'hi' : riskClass(r.absences) === 'mid' ? 'mid' : '']">
                {{ r.absences }}<span class="of"> / {{ sessionsCounted }}</span>
            </div>
        </div>
    </div>
</template>
