<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/academic/subjects'
import type { Career, Pensum } from '@/types/academic'

const props = defineProps<{
    career: Career
    pensum: Pensum
    open: boolean
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        name:          '',
        credits_uc:    1,
        period_number: 1,
        description:   null as string | null,
    })
}

const form = ref(makeForm())

function close(v: boolean): void {
    emit('update:open', v)
}

watch(
    () => props.open,
    (opened) => {
        if (opened) {
            form.value = makeForm()
        }
    },
)

function submit(): void {
    form.value.post(store.url({ career: props.career, pensum: props.pensum }), {
        onSuccess: () => close(false),
    })
}

function periods(): number[] {
    return Array.from({ length: props.pensum.totalPeriods }, (_, i) => i + 1)
}
</script>

<template>
    <Modal :open="open" title="Nueva materia" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cs-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input
                        id="cs-name"
                        v-model="form.name"
                        class="input"
                        placeholder="Ej: Cálculo I"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cs-credits" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Créditos (UC)
                    </label>
                    <input
                        id="cs-credits"
                        v-model.number="form.credits_uc"
                        type="number"
                        min="1"
                        max="20"
                        class="input"
                        required
                    />
                    <InputError :message="form.errors.credits_uc" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cs-period" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Período
                    </label>
                    <select id="cs-period" v-model.number="form.period_number" class="input" required>
                        <option v-for="p in periods()" :key="p" :value="p">{{ p }}</option>
                    </select>
                    <InputError :message="form.errors.period_number" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cs-description" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Descripción
                    </label>
                    <textarea
                        id="cs-description"
                        v-model="form.description"
                        class="input"
                        rows="3"
                        placeholder="Descripción opcional de la materia"
                    />
                    <InputError :message="form.errors.description" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Crear materia</Button>
            </div>
        </form>
    </Modal>
</template>
