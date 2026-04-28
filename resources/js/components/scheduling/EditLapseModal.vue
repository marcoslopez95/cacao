<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/actions/App/Http/Controllers/Scheduling/LapseController'
import type { Lapse, Period } from '@/types/scheduling'

const props = defineProps<{ open: boolean; period: Period; lapse: Lapse }>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const formKey = ref(0)

function makeForm() {
    return useForm({
        number:     props.lapse.number,
        name:       props.lapse.name,
        start_date: props.lapse.startDate,
        end_date:   props.lapse.endDate,
    })
}

let form = makeForm()

watch(() => props.lapse, () => {
    form = makeForm()
    formKey.value++
})

function close(v: boolean): void {
    emit('update:open', v)
}

function submit(): void {
    form.patch(update.url({ period: props.period, lapse: props.lapse }), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Editar lapso" size="sm" @update:open="close">
        <div :key="formKey" style="display:grid;gap:16px;">
            <div style="display:grid;gap:6px;">
                <label for="el-number" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                    Número
                </label>
                <input
                    id="el-number"
                    v-model="form.number"
                    type="number"
                    min="1"
                    class="input"
                    required
                />
                <InputError :message="form.errors.number" />
            </div>
            <div style="display:grid;gap:6px;">
                <label for="el-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                    Nombre
                </label>
                <input id="el-name" v-model="form.name" class="input" required />
                <InputError :message="form.errors.name" />
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="display:grid;gap:6px;">
                    <label for="el-start" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Inicio
                    </label>
                    <input id="el-start" v-model="form.start_date" type="date" class="input" required />
                    <InputError :message="form.errors.start_date" />
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="el-end" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Fin
                    </label>
                    <input id="el-end" v-model="form.end_date" type="date" class="input" required />
                    <InputError :message="form.errors.end_date" />
                </div>
            </div>
            <InputError :message="form.errors.period_id" />
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
            <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
            <Button type="button" variant="primary" :loading="form.processing" @click="submit">Guardar cambios</Button>
        </div>
    </Modal>
</template>
