<script setup lang="ts">
import { computed } from 'vue'
import EnrollmentSectionCard from '@/components/enrollment/EnrollmentSectionCard.vue'
import AppIcon from '@/components/UI/AppIcon.vue'
import type {
    EnrollmentSubject,
    EnrollmentSelections,
    EnrollmentConflict,
    EnrollmentGhostCandidate,
    EnrollmentSection,
} from '@/types/enrollment'
import { enrollmentColor } from '@/utils/enrollmentColor'

const DAY_ABBRS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

const props = defineProps<{
    subject: EnrollmentSubject
    selections: EnrollmentSelections
    expanded: boolean
    findConflict: (section: EnrollmentSection, ownCode: string) => EnrollmentConflict | null
}>()

const emit = defineEmits<{
    toggle: []
    select: [subjectCode: string, sectionIdx: number]
    unselect: [subjectCode: string]
    'ghost-enter': [candidate: EnrollmentGhostCandidate]
    'ghost-leave': []
}>()

const color = computed(() => enrollmentColor(props.subject.code))
const selectedIdx = computed(() => props.selections[props.subject.code] ?? null)
const isSelected = computed(() => selectedIdx.value != null)
const blocked = computed(() => !props.subject.prereqsOk)

const selectedSection = computed(() =>
    isSelected.value ? props.subject.sections[selectedIdx.value!] : null,
)

const sectionStates = computed(() =>
    props.subject.sections.map((sec, i) => ({
        sec,
        i,
        conflict: props.findConflict(sec, props.subject.code),
    })),
)

const anyAvailable = computed(() =>
    sectionStates.value.some(({ sec, i, conflict }) =>
        selectedIdx.value === i || (!conflict && sec.enrolled < sec.capacity),
    ),
)

const statusText = computed(() => {
    if (blocked.value) {
return null
}

    if (isSelected.value && selectedSection.value) {
        const sec = selectedSection.value
        const slots = sec.noSchedule
            ? 'sin horario aún'
            : sec.slots.map(sl => `${DAY_ABBRS[sl.day]} ${sl.start.slice(0, 5)}`).join(', ')

        return `Sección ${sec.code} · ${slots}`
    }

    if (!anyAvailable.value) {
return 'Sin opciones libres ahora'
}

    const n = props.subject.sections.length

    return `${n} sección${n === 1 ? '' : 'es'} disponible${n === 1 ? '' : 's'}`
})
</script>

<template>
    <div
        :class="[
            'enr-mat-row',
            expanded && 'enr-mat-row--open',
            isSelected && 'enr-mat-row--selected',
            blocked && 'enr-mat-row--blocked',
        ]"
        :style="{ '--enr-c': color }"
    >
        <!-- Accordion trigger -->
        <button class="enr-mat-trigger" @click="emit('toggle')">
            <AppIcon
                :name="expanded ? 'chevronDown' : 'chevronRight'"
                :size="14"
                class="enr-mat-chevron"
            />
            <span class="enr-mat-code">{{ subject.code }}</span>
            <span class="enr-mat-name">
                {{ subject.name }}
                <span v-if="subject.type === 'electiva'" class="enr-mat-type-tag">Electiva</span>
            </span>
            <span class="enr-mat-uc">{{ subject.credits }} UC</span>
            <span class="enr-mat-status">
                <template v-if="blocked">
                    <span class="enr-status enr-status--blocked">
                        <AppIcon name="alert" :size="11" /> Prerequisitos pendientes
                    </span>
                </template>
                <template v-else-if="isSelected">
                    <span class="enr-status enr-status--selected">
                        <AppIcon name="check" :size="11" /> {{ statusText }}
                    </span>
                </template>
                <template v-else-if="!anyAvailable">
                    <span class="enr-status enr-status--warn">
                        <AppIcon name="alert" :size="11" /> {{ statusText }}
                    </span>
                </template>
                <template v-else>
                    <span class="enr-status enr-status--idle">{{ statusText }}</span>
                </template>
            </span>
        </button>

        <!-- Expanded body -->
        <Transition name="enr-expand">
            <div v-if="expanded" class="enr-mat-body">
                <p v-if="subject.description" class="enr-mat-desc">{{ subject.description }}</p>

                <div v-if="blocked" class="enr-mat-blocked-msg">
                    <AppIcon name="alert" :size="14" />
                    Esta materia tiene prerequisitos no cumplidos. Pasa por Coordinación para revisar tu plan.
                </div>
                <div v-else class="enr-sec-grid">
                    <EnrollmentSectionCard
                        v-for="({ sec, i, conflict }) in sectionStates"
                        :key="i"
                        :subject-code="subject.code"
                        :section="sec"
                        :section-idx="i"
                        :selected-idx="selectedIdx"
                        :conflict="conflict"
                        @select="(idx) => emit('select', subject.code, idx)"
                        @unselect="emit('unselect', subject.code)"
                        @mouseenter="emit('ghost-enter', { subjectCode: subject.code, section: sec })"
                        @mouseleave="emit('ghost-leave')"
                    />
                </div>
            </div>
        </Transition>
    </div>
</template>

<style>
.enr-mat-row {
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    overflow: hidden;
    transition: border-color .15s;
}
.enr-mat-row + .enr-mat-row { margin-top: 6px; }
.enr-mat-row--selected { border-color: var(--enr-c); }
.enr-mat-row--blocked { opacity: 0.7; }

.enr-mat-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: transparent;
    border: 0;
    cursor: pointer;
    text-align: left;
    color: var(--text-primary);
    transition: background .1s;
}
.enr-mat-trigger:hover { background: var(--bg-surface-2); }

.enr-mat-chevron { color: var(--text-muted); flex-shrink: 0; transition: transform .15s; }
.enr-mat-row--open .enr-mat-chevron { color: var(--text-primary); }

.enr-mat-code {
    font-size: 11px;
    font-family: var(--font-mono);
    font-weight: 500;
    color: var(--text-muted);
    flex-shrink: 0;
    min-width: 70px;
}
.enr-mat-name {
    flex: 1;
    font-size: 13px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}
.enr-mat-type-tag {
    font-size: 10px;
    font-weight: 600;
    padding: 1px 6px;
    border-radius: var(--radius-pill);
    background: var(--bg-sunken);
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.enr-mat-uc {
    font-size: 12px;
    font-family: var(--font-mono);
    font-weight: 600;
    color: var(--text-muted);
    flex-shrink: 0;
}
.enr-mat-status { flex-shrink: 0; font-size: 12px; }

.enr-status { display: inline-flex; align-items: center; gap: 4px; }
.enr-status--selected { color: var(--success); }
.enr-status--blocked  { color: var(--danger); }
.enr-status--warn     { color: var(--warning-fg); }
.enr-status--idle     { color: var(--text-muted); }

.enr-mat-body { padding: 0 12px 12px; }
.enr-mat-desc { font-size: 12px; color: var(--text-muted); margin: 0 0 10px; }
.enr-mat-blocked-msg {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 10px 12px;
    background: var(--danger-bg);
    color: var(--danger-fg);
    border-radius: var(--radius-sm);
    font-size: 13px;
}
.enr-sec-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 10px;
}

/* Transition */
.enr-expand-enter-active, .enr-expand-leave-active {
    transition: max-height .2s ease, opacity .15s ease;
    overflow: hidden;
}
.enr-expand-enter-from, .enr-expand-leave-to { max-height: 0; opacity: 0; }
.enr-expand-enter-to, .enr-expand-leave-from { max-height: 1000px; opacity: 1; }
</style>
