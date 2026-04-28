<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/actions/App/Http/Controllers/Scheduling/LapseController'
import type { Period } from '@/types/scheduling'

const props = defineProps<{ open: boolean; period: Period }>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const formKey = ref(0)

function makeForm() {
    return useForm({
        number:     '' as unknown as number,
        name:       '',
        start_date: '',
        end_date:   '',
    })
}

let form = makeForm()

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) {
        form = makeForm()
        formKey.value++
    }
}

function submit(): void {
    form.post(store.url({ period: props.period }), {
        onSuccess: () => close(false),
    })
}
</script>

<template>
    <Modal :open="open" title="Nuevo lapso" size="sm" @update:open="close">
        <div :key="formKey" style="display:grid;gap:16px;">
            <div style="display:grid;gap:6px;">
                <label for="cl-number" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                    Número
                </label>
                <input
                    id="cl-number"
                    v-model="form.number"
                    type="number"
                    min="1"
                    class="input"
                    placeholder="Ej: 1"
                    required
                />
                <InputError :message="form.errors.number" />
            </div>
            <div style="display:grid;gap:6px;">
                <label for="cl-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                    Nombre
                </label>
                <input id="cl-name" v-model="form.name" class="input" placeholder="Ej: Primer Lapso" required />
                <InputError :message="form.errors.name" />
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="display:grid;gap:6px;">
                    <label for="cl-start" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Inicio
                    </label>
                    <input id="cl-start" v-model="form.start_date" type="date" class="input" required />
                    <InputError :message="form.errors.start_date" />
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="cl-end" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Fin
                    </label>
                    <input id="cl-end" v-model="form.end_date" type="date" class="input" required />
                    <InputError :message="form.errors.end_date" />
                </div>
            </div>
            <InputError :message="form.errors.period_id" />
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
            <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
            <Button type="button" variant="primary" :loading="form.processing" @click="submit">Crear lapso</Button>
        </div>
    </Modal>
</template>
