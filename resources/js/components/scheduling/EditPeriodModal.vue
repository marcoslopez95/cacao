<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/scheduling/periods'
import type { Period } from '@/types/scheduling'

const props = defineProps<{ period: Period; open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        name:       props.period.name,
        type:       props.period.type,
        start_date: props.period.startDate,
        end_date:   props.period.endDate,
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
    form.value.patch(update.url({ period: props.period }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Editar período" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="ep-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="ep-name" v-model="form.name" class="input" required />
                    <InputError :message="form.errors.name" />
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="ep-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo
                    </label>
                    <select id="ep-type" v-model="form.type" class="input" required>
                        <option value="semester">Semestral</option>
                        <option value="year">Anual</option>
                        <option value="trimester">Trimestral</option>
                    </select>
                    <InputError :message="form.errors.type" />
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="display:grid;gap:6px;">
                        <label for="ep-start" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Inicio
                        </label>
                        <input id="ep-start" v-model="form.start_date" type="date" class="input" required />
                        <InputError :message="form.errors.start_date" />
                    </div>
                    <div style="display:grid;gap:6px;">
                        <label for="ep-end" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Fin
                        </label>
                        <input id="ep-end" v-model="form.end_date" type="date" class="input" required />
                        <InputError :message="form.errors.end_date" />
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Guardar cambios</Button>
            </div>
        </form>
    </Modal>
</template>
