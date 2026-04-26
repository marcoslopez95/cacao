<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import { sync } from '@/routes/academic/subjects/prerequisites'
import type { Career, Pensum, Subject } from '@/types/academic'

const props = defineProps<{
    career: Career
    pensum: Pensum
    subject: Subject
    subjects: Subject[]
    open: boolean
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({ prerequisites: [] as number[] })

const selectedIds = ref<number[]>(props.subject.prerequisites.map((p) => p.id))

watch(
    () => props.open,
    (opened) => {
        if (opened) {
            selectedIds.value = props.subject.prerequisites.map((p) => p.id)
        }
    },
)

const eligibleSubjects = computed(() =>
    props.subjects.filter((s) => s.periodNumber < props.subject.periodNumber),
)

const subjectsByPeriod = computed(() => {
    const groups: Record<number, Subject[]> = {}
    for (const s of eligibleSubjects.value) {
        if (!groups[s.periodNumber]) {
            groups[s.periodNumber] = []
        }
        groups[s.periodNumber].push(s)
    }
    return groups
})

const sortedPeriods = computed(() =>
    Object.keys(subjectsByPeriod.value)
        .map(Number)
        .sort((a, b) => a - b),
)

function close(v: boolean): void {
    emit('update:open', v)
}

function togglePrereq(id: number): void {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter((x) => x !== id)
    } else {
        selectedIds.value = [...selectedIds.value, id]
    }
}

function submit(): void {
    form.prerequisites = selectedIds.value
    form.post(sync.url({ career: props.career, pensum: props.pensum, subject: props.subject }), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Prerrequisitos" size="md" @update:open="close">
        <p style="color:var(--text-secondary);font-size:var(--text-sm);margin:0 0 20px;">
            Materia: <strong>{{ subject.code }} — {{ subject.name }}</strong>
        </p>

        <template v-if="eligibleSubjects.length === 0">
            <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0 0 24px;">
                Esta materia no puede tener prerrequisitos.
            </p>
        </template>

        <template v-else>
            <div style="display:flex;flex-direction:column;gap:20px;margin-bottom:24px;">
                <div v-for="period in sortedPeriods" :key="period">
                    <p style="font-size:var(--text-xs);font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin:0 0 8px;">
                        Período {{ period }}
                    </p>
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        <label
                            v-for="s in subjectsByPeriod[period]"
                            :key="s.id"
                            :for="`prereq-${s.id}`"
                            style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:var(--text-sm);color:var(--text-primary);"
                        >
                            <input
                                :id="`prereq-${s.id}`"
                                type="checkbox"
                                :value="s.id"
                                :checked="selectedIds.includes(s.id)"
                                style="width:16px;height:16px;flex-shrink:0;cursor:pointer;"
                                @change="togglePrereq(s.id)"
                            />
                            <span>{{ s.code }} — {{ s.name }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </template>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <Button variant="ghost" @click="close(false)">Cancelar</Button>
            <Button
                v-if="eligibleSubjects.length > 0"
                variant="primary"
                :loading="form.processing"
                @click="submit"
            >
                Guardar
            </Button>
        </div>
    </Modal>
</template>
