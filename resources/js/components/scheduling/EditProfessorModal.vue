<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/scheduling/professors'
import type { Professor } from '@/types/scheduling'

const props = defineProps<{ professor: Professor; open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        weekly_hour_limit: props.professor.weeklyHourLimit,
        active:            props.professor.active,
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
    form.value.patch(update.url({ professor: props.professor }), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Editar profesor" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-muted);">
                        Usuario
                    </label>
                    <p style="font-size:var(--text-sm);color:var(--text-primary);margin:0;">
                        {{ professor.user.name }}
                    </p>
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="ep-hours" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Límite horas/semana
                    </label>
                    <input id="ep-hours" v-model.number="form.weekly_hour_limit" type="number" min="1" max="60" class="input" required />
                    <InputError :message="form.errors.weekly_hour_limit" />
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <input id="ep-active" v-model="form.active" type="checkbox" class="checkbox" />
                    <label for="ep-active" style="font-size:var(--text-sm);color:var(--text-primary);">Activo</label>
                    <InputError :message="form.errors.active" />
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Guardar cambios</Button>
            </div>
        </form>
    </Modal>
</template>
