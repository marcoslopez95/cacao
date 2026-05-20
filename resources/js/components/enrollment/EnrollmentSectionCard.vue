<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '@/components/UI/AppIcon.vue'
import { enrollmentColor } from '@/utils/enrollmentColor'
import type { EnrollmentSection, EnrollmentConflict } from '@/types/enrollment'

const DAY_ABBRS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

const props = defineProps<{
    subjectCode: string
    section: EnrollmentSection
    sectionIdx: number
    selectedIdx: number | null
    conflict: EnrollmentConflict | null
}>()

const emit = defineEmits<{
    select: [sectionIdx: number]
    unselect: []
    mouseenter: []
    mouseleave: []
}>()

const color = computed(() => enrollmentColor(props.subjectCode))
const isSelf = computed(() => props.selectedIdx === props.sectionIdx)
const isOtherSelected = computed(() => props.selectedIdx != null && !isSelf.value)
const full = computed(() => props.section.enrolled >= props.section.capacity)
const disabled = computed(() => !isSelf.value && (full.value || !!props.conflict))

const fillPct = computed(() =>
    Math.min(100, (props.section.enrolled / props.section.capacity) * 100),
)
const fillState = computed(() => {
    const ratio = props.section.enrolled / props.section.capacity
    if (ratio >= 1) return 'full'
    if (ratio > 0.9) return 'high'
    if (ratio > 0.7) return 'mid'
    return 'low'
})

const cuposLeft = computed(() => props.section.capacity - props.section.enrolled)

const conflictTitle = computed(() => {
    if (!props.conflict) return ''
    const { subject, section, slotB } = props.conflict
    return `Choca con ${subject.code} (${section.code}) · ${DAY_ABBRS[slotB.day]} ${slotB.start.slice(0, 5)}–${slotB.end.slice(0, 5)}`
})
</script>

<template>
    <div
        :class="[
            'enr-sec-card',
            isSelf && 'enr-sec-card--selected',
            disabled && 'enr-sec-card--disabled',
            section.noSchedule && 'enr-sec-card--no-schedule',
        ]"
        :style="{ '--enr-c': color }"
        @mouseenter="!disabled && !isSelf && emit('mouseenter')"
        @mouseleave="emit('mouseleave')"
    >
        <!-- Header: code + modality -->
        <div class="enr-sec-card-head">
            <div class="enr-sec-card-code">
                <span class="enr-sec-card-dot" />
                <span>{{ section.code }}</span>
            </div>
            <span v-if="section.noSchedule" class="enr-sec-card-tag enr-sec-card-tag--warn">
                <AppIcon name="clock" :size="10" /> Horario por definir
            </span>
            <span v-else class="enr-sec-card-tag enr-sec-card-tag--muted">{{ section.modality }}</span>
        </div>

        <!-- Professor -->
        <div class="enr-sec-card-prof">
            <span class="enr-sec-card-avatar" :style="{ '--enr-c': color }">
                {{ section.professor.initials }}
            </span>
            <span class="enr-sec-card-prof-name">{{ section.professor.name }}</span>
        </div>

        <!-- Slots -->
        <div v-if="section.slots.length" class="enr-sec-card-slots">
            <span
                v-for="(slot, i) in section.slots"
                :key="i"
                class="enr-sec-card-slot"
            >
                <span class="day">{{ DAY_ABBRS[slot.day] }}</span>
                <span class="hrs">{{ slot.start.slice(0, 5) }}–{{ slot.end.slice(0, 5) }}</span>
            </span>
        </div>
        <div v-else class="enr-sec-card-slots enr-sec-card-slots--empty">
            <AppIcon name="info" :size="11" />
            La sección existe pero aún no tiene horario asignado.
        </div>

        <!-- Room -->
        <div class="enr-sec-card-meta">
            <AppIcon name="grid" :size="11" />
            <span>{{ section.room }}</span>
        </div>

        <!-- Capacity bar + CTA -->
        <div class="enr-sec-card-foot">
            <div class="enr-sec-card-cupos">
                <div class="enr-sec-card-bar">
                    <div
                        :class="['enr-sec-card-bar-fill', `enr-sec-card-bar-fill--${fillState}`]"
                        :style="{ width: fillPct + '%' }"
                    />
                </div>
                <div class="enr-sec-card-bar-lbl">
                    <strong>{{ section.enrolled }}</strong>/{{ section.capacity }}
                    <span class="hint">
                        {{ full ? 'cupos agotados' : `${cuposLeft} disponible${cuposLeft === 1 ? '' : 's'}` }}
                    </span>
                </div>
            </div>

            <div class="enr-sec-card-cta">
                <button
                    v-if="isSelf"
                    class="btn btn-sm enr-btn-selected"
                    @click="emit('unselect')"
                >
                    <AppIcon name="check" :size="12" /> Seleccionada
                    <span class="enr-btn-undo">Quitar</span>
                </button>
                <button
                    v-else-if="disabled"
                    class="btn btn-sm btn-secondary enr-btn-blocked"
                    disabled
                    :title="conflictTitle || 'Cupos agotados'"
                >
                    <AppIcon v-if="conflict" name="alert" :size="11" />
                    <AppIcon v-else name="x" :size="11" />
                    {{ conflict ? `Choca con ${conflict.subject.code}` : 'Sin cupos' }}
                </button>
                <button
                    v-else-if="isOtherSelected"
                    class="btn btn-sm btn-ghost"
                    @click="emit('select', sectionIdx)"
                >
                    <AppIcon name="arrowRight" :size="11" /> Cambiar a esta
                </button>
                <button
                    v-else
                    class="btn btn-sm btn-secondary"
                    @click="emit('select', sectionIdx)"
                >
                    Seleccionar
                </button>
            </div>
        </div>
    </div>
</template>

<style>
.enr-sec-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    transition: border-color .15s, box-shadow .15s;
}
.enr-sec-card:hover:not(.enr-sec-card--disabled) {
    border-color: var(--border-strong);
}
.enr-sec-card--selected {
    border-color: var(--enr-c);
    background: color-mix(in srgb, var(--enr-c) 5%, var(--bg-surface));
    box-shadow: 0 0 0 1px var(--enr-c);
}
.enr-sec-card--disabled { opacity: 0.55; }

.enr-sec-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.enr-sec-card-code {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
}
.enr-sec-card-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--enr-c);
    flex-shrink: 0;
}
.enr-sec-card-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    padding: 2px 7px;
    border-radius: var(--radius-pill);
}
.enr-sec-card-tag--muted { background: var(--bg-sunken); color: var(--text-muted); }
.enr-sec-card-tag--warn { background: var(--warning-bg); color: var(--warning-fg); }

.enr-sec-card-prof {
    display: flex;
    align-items: center;
    gap: 8px;
}
.enr-sec-card-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--enr-c) 15%, var(--bg-sunken));
    color: var(--enr-c);
    font-size: 9px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-family: var(--font-mono);
}
.enr-sec-card-prof-name { font-size: 12px; color: var(--text-secondary); }

.enr-sec-card-slots { display: flex; flex-wrap: wrap; gap: 4px; }
.enr-sec-card-slot {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--bg-page);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 2px 7px;
    font-size: 11px;
}
.enr-sec-card-slot .day { font-weight: 600; color: var(--text-primary); }
.enr-sec-card-slot .hrs { color: var(--text-muted); font-family: var(--font-mono); }
.enr-sec-card-slots--empty {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: var(--text-muted);
    font-style: italic;
}

.enr-sec-card-meta {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: var(--text-muted);
}

.enr-sec-card-foot { display: flex; flex-direction: column; gap: 8px; margin-top: 2px; }

.enr-sec-card-cupos { display: flex; flex-direction: column; gap: 4px; }
.enr-sec-card-bar {
    height: 4px;
    background: var(--bg-sunken);
    border-radius: 2px;
    overflow: hidden;
}
.enr-sec-card-bar-fill { height: 100%; border-radius: 2px; transition: width .3s; }
.enr-sec-card-bar-fill--low  { background: var(--success); }
.enr-sec-card-bar-fill--mid  { background: var(--warning); }
.enr-sec-card-bar-fill--high { background: var(--danger); }
.enr-sec-card-bar-fill--full { background: var(--danger); }
.enr-sec-card-bar-lbl {
    font-size: 11px;
    color: var(--text-muted);
    display: flex;
    gap: 4px;
}
.enr-sec-card-bar-lbl strong { color: var(--text-primary); }
.enr-sec-card-bar-lbl .hint { margin-left: auto; }

.enr-sec-card-cta .btn { width: 100%; justify-content: center; }

.enr-btn-selected {
    background: color-mix(in srgb, var(--enr-c) 12%, var(--bg-surface));
    border: 1px solid var(--enr-c);
    color: var(--enr-c);
    position: relative;
}
.enr-btn-selected:hover .enr-btn-undo { opacity: 1; }
.enr-btn-undo {
    opacity: 0;
    font-size: 11px;
    color: var(--danger);
    margin-left: auto;
    transition: opacity .15s;
}
.enr-btn-blocked { cursor: not-allowed !important; }
</style>
