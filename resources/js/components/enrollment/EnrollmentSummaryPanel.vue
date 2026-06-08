<script setup lang="ts">
import EnrollmentMiniGrid from '@/components/enrollment/EnrollmentMiniGrid.vue'
import AppIcon from '@/components/UI/AppIcon.vue'
import type {
    EnrollmentSummary,
    EnrollmentRules,
    EnrollmentSelections,
    EnrollmentSubject,
    EnrollmentGhostCandidate,
} from '@/types/enrollment'
import { enrollmentColor } from '@/utils/enrollmentColor'

const props = defineProps<{
    summary: EnrollmentSummary
    rules: EnrollmentRules
    creditsPct: number
    creditsStatus: 'low' | 'ok' | 'high'
    subjects: EnrollmentSubject[]
    selections: EnrollmentSelections
    ghost: EnrollmentGhostCandidate | null
    mobile?: boolean
    isReadOnly?: boolean
    canConfirm?: boolean
}>()

const emit = defineEmits<{
    confirm: []
}>()

const minPct = (props.rules.creditsMin / props.rules.creditsMax) * 100
</script>

<template>
    <div :class="['enr-side', mobile && 'enr-side--mobile']">
        <!-- Header: title + UC badge -->
        <div class="enr-side-head">
            <div>
                <div class="enr-side-title">Tu inscripción</div>
                <div class="enr-side-sub">{{ rules.period }} · {{ rules.studentName }}</div>
            </div>
            <span :class="['enr-uc-badge', `enr-uc-badge--${creditsStatus}`]">
                <strong>{{ summary.credits }}</strong>
                <span>/ {{ rules.creditsMax }} UC</span>
            </span>
        </div>

        <!-- UC progress track -->
        <div class="enr-uc-track">
            <div class="enr-uc-track-fill" :style="{ width: creditsPct + '%', background: creditsStatus === 'low' ? 'var(--warning)' : 'var(--accent)' }" />
            <div class="enr-uc-track-min" :style="{ left: minPct + '%' }" :title="`Mínimo ${rules.creditsMin} UC`" />
            <span class="enr-uc-track-lbl">Mín {{ rules.creditsMin }} · Máx {{ rules.creditsMax }}</span>
        </div>

        <!-- Stats row -->
        <div class="enr-side-stats">
            <div class="enr-side-stat">
                <div class="enr-side-stat-v">{{ summary.count }}</div>
                <div class="enr-side-stat-l">materia{{ summary.count === 1 ? '' : 's' }}</div>
            </div>
            <div class="enr-side-stat">
                <div class="enr-side-stat-v">{{ summary.hours }}<span class="unit">h</span></div>
                <div class="enr-side-stat-l">por semana</div>
            </div>
            <div class="enr-side-stat">
                <div class="enr-side-stat-v">{{ summary.scheduled }}</div>
                <div class="enr-side-stat-l">con horario</div>
            </div>
            <div v-if="summary.pending > 0" class="enr-side-stat enr-side-stat--warn">
                <div class="enr-side-stat-v">{{ summary.pending }}</div>
                <div class="enr-side-stat-l">por definir</div>
            </div>
        </div>

        <!-- Mini week grid -->
        <div class="enr-side-grid">
            <EnrollmentMiniGrid
                :subjects="subjects"
                :selections="selections"
                :ghost-candidate="ghost"
            />
        </div>

        <!-- Pending list (no-schedule sections) -->
        <div v-if="summary.pending > 0" class="enr-side-pending">
            <div class="enr-side-pending-head">
                <AppIcon name="clock" :size="12" /> Sin horario asignado
            </div>
            <div
                v-for="it in summary.items.filter(i => i.section.noSchedule)"
                :key="it.subject.code"
                class="enr-side-pending-row"
                :style="{ '--enr-c': enrollmentColor(it.subject.code) }"
            >
                <span class="enr-side-pending-dot" />
                <span class="enr-side-pending-t">{{ it.subject.code }} · {{ it.section.code }}</span>
                <span class="enr-side-pending-s">Se publicará cuando se asigne profesor y aula</span>
            </div>
        </div>

        <!-- Actions -->
        <div v-if="! isReadOnly" class="enr-side-actions">
            <button
                class="btn btn-primary"
                :disabled="! canConfirm || summary.credits < rules.creditsMin || summary.credits > rules.creditsMax"
                @click="emit('confirm')"
            >
                <AppIcon name="check" :size="14" /> Confirmar inscripción
            </button>
        </div>
        <div v-else class="enr-side-readonly">
            <AppIcon name="lock" :size="13" />
            <span>Inscripción {{ summary.credits > 0 ? 'confirmada' : 'cerrada' }}</span>
        </div>
        <p v-if="summary.credits < rules.creditsMin" class="enr-side-note">
            Te faltan <strong>{{ rules.creditsMin - summary.credits }} UC</strong> para alcanzar el mínimo.
        </p>
        <p v-if="summary.credits > rules.creditsMax" class="enr-side-note enr-side-note--danger">
            Superás el máximo de <strong>{{ rules.creditsMax }} UC</strong>.
        </p>
    </div>
</template>

<style>
.enr-side {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    position: sticky;
    top: 16px;
}
.enr-side--mobile { position: static; border-radius: var(--radius-md); }

.enr-side-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.enr-side-title { font-size: 14px; font-weight: 600; color: var(--text-primary); }
.enr-side-sub { font-size: 11px; color: var(--text-muted); font-family: var(--font-mono); margin-top: 2px; }

.enr-uc-badge {
    display: flex;
    align-items: baseline;
    gap: 3px;
    padding: 4px 10px;
    border-radius: var(--radius-pill);
    font-size: 13px;
    flex-shrink: 0;
}
.enr-uc-badge strong { font-weight: 700; font-family: var(--font-mono); font-size: 16px; }
.enr-uc-badge span { font-size: 11px; color: var(--text-muted); }
.enr-uc-badge--low { background: var(--warning-bg); color: var(--warning-fg); }
.enr-uc-badge--ok  { background: var(--success-bg); color: var(--success-fg); }
.enr-uc-badge--high{ background: var(--danger-bg);  color: var(--danger-fg); }

.enr-uc-track {
    position: relative;
    height: 6px;
    background: var(--bg-sunken);
    border-radius: 3px;
    overflow: visible;
}
.enr-uc-track-fill { height: 100%; border-radius: 3px; transition: width .3s; }
.enr-uc-track-min {
    position: absolute;
    top: -3px;
    width: 2px;
    height: 12px;
    background: var(--text-muted);
    border-radius: 1px;
    transform: translateX(-50%);
}
.enr-uc-track-lbl {
    position: absolute;
    top: 10px;
    right: 0;
    font-size: 10px;
    color: var(--text-muted);
    font-family: var(--font-mono);
    white-space: nowrap;
}

.enr-side-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    text-align: center;
}
.enr-side-stat { }
.enr-side-stat--warn .enr-side-stat-v { color: var(--warning-fg); }
.enr-side-stat-v {
    font-size: 20px;
    font-weight: 700;
    font-family: var(--font-mono);
    color: var(--text-primary);
    line-height: 1;
}
.enr-side-stat-v .unit { font-size: 13px; font-weight: 400; }
.enr-side-stat-l { font-size: 10px; color: var(--text-muted); margin-top: 2px; }

.enr-side-grid { }

.enr-side-pending {
    background: var(--bg-page);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 8px 10px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.enr-side-pending-head {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.enr-side-pending-row {
    display: grid;
    grid-template-columns: 10px 1fr;
    grid-template-rows: auto auto;
    column-gap: 8px;
    row-gap: 1px;
}
.enr-side-pending-dot {
    grid-row: 1 / 3;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--enr-c);
    margin-top: 3px;
}
.enr-side-pending-t { font-size: 12px; font-weight: 500; color: var(--text-primary); }
.enr-side-pending-s { font-size: 11px; color: var(--text-muted); }

.enr-side-actions { display: flex; gap: 8px; }
.enr-side-actions .btn-primary { flex: 1; justify-content: center; }

.enr-side-readonly {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: var(--bg-sunken);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 12px;
    color: var(--text-muted);
}

.enr-side-note {
    font-size: 12px;
    color: var(--warning-fg);
    margin: 0;
    padding: 6px 10px;
    background: var(--warning-bg);
    border-radius: var(--radius-sm);
}
.enr-side-note--danger { color: var(--danger-fg); background: var(--danger-bg); }
.enr-side-note strong { font-weight: 600; }
</style>
