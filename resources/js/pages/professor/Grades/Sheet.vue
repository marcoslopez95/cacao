<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { sheet as sheetRoute } from '@/routes/professor/grades'
import { useGradeEntryForm } from '@/composables/forms/useGradeEntryForm'
import type { SectionGradeSheet, GradeEntry, GradeSheetStudent } from '@/types/grade-entry'

type Lapse = { id: number; name: string }

type Props = {
    sheet: SectionGradeSheet
    lapses: Lapse[]
    current_lapse_id: number | null
    visibility: 'real_time' | 'manual'
}

const props = defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Profesor', href: '#' },
            { title: props.sheet.subject_name },
            { title: 'Notas' },
        ],
    },
})

const { saveStates, cellKey, upsertEntry, publishSlot, enableRemedial } = useGradeEntryForm(props.sheet.section_id)

const editingCell = ref<string | null>(null)
const editingValue = ref<string>('')
const expandedSlot = ref<number | null>(null)
const expandedStudent = ref<number | null>(null)

const nonRemedialSlots = computed(() => props.sheet.slots.filter(s => !s.is_remedial))
const remedialSlot = computed(() => props.sheet.slots.find(s => s.is_remedial) ?? null)

function studentFinalGrade(student: SectionGradeSheet['students'][0]): number | null {
    const slots = nonRemedialSlots.value
    if (slots.some(s => !getEntry(student, s.id)?.value)) return null
    return slots.reduce((sum, s) => sum + Number(getEntry(student, s.id)!.value) * Number(s.weight) / 100, 0)
}

function studentPassed(student: SectionGradeSheet['students'][0]): boolean | null {
    const grade = studentFinalGrade(student)
    if (grade === null) return null
    return grade >= props.sheet.passing_value
}

function startEdit(enrollmentDetailId: number, slotId: number, currentValue: string | null): void {
    const key = cellKey(enrollmentDetailId, slotId, props.current_lapse_id)
    editingCell.value = key
    editingValue.value = currentValue ?? ''
}

async function commitEdit(enrollmentDetailId: number, slotId: number): Promise<void> {
    const key = cellKey(enrollmentDetailId, slotId, props.current_lapse_id)
    if (editingCell.value !== key) return

    editingCell.value = null

    await upsertEntry({
        enrollment_detail_id: enrollmentDetailId,
        grade_slot_id: slotId,
        lapse_id: props.current_lapse_id,
        parent_id: null,
        value: editingValue.value === '' ? null : Number(editingValue.value),
    })
}

function stateIcon(key: string): string {
    const s = saveStates.value[key]
    if (s === 'saving') return '⟳'
    if (s === 'saved') return '✓'
    if (s === 'error') return '!'
    return ''
}

function toggleSubEntries(slotId: number, enrollmentDetailId: number): void {
    const same = expandedSlot.value === slotId && expandedStudent.value === enrollmentDetailId
    expandedSlot.value = same ? null : slotId
    expandedStudent.value = same ? null : enrollmentDetailId
}

function getEntry(student: SectionGradeSheet['students'][0], slotId: number): GradeEntry | null {
    return student.entries_by_slot[slotId] ?? null
}

async function saveSubEntry(
    enrollmentDetailId: number,
    slotId: number,
    parentId: number | null,
    name: string,
    weight: number,
    value: string,
): Promise<void> {
    await upsertEntry({
        enrollment_detail_id: enrollmentDetailId,
        grade_slot_id: slotId,
        lapse_id: props.current_lapse_id,
        parent_id: parentId,
        name,
        weight,
        value: value === '' ? null : Number(value),
    })
}
</script>

<template>
    <Head :title="`Notas — ${sheet.subject_name}`" />

    <div style="display:flex;flex-direction:column;gap:20px;">
        <!-- Header -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    {{ sheet.subject_name }}
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Sección {{ sheet.section_code }} ·
                    <span :style="visibility === 'manual' ? 'color:#e67e22' : 'color:#27ae60'">
                        {{ visibility === 'manual' ? 'Publicación manual' : 'Tiempo real' }}
                    </span>
                </p>
            </div>

            <!-- Selector de lapso -->
            <div v-if="lapses.length > 0" style="display:flex;gap:8px;flex-wrap:wrap;">
                <Link
                    v-for="lapse in lapses"
                    :key="lapse.id"
                    :href="sheetRoute.url({ section: sheet.section_id }, { query: { lapse_id: lapse.id } })"
                    :style="`padding:6px 14px;border-radius:6px;font-size:var(--text-sm);text-decoration:none;${current_lapse_id === lapse.id ? 'background:var(--color-terracota);color:white;' : 'border:1px solid var(--color-borde);color:var(--text-secondary);'}`"
                >
                    {{ lapse.name }}
                </Link>
            </div>
        </div>

        <!-- Planilla -->
        <div style="overflow-x:auto;border:1px solid var(--color-borde);border-radius:8px;">
            <table style="width:100%;border-collapse:collapse;font-size:var(--text-sm);">
                <thead>
                    <tr style="background:var(--color-papel);">
                        <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--text-primary);border-bottom:1px solid var(--color-borde);min-width:200px;">
                            Estudiante
                        </th>
                        <th
                            v-for="slot in nonRemedialSlots"
                            :key="slot.id"
                            style="text-align:center;padding:12px 16px;font-weight:600;color:var(--text-primary);border-bottom:1px solid var(--color-borde);min-width:140px;"
                        >
                            <div>{{ slot.name }}</div>
                            <div style="font-size:var(--text-xs);font-weight:400;color:var(--text-muted);">{{ slot.weight }}%</div>
                            <button
                                v-if="visibility === 'manual'"
                                type="button"
                                @click="publishSlot(slot.id, current_lapse_id)"
                                style="margin-top:4px;font-size:var(--text-xs);padding:2px 8px;background:var(--color-terracota);color:white;border:none;border-radius:4px;cursor:pointer;"
                            >
                                Publicar
                            </button>
                        </th>
                        <th
                            v-if="remedialSlot"
                            style="text-align:center;padding:12px 16px;font-weight:600;color:var(--text-primary);border-bottom:1px solid var(--color-borde);min-width:140px;border-left:2px dashed var(--color-borde);"
                        >
                            <div>{{ remedialSlot.name }}</div>
                            <div style="font-size:var(--text-xs);font-weight:400;color:var(--text-muted);">Reparación</div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="student in sheet.students" :key="student.enrollment_detail_id">
                        <tr style="border-bottom:1px solid var(--color-borde);">
                            <td style="padding:12px 16px;color:var(--text-primary);font-weight:500;">
                                {{ student.name }}
                            </td>
                            <td
                                v-for="slot in nonRemedialSlots"
                                :key="slot.id"
                                style="text-align:center;padding:8px 12px;position:relative;"
                            >
                                <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
                                    <!-- Celda editable -->
                                    <template v-if="editingCell === cellKey(student.enrollment_detail_id, slot.id, current_lapse_id)">
                                        <input
                                            :ref="el => el && (el as HTMLInputElement).focus()"
                                            v-model="editingValue"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            @blur="commitEdit(student.enrollment_detail_id, slot.id)"
                                            @keyup.enter="commitEdit(student.enrollment_detail_id, slot.id)"
                                            @keyup.escape="editingCell = null"
                                            style="width:70px;padding:4px 8px;border:1px solid var(--color-terracota);border-radius:4px;text-align:center;font-size:var(--text-sm);"
                                        />
                                    </template>
                                    <template v-else>
                                        <span
                                            @click="startEdit(student.enrollment_detail_id, slot.id, getEntry(student, slot.id)?.value ?? null)"
                                            style="display:inline-block;min-width:60px;padding:4px 8px;border:1px solid transparent;border-radius:4px;cursor:text;"
                                            :style="getEntry(student, slot.id)?.value ? 'border-color:var(--color-borde);' : 'border-color:var(--color-gris-light);color:var(--text-muted);'"
                                        >
                                            {{ getEntry(student, slot.id)?.value ?? '—' }}
                                        </span>
                                        <span style="font-size:10px;color:var(--color-terracota);">
                                            {{ stateIcon(cellKey(student.enrollment_detail_id, slot.id, current_lapse_id)) }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="toggleSubEntries(slot.id, student.enrollment_detail_id)"
                                            style="background:none;border:none;cursor:pointer;font-size:12px;color:var(--text-muted);padding:2px;"
                                            title="Subdividir nota"
                                        >
                                            ⊞
                                        </button>
                                    </template>
                                </div>

                                <!-- Panel sub-notas -->
                                <div
                                    v-if="expandedSlot === slot.id && expandedStudent === student.enrollment_detail_id"
                                    style="margin-top:8px;background:var(--color-papel);border:1px solid var(--color-borde);border-radius:6px;padding:10px;text-align:left;"
                                >
                                    <p style="font-size:var(--text-xs);font-weight:600;color:var(--text-primary);margin:0 0 8px;">Sub-notas</p>
                                    <div
                                        v-for="child in (getEntry(student, slot.id)?.children ?? [])"
                                        :key="child.id"
                                        style="display:grid;grid-template-columns:1fr 60px 70px;gap:6px;margin-bottom:6px;align-items:center;"
                                    >
                                        <span style="font-size:var(--text-xs);">{{ child.name }}</span>
                                        <span style="font-size:var(--text-xs);color:var(--text-muted);">{{ child.weight }}%</span>
                                        <input
                                            :value="child.value ?? ''"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            @change="saveSubEntry(student.enrollment_detail_id, slot.id, getEntry(student, slot.id)?.id ?? null, child.name ?? '', Number(child.weight), ($event.target as HTMLInputElement).value)"
                                            style="padding:3px 6px;border:1px solid var(--color-borde);border-radius:4px;font-size:var(--text-xs);width:100%;"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        @click="saveSubEntry(student.enrollment_detail_id, slot.id, getEntry(student, slot.id)?.id ?? null, 'Nueva sub-nota', 50, '')"
                                        style="font-size:var(--text-xs);color:var(--color-terracota);background:none;border:none;cursor:pointer;padding:4px 0;"
                                    >
                                        + Añadir sub-nota
                                    </button>
                                </div>
                            </td>
                            <!-- Celda reparación -->
                            <td
                                v-if="remedialSlot"
                                style="text-align:center;padding:8px 12px;border-left:2px dashed var(--color-borde);"
                            >
                                <template v-if="getEntry(student, remedialSlot.id)">
                                    <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
                                        <template v-if="editingCell === cellKey(student.enrollment_detail_id, remedialSlot.id, current_lapse_id)">
                                            <input
                                                :ref="el => el && (el as HTMLInputElement).focus()"
                                                v-model="editingValue"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                @blur="commitEdit(student.enrollment_detail_id, remedialSlot.id)"
                                                @keyup.enter="commitEdit(student.enrollment_detail_id, remedialSlot.id)"
                                                @keyup.escape="editingCell = null"
                                                style="width:70px;padding:4px 8px;border:1px solid var(--color-terracota);border-radius:4px;text-align:center;font-size:var(--text-sm);"
                                            />
                                        </template>
                                        <template v-else>
                                            <span
                                                @click="startEdit(student.enrollment_detail_id, remedialSlot.id, getEntry(student, remedialSlot.id)?.value ?? null)"
                                                style="display:inline-block;min-width:60px;padding:4px 8px;border:1px solid transparent;border-radius:4px;cursor:text;"
                                                :style="getEntry(student, remedialSlot.id)?.value ? 'border-color:var(--color-borde);' : 'border-color:var(--color-gris-light);color:var(--text-muted);'"
                                            >
                                                {{ getEntry(student, remedialSlot.id)?.value ?? '—' }}
                                            </span>
                                            <span style="font-size:10px;color:var(--color-terracota);">
                                                {{ stateIcon(cellKey(student.enrollment_detail_id, remedialSlot.id, current_lapse_id)) }}
                                            </span>
                                        </template>
                                    </div>
                                </template>
                                <template v-else-if="studentPassed(student) === false">
                                    <button
                                        type="button"
                                        @click="enableRemedial(student.enrollment_detail_id)"
                                        style="font-size:var(--text-xs);padding:4px 10px;background:none;border:1px solid var(--color-terracota);color:var(--color-terracota);border-radius:4px;cursor:pointer;"
                                    >
                                        Habilitar reparación
                                    </button>
                                </template>
                                <template v-else>
                                    <span style="font-size:var(--text-xs);color:var(--text-muted);">—</span>
                                </template>
                            </td>
                        </tr>
                    </template>

                    <tr v-if="sheet.students.length === 0">
                        <td :colspan="nonRemedialSlots.length + 1 + (remedialSlot ? 1 : 0)" style="text-align:center;padding:24px;color:var(--text-muted);">
                            No hay estudiantes inscritos en esta sección.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
